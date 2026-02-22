<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Candle;
use Illuminate\Http\Request;

class CandleController extends Controller
{
    private const TF_SECONDS = [
        '1m'  => 60,
        '5m'  => 300,
        '15m' => 900,
        '1h'  => 3600,
        '1d'  => 86400,
    ];

    // Kompatibilitás: ha valahol még get()-et hívsz, ne omoljon össze
    public function get(Request $request)
    {
        return $this->index($request);
    }

    public function index(Request $request)
    {
        $data = $request->validate([
            'symbol' => ['required','string','max:20'],
            'tf'     => ['required','string','in:1m,5m,15m,1h,1d'],
            'from'   => ['nullable','integer','min:1'],
            'to'     => ['nullable','integer','min:1'],
            'limit'  => ['nullable','integer','min:1','max:5000'],
            'order'  => ['nullable','in:asc,desc'],
        ]);

        $symbol = strtoupper(trim($data['symbol']));
        $tf     = $data['tf'];
        $tfSec  = self::TF_SECONDS[$tf];

        $from   = isset($data['from']) ? (int)$data['from'] : null;
        $to     = isset($data['to'])   ? (int)$data['to']   : null;

        // ms -> sec védelem (ha valaki ms-et küld)
        if ($from !== null && $from > 2_000_000_000_000) $from = intdiv($from, 1000);
        if ($to   !== null && $to   > 2_000_000_000_000) $to   = intdiv($to, 1000);

        $limit = isset($data['limit']) ? (int)$data['limit'] : 300;
        $order = $data['order'] ?? 'desc'; // DB-ből desc gyorsabb (utolsó N), majd megfordítjuk

        $q = Candle::query()
            ->where('symbol', $symbol)
            ->where('tf', $tf);

        if ($from !== null) $q->where('open_ts', '>=', $from);
        if ($to   !== null) $q->where('open_ts', '<=', $to);

        $rows = $q->orderBy('open_ts', $order)->limit($limit)->get();

        // chartnak asc kell
        if ($order === 'desc') {
            $rows = $rows->reverse()->values();
        }

        $candles = $rows->map(function ($c) {
            return [
                'time'  => (int)$c->open_ts, // UNIX seconds
                'open'  => (float)$c->open,
                'high'  => (float)$c->high,
                'low'   => (float)$c->low,
                'close' => (float)$c->close,
            ];
        })->all();

        return response()->json([
            'ok'      => true,
            'symbol'  => $symbol,
            'tf'      => $tf,
            'tf_sec'  => $tfSec,
            'from'    => $from,
            'to'      => $to,
            'count'   => count($candles),
            'candles' => $candles,
        ]);
    }
}