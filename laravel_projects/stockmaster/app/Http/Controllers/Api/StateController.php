<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StateController extends Controller
{
    /**
     * GET /api/state?user_id=1
     * -> { ok, balance, positions: [...] }
     */
    public function show(Request $request)
    {
        $userId = (int) $request->query('user_id', 0);
        if ($userId <= 0) {
            return response()->json(['ok' => false, 'error' => 'user_id is required'], 400);
        }

        $user = DB::table('users')->select('DemoBalance')->where('ID', $userId)->first();
        if (!$user) {
            return response()->json(['ok' => false, 'error' => 'user_not_found'], 404);
        }

        $balance = (float) ($user->DemoBalance ?? 0);

        $rows = DB::select("
            SELECT
              a.ID AS AssetID,
              a.Symbol,
              a.Name,
              SUM(CASE WHEN p.PositionType='buy' THEN p.Quantity ELSE -p.Quantity END) AS NetQty,
              (
                SUM(
                  CASE
                    WHEN p.PositionType='buy' THEN  (p.Quantity * p.EntryPrice)
                    ELSE                             (-p.Quantity * p.EntryPrice)
                  END
                )
                /
                NULLIF(SUM(CASE WHEN p.PositionType='buy' THEN p.Quantity ELSE -p.Quantity END), 0)
              ) AS AvgEntryPrice
            FROM positions p
            JOIN assets a ON a.ID = p.AssetID
            WHERE p.UserID = ? AND p.IsOpen = 1
            GROUP BY a.ID, a.Symbol, a.Name
            HAVING ABS(NetQty) > 0.000001
            ORDER BY a.Symbol
        ", [$userId]);

        $positions = array_map(function ($r) {
            return [
                'AssetID'       => (int) $r->AssetID,
                'Symbol'        => (string) $r->Symbol,
                'Name'          => (string) $r->Name,
                'Quantity'      => (float) $r->NetQty,
                'AvgEntryPrice' => $r->AvgEntryPrice !== null ? (float) $r->AvgEntryPrice : 0.0,
            ];
        }, $rows);

        return response()->json([
            'ok' => true,
            'balance' => $balance,
            'positions' => $positions,
        ]);
    }
}