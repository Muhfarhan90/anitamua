<?php

use App\Models\Booking;
use App\Models\User;
use App\Services\WebsiteAnalyticsService;
use Carbon\CarbonImmutable;
use Google\Analytics\Data\V1beta\DimensionValue;
use Google\Analytics\Data\V1beta\MetricValue;
use Google\Analytics\Data\V1beta\Row;
use Google\Analytics\Data\V1beta\RunReportResponse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed();
});

it('allows only the owner to open website analytics', function () {
    $owner = User::where('role', User::ROLE_OWNER)->firstOrFail();

    $this->actingAs($owner)
        ->get(route('admin.analytics.index'))
        ->assertOk()
        ->assertSee('Google Analytics belum dikonfigurasi')
        ->assertSee('Analitik Website');

    foreach ([User::ROLE_ADMIN, User::ROLE_TEAM, User::ROLE_CLIENT] as $role) {
        $user = User::where('role', $role)->firstOrFail();
        $this->actingAs($user)->get(route('admin.analytics.index'))->assertForbidden();
        $this->actingAs($user)->get(route('dashboard'))->assertDontSee('Analitik Website');
    }

    $this->actingAs($owner)->get(route('dashboard'))->assertSee('Analitik Website');
});

it('normalizes analytics periods before requesting a report', function () {
    $owner = User::where('role', User::ROLE_OWNER)->firstOrFail();
    $empty = fn (string $period) => [
        'status' => 'empty',
        'period' => $period,
        'periodLabel' => WebsiteAnalyticsService::PERIODS[$period],
        'rangeLabel' => null,
        'summary' => [],
        'trend' => [],
        'topPages' => [],
    ];

    $service = Mockery::mock(WebsiteAnalyticsService::class);
    $service->shouldReceive('report')->once()->with('all')->andReturn($empty('all'));
    $service->shouldReceive('report')->once()->with('30d')->andReturn($empty('30d'));
    $this->app->instance(WebsiteAnalyticsService::class, $service);

    $this->actingAs($owner)->get(route('admin.analytics.index', ['period' => 'all']))
        ->assertOk()->assertSee('periode semua');
    $this->actingAs($owner)->get(route('admin.analytics.index', ['period' => 'invalid']))
        ->assertOk()->assertSee('periode 30 hari');
});

it('uses the configured ranges and monthly grouping for long periods', function () {
    CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-09-29 12:00:00', 'Asia/Jakarta'));
    Config::set('services.google_analytics.start_date', '2026-01-15');

    $service = new class extends WebsiteAnalyticsService
    {
        public function range(string $period): array
        {
            return $this->rangeFor($period);
        }
    };

    [$sevenStart, $sevenEnd, $sevenDimension] = $service->range('7d');
    [$yearStart, $yearEnd, $yearDimension] = $service->range('1y');
    [$allStart, $allEnd, $allDimension] = $service->range('all');

    expect($sevenStart->toDateString())->toBe('2026-09-22')
        ->and($sevenEnd->toDateString())->toBe('2026-09-28')
        ->and($sevenDimension)->toBe('date')
        ->and($yearStart->toDateString())->toBe('2025-09-29')
        ->and($yearEnd->toDateString())->toBe('2026-09-28')
        ->and($yearDimension)->toBe('yearMonth')
        ->and($allStart->toDateString())->toBe('2026-01-15')
        ->and($allEnd->toDateString())->toBe('2026-09-28')
        ->and($allDimension)->toBe('yearMonth');

    CarbonImmutable::setTestNow();
});

it('maps and caches google analytics reports', function () {
    CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-09-29 12:00:00', 'Asia/Jakarta'));
    Config::set('services.google_analytics.property_id', '123456789');
    Cache::flush();

    $summary = new RunReportResponse(['rows' => [new Row(['metric_values' => [
        new MetricValue(['value' => '24']),
        new MetricValue(['value' => '31']),
        new MetricValue(['value' => '78']),
        new MetricValue(['value' => '0.625']),
    ]])]]);
    $trend = new RunReportResponse(['rows' => [new Row([
        'dimension_values' => [new DimensionValue(['value' => '20260928'])],
        'metric_values' => [new MetricValue(['value' => '4']), new MetricValue(['value' => '9'])],
    ])]]);
    $pages = new RunReportResponse(['rows' => [new Row([
        'dimension_values' => [
            new DimensionValue(['value' => 'Paket Pernikahan']),
            new DimensionValue(['value' => '/paket']),
        ],
        'metric_values' => [new MetricValue(['value' => '18'])],
    ])]]);

    $service = new class([$summary, $trend, $pages]) extends WebsiteAnalyticsService
    {
        public int $calls = 0;

        public function __construct(private array $responses) {}

        protected function isConfigured(): bool
        {
            return true;
        }

        protected function runReports(array $requests): array
        {
            $this->calls++;

            return $this->responses;
        }
    };

    $first = $service->report('30d');
    $second = $service->report('30d');

    expect($first['status'])->toBe('ready')
        ->and($first['summary'])->toBe([
            'activeUsers' => 24,
            'sessions' => 31,
            'screenPageViews' => 78,
            'engagementRate' => 62.5,
        ])
        ->and($first['trend'])->toHaveCount(30)
        ->and($first['trend'][29])->toMatchArray(['sessions' => 4, 'views' => 9])
        ->and($first['topPages'][0])->toBe([
            'title' => 'Paket Pernikahan',
            'path' => '/paket',
            'views' => 18,
        ])
        ->and($second)->toBe($first)
        ->and($service->calls)->toBe(1);

    CarbonImmutable::setTestNow();
});

it('keeps analytics available as an error state when google fails', function () {
    Config::set('services.google_analytics.property_id', 'failed-property');
    Cache::flush();

    $service = new class extends WebsiteAnalyticsService
    {
        protected function isConfigured(): bool
        {
            return true;
        }

        protected function fetch(string $period): array
        {
            throw new RuntimeException('Google unavailable');
        }
    };

    expect($service->report('7d')['status'])->toBe('error');
});

it('adds a sanitized google tag only to public pages', function () {
    Config::set('services.google_analytics.measurement_id');
    $this->get('/')->assertOk()->assertDontSee('googletagmanager.com/gtag/js', false);

    Config::set('services.google_analytics.measurement_id', 'G-TEST123');
    $booking = Booking::firstOrFail();

    $this->get('/')->assertOk()
        ->assertSee('googletagmanager.com/gtag/js?id=G-TEST123', false)
        ->assertSee('page_path: "/"', false);

    $success = $this->get(route('booking.success', $booking->code))->assertOk();
    $success->assertSee('page_path: "/booking/sukses"', false)
        ->assertSee('page_location: "'.url('/booking/sukses').'"', false);

    $owner = User::where('role', User::ROLE_OWNER)->firstOrFail();
    $this->actingAs($owner)->get(route('dashboard'))
        ->assertOk()
        ->assertDontSee('googletagmanager.com/gtag/js', false);
});
