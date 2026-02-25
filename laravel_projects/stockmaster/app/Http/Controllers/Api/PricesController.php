<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PriceTick;
use App\Services\CandleEngine;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;

class PricesController extends Controller
{
    public function index(Request $req)
    {
        // UI polling miatt kell egy alap rate-limit
        $key = 'prices:' . $req->ip();
        if (RateLimiter::tooManyAttempts($key, 240)) {
            return response()->json(['ok' => false, 'error' => 'Rate limited'], 429);
        }
        RateLimiter::hit($key, 1);

        $symbolsRaw = (string) $req->query('symbols', '');
        $ingest = (int) $req->query('ingest', 0) === 1;

        $symbols = collect(explode(',', $symbolsRaw))
            ->map(fn ($s) => strtoupper(trim($s)))
            ->filter(fn ($s) => $s !== '')
            ->unique()
            ->values();

        if ($symbols->isEmpty()) {
            return response()->json(['ok' => false, 'error' => 'Missing symbols'], 422);
        }

        $apiKey = env('FINNHUB_API_KEY');
        if (!$apiKey) {
            return response()->json(['ok' => false, 'error' => 'FINNHUB_API_KEY not set'], 500);
        }

        $data = [];
        $errors = [];

        foreach ($symbols as $symbol) {
            $symbol = strtoupper($symbol);

            // Cache: price-only 2s, ingest 1s (hogy a tick friss legyen)
            $ttl = $ingest ? 1 : 2;
            $cacheKey = "sm:quote:" . ($ingest ? "i:" : "p:") . $symbol;

            $cached = Cache::get($cacheKey);
            if ($cached) {
                $data[$symbol] = $cached;
                continue;
            }

            // Per-symbol lock: ugyanarra a symbolra ne fusson párhuzamos Finnhub fetch
            $lock = Cache::lock("sm:quote_lock:" . $symbol, 2);

            try {
                if (!$lock->get()) {
                    // Ha lockolt, próbáljuk a price cache-t (akkor is, ha ingest)
                    $fallback = Cache::get("sm:quote:p:" . $symbol);
                    if ($fallback) {
                        $data[$symbol] = $fallback;
                        continue;
                    }
                    $errors[$symbol] = 'Locked / try again';
                    continue;
                }

                $quote = $this->fetchFinnhubQuote($symbol, $apiKey);

                $payload = [
                    'symbol' => $symbol,
                    'price'  => $quote['c'] ?? null,
                    'bid'    => $quote['b'] ?? null,
                    'ask'    => $quote['a'] ?? null,
                    'ts'     => now()->timestamp,
                ];

                if ($payload['price'] === null) {
                    $errors[$symbol] = 'No price returned';
                    continue;
                }

                // Ingest: tick + candle rollup (TickController logikája)
                if ($ingest) {
                    $this->ingestTick(
                        $symbol,
                        (int) $payload['ts'],
                        (float) $payload['price'],
                        $payload['bid'],
                        $payload['ask']
                    );
                }

                Cache::put($cacheKey, $payload, $ttl);
                // price-only cache frissítése fallbacknak
                Cache::put("sm:quote:p:" . $symbol, $payload, 2);

                $data[$symbol] = $payload;

            } catch (\Throwable $e) {
                $errors[$symbol] = $e->getMessage();
                Log::warning('prices.index failed', [
                    'symbol' => $symbol,
                    'ingest' => $ingest,
                    'err'    => $e->getMessage()
                ]);
            } finally {
                try { $lock->release(); } catch (\Throwable $e) {}
            }
        }

        return response()->json([
            'ok' => true,
            'data' => $data,
            'errors' => $errors,
        ]);
    }

    private function fetchFinnhubQuote(string $symbol, string $apiKey): array
    {
        $resp = Http::timeout(2)
            ->retry(1, 150)
            ->get('https://finnhub.io/api/v1/quote', [
                'symbol' => $symbol,
                'token'  => $apiKey,
            ]);

        if (!$resp->ok()) {
            throw new \RuntimeException("Finnhub HTTP " . $resp->status());
        }

        $json = $resp->json();
        if (!is_array($json)) {
            throw new \RuntimeException("Finnhub bad json");
        }

        return $json;
    }

    private function ingestTick(string $symbol, int $ts, float $price, $bid = null, $ask = null): void
    {
        PriceTick::updateOrCreate(
            ['symbol' => $symbol, 'ts' => $ts],
            [
                'price'  => $price,
                'bid'    => $bid !== null ? (float) $bid : null,
                'ask'    => $ask !== null ? (float) $ask : null,
                'source' => 'finnhub',
            ]
        );

        $c1 = CandleEngine::upsert1m($symbol, $ts, $price);
        $openTs = (int) $c1->open_ts;

        CandleEngine::rollupFrom1m($symbol, '5m',   $openTs - 3600,     $openTs + 3600);
        CandleEngine::rollupFrom1m($symbol, '15m',  $openTs - 7200,     $openTs + 7200);
        CandleEngine::rollupFrom1m($symbol, '1h',   $openTs - 86400,    $openTs + 86400);
        CandleEngine::rollupFrom1m($symbol, '1d',   $openTs - 86400*7,  $openTs + 86400*7);
    }
}