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
                ['Symbol' => 'MSFT', 'Name' => 'Microsoft Corporation', 'IsTradable' => 1],
                ['Symbol' => 'NVDA', 'Name' => 'NVIDIA Corporation', 'IsTradable' => 1],
                ['Symbol' => 'AMZN', 'Name' => 'Amazon.com, Inc.', 'IsTradable' => 1],
                ['Symbol' => 'GOOGL', 'Name' => 'Alphabet Inc. Class A', 'IsTradable' => 1],
                ['Symbol' => 'GOOG', 'Name' => 'Alphabet Inc. Class C', 'IsTradable' => 1],
                ['Symbol' => 'META', 'Name' => 'Meta Platforms, Inc.', 'IsTradable' => 1],
                ['Symbol' => 'TSLA', 'Name' => 'Tesla, Inc.', 'IsTradable' => 1],
                ['Symbol' => 'BRK.B', 'Name' => 'Berkshire Hathaway Inc. Class B', 'IsTradable' => 1],
                ['Symbol' => 'JPM', 'Name' => 'JPMorgan Chase & Co.', 'IsTradable' => 1],
                ['Symbol' => 'V', 'Name' => 'Visa Inc.', 'IsTradable' => 1],
                ['Symbol' => 'MA', 'Name' => 'Mastercard Incorporated', 'IsTradable' => 1],
                ['Symbol' => 'UNH', 'Name' => 'UnitedHealth Group Incorporated', 'IsTradable' => 1],
                ['Symbol' => 'XOM', 'Name' => 'Exxon Mobil Corporation', 'IsTradable' => 1],
                ['Symbol' => 'LLY', 'Name' => 'Eli Lilly and Company', 'IsTradable' => 1],
                ['Symbol' => 'WMT', 'Name' => 'Walmart Inc.', 'IsTradable' => 1],
                ['Symbol' => 'JNJ', 'Name' => 'Johnson & Johnson', 'IsTradable' => 1],
                ['Symbol' => 'PG', 'Name' => 'Procter & Gamble Company', 'IsTradable' => 1],
                ['Symbol' => 'COST', 'Name' => 'Costco Wholesale Corporation', 'IsTradable' => 1],
                ['Symbol' => 'AVGO', 'Name' => 'Broadcom Inc.', 'IsTradable' => 1],
                ['Symbol' => 'HD', 'Name' => 'The Home Depot, Inc.', 'IsTradable' => 1],
                ['Symbol' => 'ORCL', 'Name' => 'Oracle Corporation', 'IsTradable' => 1],
                ['Symbol' => 'BAC', 'Name' => 'Bank of America Corporation', 'IsTradable' => 1],
                ['Symbol' => 'ABBV', 'Name' => 'AbbVie Inc.', 'IsTradable' => 1],
                ['Symbol' => 'KO', 'Name' => 'The Coca-Cola Company', 'IsTradable' => 1],
                ['Symbol' => 'CRM', 'Name' => 'Salesforce, Inc.', 'IsTradable' => 1],
                ['Symbol' => 'NFLX', 'Name' => 'Netflix, Inc.', 'IsTradable' => 1],
                ['Symbol' => 'AMD', 'Name' => 'Advanced Micro Devices, Inc.', 'IsTradable' => 1],
                ['Symbol' => 'ADBE', 'Name' => 'Adobe Inc.', 'IsTradable' => 1],
                ['Symbol' => 'PEP', 'Name' => 'PepsiCo, Inc.', 'IsTradable' => 1],
                ['Symbol' => 'TMO', 'Name' => 'Thermo Fisher Scientific Inc.', 'IsTradable' => 1],
                ['Symbol' => 'MCD', 'Name' => 'McDonald\'s Corporation', 'IsTradable' => 1],
                ['Symbol' => 'CSCO', 'Name' => 'Cisco Systems, Inc.', 'IsTradable' => 1],
                ['Symbol' => 'ACN', 'Name' => 'Accenture plc', 'IsTradable' => 1],
                ['Symbol' => 'ABT', 'Name' => 'Abbott Laboratories', 'IsTradable' => 1],
                ['Symbol' => 'DHR', 'Name' => 'Danaher Corporation', 'IsTradable' => 1],
                ['Symbol' => 'LIN', 'Name' => 'Linde plc', 'IsTradable' => 1],
                ['Symbol' => 'WFC', 'Name' => 'Wells Fargo & Company', 'IsTradable' => 1],
                ['Symbol' => 'MRK', 'Name' => 'Merck & Co., Inc.', 'IsTradable' => 1],
                ['Symbol' => 'DIS', 'Name' => 'The Walt Disney Company', 'IsTradable' => 1],
                ['Symbol' => 'INTU', 'Name' => 'Intuit Inc.', 'IsTradable' => 1],
                ['Symbol' => 'QCOM', 'Name' => 'QUALCOMM Incorporated', 'IsTradable' => 1],
                ['Symbol' => 'TXN', 'Name' => 'Texas Instruments Incorporated', 'IsTradable' => 1],
                ['Symbol' => 'INTC', 'Name' => 'Intel Corporation', 'IsTradable' => 1],
                ['Symbol' => 'IBM', 'Name' => 'International Business Machines Corporation', 'IsTradable' => 1],
                ['Symbol' => 'AMGN', 'Name' => 'Amgen Inc.', 'IsTradable' => 1],
                ['Symbol' => 'GE', 'Name' => 'GE Aerospace', 'IsTradable' => 1],
                ['Symbol' => 'CAT', 'Name' => 'Caterpillar Inc.', 'IsTradable' => 1],
                ['Symbol' => 'NOW', 'Name' => 'ServiceNow, Inc.', 'IsTradable' => 1],
                ['Symbol' => 'BKNG', 'Name' => 'Booking Holdings Inc.', 'IsTradable' => 1],
                ['Symbol' => 'GS', 'Name' => 'The Goldman Sachs Group, Inc.', 'IsTradable' => 1],
                ['Symbol' => 'MS', 'Name' => 'Morgan Stanley', 'IsTradable' => 1],
                ['Symbol' => 'RTX', 'Name' => 'RTX Corporation', 'IsTradable' => 1],
                ['Symbol' => 'BLK', 'Name' => 'BlackRock, Inc.', 'IsTradable' => 1],
                ['Symbol' => 'SPGI', 'Name' => 'S&P Global Inc.', 'IsTradable' => 1],
                ['Symbol' => 'PLD', 'Name' => 'Prologis, Inc.', 'IsTradable' => 1],
                ['Symbol' => 'MDT', 'Name' => 'Medtronic plc', 'IsTradable' => 1],
                ['Symbol' => 'ISRG', 'Name' => 'Intuitive Surgical, Inc.', 'IsTradable' => 1],
                ['Symbol' => 'CMCSA', 'Name' => 'Comcast Corporation', 'IsTradable' => 1],
                ['Symbol' => 'UBER', 'Name' => 'Uber Technologies, Inc.', 'IsTradable' => 1],
                ['Symbol' => 'SHOP', 'Name' => 'Shopify Inc.', 'IsTradable' => 1],
                ['Symbol' => 'PYPL', 'Name' => 'PayPal Holdings, Inc.', 'IsTradable' => 1],
                ['Symbol' => 'NKE', 'Name' => 'NIKE, Inc.', 'IsTradable' => 1],
                ['Symbol' => 'HON', 'Name' => 'Honeywell International Inc.', 'IsTradable' => 1],
                ['Symbol' => 'LOW', 'Name' => 'Lowe\'s Companies, Inc.', 'IsTradable' => 1],
                ['Symbol' => 'UNP', 'Name' => 'Union Pacific Corporation', 'IsTradable' => 1],
                ['Symbol' => 'DE', 'Name' => 'Deere & Company', 'IsTradable' => 1],
                ['Symbol' => 'BA', 'Name' => 'The Boeing Company', 'IsTradable' => 1],
                ['Symbol' => 'AMAT', 'Name' => 'Applied Materials, Inc.', 'IsTradable' => 1],
                ['Symbol' => 'MU', 'Name' => 'Micron Technology, Inc.', 'IsTradable' => 1],
                ['Symbol' => 'ADI', 'Name' => 'Analog Devices, Inc.', 'IsTradable' => 1],
                ['Symbol' => 'LRCX', 'Name' => 'Lam Research Corporation', 'IsTradable' => 1],
                ['Symbol' => 'PANW', 'Name' => 'Palo Alto Networks, Inc.', 'IsTradable' => 1],
                ['Symbol' => 'CRWD', 'Name' => 'CrowdStrike Holdings, Inc.', 'IsTradable' => 1],
                ['Symbol' => 'SNOW', 'Name' => 'Snowflake Inc.', 'IsTradable' => 1],
                ['Symbol' => 'KLAC', 'Name' => 'KLA Corporation', 'IsTradable' => 1],
                ['Symbol' => 'PFE', 'Name' => 'Pfizer Inc.', 'IsTradable' => 1],
                ['Symbol' => 'GILD', 'Name' => 'Gilead Sciences, Inc.', 'IsTradable' => 1],
                ['Symbol' => 'COP', 'Name' => 'ConocoPhillips', 'IsTradable' => 1],
                ['Symbol' => 'CVX', 'Name' => 'Chevron Corporation', 'IsTradable' => 1],
                ['Symbol' => 'SLB', 'Name' => 'Schlumberger Limited', 'IsTradable' => 1],
                ['Symbol' => 'BX', 'Name' => 'Blackstone Inc.', 'IsTradable' => 1],
                ['Symbol' => 'SCHW', 'Name' => 'The Charles Schwab Corporation', 'IsTradable' => 1],
                ['Symbol' => 'C', 'Name' => 'Citigroup Inc.', 'IsTradable' => 1],
                ['Symbol' => 'USB', 'Name' => 'U.S. Bancorp', 'IsTradable' => 1],
                ['Symbol' => 'AXP', 'Name' => 'American Express Company', 'IsTradable' => 1],
                ['Symbol' => 'T', 'Name' => 'AT&T Inc.', 'IsTradable' => 1],
                ['Symbol' => 'VZ', 'Name' => 'Verizon Communications Inc.', 'IsTradable' => 1],
                ['Symbol' => 'TMUS', 'Name' => 'T-Mobile US, Inc.', 'IsTradable' => 1],
                ['Symbol' => 'F', 'Name' => 'Ford Motor Company', 'IsTradable' => 1],
                ['Symbol' => 'GM', 'Name' => 'General Motors Company', 'IsTradable' => 1],
                ['Symbol' => 'RIVN', 'Name' => 'Rivian Automotive, Inc.', 'IsTradable' => 1],
                ['Symbol' => 'SPY', 'Name' => 'SPDR S&P 500 ETF Trust', 'IsTradable' => 1],
                ['Symbol' => 'QQQ', 'Name' => 'Invesco QQQ Trust', 'IsTradable' => 1],
                ['Symbol' => 'IWM', 'Name' => 'iShares Russell 2000 ETF', 'IsTradable' => 1],
                ['Symbol' => 'DIA', 'Name' => 'SPDR Dow Jones Industrial Average ETF Trust', 'IsTradable' => 1],
                ['Symbol' => 'XLF', 'Name' => 'Financial Select Sector SPDR Fund', 'IsTradable' => 1],
                ['Symbol' => 'XLK', 'Name' => 'Technology Select Sector SPDR Fund', 'IsTradable' => 1],
                ['Symbol' => 'XLE', 'Name' => 'Energy Select Sector SPDR Fund', 'IsTradable' => 1],
                ['Symbol' => 'SMH', 'Name' => 'VanEck Semiconductor ETF', 'IsTradable' => 1],
            ]);
        }
    }
}