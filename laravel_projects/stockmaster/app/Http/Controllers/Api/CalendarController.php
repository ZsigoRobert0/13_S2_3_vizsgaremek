<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class CalendarController extends Controller
{
    public function index(Request $request)
    {
        $userId = (int) $request->query('user_id', 1);
        $from = (string) $request->query('from', now()->format('Y-m-d'));
        $to = (string) $request->query('to', now()->addDays(14)->format('Y-m-d'));

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $from) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $to)) {
            return response()->json([
                'ok' => false,
                'error' => 'Bad date format (YYYY-MM-DD)',
                'from' => $from,
                'to' => $to,
            ], 400);
        }

        $s = $this->getUserSettings($userId);
        $defCalendarLimit = (int)($s['calendar_limit'] ?? 8);

        $limit = (int) $request->query('limit', $defCalendarLimit);
        $limit = max(3, min(60, $limit));

        $apiKey = env('FINNHUB_API_KEY');
        if (!$apiKey) {
            // demo mód is ok
            return response()->json([
                'ok' => true,
                'mode' => 'demo',
                'from' => $from,
                'to' => $to,
                'source' => 'demo',
                'limit' => $limit,
                'warning' => 'FINNHUB_API_KEY not set, demo mode',
                'items' => array_slice($this->demoCalendar($from), 0, $limit),
            ]);
        }

        $cacheKey = "sm:cal:$userId:$from:$to:$limit";
        $cached = Cache::get($cacheKey);
        if ($cached) return response()->json($cached);

        // 1) economic
        $econUrl = "https://finnhub.io/api/v1/calendar/economic?from=" . urlencode($from)
            . "&to=" . urlencode($to)
            . "&token=" . urlencode($apiKey);

        $econResp = Http::timeout(3)->retry(1, 150)->get($econUrl);
        if ($econResp->ok()) {
            $payload = $econResp->json();
            $events = $payload['economicCalendar'] ?? [];
            if (!is_array($events)) $events = [];

            $outItems = [];
            foreach (array_slice($events, 0, $limit) as $e) {
                $outItems[] = [
                    'date' => $e['date'] ?? '',
                    'country' => $e['country'] ?? '',
                    'event' => $e['event'] ?? '',
                    'impact' => $e['impact'] ?? '',
                    'actual' => $e['actual'] ?? null,
                    'forecast' => $e['forecast'] ?? null,
                    'previous' => $e['previous'] ?? null,
                ];
            }

            $out = [
                'ok' => true,
                'mode' => 'economic',
                'from' => $from,
                'to' => $to,
                'source' => 'finnhub',
                'limit' => $limit,
                'items' => $outItems,
            ];
            Cache::put($cacheKey, $out, 60); // 60s cache
            return response()->json($out);
        }

        // 2) earnings fallback
        $earnUrl = "https://finnhub.io/api/v1/calendar/earnings?from=" . urlencode($from)
            . "&to=" . urlencode($to)
            . "&token=" . urlencode($apiKey);

        $earnResp = Http::timeout(3)->retry(1, 150)->get($earnUrl);
        if ($earnResp->ok()) {
            $payload = $earnResp->json();
            $items = $payload['earningsCalendar'] ?? [];
            if (!is_array($items)) $items = [];

            $outItems = [];
            foreach (array_slice($items, 0, $limit) as $e) {
                $symbol = (string)($e['symbol'] ?? '');
                $date = (string)($e['date'] ?? ($e['earningsDate'] ?? ''));
                $time = (string)($e['time'] ?? '');
                $quarter = (string)($e['quarter'] ?? '');

                $outItems[] = [
                    'date' => $date,
                    'type' => 'earnings',
                    'symbol' => $symbol,
                    'title' => trim($symbol . ' earnings'),
                    'time' => $time,
                    'quarter' => $quarter,
                    'epsActual' => $e['epsActual'] ?? ($e['actual'] ?? null),
                    'epsEstimate' => $e['epsEstimate'] ?? ($e['estimate'] ?? null),
                    'epsSurprise' => $e['epsSurprise'] ?? null,
                    'epsSurprisePercent' => $e['epsSurprisePercent'] ?? null,
                    'revenueActual' => $e['revenueActual'] ?? null,
                    'revenueEstimate' => $e['revenueEstimate'] ?? null,
                    'yearAgo' => $e['yearAgo'] ?? null,
                ];
            }

            $out = [
                'ok' => true,
                'mode' => 'earnings',
                'from' => $from,
                'to' => $to,
                'source' => 'finnhub',
                'limit' => $limit,
                'items' => $outItems,
                'note' => 'Economic calendar failed, earnings fallback used.',
            ];
            Cache::put($cacheKey, $out, 60);
            return response()->json($out);
        }

        // 3) demo fallback
        $out = [
            'ok' => true,
            'mode' => 'demo',
            'from' => $from,
            'to' => $to,
            'source' => 'demo',
            'limit' => $limit,
            'warning' => 'Finnhub calendar not available with this key/plan.',
            'items' => array_slice($this->demoCalendar($from), 0, $limit),
        ];
        Cache::put($cacheKey, $out, 60);
        return response()->json($out);
    }

    private function demoCalendar(string $from): array
    {
        return [
            [
                'date' => $from,
                'country' => 'US',
                'event' => 'CPI (demo)',
                'impact' => 'high',
                'actual' => null,
                'forecast' => null,
                'previous' => null,
            ],
            [
                'date' => date('Y-m-d', strtotime($from . ' +3 days')),
                'country' => 'US',
                'event' => 'FOMC Minutes (demo)',
                'impact' => 'medium',
                'actual' => null,
                'forecast' => null,
                'previous' => null,
            ],
            [
                'date' => date('Y-m-d', strtotime($from . ' +7 days')),
                'country' => 'US',
                'event' => 'GDP (demo)',
                'impact' => 'high',
                'actual' => null,
                'forecast' => null,
                'previous' => null,
            ],
        ];
    }

    private function getUserSettings(int $userId): array
    {
        $row = \DB::table('user_settings')->where('user_id', $userId)->first();
        if (!$row) return [];
        return (array) $row;
    }
}