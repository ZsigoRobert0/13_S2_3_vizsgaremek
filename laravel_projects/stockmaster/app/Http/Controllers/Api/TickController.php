<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PriceTick;
use App\Services\CandleEngine;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Log;

class TickController extends Controller
{
    public function ingest(Request $req)
    {
        // Basic protection
        $key = 'tick-ingest:' . $req->ip();
        if (RateLimiter::tooManyAttempts($key, 240)) {
            return response()->json(['ok'=>false,'error'=>'Rate limited'], 429);
        }
        RateLimiter::hit($key, 1);

        $data = $req->validate([
            'symbol' => ['required','string','max:20'],
            'ts'     => ['required','integer','min:1'],
            'price'  => ['required','numeric'],
            'bid'    => ['nullable','numeric'],
            'ask'    => ['nullable','numeric'],
            'source' => ['nullable','string','max:20'],
        ]);

        $symbol = strtoupper(trim($data['symbol']));
        $tsRaw  = (int)$data['ts'];

        // Finnhub / kliensek néha ms-ben küldenek timestampet.
        // A rendszer unix másodpercekkel dolgozik, ezért normalizálunk.
        $ts     = ($tsRaw > 2_000_000_000_000) ? intdiv($tsRaw, 1000) : $tsRaw;

        $price  = (float)$data['price'];

        try {
            // Tick mentés (unique: symbol+ts)
            PriceTick::updateOrCreate(
                ['symbol'=>$symbol,'ts'=>$ts],
                [
                    'price'=>$price,
                    'bid'=>isset($data['bid']) ? (float)$data['bid'] : null,
                    'ask'=>isset($data['ask']) ? (float)$data['ask'] : null,
                    'source'=>$data['source'] ?? 'finnhub',
                ]
            );

            // 1m candle + rollup
            $c1 = CandleEngine::upsert1m($symbol, $ts, $price);
            $openTs = (int)$c1->open_ts;

            // csak “környéket” rollupolunk (gyors)
            CandleEngine::rollupFrom1m($symbol, '5m',   $openTs - 3600,     $openTs + 3600);
            CandleEngine::rollupFrom1m($symbol, '15m',  $openTs - 7200,     $openTs + 7200);
            CandleEngine::rollupFrom1m($symbol, '1h',   $openTs - 86400,    $openTs + 86400);
            CandleEngine::rollupFrom1m($symbol, '1d',   $openTs - 86400*7,  $openTs + 86400*7);
        } catch (\Throwable $e) {
            Log::error('tick.ingest failed', [
                'symbol' => $symbol,
                'ts_raw' => $tsRaw,
                'ts'     => $ts,
                'price'  => $price,
                'ip'     => $req->ip(),
                'error'  => $e->getMessage(),
            ]);

            return response()->json([
                'ok' => false,
                'error' => 'Tick ingest hiba. Nézd meg a storage/logs/laravel.log-ot.',
            ], 500);
        }

        return response()->json([
            'ok' => true,
            'symbol' => $symbol,
            'ts' => $ts,
            'price' => $price,
        ]);
    }
}