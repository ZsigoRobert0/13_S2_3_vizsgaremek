<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SeedManualAaplWeek extends Command
{
    protected $signature = 'stockmaster:seed-manual-aapl-week
                            {--symbol=AAPL}
                            {--wipe : Törli a célablakban a meglévő adott symbol candles sorokat}
                            {--days=5 : Hány kereskedési napot generáljon (alapértelmezett: 5)}';

    protected $description = 'Manuális demo AAPL history generálása 1m/5m/15m/1h/1d timeframe-ekre';

    public function handle(): int
    {
        $symbol = strtoupper((string) $this->option('symbol'));
        $days = max(1, min(7, (int) $this->option('days')));
        $wipe = (bool) $this->option('wipe');

        // Kézzel hangolt napi profilok: oldalt szépen kirajzolható, normális mozgás.
        // A sorrend: legrégebbitől a legfrissebb felé.
        $dailyProfiles = [
            ['open' => 257.80, 'high' => 260.10, 'low' => 256.90, 'close' => 259.40],
            ['open' => 259.60, 'high' => 261.90, 'low' => 258.70, 'close' => 261.20],
            ['open' => 261.00, 'high' => 262.40, 'low' => 259.80, 'close' => 260.30],
            ['open' => 260.50, 'high' => 263.20, 'low' => 260.10, 'close' => 262.70],
            ['open' => 262.90, 'high' => 264.80, 'low' => 261.70, 'close' => 263.40],
            ['open' => 263.20, 'high' => 265.10, 'low' => 262.60, 'close' => 264.30],
            ['open' => 264.10, 'high' => 265.40, 'low' => 262.90, 'close' => 263.80],
        ];

        $profiles = array_slice($dailyProfiles, -$days);

        $tradingDays = $this->getRecentTradingDays($days);

        if (count($tradingDays) !== count($profiles)) {
            $this->error('Napprofil / kereskedési napok szám eltérés.');
            return self::FAILURE;
        }

        $allRows = [];

        foreach ($tradingDays as $idx => $day) {
            $profile = $profiles[$idx];

            $oneMinuteRows = $this->buildMinuteSeriesForDay(
                $symbol,
                $day,
                (float) $profile['open'],
                (float) $profile['high'],
                (float) $profile['low'],
                (float) $profile['close']
            );

            $rows1m = $oneMinuteRows;
            $rows5m = $this->aggregateRows($symbol, '5m', $oneMinuteRows, 5);
            $rows15m = $this->aggregateRows($symbol, '15m', $oneMinuteRows, 15);
            $rows1h = $this->aggregateRows($symbol, '1h', $oneMinuteRows, 60);
            $rows1d = [$this->buildDailyRow($symbol, $day, $oneMinuteRows)];

            $allRows = array_merge($allRows, $rows1m, $rows5m, $rows15m, $rows1h, $rows1d);
        }

        if (empty($allRows)) {
            $this->error('Nem készült egyetlen sor sem.');
            return self::FAILURE;
        }

        $minTs = min(array_column($allRows, 'open_ts'));
        $maxTs = max(array_column($allRows, 'open_ts'));

        if ($wipe) {
            DB::table('candles')
                ->where('symbol', $symbol)
                ->whereBetween('open_ts', [$minTs, $maxTs + 86400])
                ->delete();

            $this->warn("Régi {$symbol} candles sorok törölve a célidőablakból.");
        }

        foreach (array_chunk($allRows, 500) as $chunk) {
            DB::table('candles')->upsert(
                $chunk,
                ['symbol', 'tf', 'open_ts'],
                ['close_ts', 'open', 'high', 'low', 'close', 'ticks', 'updated_at']
            );
        }

        $count1m = count(array_filter($allRows, fn ($r) => $r['tf'] === '1m'));
        $count5m = count(array_filter($allRows, fn ($r) => $r['tf'] === '5m'));
        $count15m = count(array_filter($allRows, fn ($r) => $r['tf'] === '15m'));
        $count1h = count(array_filter($allRows, fn ($r) => $r['tf'] === '1h'));
        $count1d = count(array_filter($allRows, fn ($r) => $r['tf'] === '1d'));

        $this->info("Kész.");
        $this->line("1m:  {$count1m}");
        $this->line("5m:  {$count5m}");
        $this->line("15m: {$count15m}");
        $this->line("1h:  {$count1h}");
        $this->line("1d:  {$count1d}");
        $this->line("Összesen: " . count($allRows));

        return self::SUCCESS;
    }

    private function getRecentTradingDays(int $days): array
    {
        $result = [];
        $cursor = Carbon::today('UTC');

        while (count($result) < $days) {
            if ($cursor->isWeekday()) {
                array_unshift($result, $cursor->copy());
            }
            $cursor->subDay();
        }

        return $result;
    }

    private function buildMinuteSeriesForDay(
        string $symbol,
        Carbon $day,
        float $dayOpen,
        float $dayHigh,
        float $dayLow,
        float $dayClose
    ): array {
        $rows = [];

        // USA cash session demo: 13:30 - 19:59 UTC (390 perc)
        $sessionStart = $day->copy()->setTime(13, 30, 0);
        $minutes = 390;

        $isGreen = $dayClose >= $dayOpen;

        // Waypointok, hogy legyen normális intraday görbe
        if ($isGreen) {
            $points = [
                ['m' => 0,   'p' => $dayOpen],
                ['m' => 50,  'p' => $dayLow + (($dayOpen - $dayLow) * 0.35)],
                ['m' => 120, 'p' => $dayLow],
                ['m' => 220, 'p' => ($dayOpen + $dayClose) / 2],
                ['m' => 315, 'p' => $dayHigh],
                ['m' => 389, 'p' => $dayClose],
            ];
        } else {
            $points = [
                ['m' => 0,   'p' => $dayOpen],
                ['m' => 60,  'p' => $dayHigh],
                ['m' => 150, 'p' => ($dayOpen + $dayClose) / 2],
                ['m' => 280, 'p' => $dayLow],
                ['m' => 389, 'p' => $dayClose],
            ];
        }

        $prevClose = $dayOpen;

        for ($i = 0; $i < $minutes; $i++) {
            $ts = $sessionStart->copy()->addMinutes($i)->timestamp;

            $base = $this->interpolatePrice($i, $points);
            $wave = sin($i / 11.0) * 0.07 + cos($i / 23.0) * 0.04;
            $targetClose = round($base + $wave, 2);

            $open = round($prevClose, 2);
            $close = round($targetClose, 2);

            $pad = 0.05 + abs(sin($i / 7.0)) * 0.08;
            $high = round(max($open, $close) + $pad, 2);
            $low = round(min($open, $close) - $pad, 2);

            $high = min($high, $dayHigh);
            $low = max($low, $dayLow);

            if ($low > min($open, $close)) {
                $low = min($open, $close);
            }
            if ($high < max($open, $close)) {
                $high = max($open, $close);
            }

            // Garantáljuk, hogy napon belül tényleg legyen low/high érintés
            if ($i === 120 || $i === 280) {
                $low = min($low, $dayLow);
            }
            if ($i === 60 || $i === 315) {
                $high = max($high, $dayHigh);
            }

            $ticks = 8 + ($i % 17);

            $rows[] = [
                'symbol' => $symbol,
                'tf' => '1m',
                'open_ts' => $ts,
                'close_ts' => $ts + 59,
                'open' => number_format($open, 6, '.', ''),
                'high' => number_format($high, 6, '.', ''),
                'low' => number_format($low, 6, '.', ''),
                'close' => number_format($close, 6, '.', ''),
                'ticks' => $ticks,
                'created_at' => now(),
                'updated_at' => now(),
            ];

            $prevClose = $close;
        }

        return $rows;
    }

    private function aggregateRows(string $symbol, string $tf, array $rows1m, int $minutesPerBar): array
    {
        $chunks = array_chunk($rows1m, $minutesPerBar);
        $result = [];

        foreach ($chunks as $chunk) {
            if (empty($chunk)) {
                continue;
            }

            $open = (float) $chunk[0]['open'];
            $close = (float) $chunk[count($chunk) - 1]['close'];
            $high = max(array_map(fn ($r) => (float) $r['high'], $chunk));
            $low = min(array_map(fn ($r) => (float) $r['low'], $chunk));
            $ticks = array_sum(array_map(fn ($r) => (int) $r['ticks'], $chunk));

            $result[] = [
                'symbol' => $symbol,
                'tf' => $tf,
                'open_ts' => (int) $chunk[0]['open_ts'],
                'close_ts' => (int) $chunk[count($chunk) - 1]['close_ts'],
                'open' => number_format($open, 6, '.', ''),
                'high' => number_format($high, 6, '.', ''),
                'low' => number_format($low, 6, '.', ''),
                'close' => number_format($close, 6, '.', ''),
                'ticks' => $ticks,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        return $result;
    }

    private function buildDailyRow(string $symbol, Carbon $day, array $rows1m): array
    {
        $open = (float) $rows1m[0]['open'];
        $close = (float) $rows1m[count($rows1m) - 1]['close'];
        $high = max(array_map(fn ($r) => (float) $r['high'], $rows1m));
        $low = min(array_map(fn ($r) => (float) $r['low'], $rows1m));
        $ticks = array_sum(array_map(fn ($r) => (int) $r['ticks'], $rows1m));

        return [
            'symbol' => $symbol,
            'tf' => '1d',
            'open_ts' => $day->copy()->startOfDay()->timestamp,
            'close_ts' => $day->copy()->endOfDay()->timestamp,
            'open' => number_format($open, 6, '.', ''),
            'high' => number_format($high, 6, '.', ''),
            'low' => number_format($low, 6, '.', ''),
            'close' => number_format($close, 6, '.', ''),
            'ticks' => $ticks,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    private function interpolatePrice(int $minute, array $points): float
    {
        $count = count($points);

        if ($minute <= $points[0]['m']) {
            return (float) $points[0]['p'];
        }

        if ($minute >= $points[$count - 1]['m']) {
            return (float) $points[$count - 1]['p'];
        }

        for ($i = 0; $i < $count - 1; $i++) {
            $a = $points[$i];
            $b = $points[$i + 1];

            if ($minute >= $a['m'] && $minute <= $b['m']) {
                $span = max(1, $b['m'] - $a['m']);
                $ratio = ($minute - $a['m']) / $span;
                return (float) $a['p'] + (((float) $b['p'] - (float) $a['p']) * $ratio);
            }
        }

        return (float) $points[$count - 1]['p'];
    }
}