<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class StockMasterSeeder extends Seeder
{
    public function run(): void
    {
        // 1) Teszt user (legacy users tábla: Username / Email / PasswordHash)
        // Ha már létezik, nem szúrjuk újra.
        $existing = DB::table('users')->where('Email', 'test@stockmaster.local')->first();

        if (!$existing) {
            DB::table('users')->insert([
                'Username' => 'test',
                'Email' => 'test@stockmaster.local',
                'PasswordHash' => Hash::make('test1234'),

                'PreferredTheme' => 'dark',
                'NotificationsEnabled' => 1,
                'DemoBalance' => 10000.00,
                'RealBalance' => 0.00,
                'PreferredCurrency' => 'USD',

                // ha a táblában van RegistrationDate default, ezt nem kötelező megadni
                'RegistrationDate' => now(),
                'IsLoggedIn' => 0,
            ]);
        }

        $user = DB::table('users')->where('Email', 'test@stockmaster.local')->first();

        // 2) user_settings alapértelmezett sor (FK miatt kell, hogy user létezzen)
        if ($user && !DB::table('user_settings')->where('user_id', $user->ID)->exists()) {
            DB::table('user_settings')->insert([
                'user_id' => $user->ID,
                'timezone' => 'Europe/Budapest',
                'chart_interval' => '1m',
                'chart_theme' => 'dark',
                'chart_limit_initial' => 1500,
                'chart_backfill_chunk' => 1500,
                'news_limit' => 8,
                'news_per_symbol_limit' => 3,
                'news_portfolio_total_limit' => 20,
                'calendar_limit' => 8,
                'auto_login' => 0,
                'receive_notifications' => 1,
                'data' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // 3) Demo assets (ha üres az assets tábla)
        if (DB::table('assets')->count() === 0) {
            DB::table('assets')->insert([
                ['Symbol' => 'AAPL', 'Name' => 'Apple Inc.', 'IsTradable' => 1],
                ['Symbol' => 'ADBE', 'Name' => 'Adobe Inc.', 'IsTradable' => 1],
                ['Symbol' => 'AMD',  'Name' => 'Advanced Micro Devices', 'IsTradable' => 1],
                ['Symbol' => 'AMZN', 'Name' => 'Amazon.com, Inc.', 'IsTradable' => 1],
                ['Symbol' => 'AVGO', 'Name' => 'Broadcom Inc.', 'IsTradable' => 1],
                ['Symbol' => 'BA',   'Name' => 'Boeing Company', 'IsTradable' => 1],
                ['Symbol' => 'BAC',  'Name' => 'Bank of America Corporation', 'IsTradable' => 1],
                ['Symbol' => 'BLK',  'Name' => 'BlackRock, Inc.', 'IsTradable' => 1],
            ]);
        }
    }
}