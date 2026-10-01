@extends('layouts.app')

@section('title', 'Analitik Website')

@section('content')
<x-page-header title="Analitik Website" subtitle="Pahami pola kunjungan website dan halaman yang paling diminati.">
    <x-slot:actions>
        <span class="inline-flex items-center gap-2 rounded-full bg-white px-3 py-1.5 text-xs font-medium text-gray-500 shadow-sm border border-gray-100">
            <span class="h-2 w-2 rounded-full {{ $analytics['status'] === 'ready' ? 'bg-emerald-400' : 'bg-amber-400' }}"></span>
            Google Analytics
        </span>
    </x-slot:actions>
</x-page-header>

<nav class="mb-5 overflow-x-auto" aria-label="Periode laporan">
    <div class="inline-flex min-w-max gap-1 rounded-2xl border border-gray-100 bg-white p-1.5 shadow-sm">
        @foreach($periods as $value => $label)
            <a href="{{ route('admin.analytics.index', ['period' => $value]) }}"
               @if($period === $value) aria-current="page" @endif
               class="rounded-xl px-4 py-2 text-xs font-semibold no-underline transition-colors focus:outline-none focus:ring-2 focus:ring-brand-200
                      {{ $period === $value ? 'bg-brand text-white shadow-sm' : 'text-gray-500 hover:bg-brand-50 hover:text-brand' }}">
                {{ $label }}
            </a>
        @endforeach
    </div>
</nav>

<form action="{{ route('admin.analytics.index') }}" method="GET" class="mb-5 rounded-2xl border border-gray-100 bg-white p-4 shadow-sm">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-end">
        <div class="flex-1">
            <label for="analyticsStartDate" class="mb-1.5 block text-xs font-semibold text-gray-600">Dari tanggal</label>
            <input id="analyticsStartDate" name="start_date" type="date" value="{{ $startDate }}" max="{{ $yesterday }}" required
                   class="w-full rounded-xl border border-gray-200 bg-gray-50 px-3 py-2.5 text-sm text-gray-700 focus:border-brand focus:ring-brand">
            @error('start_date') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>
        <div class="flex-1">
            <label for="analyticsEndDate" class="mb-1.5 block text-xs font-semibold text-gray-600">Sampai tanggal</label>
            <input id="analyticsEndDate" name="end_date" type="date" value="{{ $endDate }}" max="{{ $yesterday }}" required
                   class="w-full rounded-xl border border-gray-200 bg-gray-50 px-3 py-2.5 text-sm text-gray-700 focus:border-brand focus:ring-brand">
            @error('end_date') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>
        <button type="submit" class="inline-flex items-center justify-center gap-2 rounded-xl bg-brand px-5 py-2.5 text-sm font-semibold text-white transition-colors hover:bg-brand-dark focus:outline-none focus:ring-2 focus:ring-brand-200">
            <i class="fas fa-filter text-xs"></i> Terapkan
        </button>
        @if($period === 'custom')
            <a href="{{ route('admin.analytics.index') }}" class="inline-flex items-center justify-center rounded-xl px-4 py-2.5 text-sm font-semibold text-gray-500 no-underline hover:bg-gray-50">Reset</a>
        @endif
    </div>
</form>

@if($analytics['status'] === 'ready')
    <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-brand">{{ $analytics['periodLabel'] }}</p>
        <p class="text-xs text-gray-400">{{ $analytics['rangeLabel'] }}</p>
    </div>

    <div class="grid grid-cols-2 gap-3 lg:grid-cols-4 mb-5">
        <x-stat-card icon="fa-user-group" color="brand" label="Pengunjung" :value="number_format($analytics['summary']['activeUsers'], 0, ',', '.')" sub="Pengunjung aktif" />
        <x-stat-card icon="fa-arrow-pointer" color="blue" label="Kunjungan" :value="number_format($analytics['summary']['sessions'], 0, ',', '.')" sub="Total sesi" />
        <x-stat-card icon="fa-eye" color="purple" label="Tayangan" :value="number_format($analytics['summary']['screenPageViews'], 0, ',', '.')" sub="Halaman dilihat" />
        <x-stat-card icon="fa-heart-pulse" color="emerald" label="Engagement" :value="number_format($analytics['summary']['engagementRate'], 1, ',', '.').'%'" sub="Sesi terlibat" />
    </div>

    <div class="grid gap-5 xl:grid-cols-[minmax(0,2fr)_minmax(320px,1fr)]">
        <x-card title="Pola Kunjungan" title-icon="fa-chart-line" padding="p-4 md:p-5">
            <div class="mb-4 flex items-center gap-5 text-xs text-gray-500">
                <span class="inline-flex items-center gap-2"><span class="h-2.5 w-2.5 rounded-full bg-brand"></span>Kunjungan</span>
                <span class="inline-flex items-center gap-2"><span class="h-2.5 w-2.5 rounded-full bg-blue-400"></span>Tayangan</span>
            </div>
            <div class="relative h-72 md:h-80">
                <canvas id="analyticsTrendChart" role="img" aria-label="Grafik kunjungan dan tayangan website"></canvas>
            </div>
        </x-card>

        <x-card title="Halaman Teratas" title-icon="fa-ranking-star" padding="p-0">
            <ol class="divide-y divide-gray-50">
                @foreach($analytics['topPages'] as $page)
                    <li class="flex items-start gap-3 px-5 py-4">
                        <span class="flex h-7 w-7 flex-shrink-0 items-center justify-center rounded-lg bg-brand-50 text-xs font-bold text-brand">{{ $loop->iteration }}</span>
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-semibold text-gray-700" title="{{ $page['title'] }}">{{ $page['title'] }}</p>
                            <p class="mt-0.5 truncate text-[11px] text-gray-400" title="{{ $page['path'] }}">{{ $page['path'] }}</p>
                        </div>
                        <div class="flex-shrink-0 text-right">
                            <p class="text-sm font-bold text-gray-800">{{ number_format($page['views'], 0, ',', '.') }}</p>
                            <p class="text-[10px] text-gray-400">tayangan</p>
                        </div>
                    </li>
                @endforeach
            </ol>
        </x-card>
    </div>
@else
    <x-card padding="p-8 md:p-12">
        <div class="mx-auto max-w-lg text-center">
            <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-2xl bg-brand-50 text-brand">
                <i class="fas {{ $analytics['status'] === 'error' ? 'fa-triangle-exclamation' : 'fa-chart-simple' }} text-xl"></i>
            </div>
            @if($analytics['status'] === 'unconfigured')
                <h2 class="font-display text-lg font-bold text-gray-800">Google Analytics belum dikonfigurasi</h2>
                <p class="mt-2 text-sm leading-6 text-gray-500">Lengkapi Measurement ID, Property ID, dan kredensial service account pada server untuk mulai menampilkan statistik.</p>
            @elseif($analytics['status'] === 'error')
                <h2 class="font-display text-lg font-bold text-gray-800">Data belum dapat dimuat</h2>
                <p class="mt-2 text-sm leading-6 text-gray-500">Periksa akses service account atau coba buka halaman ini kembali beberapa saat lagi.</p>
            @else
                <h2 class="font-display text-lg font-bold text-gray-800">Belum ada kunjungan</h2>
                <p class="mt-2 text-sm leading-6 text-gray-500">Google Analytics belum mencatat kunjungan pada periode {{ strtolower($analytics['periodLabel']) }}.</p>
            @endif
        </div>
    </x-card>
@endif
@endsection

@push('scripts')
@if($analytics['status'] === 'ready')
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const canvas = document.getElementById('analyticsTrendChart');
        if (!canvas || !window.Chart) return;

        const points = {{ Illuminate\Support\Js::from($analytics['trend']) }};

        new window.Chart(canvas, {
            type: 'line',
            data: {
                labels: points.map(point => point.label),
                datasets: [
                    {
                        label: 'Kunjungan',
                        data: points.map(point => point.sessions),
                        borderColor: '#d4739a',
                        backgroundColor: 'rgba(212, 115, 154, .12)',
                        fill: true,
                        tension: .35,
                        pointRadius: points.length > 45 ? 0 : 2,
                        pointHoverRadius: 4,
                        borderWidth: 2
                    },
                    {
                        label: 'Tayangan',
                        data: points.map(point => point.views),
                        borderColor: '#60a5fa',
                        backgroundColor: 'transparent',
                        tension: .35,
                        pointRadius: points.length > 45 ? 0 : 2,
                        pointHoverRadius: 4,
                        borderWidth: 2
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { intersect: false, mode: 'index' },
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: '#2d2521',
                        padding: 10,
                        titleFont: { family: 'Plus Jakarta Sans', size: 11 },
                        bodyFont: { family: 'Plus Jakarta Sans', size: 12 }
                    }
                },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: { color: '#9ca3af', maxTicksLimit: 10, font: { size: 10 } }
                    },
                    y: {
                        beginAtZero: true,
                        border: { display: false },
                        grid: { color: 'rgba(229, 231, 235, .65)' },
                        ticks: { color: '#9ca3af', precision: 0, font: { size: 10 } }
                    }
                }
            }
        });
    });
</script>
@endif
@endpush
