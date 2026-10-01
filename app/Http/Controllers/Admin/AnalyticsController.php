<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\WebsiteAnalyticsService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

class AnalyticsController extends Controller
{
    public function index(Request $request, WebsiteAnalyticsService $analytics): View|RedirectResponse
    {
        $startDate = null;
        $endDate = null;
        $yesterday = now('Asia/Jakarta')->subDay()->toDateString();
        $hasCustomRange = $request->filled('start_date') || $request->filled('end_date');

        if ($hasCustomRange) {
            $validator = Validator::make($request->query(), [
                'start_date' => ['required', 'date_format:Y-m-d', 'before_or_equal:'.$yesterday],
                'end_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:start_date', 'before_or_equal:'.$yesterday],
            ]);

            if ($validator->fails()) {
                return redirect()->route('admin.analytics.index')
                    ->withErrors($validator)
                    ->withInput($request->query());
            }

            $dates = $validator->validated();
            $startDate = $dates['start_date'];
            $endDate = $dates['end_date'];
            $period = 'custom';
            $report = $analytics->reportCustom($startDate, $endDate);
        } else {
            $period = WebsiteAnalyticsService::normalizePeriod($request->query('period'));
            $report = $analytics->report($period);
        }

        return view('admin.analytics.index', [
            'analytics' => $report,
            'period' => $period,
            'periods' => WebsiteAnalyticsService::PERIODS,
            'startDate' => old('start_date', $startDate),
            'endDate' => old('end_date', $endDate),
            'yesterday' => $yesterday,
        ]);
    }
}
