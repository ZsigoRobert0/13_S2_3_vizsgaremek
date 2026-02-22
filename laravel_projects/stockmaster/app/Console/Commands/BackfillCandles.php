<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class BackfillCandles extends Command
{
    // EZ A LÉNYEG: most már lesz "candles" namespace és "backfill" command
    protected $signature = 'candles:backfill 
        {symbol : Pl. AAPL} 
        {tf : 1m|5m|15m|1h|1d} 
        {days=10 : Hány napot töltsön vissza}';

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

        // chunk méret (Finnhub limit/terhelés miatt)
        $chunkDays = match ($tf) {
            '1m'  => 2,
            '5m'  => 7,
            '15m' => 14,
            '1h'  => 90,
            '1d'  => 3650,
            default => 7,
        };

        $toTs   = now()->timestamp;
        $fromTs = now()->subDays($days)->timestamp;

        $this->info("Backfill: {$symbol} tf={$tf} (resolution={$resolution}) days={$days}");
        $this->info("Range: {$fromTs} -> {$toTs} (unix sec)");
        $this->info("ChunkDays: {$chunkDays}");

        $totalInsertedOrUpdated = 0;

        // időablak darabolása
        $cursorFrom = $fromTs;
        while ($cursorFrom < $toTs) {
            $cursorTo = min($toTs, $cursorFrom + ($chunkDays * 86400));

            $this->line("Fetch chunk: {$cursorFrom} -> {$cursorTo}");

            $data = $this->fetchFinnhubCandles($apiKey, $symbol, $resolution, $cursorFrom, $cursorTo);
            if ($data === null) {
                $this->warn("Chunk: üres/hibás válasz (lehet market zárva vagy limit).");
                $cursorFrom = $cursorTo + 1;
                usleep(350000); // kis pihi rate limit ellen
                continue;
            }

            $rows = $this->transformFinnhubToRows($symbol, $tf, $data);
            if (!$rows) {
                $this->warn("Chunk: 0 candle.");
                $cursorFrom = $cursorTo + 1;
                usleep(350000);
                continue;
            }

            // Upsert: (symbol, tf, open_ts) unique kulcs alapján
            // Feltételezett oszlopok: symbol, tf, open_ts, open, high, low, close, volume, created_at, updated_at
            $now = now();
            foreach ($rows as &$r) {
                $r['created_at'] = $now;
                $r['updated_at'] = $now;
            }
            unset($r);

            DB::table('candles')->upsert(
                $rows,
                ['symbol', 'tf', 'open_ts'],
                ['open', 'high', 'low', 'close', 'volume', 'updated_at']
            );

            $totalInsertedOrUpdated += count($rows);

            $this->info("Upserted: " . count($rows) . " rows (running total: {$totalInsertedOrUpdated})");

            $cursorFrom = $cursorTo + 1;
            usleep(350000);
        }

        $this->info("DONE. Total processed candles: {$totalInsertedOrUpdated}");
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

    private function fetchFinnhubCandles(string $apiKey, string $symbol, string $resolution, int $from, int $to): ?array
{
    $resp = Http::timeout(20)
        ->retry(2, 300)
        ->acceptJson()
        ->get('https://finnhub.io/api/v1/stock/candle', [
            'symbol'     => $symbol,
            'resolution' => $resolution,
            'from'       => $from,
            'to'         => $to,
            'token'      => $apiKey,
        ]);

    // Itt a lényeg: ne dobjon, hanem mondja meg mi a baj
    if (!$resp->ok()) {
        $this->error("Finnhub HTTP STATUS: " . $resp->status());
        $this->error("Finnhub BODY (first 500): " . substr($resp->body(), 0, 500));
        return null;
    }

    $json = $resp->json();
    if (!is_array($json)) {
        $this->error("Finnhub: nem JSON válasz. BODY (first 200): " . substr($resp->body(), 0, 200));
        return null;
    }

    if (($json['s'] ?? null) !== 'ok') {
        $this->warn("Finnhub s != ok: " . json_encode($json));
        return null;
    }

    return $json;
}

    private function transformFinnhubToRows(string $symbol, string $tf, array $json): array
    {
        // Finnhub tömbök: t,o,h,l,c,v
        $t = $json['t'] ?? [];
        $o = $json['o'] ?? [];
        $h = $json['h'] ?? [];
        $l = $json['l'] ?? [];
        $c = $json['c'] ?? [];
        $v = $json['v'] ?? [];

        if (!is_array($t) || count($t) === 0) return [];

        $rows = [];
        $n = count($t);
        for ($i = 0; $i < $n; $i++) {
            $openTs = (int)($t[$i] ?? 0);
            if ($openTs <= 0) continue;

            $rows[] = [
                'symbol'  => $symbol,
                'tf'      => $tf,
                'open_ts' => $openTs,
                'open'    => (float)($o[$i] ?? 0),
                'high'    => (float)($h[$i] ?? 0),
                'low'     => (float)($l[$i] ?? 0),
                'close'   => (float)($c[$i] ?? 0),
                'volume'  => (float)($v[$i] ?? 0),
            ];
        }

        return $rows;
    }
}