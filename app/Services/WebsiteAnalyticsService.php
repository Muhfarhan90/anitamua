<?php

namespace App\Services;

use Carbon\CarbonImmutable;
use Google\Analytics\Data\V1beta\Client\BetaAnalyticsDataClient;
use Google\Analytics\Data\V1beta\DateRange;
use Google\Analytics\Data\V1beta\Dimension;
use Google\Analytics\Data\V1beta\Metric;
use Google\Analytics\Data\V1beta\OrderBy;
use Google\Analytics\Data\V1beta\OrderBy\MetricOrderBy;
use Google\Analytics\Data\V1beta\RunReportRequest;
use Google\Auth\Credentials\ServiceAccountCredentials;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

class WebsiteAnalyticsService
{
    public const PERIODS = [
        '7d' => '7 Hari',
        '30d' => '30 Hari',
        '90d' => '90 Hari',
        '1y' => '1 Tahun',
        'all' => 'Semua',
    ];

    public static function normalizePeriod(?string $period): string
    {
        return array_key_exists((string) $period, self::PERIODS) ? $period : '30d';
    }

    public function report(string $period): array
    {
        $period = self::normalizePeriod($period);

        if (! $this->isConfigured()) {
            return $this->unavailable($period, 'unconfigured');
        }

        try {
            $propertyId = (string) config('services.google_analytics.property_id');

            return Cache::remember(
                "google-analytics:{$propertyId}:{$period}",
                now()->addHour(),
                fn () => $this->fetch($period)
            );
        } catch (Throwable $exception) {
            Log::warning('Google Analytics report could not be loaded.', [
                'exception' => $exception::class,
                'message' => $exception->getMessage(),
            ]);

            return $this->unavailable($period, 'error');
        }
    }

    public function reportCustom(string $startDate, string $endDate): array
    {
        if (! $this->isConfigured()) {
            return $this->unavailable('custom', 'unconfigured');
        }

        try {
            $propertyId = (string) config('services.google_analytics.property_id');
            $start = CarbonImmutable::parse($startDate, 'Asia/Jakarta')->startOfDay();
            $end = CarbonImmutable::parse($endDate, 'Asia/Jakarta')->startOfDay();

            return Cache::remember(
                "google-analytics:{$propertyId}:custom:{$startDate}:{$endDate}",
                now()->addHour(),
                fn () => $this->fetchRange('custom', $start, $end)
            );
        } catch (Throwable $exception) {
            Log::warning('Google Analytics custom report could not be loaded.', [
                'exception' => $exception::class,
                'message' => $exception->getMessage(),
            ]);

            return $this->unavailable('custom', 'error');
        }
    }

    protected function fetch(string $period): array
    {
        [$start, $end, $dimension] = $this->rangeFor($period);
        return $this->fetchRange($period, $start, $end, $dimension);
    }

    protected function fetchRange(
        string $period,
        CarbonImmutable $start,
        CarbonImmutable $end,
        ?string $dimension = null
    ): array {
        $dimension ??= $start->diffInDays($end) < 90 ? 'date' : 'yearMonth';
        $property = 'properties/'.config('services.google_analytics.property_id');

        [$summary, $trend, $topPages] = $this->runReports([
            $this->request($property, $start, $end)
                ->setMetrics($this->metrics(['activeUsers', 'sessions', 'screenPageViews', 'engagementRate'])),
            $this->request($property, $start, $end)
                ->setDimensions([new Dimension(['name' => $dimension])])
                ->setMetrics($this->metrics(['sessions', 'screenPageViews'])),
            $this->request($property, $start, $end)
                ->setDimensions([
                    new Dimension(['name' => 'pageTitle']),
                    new Dimension(['name' => 'pagePath']),
                ])
                ->setMetrics($this->metrics(['screenPageViews']))
                ->setOrderBys([
                    (new OrderBy)
                        ->setMetric(new MetricOrderBy(['metric_name' => 'screenPageViews']))
                        ->setDesc(true),
                ])
                ->setLimit(5),
        ]);

        $summaryRow = $summary->getRows()[0] ?? null;
        $summaryValues = $summaryRow?->getMetricValues() ?? [];
        $totals = [
            'activeUsers' => (int) ($summaryValues[0]?->getValue() ?? 0),
            'sessions' => (int) ($summaryValues[1]?->getValue() ?? 0),
            'screenPageViews' => (int) ($summaryValues[2]?->getValue() ?? 0),
            'engagementRate' => (float) ($summaryValues[3]?->getValue() ?? 0) * 100,
        ];

        $status = array_sum(array_slice($totals, 0, 3)) > 0 ? 'ready' : 'empty';

        return [
            'status' => $status,
            'period' => $period,
            'periodLabel' => self::PERIODS[$period] ?? 'Tanggal Kustom',
            'rangeLabel' => $this->rangeLabel($start, $end),
            'summary' => $totals,
            'trend' => $this->trendRows($trend->getRows(), $start, $end, $dimension),
            'topPages' => collect($topPages->getRows())->map(function ($row) {
                $dimensions = $row->getDimensionValues();

                return [
                    'title' => $dimensions[0]?->getValue() ?: 'Tanpa judul',
                    'path' => $dimensions[1]?->getValue() ?: '/',
                    'views' => (int) ($row->getMetricValues()[0]?->getValue() ?? 0),
                ];
            })->all(),
        ];
    }

    protected function isConfigured(): bool
    {
        return filled(config('services.google_analytics.measurement_id'))
            && filled(config('services.google_analytics.property_id'))
            && is_file((string) config('services.google_analytics.credentials_path'));
    }

    protected function rangeFor(string $period): array
    {
        $end = CarbonImmutable::now('Asia/Jakarta')->startOfDay()->subDay();
        $start = match ($period) {
            '7d' => $end->subDays(6),
            '90d' => $end->subDays(89),
            '1y' => $end->subYear()->addDay(),
            'all' => $this->analyticsStartDate($end),
            default => $end->subDays(29),
        };

        return [$start, $end, in_array($period, ['1y', 'all'], true) ? 'yearMonth' : 'date'];
    }

    private function analyticsStartDate(CarbonImmutable $end): CarbonImmutable
    {
        try {
            $start = CarbonImmutable::parse(
                config('services.google_analytics.start_date') ?: '2020-10-14',
                'Asia/Jakarta'
            )->startOfDay();
        } catch (Throwable) {
            $start = CarbonImmutable::create(2020, 10, 14, timezone: 'Asia/Jakarta');
        }

        return $start->greaterThan($end) ? $end : $start;
    }

    protected function client(): BetaAnalyticsDataClient
    {
        $credentials = new ServiceAccountCredentials(
            ['https://www.googleapis.com/auth/analytics.readonly'],
            (string) config('services.google_analytics.credentials_path')
        );

        return new BetaAnalyticsDataClient([
            'credentials' => $credentials,
            'transport' => 'rest',
        ]);
    }

    protected function runReports(array $requests): array
    {
        $client = $this->client();

        return array_map(fn (RunReportRequest $request) => $client->runReport($request), $requests);
    }

    private function request(
        string $property,
        CarbonImmutable $start,
        CarbonImmutable $end
    ): RunReportRequest {
        return (new RunReportRequest)
            ->setProperty($property)
            ->setDateRanges([
                new DateRange([
                    'start_date' => $start->toDateString(),
                    'end_date' => $end->toDateString(),
                ]),
            ]);
    }

    private function metrics(array $names): array
    {
        return array_map(fn (string $name) => new Metric(['name' => $name]), $names);
    }

    private function trendRows($rows, CarbonImmutable $start, CarbonImmutable $end, string $dimension): array
    {
        $values = collect($rows)->mapWithKeys(function ($row) {
            $metrics = $row->getMetricValues();

            return [$row->getDimensionValues()[0]->getValue() => [
                'sessions' => (int) ($metrics[0]?->getValue() ?? 0),
                'views' => (int) ($metrics[1]?->getValue() ?? 0),
            ]];
        });

        $result = [];
        $cursor = $dimension === 'date' ? $start : $start->startOfMonth();
        $last = $dimension === 'date' ? $end : $end->startOfMonth();

        while ($cursor->lessThanOrEqualTo($last)) {
            $key = $cursor->format($dimension === 'date' ? 'Ymd' : 'Ym');
            $result[] = [
                'label' => $dimension === 'date'
                    ? $cursor->locale('id')->translatedFormat('d M')
                    : $cursor->locale('id')->translatedFormat('M Y'),
                ...($values[$key] ?? ['sessions' => 0, 'views' => 0]),
            ];
            $cursor = $dimension === 'date' ? $cursor->addDay() : $cursor->addMonth();
        }

        return $result;
    }

    private function rangeLabel(CarbonImmutable $start, CarbonImmutable $end): string
    {
        return $start->locale('id')->translatedFormat('d M Y')
            .' – '.$end->locale('id')->translatedFormat('d M Y');
    }

    private function unavailable(string $period, string $status): array
    {
        return [
            'status' => $status,
            'period' => $period,
            'periodLabel' => self::PERIODS[$period] ?? 'Tanggal Kustom',
            'rangeLabel' => null,
            'summary' => [],
            'trend' => [],
            'topPages' => [],
        ];
    }
}
