<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Throwable;

class CandleController extends Controller
{
    public function index(Request $request)
    {
        try {
            $symbol = strtoupper(trim((string) $request->query('symbol', '')));
            $tf = strtolower(trim((string) $request->query('tf', '1m')));
            $limit = (int) $request->query('limit', 500);
            $from = $request->query('from');
            $to = $request->query('to');

            if ($symbol === '' || !preg_match('/^[A-Z0-9\.\-_]{1,20}$/', $symbol)) {
                return response()->json([
                    'ok' => false,
                    'error' => 'Invalid symbol',
                    'candles' => [],
                ], 422);
            }

            $allowedTf = ['1m', '5m', '15m', '1h', '1d'];
            if (!in_array($tf, $allowedTf, true)) {
                return response()->json([
                    'ok' => false,
                    'error' => 'Invalid timeframe',
                    'candles' => [],
                ], 422);
            }

            $limit = max(1, min($limit, 5000));

            $query = DB::table('candles')
                ->select(['open_ts', 'open', 'high', 'low', 'close'])
                ->where('symbol', $symbol)
                ->where('tf', $tf);

            if ($from !== null && $from !== '') {
                $query->where('open_ts', '>=', (int) $from);
            }

            if ($to !== null && $to !== '') {
                $query->where('open_ts', '<=', (int) $to);
            }

            $rows = $query
                ->orderBy('open_ts', 'desc')
                ->limit($limit)
                ->get()
                ->reverse()
                ->values();

            $candles = $rows->map(function ($row) {
                return [
                    'time' => (int) $row->open_ts,
                    'open' => (float) $row->open,
                    'high' => (float) $row->high,
                    'low' => (float) $row->low,
                    'close' => (float) $row->close,
                ];
            })->all();

            return response()->json([
                'ok' => true,
                'symbol' => $symbol,
                'tf' => $tf,
                'count' => count($candles),
                'candles' => $candles,
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'ok' => false,
                'error' => 'candle_fetch_failed',
                'message' => $e->getMessage(),
                'candles' => [],
            ], 500);
        }
    }
}