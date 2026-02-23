<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class BackfillCandles extends Command
{
    protected $signature = 'candles:backfill
        {symbol : Pl. AAPL}
        {tf : 1m|5m|15m|1h|1d}
        {days=30 : Hány napot töltsön vissza}';

    protected $description = 'Backfill candles from Finnhub into DB (candles table) with upsert.';

    public function handle(): int
    {
        $symbol = strtoupper(trim((string)$this->argument('symbol')));
        $tf     = trim((string)$this->argument('tf'));
        $days   = (int)$this->argument('days');

        if ($days <= 0) {
            $this->error("days legyen > 0");
            return self::FAILURE;
        }

        $apiKey = config('services.finnhub.key') ?: env('FINNHUB_API_KEY');
        if (!$apiKey) {
            $this->error("Nincs Finnhub API key. Tedd be .env-be: FINNHUB_API_KEY=...");
            return self::FAILURE;
        }

        $resolution = $this->mapTfToFinnhubResolution($tf);
        if ($resolution === null) {
            $this->error("Ismeretlen tf: {$tf}. Engedélyezett: 1m,5m,15m,1h,1d");
            return self::FAILURE;
        }

        $tfSec = $this->tfToSeconds($tf);

        // Chunkolás Finnhub limit miatt (biztos)
        $chunkDays = match ($tf) {
            '1m'  => 2,
            '5m'  => 7,
            '15m' => 14,
            '1h'  => 60,
            '1d'  => 3650,
            default => 7,
        };

        $toTs   = now()->timestamp;
        $fromTs = now()->subDays($days)->timestamp;

        $this->info("Backfill: {$symbol} tf={$tf} res={$resolution} days={$days}");
        $this->info("Range: {$fromTs} -> {$toTs}");
        $this->info("ChunkDays: {$chunkDays}");

        $total = 0;
        $cursorFrom = $fromTs;

        while ($cursorFrom < $toTs) {
            $cursorTo = min($toTs, $cursorFrom + ($chunkDays * 86400));

            $this->line("Fetch chunk: {$cursorFrom} -> {$cursorTo}");

            $json = $this->fetchFinnhubCandles($apiKey, $symbol, $resolution, $cursorFrom, $cursorTo);
            if ($json === null) {
                $cursorFrom = $cursorTo + 1;
                usleep(450000);
                continue;
            }

            $rows = $this->transformFinnhubToRows($symbol, $tf, $tfSec, $json);
            if (!$rows) {
                $this->warn("Chunk: 0 candle.");
                $cursorFrom = $cursorTo + 1;
                usleep(450000);
                continue;
            }

            $now = now();
            foreach ($rows as &$r) {
                $r['created_at'] = $now;
                $r['updated_at'] = $now;
            }
            unset($r);

            DB::table('candles')->upsert(
                $rows,
                ['symbol', 'tf', 'open_ts'],
                ['close_ts', 'open', 'high', 'low', 'close', 'ticks', 'updated_at']
            );

            $total += count($rows);
            $this->info("Upserted: " . count($rows) . " (running total: {$total})");

            $cursorFrom = $cursorTo + 1;
            usleep(450000);
        }

        $this->info("DONE. Total processed candles: {$total}");
        return self::SUCCESS;
    }

    private function mapTfToFinnhubResolution(string $tf): ?string
    {
        return match ($tf) {
            '1m'  => '1',
            '5m'  => '5',
            '15m' => '15',
            '1h'  => '60',
            '1d'  => 'D',
            default => null,
        };
    }

    private function tfToSeconds(string $tf): int
    {
        return match ($tf) {
            '1m'  => 60,
            '5m'  => 300,
            '15m' => 900,
            '1h'  => 3600,
            '1d'  => 86400,
            default => 60,
        };
    }

    private function fetchFinnhubCandles(string $apiKey, string $symbol, string $resolution, int $from, int $to): ?array
    {
        $resp = Http::timeout(25)
            ->retry(2, 400)
            ->acceptJson()
            ->get('https://finnhub.io/api/v1/stock/candle', [
                'symbol'     => $symbol,
                'resolution' => $resolution,
                'from'       => $from,
                'to'         => $to,
                'token'      => $apiKey,
            ]);

        if (!$resp->ok()) {
            $this->error("Finnhub HTTP STATUS: " . $resp->status());
            $this->error("Finnhub BODY (first 400): " . substr($resp->body(), 0, 400));
            return null;
        }

        $json = $resp->json();
        if (!is_array($json)) {
            $this->error("Finnhub: nem JSON válasz. BODY (first 200): " . substr($resp->body(), 0, 200));
            return null;
        }

        // Finnhub: s = ok / no_data
        if (($json['s'] ?? null) !== 'ok') {
            $this->warn("Finnhub s != ok: " . json_encode($json));
            return null;
        }

        return $json;
    }

    private function transformFinnhubToRows(string $symbol, string $tf, int $tfSec, array $json): array
    {
        $t = $json['t'] ?? [];
        $o = $json['o'] ?? [];
        $h = $json['h'] ?? [];
        $l = $json['l'] ?? [];
        $c = $json['c'] ?? [];

        if (!is_array($t) || count($t) === 0) return [];

        $rows = [];
        $n = count($t);
        for ($i = 0; $i < $n; $i++) {
            $openTs = (int)($t[$i] ?? 0);
            if ($openTs <= 0) continue;

            $open  = (float)($o[$i] ?? 0);
            $high  = (float)($h[$i] ?? 0);
            $low   = (float)($l[$i] ?? 0);
            $close = (float)($c[$i] ?? 0);

            if (!is_finite($open) || !is_finite($high) || !is_finite($low) || !is_finite($close)) continue;

            $rows[] = [
                'symbol'   => $symbol,
                'tf'       => $tf,
                'open_ts'  => $openTs,
                'close_ts' => $openTs + $tfSec - 1,
                'open'     => $open,
                'high'     => $high,
                'low'      => $low,
                'close'    => $close,
                'ticks'    => 1, // backfillből jön, nem tick-aggregáció
            ];
        }

        return $rows;
    }
}