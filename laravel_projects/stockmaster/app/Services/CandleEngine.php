<?php

namespace App\Services;

use App\Models\Candle;
use Illuminate\Support\Facades\DB;

class CandleEngine
{
    public static function tfSeconds(string $tf): int
    {
        return match ($tf) {
            '1m' => 60,
            '5m' => 300,
            '15m' => 900,
            '1h' => 3600,
            '1d' => 86400,
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
        $tf = '1m';
        $openTs = self::floorOpenTs($ts, $tf);
        $closeTs = $openTs + 60 - 1;

        DB::statement("
            INSERT INTO candles (symbol, tf, open_ts, close_ts, `open`, high, low, `close`, ticks, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1, NOW(), NOW())
            ON DUPLICATE KEY UPDATE
                high = GREATEST(high, VALUES(high)),
                low  = LEAST(low, VALUES(low)),
                `close` = VALUES(`close`),
                ticks = ticks + 1,
                updated_at = NOW()
        ", [$symbol, $tf, $openTs, $closeTs, $price, $price, $price, $price]);

        return Candle::where('symbol',$symbol)->where('tf',$tf)->where('open_ts',$openTs)->firstOrFail();
    }

    public static function rollupFrom1m(string $symbol, string $tf, int $fromOpenTs, int $toOpenTs): void
    {
        $sec = self::tfSeconds($tf);
        if ($sec < 300) return; // 1m-et nem rollupolunk

        $rows = DB::select("
            SELECT
                (FLOOR(open_ts / ?) * ?) AS bucket_open,
                SUBSTRING_INDEX(GROUP_CONCAT(`open` ORDER BY open_ts ASC), ',', 1) as o,
                MAX(high) as h,
                MIN(low) as l,
                SUBSTRING_INDEX(GROUP_CONCAT(`close` ORDER BY open_ts ASC), ',', -1) as c,
                SUM(ticks) as ticks_sum
            FROM candles
            WHERE symbol = ?
              AND tf = '1m'
              AND open_ts BETWEEN ? AND ?
            GROUP BY bucket_open
            ORDER BY bucket_open ASC
        ", [$sec, $sec, $symbol, $fromOpenTs, $toOpenTs]);

        foreach ($rows as $r) {
            $openTs = (int)$r->bucket_open;
            $closeTs = $openTs + $sec - 1;

            DB::statement("
                INSERT INTO candles (symbol, tf, open_ts, close_ts, `open`, high, low, `close`, ticks, created_at, updated_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
                ON DUPLICATE KEY UPDATE
                    `open` = VALUES(`open`),
                    high = VALUES(high),
                    low = VALUES(low),
                    `close` = VALUES(`close`),
                    ticks = VALUES(ticks),
                    updated_at = NOW()
            ", [
                $symbol, $tf, $openTs, $closeTs,
                (float)$r->o, (float)$r->h, (float)$r->l, (float)$r->c, (int)$r->ticks_sum
            ]);
        }
    }
}