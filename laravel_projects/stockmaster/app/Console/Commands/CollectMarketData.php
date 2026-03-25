<?php

namespace App\Console\Commands;

use App\Models\Asset;
use App\Models\PriceTick;
use App\Services\CandleEngine;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CollectMarketData extends Command
{
    protected $signature = 'stockmaster:collect-data {--symbol=} {--limit=0}';
    protected $description = 'Collect market data without active UI and build candles';

    public function handle(): int
    {
        $this->info('=== STOCKMASTER COLLECT START ===');

        $apiKey = (string) env('FINNHUB_API_KEY', '');
        if ($apiKey === '') {
            $this->error('Hiányzik a FINNHUB_API_KEY a .env fájlból.');
            return self::FAILURE;
        }

        $singleSymbol = strtoupper(trim((string) $this->option('symbol')));
        $limit = (int) $this->option('limit');

        $query = Asset::query()
            ->where('IsTradable', 1)
            ->orderBy('Symbol');

        if ($singleSymbol !== '') {
            $query->where('Symbol', $singleSymbol);
        }

        if ($limit > 0) {
            $query->limit($limit);
        }

        $assets = $query->get();

        if ($assets->isEmpty()) {
            $this->warn('Nincs egyetlen tradelhető asset sem.');
            return self::SUCCESS;
        }

        $okCount = 0;
        $errCount = 0;

        foreach ($assets as $asset) {
            $symbol = strtoupper((string) $asset->Symbol);

            try {
                $quote = $this->fetchFinnhubQuote($symbol, $apiKey);

                $price = isset($quote['c']) && is_numeric($quote['c']) ? (float) $quote['c'] : null;
                $bid   = isset($quote['b']) && is_numeric($quote['b']) ? (float) $quote['b'] : null;
                $ask   = isset($quote['a']) && is_numeric($quote['a']) ? (float) $quote['a'] : null;

                if ($price === null) {
                    $this->warn("SKIP {$symbol} -> nincs ár a válaszban");
                    continue;
                }

                $ts = now()->timestamp;

                PriceTick::updateOrCreate(
                    [
                        'symbol' => $symbol,
                        'ts'     => $ts,
                    ],
                    [
                        'price'  => $price,
                        'bid'    => $bid,
                        'ask'    => $ask,
                        'source' => 'collector',
                    ]
                );

                $c1 = CandleEngine::upsert1m($symbol, $ts, $price);
                $openTs = (int) $c1->open_ts;

                CandleEngine::rollupFrom1m($symbol, '5m',  $openTs - 3600,       $openTs + 3600);
                CandleEngine::rollupFrom1m($symbol, '15m', $openTs - 7200,       $openTs + 7200);
                CandleEngine::rollupFrom1m($symbol, '1h',  $openTs - 86400,      $openTs + 86400);
                CandleEngine::rollupFrom1m($symbol, '1d',  $openTs - 86400 * 7,  $openTs + 86400 * 7);

                $this->info("OK {$symbol} | price={$price} | ts={$ts}");
                $okCount++;
            } catch (\Throwable $e) {
                $this->error("ERR {$symbol} | " . $e->getMessage());

                Log::error('collector failed', [
                    'symbol' => $symbol,
                    'error'  => $e->getMessage(),
                ]);

                $errCount++;
            }

            usleep(250000);
        }

        $this->newLine();
        $this->info("Kész. Sikeres: {$okCount}, Hibás: {$errCount}");
        $this->info('=== STOCKMASTER COLLECT END ===');

        return self::SUCCESS;
    }

    private function fetchFinnhubQuote(string $symbol, string $apiKey): array
    {
        $resp = Http::timeout(5)
            ->retry(1, 200)
            ->get('https://finnhub.io/api/v1/quote', [
                'symbol' => $symbol,
                'token'  => $apiKey,
            ]);

        if (!$resp->ok()) {
            throw new \RuntimeException("Finnhub HTTP " . $resp->status());
        }

        $json = $resp->json();

        if (!is_array($json)) {
            throw new \RuntimeException('Finnhub invalid JSON');
        }

        return $json;
    }
}