<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Asset;
use Illuminate\Http\Request;

class AssetsController extends Controller
{
    public function index(Request $request)
    {
        $search   = trim((string)$request->query('search', ''));
        $tradable = (int)$request->query('tradable', 1);
        $limit    = (int)$request->query('limit', 200);
        $limit    = max(1, min($limit, 500));

        $q = Asset::query()
            ->select(['Symbol', 'Name', 'IsTradable'])
            ->when($tradable === 1, fn($qq) => $qq->where('IsTradable', 1))
            ->when($search !== '', function ($qq) use ($search) {
                $qq->where(function ($w) use ($search) {
                    $w->where('Symbol', 'like', $search . '%')
                      ->orWhere('Name', 'like', '%' . $search . '%');
                });
            })
            ->orderBy('Symbol')
            ->limit($limit);

        $rows = $q->get()->map(fn($r) => [
            'symbol' => $r->Symbol,
            'name'   => $r->Name,
        ])->values();

        return response()->json([
            'ok'   => true,
            'data' => $rows,
            'meta' => [
                'search'   => $search,
                'tradable' => (string)$tradable,
                'limit'    => $limit,
                'count'    => $rows->count(),
            ],
        ]);
    }
}