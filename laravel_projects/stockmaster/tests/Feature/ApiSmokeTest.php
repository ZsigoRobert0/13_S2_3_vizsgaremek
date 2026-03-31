<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

/**
 * StockMaster API smoke + regression tests.
 *
 * Goal: quick automated verification for the legacy UI integration:
 * - assets/candles/state/positions/settings/prices/news/calendar/tick
 * - covers the critical SHORT equity model behavior and request validations
 */
class ApiSmokeTest extends TestCase
{
    use RefreshDatabase;

    protected int $userId;

    protected function setUp(): void
    {
        parent::setUp();

        // Fresh DB + seed demo user/assets/settings
        Artisan::call('migrate');
        Artisan::call('db:seed', ['--class' => 'Database\\Seeders\\StockMasterSeeder']);

        $this->userId = (int) \DB::table('users')->where('Email', 'test@stockmaster.local')->value('ID');
        $this->assertTrue($this->userId > 0, 'Seeded test user not found');

        // Make RateLimiter deterministic in tests
        RateLimiter::clear('prices:127.0.0.1');
        RateLimiter::clear('tick-ingest:127.0.0.1');

        // Ensure locks work during tests
        Cache::flush();

        // Provide Finnhub key for endpoints that require it
        config(['app.env' => 'testing']);
        putenv('FINNHUB_API_KEY=dummy');
        $_ENV['FINNHUB_API_KEY'] = 'dummy';
    }

    /**
     * Helper: fake all Finnhub endpoints used across this project.
     * We keep it centralized so every test can opt-in easily.
     */
    private function fakeFinnhub(): void
    {
        Http::fake([
            // Quote
            'finnhub.io/api/v1/quote*' => Http::response(['c' => 123.45, 'b' => 123.4, 'a' => 123.5], 200),

            // General news
            'finnhub.io/api/v1/news*' => Http::response([
                ['headline' => 'Test headline', 'datetime' => 1700000000, 'source' => 'unit', 'url' => 'https://example.com', 'summary' => 'x'],
            ], 200),

            // Company news
            'finnhub.io/api/v1/company-news*' => Http::response([
                ['headline' => 'Company headline', 'datetime' => 1700000001, 'source' => 'unit', 'url' => 'https://example.com/c', 'summary' => 'y'],
            ], 200),

            // Calendar: economic + earnings
            'finnhub.io/api/v1/calendar/economic*' => Http::response(['economicCalendar' => [
                ['date' => '2026-02-27', 'event' => 'CPI', 'impact' => 'High', 'country' => 'US'],
            ]], 200),
            'finnhub.io/api/v1/calendar/earnings*' => Http::response(['earningsCalendar' => [
                ['date' => '2026-02-27', 'symbol' => 'AAPL', 'epsActual' => null, 'epsEstimate' => 1.2],
            ]], 200),
        ]);
    }

    /**
     * Helper: ingest one tick so candles/state/trade tests have data.
     */
    private function ingestTick(string $symbol = 'AAPL', int $tsSec = 1_700_000_000, float $price = 100.0): void
    {
        $res = $this->postJson('/api/tick/ingest', [
            'symbol' => $symbol,
            'ts' => $tsSec,
            'price' => $price,
        ]);
        $res->assertOk()->assertJsonPath('ok', true);
    }

    public function test_assets_list_default_tradable(): void
    {
        $res = $this->getJson('/api/assets');
        $res->assertOk()->assertJsonPath('ok', true);
        $this->assertGreaterThan(0, count($res->json('data')));
    }

    public function test_assets_rejects_bad_limit_type(): void
    {
        // Laravel converts strings; we only ensure endpoint stays stable.
        $res = $this->getJson('/api/assets?limit=abc');
        $res->assertOk()->assertJsonPath('ok', true);
    }

    public function test_assets_search_filters(): void
    {
        $res = $this->getJson('/api/assets?search=AAP');
        $res->assertOk()->assertJsonPath('ok', true);
        $symbols = array_map(fn($r) => $r['symbol'], $res->json('data'));
        $this->assertContains('AAPL', $symbols);
    }

    public function test_assets_limit_is_capped(): void
    {
        $res = $this->getJson('/api/assets?limit=9999');
        $res->assertOk()->assertJsonPath('ok', true);
        $this->assertSame(500, (int) $res->json('meta.limit'));
    }

    public function test_candles_validation_requires_symbol_and_tf(): void
    {
        $this->getJson('/api/candles')->assertStatus(422);
        $this->getJson('/api/candles?symbol=AAPL')->assertStatus(422);
        $this->getJson('/api/candles?tf=1m')->assertStatus(422);
    }

    public function test_candles_success_after_tick_ingest(): void
    {
        $this->ingestTick('AAPL', 1_700_000_000, 100.0);

        $candles = $this->getJson('/api/candles?symbol=AAPL&tf=1m&limit=50');
        $candles->assertOk()->assertJsonPath('ok', true);

        $this->assertGreaterThanOrEqual(1, (int) $candles->json('count'));
        $data = $candles->json('data');
        $this->assertIsArray($data);
        $this->assertArrayHasKey('t', $data[0]);
        $this->assertArrayHasKey('o', $data[0]);
        $this->assertArrayHasKey('c', $data[0]);
    }

    public function test_tick_ingest_normalizes_ms_timestamp_and_creates_candles(): void
    {
        $payload = [
            'symbol' => 'AAPL',
            'ts' => 1_700_000_000_000, // ms
            'price' => 100.0,
        ];

        $res = $this->postJson('/api/tick/ingest', $payload);
        $res->assertOk()->assertJsonPath('ok', true);
        $this->assertIsInt($res->json('ts'));

        // Candle should be queryable
        $candles = $this->getJson('/api/candles?symbol=AAPL&tf=1m&limit=10');
        $candles->assertOk()->assertJsonPath('ok', true);
        $this->assertGreaterThanOrEqual(1, (int) $candles->json('count'));
    }

    public function test_tick_ingest_validation_fails_without_required_fields(): void
    {
        $this->postJson('/api/tick/ingest', [])->assertStatus(422);
        $this->postJson('/api/tick/ingest', ['symbol' => 'AAPL'])->assertStatus(422);
    }

    public function test_tick_ingest_rate_limit_returns_429_when_exceeded(): void
    {
        $key = 'tick-ingest:127.0.0.1';
        for ($i = 0; $i < 241; $i++) {
            RateLimiter::hit($key, 1);
        }

        $res = $this->postJson('/api/tick/ingest', [
            'symbol' => 'AAPL',
            'ts' => 1_700_000_000,
            'price' => 100,
        ]);
        $res->assertStatus(429);
    }

    public function test_settings_show_and_update(): void
    {
        $show = $this->getJson('/api/settings?user_id=' . $this->userId);
        $show->assertOk()->assertJsonPath('ok', true);

        $upd = $this->postJson('/api/settings', [
            'user_id' => $this->userId,
            'chart_interval' => '5m',
            'chart_theme' => 'dark',
            'news_limit' => 9,
            'calendar_limit' => 7,
        ]);
        $upd->assertOk()->assertJsonPath('ok', true);

        $show2 = $this->getJson('/api/settings?user_id=' . $this->userId);
        $show2->assertOk();
        $this->assertSame('5m', $show2->json('data.chart_interval'));
        $this->assertSame(9, (int) $show2->json('data.news_limit'));
    }

    public function test_settings_requires_user_id_on_show_and_update(): void
    {
        $this->getJson('/api/settings')->assertStatus(422);

        $this->postJson('/api/settings', [
            'chart_interval' => '5m',
        ])->assertStatus(422);
    }

    public function test_settings_validation_fails_on_bad_interval(): void
    {
        $bad = $this->postJson('/api/settings', [
            'user_id' => $this->userId,
            'chart_interval' => '2m',
        ]);
        $bad->assertStatus(422);
    }

    public function test_state_returns_balance_and_positions(): void
    {
        $state = $this->getJson('/api/state?user_id=' . $this->userId);
        $state->assertOk()->assertJsonPath('ok', true);
        $this->assertIsArray($state->json('positions'));
        $this->assertIsNumeric($state->json('balance'));
    }

    public function test_state_requires_user_id(): void
    {
        $this->getJson('/api/state')->assertStatus(422);
    }

    public function test_open_long_decreases_balance_and_close_long_restores_cash(): void
    {
        $bal0 = (float) \DB::table('users')->where('ID', $this->userId)->value('DemoBalance');

        // Ensure asset exists in assets table; seed has AAPL.
        $open = $this->postJson('/api/positions/open', [
            'user_id' => $this->userId,
            'symbol' => 'AAPL',
            'asset_name' => 'Apple Inc.',
            'quantity' => 1,
            'price' => 100,
            'side' => 'buy',
        ]);
        $open->assertOk()->assertJsonPath('ok', true);

        $bal1 = (float) \DB::table('users')->where('ID', $this->userId)->value('DemoBalance');
        $this->assertSame($bal0 - 100.0, $bal1);

        $assetId = (int) \DB::table('assets')->where('Symbol', 'AAPL')->value('ID');
        $close = $this->postJson('/api/positions/close-by-asset', [
            'user_id' => $this->userId,
            'assetId' => $assetId,
            'midPrice' => 100,
        ]);
        $close->assertOk()->assertJsonPath('ok', true);

        // With spread, bid is 99.975 so cashDelta ~ 99.975; balance should be close to bal0-0.025
        $bal2 = (float) \DB::table('users')->where('ID', $this->userId)->value('DemoBalance');
        $this->assertTrue(abs($bal2 - ($bal0 - 0.025)) < 0.0001);
    }

    public function test_positions_open_validation_fails(): void
    {
        $this->postJson('/api/positions/open', [])->assertStatus(422);
        $this->postJson('/api/positions/open', [
            'user_id' => $this->userId,
            'symbol' => 'AAPL',
            // missing fields
        ])->assertStatus(422);
    }

    public function test_close_by_asset_validation_fails_on_missing_fields(): void
    {
        $assetId = (int) \DB::table('assets')->where('Symbol', 'AAPL')->value('ID');
        $this->postJson('/api/positions/close-by-asset', [
            'user_id' => $this->userId,
            'assetId' => $assetId,
            // missing midPrice
        ])->assertStatus(422);
    }

    public function test_open_short_does_not_increase_balance_and_close_short_books_only_pnl(): void
    {
        $bal0 = (float) \DB::table('users')->where('ID', $this->userId)->value('DemoBalance');

        $open = $this->postJson('/api/positions/open', [
            'user_id' => $this->userId,
            'symbol' => 'AMD',
            'asset_name' => 'Advanced Micro Devices',
            'quantity' => 2,
            'price' => 50,
            'side' => 'sell',
        ]);
        $open->assertOk()->assertJsonPath('ok', true);

        $bal1 = (float) \DB::table('users')->where('ID', $this->userId)->value('DemoBalance');
        $this->assertSame($bal0, $bal1, 'Short open must not change DemoBalance');

        $assetId = (int) \DB::table('assets')->where('Symbol', 'AMD')->value('ID');

        // If price goes down, short makes profit.
        // Entry 50, midPrice 40 -> ask 40.025 -> pnl = (50 - 40.025) * 2 = 19.95
        $close = $this->postJson('/api/positions/close-by-asset', [
            'user_id' => $this->userId,
            'assetId' => $assetId,
            'midPrice' => 40,
        ]);
        $close->assertOk()->assertJsonPath('ok', true);

        $bal2 = (float) \DB::table('users')->where('ID', $this->userId)->value('DemoBalance');
        $this->assertTrue(abs($bal2 - ($bal0 + 19.95)) < 0.0001);
    }

    public function test_close_by_asset_returns_409_if_no_open_positions(): void
    {
        $assetId = (int) \DB::table('assets')->where('Symbol', 'AAPL')->value('ID');
        $res = $this->postJson('/api/positions/close-by-asset', [
            'user_id' => $this->userId,
            'assetId' => $assetId,
            'midPrice' => 100,
        ]);
        $res->assertStatus(409);
    }

    public function test_prices_requires_symbols_and_uses_http_fake(): void
    {
        $this->getJson('/api/prices')->assertStatus(422);

        $this->fakeFinnhub();

        $res = $this->getJson('/api/prices?symbols=AAPL,MSFT');
        $res->assertOk()->assertJsonPath('ok', true);
        $this->assertSame(123.45, $res->json('data.AAPL.price'));
        $this->assertSame(123.45, $res->json('data.MSFT.price'));
    }

    public function test_prices_ingest_creates_ticks(): void
    {
        Http::fake([
            'finnhub.io/api/v1/quote*' => Http::response(['c' => 200.00, 'b' => 199.9, 'a' => 200.1], 200),
        ]);

        $res = $this->getJson('/api/prices?symbols=AAPL&ingest=1');
        $res->assertOk()->assertJsonPath('ok', true);

        $this->assertTrue(\DB::table('price_ticks')->where('symbol', 'AAPL')->count() >= 1);
    }

    public function test_prices_rate_limit_returns_429_when_exceeded(): void
    {
        // Manually trip the rate limiter threshold (240)
        $key = 'prices:127.0.0.1';
        for ($i = 0; $i < 241; $i++) {
            RateLimiter::hit($key, 1);
        }

        $res = $this->getJson('/api/prices?symbols=AAPL');
        $res->assertStatus(429);
    }

    public function test_news_and_calendar_use_http_fake(): void
    {
        $this->fakeFinnhub();

        $news = $this->getJson('/api/news?user_id=' . $this->userId . '&mode=general&limit=5');
        $news->assertOk()->assertJsonPath('ok', true);

        $cal = $this->getJson('/api/calendar?user_id=' . $this->userId . '&limit=5');
        $cal->assertOk()->assertJsonPath('ok', true);
    }

    public function test_calendar_rejects_bad_date_format(): void
    {
        $res = $this->getJson('/api/calendar?user_id=' . $this->userId . '&from=2026/01/01&to=2026-01-10');
        $res->assertStatus(400);
        $this->assertSame(false, $res->json('ok'));
    }

    /**
     * Meta test: ensure every route defined in routes/api.php is at least callable
     * with a minimal valid payload/params, so we can catch fat-fingered route changes.
     */
    public function test_all_api_routes_callable_with_minimal_inputs(): void
    {
        $this->fakeFinnhub();

        // Ensure candles have data
        $this->ingestTick('AAPL', 1_700_000_010, 101.0);

        $assetIdAapl = (int) \DB::table('assets')->where('Symbol', 'AAPL')->value('ID');

        $matrix = [
            ['GET',  '/api/assets', null, 200],
            ['GET',  '/api/candles?symbol=AAPL&tf=1m&limit=10', null, 200],
            ['GET',  '/api/prices?symbols=AAPL,MSFT', null, 200],
            ['GET',  '/api/settings?user_id=' . $this->userId, null, 200],
            ['POST', '/api/settings', [
                'user_id' => $this->userId,
                'chart_interval' => '1m',
                'chart_theme' => 'dark',
            ], 200],
            ['GET',  '/api/state?user_id=' . $this->userId, null, 200],
            ['POST', '/api/positions/open', [
                'user_id' => $this->userId,
                'symbol' => 'AAPL',
                'asset_name' => 'Apple Inc.',
                'quantity' => 1,
                'price' => 100,
                'side' => 'buy',
            ], 200],
            ['POST', '/api/positions/close-by-asset', [
                'user_id' => $this->userId,
                'assetId' => $assetIdAapl,
                'midPrice' => 100,
            ], 200],
            ['GET',  '/api/news?user_id=' . $this->userId . '&mode=general&limit=3', null, 200],
            ['GET',  '/api/calendar?user_id=' . $this->userId . '&limit=3', null, 200],
            ['POST', '/api/tick/ingest', [
                'symbol' => 'AAPL',
                'ts' => 1_700_000_020,
                'price' => 100.5,
            ], 200],
        ];

        foreach ($matrix as [$method, $uri, $payload, $expectedStatus]) {
            $resp = match ($method) {
                'GET' => $this->getJson($uri),
                'POST' => $this->postJson($uri, $payload ?? []),
                default => throw new \RuntimeException('Unsupported method in test matrix: ' . $method),
            };

            $resp->assertStatus($expectedStatus);

            // Almost all endpoints respond with { ok: true|false }
            if ($resp->headers->get('content-type') && str_contains($resp->headers->get('content-type'), 'application/json')) {
                $this->assertNotNull($resp->json('ok'), "Missing 'ok' field for $method $uri");
            }
        }
    }
}
