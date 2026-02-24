<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\UserSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SettingsController extends Controller
{
    /**
     * GET /api/settings?user_id=1
     */
    public function show(Request $request)
    {
        $userId = (int) $request->query('user_id', 0);

        if ($userId <= 0) {
            return response()->json([
                'ok' => false,
                'error' => 'user_id is required'
            ], 400);
        }

        $settings = UserSetting::firstOrCreate(
            ['user_id' => $userId],
            [
                'timezone' => 'Europe/Budapest',
                'chart_interval' => '1m',
                'chart_theme' => 'dark',
                'chart_limit_initial' => 1500,
                'chart_backfill_chunk' => 1500,
                'news_limit' => 8,
                'news_per_symbol_limit' => 3,
                'news_portfolio_total_limit' => 20,
                'calendar_limit' => 8,
                'auto_login' => false,
                'receive_notifications' => true,
                'data' => null,
            ]
        );

        return response()->json([
            'ok' => true,
            'data' => $settings
        ]);
    }

    /**
     * POST /api/settings
     * Body JSON:
     * {
     *   "user_id": 1,
     *   "timezone": "...",
     *   "chart_interval": "1m",
     *   ...
     * }
     */
    public function update(Request $request)
    {
        $payload = $request->all();

        $validator = Validator::make($payload, [
            'user_id' => ['required', 'integer', 'min:1'],

            'timezone' => ['sometimes', 'string', 'max:64'],

            'chart_interval' => ['sometimes', 'string', 'max:10'],
            'chart_theme' => ['sometimes', 'string', 'max:20'],
            'chart_limit_initial' => ['sometimes', 'integer', 'min:100', 'max:5000'],
            'chart_backfill_chunk' => ['sometimes', 'integer', 'min:100', 'max:5000'],

            'news_limit' => ['sometimes', 'integer', 'min:0', 'max:50'],
            'news_per_symbol_limit' => ['sometimes', 'integer', 'min:0', 'max:50'],
            'news_portfolio_total_limit' => ['sometimes', 'integer', 'min:0', 'max:200'],
            'calendar_limit' => ['sometimes', 'integer', 'min:0', 'max:50'],

            'auto_login' => ['sometimes', 'boolean'],
            'receive_notifications' => ['sometimes', 'boolean'],

            'data' => ['sometimes'], // json/array vegyesen jöhet
        ]);

        if ($validator->fails()) {
            return response()->json([
                'ok' => false,
                'error' => 'validation_failed',
                'details' => $validator->errors(),
            ], 422);
        }

        $userId = (int) $payload['user_id'];

        $settings = UserSetting::firstOrCreate(['user_id' => $userId]);

        // csak a megengedett mezőket update-eljük
        $allowed = [
            'timezone',
            'chart_interval',
            'chart_theme',
            'chart_limit_initial',
            'chart_backfill_chunk',
            'news_limit',
            'news_per_symbol_limit',
            'news_portfolio_total_limit',
            'calendar_limit',
            'auto_login',
            'receive_notifications',
            'data',
        ];

        $toUpdate = [];
        foreach ($allowed as $key) {
            if (array_key_exists($key, $payload)) {
                $toUpdate[$key] = $payload[$key];
            }
        }

        $settings->fill($toUpdate);
        $settings->save();

        return response()->json([
            'ok' => true,
            'data' => $settings
        ]);
    }
}