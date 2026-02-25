<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class NewsController extends Controller
{
    public function index(Request $request)
    {
        $userId = (int) $request->query('user_id', 1);
        $mode = strtolower((string) $request->query('mode', 'portfolio'));
        if (!in_array($mode, ['portfolio', 'general'], true)) $mode = 'portfolio';

        $limit = (int) $request->query('limit', 0);
        $perSymbol = (int) $request->query('perSymbol', 0);

        $apiKey = env('FINNHUB_API_KEY');
        if (!$apiKey) {
            return response()->json(['ok' => false, 'error' => 'FINNHUB_API_KEY not set'], 500);
        }

        // --- Settings from DB (Laravel user_settings) ---
        // Ezeket a kulcsokat már a te SettingsControllered kezeli.
        // Ha nálad más mezőnevek vannak, szólj és átmappelem.
        $s = $this->getUserSettings($userId);

        $defNewsLimit = (int)($s['news_limit'] ?? 8);
        $defPerSymbol = (int)($s['news_per_symbol_limit'] ?? 3);
        $defPortfolioTotal = (int)($s['news_portfolio_total_limit'] ?? 20);

        if ($limit <= 0) $limit = ($mode === 'general') ? $defNewsLimit : $defPortfolioTotal;
        if ($perSymbol <= 0) $perSymbol = $defPerSymbol;

        $limit = max(3, min(60, $limit));
        $perSymbol = max(1, min(10, $perSymbol));

        // --- Portfolio symbols: a nyitott pozikból ---
        $symbols = [];
        if ($mode === 'portfolio') {
            $symbols = $this->getOpenSymbols($userId);

            if (count($symbols) === 0) {
                $mode = 'general';
                $limit = max(3, min(30, $defNewsLimit));
            }
        }

        // CACHE kulcs (stabilitás + Finnhub limit)
        $cacheKey = "sm:news:$mode:$userId:$limit:$perSymbol:" . implode(',', $symbols);
        $cached = Cache::get($cacheKey);
        if ($cached) return response()->json($cached);

        if ($mode === 'general') {
            $url = "https://finnhub.io/api/v1/news?category=general&token=" . urlencode($apiKey);

            $resp = Http::timeout(3)->retry(1, 150)->get($url);
            if (!$resp->ok()) {
                return response()->json(['ok' => false, 'error' => 'Finnhub news HTTP ' . $resp->status()], 502);
            }

            $data = $resp->json();
            if (!is_array($data)) $data = [];

            $items = [];
            foreach (array_slice($data, 0, min(30, $limit)) as $n) {
                $items[] = [
                    'headline' => $n['headline'] ?? '',
                    'source'   => $n['source'] ?? '',
                    'url'      => $n['url'] ?? '',
                    'datetime' => $n['datetime'] ?? null,
                    'summary'  => $n['summary'] ?? '',
                    'symbol'   => null,
                ];
            }

            $out = [
                'ok' => true,
                'mode' => 'general',
                'limit' => $limit,
                'items' => $items,
            ];

            Cache::put($cacheKey, $out, 30); // 30s cache
            return response()->json($out);
        }

        // --- Portfolio mode ---
        $to = now();
        $from = now()->subDays(7);
        $fromStr = $from->format('Y-m-d');
        $toStr = $to->format('Y-m-d');

        $items = [];
        foreach ($symbols as $sym) {
            $url = "https://finnhub.io/api/v1/company-news?symbol=" . urlencode($sym)
                . "&from=" . urlencode($fromStr)
                . "&to=" . urlencode($toStr)
                . "&token=" . urlencode($apiKey);

            $resp = Http::timeout(3)->retry(1, 150)->get($url);
            if (!$resp->ok()) continue;

            $data = $resp->json();
            if (!is_array($data)) $data = [];

            foreach (array_slice($data, 0, $perSymbol) as $n) {
                $items[] = [
                    'headline' => $n['headline'] ?? '',
                    'source'   => $n['source'] ?? '',
                    'url'      => $n['url'] ?? '',
                    'datetime' => $n['datetime'] ?? null,
                    'summary'  => $n['summary'] ?? '',
                    'symbol'   => $sym,
                ];
            }
        }

        usort($items, fn($a, $b) => (int)($b['datetime'] ?? 0) <=> (int)($a['datetime'] ?? 0));

        $out = [
            'ok' => true,
            'mode' => 'portfolio',
            'symbols' => $symbols,
            'limit' => $limit,
            'perSymbol' => $perSymbol,
            'items' => array_slice($items, 0, $limit),
        ];

        Cache::put($cacheKey, $out, 30); // 30s cache
        return response()->json($out);
    }

    private function getOpenSymbols(int $userId): array
    {
        // Itt direkt Query Builderrel, hogy ne kell model
        // Táblanevek: positions, assets (a te rendszeredben így volt)
        $rows = \DB::table('positions as p')
            ->join('assets as a', 'a.ID', '=', 'p.AssetID')
            ->where('p.UserID', $userId)
            ->where('p.IsOpen', 1)
            ->select(\DB::raw('DISTINCT a.Symbol as Symbol'))
            ->orderBy('a.Symbol')
            ->limit(10)
            ->get();

        $out = [];
        foreach ($rows as $r) {
            $sym = strtoupper((string)($r->Symbol ?? ''));
            if ($sym !== '') $out[] = $sym;
        }
        return $out;
    }

    private function getUserSettings(int $userId): array
    {
        // user_settings tábla (snake_case)
        $row = \DB::table('user_settings')->where('user_id', $userId)->first();
        if (!$row) return [];
        return (array) $row;
    }
}