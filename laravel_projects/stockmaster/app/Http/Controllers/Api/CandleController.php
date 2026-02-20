<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Candle;
use App\Services\CandleEngine;
use Illuminate\Http\Request;

class CandleController extends Controller
{
    public function get(Request $req)
    {
        $data = $req->validate([
            'symbol' => ['required','string','max:20'],
            'tf'     => ['required','in:1m,5m,15m,1h,1d'],
            'from'   => ['nullable','integer','min:1'],
            'to'     => ['nullable','integer','min:1'],
            'limit'  => ['nullable','integer','min:10','max:2000'],
        ]);

        $symbol = strtoupper(trim($data['symbol']));
        $tf     = $data['tf'];
        $limit  = (int)($data['limit'] ?? 500);

        $now = time();
        $sec = CandleEngine::tfSeconds($tf);
        $to   = (int)($data['to'] ?? $now);
        $from = (int)($data['from'] ?? ($to - $sec * $limit));

        if ($from >= $to) {
            return response()->json(['ok'=>false,'error'=>'Invalid range'], 422);
        }

        $rows = Candle::query()
            ->where('symbol', $symbol)
            ->where('tf', $tf)
            ->whereBetween('open_ts', [$from, $to])
            ->orderBy('open_ts','asc')
            ->limit($limit)
            ->get(['open_ts','open','high','low','close']);

        $candles = $rows->map(fn($r) => [
            'time'  => (int)$r->open_ts,
            'open'  => (float)$r->open,
            'high'  => (float)$r->high,
            'low'   => (float)$r->low,
            'close' => (float)$r->close,
        ])->values();

        return response()->json([
            'ok' => true,
            'symbol' => $symbol,
            'tf' => $tf,
            'from' => $from,
            'to' => $to,
            'candles' => $candles,
        ]);
    }
}