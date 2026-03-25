<?php

namespace App\Services;

use App\Models\Candle;
use Illuminate\Support\Facades\DB;

class CandleEngine
{
    public static function tfSeconds(string $tf): int
    {
        return match($tf) {
            '1m'  => 60,
            '5m'  => 300,
            '15m' => 900,
            '1h'  => 3600,
            '1d'  => 86400,
            default => 60,
        };
    }

    public static function floorOpenTs(int $ts, string $tf): int
    {
        $sec = self::tfSeconds($tf);
        return intdiv($ts, $sec) * $sec;
    }

    public static function upsert1m(string $symbol, int $ts, float $price): Candle
    {
        // Tests run on SQLite (:memory:), where MySQL's ON DUPLICATE KEY UPDATE
        // is a syntax error. Implement a DB-agnostic upsert.
        $tf = '1m';
        $openTs = self::floorOpenTs($ts, $tf);
        $closeTs = $openTs + 60 - 1;

        $now = now();

        $q = DB::table('candles')
            ->where('symbol', $symbol)
            ->where('tf', $tf)
            ->where('open_ts', $openTs);

        $row = $q->first();

        if (!$row) {
            DB::table('candles')->insert([
                'symbol' => $symbol,
                'tf' => $tf,
                'open_ts' => $openTs,
                'close_ts' => $closeTs,
                'open' => $price,
                'high' => $price,
                'low' => $price,
                'close' => $price,
                'ticks' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        } else {
            // SQLite may return numerics as strings.
            $high = max((float)$row->high, $price);
            $low  = min((float)$row->low, $price);
            $ticks = ((int)$row->ticks) + 1;

            $q->update([
                'close_ts' => $closeTs,
                'high' => $high,
                'low' => $low,
                'close' => $price,
                'ticks' => $ticks,
                'updated_at' => $now,
            ]);
        }

        return Candle::where('symbol', $symbol)->where('tf', $tf)->where('open_ts', $openTs)->firstOrFail();
    }

    public static function rollupFrom1m(string $symbol, string $tf, int $fromOpenTs, int $toOpenTs): void
    {
        $sec = self::tfSeconds($tf);
        if ($sec < 300) return; // 1m-et nem rollupolunk

        // Portable rollup: fetch 1m candles and aggregate in PHP.
        $src = DB::table('candles')
            ->where('symbol', $symbol)
            ->where('tf', '1m')
            ->whereBetween('open_ts', [$fromOpenTs, $toOpenTs])
            ->orderBy('open_ts', 'asc')
            ->get();

        if ($src->isEmpty()) return;

        $buckets = [];
        foreach ($src as $c) {
            $bucketOpen = intdiv((int)$c->open_ts, $sec) * $sec;
            $buckets[$bucketOpen][] = $c;
        }

        $now = now();

        foreach ($buckets as $openTs => $candles) {
            $closeTs = $openTs + $sec - 1;

            $open = (float)$candles[0]->open;
            $close = (float)$candles[count($candles)-1]->close;
            $high = max(array_map(fn($x) => (float)$x->high, $candles));
            $low  = min(array_map(fn($x) => (float)$x->low, $candles));
            $ticks = array_sum(array_map(fn($x) => (int)$x->ticks, $candles));

            $q = DB::table('candles')
                ->where('symbol', $symbol)
                ->where('tf', $tf)
                ->where('open_ts', $openTs);

            if (!$q->exists()) {
                DB::table('candles')->insert([
                    'symbol' => $symbol,
                    'tf' => $tf,
                    'open_ts' => $openTs,
                    'close_ts' => $closeTs,
                    'open' => $open,
                    'high' => $high,
                    'low' => $low,
                    'close' => $close,
                    'ticks' => $ticks,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            } else {
                $q->update([
                    'close_ts' => $closeTs,
                    'open' => $open,
                    'high' => $high,
                    'low' => $low,
                    'close' => $close,
                    'ticks' => $ticks,
                    'updated_at' => $now,
                ]);
            }
        }
    }
}