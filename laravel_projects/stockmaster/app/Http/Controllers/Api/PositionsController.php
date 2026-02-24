<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class PositionsController extends Controller
{
    private const SPREAD = 0.05;

    // short fedezet (demo). 0.25 = 25% margin
    // ha nem kell: 0.0
    private const SHORT_MARGIN_RATE = 0.0;

    /**
     * POST /api/positions/open
     * JSON: { user_id, symbol, asset_name, quantity, price, side: buy|sell }
     *
     * BUY  -> long
     * SELL -> SHORT (engedélyezett, nem kell holding)
     */
    public function open(Request $request)
    {
        $payload = $request->all();

        $v = Validator::make($payload, [
            'user_id' => ['required', 'integer', 'min:1'],
            'symbol' => ['required', 'string', 'max:16'],
            'asset_name' => ['required', 'string', 'max:255'],
            'quantity' => ['required', 'numeric', 'gt:0'],
            'price' => ['required', 'numeric', 'gt:0'],
            'side' => ['required', 'in:buy,sell'],
        ]);

        if ($v->fails()) {
            return response()->json([
                'ok' => false,
                'error' => 'validation_failed',
                'details' => $v->errors(),
            ], 422);
        }

        $userId = (int) $payload['user_id'];
        $symbol = strtoupper(trim((string) $payload['symbol']));
        $assetName = trim((string) $payload['asset_name']);
        $qty = (float) $payload['quantity'];
        $price = (float) $payload['price'];
        $side = (string) $payload['side'];

        try {
            $out = DB::transaction(function () use ($userId, $symbol, $assetName, $qty, $price, $side) {

                // asset find/create
                $asset = DB::table('assets')->where('Symbol', $symbol)->first();
                if (!$asset) {
                    $assetId = DB::table('assets')->insertGetId([
                        'Symbol' => $symbol,
                        'Name' => $assetName,
                        'IsTradable' => 1,
                    ]);
                } else {
                    $assetId = (int) $asset->ID;
                }

                // user lock
                $user = DB::table('users')->where('ID', $userId)->lockForUpdate()->first();
                if (!$user) {
                    throw new \RuntimeException('Felhasználó nem található.');
                }

                $balance = (float) ($user->DemoBalance ?? 0);
                $tradeValue = $qty * $price;

                if ($side === 'buy') {
                    if ($tradeValue > $balance) {
                        throw new \RuntimeException('Nincs elegendő egyenleg.');
                    }
                    $newBalance = $balance - $tradeValue;
                } else {
                    // ✅ SHORT engedélyezve
                    $required = $tradeValue * self::SHORT_MARGIN_RATE;
                    if ($required > 0 && $balance < $required) {
                        throw new \RuntimeException('Nincs elég fedezet a shorthoz.');
                    }
                    $newBalance = $balance + $tradeValue;
                }

                DB::table('positions')->insert([
                    'UserID' => $userId,
                    'AssetID' => $assetId,
                    'OpenTime' => DB::raw('NOW()'),
                    'Quantity' => $qty,
                    'EntryPrice' => $price,
                    'PositionType' => $side, // buy|sell
                    'IsOpen' => 1,
                ]);

                DB::table('users')->where('ID', $userId)->update([
                    'DemoBalance' => $newBalance
                ]);

                return [
                    'ok' => true,
                    'newBalance' => $newBalance,
                    'assetId' => $assetId,
                    'symbol' => $symbol,
                    'side' => $side,
                    'quantity' => $qty,
                    'price' => $price,
                ];
            });

            return response()->json($out);
        } catch (\Throwable $e) {
            $msg = $e->getMessage();
            $status = (str_contains($msg, 'Nincs') || str_contains($msg, 'fedezet')) ? 409 : 500;
            return response()->json(['ok' => false, 'error' => $msg], $status);
        }
    }

    /**
     * POST /api/positions/close-by-asset
     * JSON: { user_id, assetId, midPrice }
     *
     * Zárás:
     *  - buy pozíciók bid-en zárnak
     *  - sell(short) pozíciók ask-en zárnak
     */
    public function closeByAsset(Request $request)
    {
        $payload = $request->all();

        $v = Validator::make($payload, [
            'user_id' => ['required', 'integer', 'min:1'],
            'assetId' => ['required', 'integer', 'min:1'],
            'midPrice' => ['required', 'numeric', 'gt:0'],
        ]);

        if ($v->fails()) {
            return response()->json([
                'ok' => false,
                'error' => 'validation_failed',
                'details' => $v->errors(),
            ], 422);
        }

        $userId = (int) $payload['user_id'];
        $assetId = (int) $payload['assetId'];
        $midPrice = (float) $payload['midPrice'];

        $half = self::SPREAD / 2.0;
        $bid = $midPrice - $half;
        $ask = $midPrice + $half;

        if ($bid <= 0 || $ask <= 0) {
            return response()->json(['ok' => false, 'error' => 'Érvénytelen bid/ask ár.'], 400);
        }

        try {
            $out = DB::transaction(function () use ($userId, $assetId, $bid, $ask, $midPrice) {

                $user = DB::table('users')->where('ID', $userId)->lockForUpdate()->first();
                if (!$user) {
                    throw new \RuntimeException('Felhasználó nem található.');
                }

                $rows = DB::table('positions')
                    ->select('ID', 'Quantity', 'EntryPrice', 'PositionType')
                    ->where('UserID', $userId)
                    ->where('AssetID', $assetId)
                    ->where('IsOpen', 1)
                    ->orderBy('ID', 'asc')
                    ->get();

                if ($rows->count() === 0) {
                    return ['ok' => false, 'error' => 'Nincs nyitott pozíció ehhez az eszközhöz.'];
                }

                $totalPnl = 0.0;
                $totalCashDelta = 0.0;
                $closedCount = 0;

                foreach ($rows as $pos) {
                    $positionId = (int) $pos->ID;
                    $q = (float) $pos->Quantity;
                    $en = (float) $pos->EntryPrice;
                    $pt = strtolower(trim((string) $pos->PositionType));

                    if ($positionId <= 0 || $q <= 0 || $en <= 0) continue;

                    if ($pt === 'buy') {
                        $closePrice = $bid;
                        $pnl = ($closePrice - $en) * $q;
                        // buy zárás: pénz bejön
                        $cashDelta = $closePrice * $q;
                    } elseif ($pt === 'sell') {
                        $closePrice = $ask;
                        $pnl = ($en - $closePrice) * $q;
                        // short zárás: pénz kimegy (visszavásárlás)
                        $cashDelta = -($closePrice * $q);
                    } else {
                        continue;
                    }

                    $affected = DB::table('positions')
                        ->where('ID', $positionId)
                        ->where('UserID', $userId)
                        ->where('IsOpen', 1)
                        ->update([
                            'CloseTime' => DB::raw('NOW()'),
                            'ExitPrice' => $closePrice,
                            'ProfitLoss' => $pnl,
                            'IsOpen' => 0,
                        ]);

                    if ($affected > 0) {
                        $totalPnl += $pnl;
                        $totalCashDelta += $cashDelta;
                        $closedCount++;
                    }
                }

                if ($closedCount <= 0) {
                    return ['ok' => false, 'error' => 'Nem volt lezárható nyitott pozíció.'];
                }

                DB::table('users')
                    ->where('ID', $userId)
                    ->update([
                        'DemoBalance' => DB::raw('DemoBalance + ' . (float)$totalCashDelta),
                    ]);

                $newBal = (float) DB::table('users')->where('ID', $userId)->value('DemoBalance');

                return [
                    'ok' => true,
                    'assetId' => $assetId,
                    'midPrice' => $midPrice,
                    'bid' => $bid,
                    'ask' => $ask,
                    'closedCount' => $closedCount,
                    'totalProfitLoss' => $totalPnl,
                    'balanceDelta' => $totalCashDelta,
                    'newBalance' => $newBal,
                    'spread' => self::SPREAD,
                ];
            });

            return response()->json($out, ($out['ok'] ?? false) ? 200 : 409);
        } catch (\Throwable $e) {
            return response()->json(['ok' => false, 'error' => $e->getMessage()], 500);
        }
    }
}