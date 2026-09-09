@extends('layouts.app')

@section('title', 'Keuangan')

@section('content')
@php
    $money = fn ($value) => 'Rp '.number_format((float) $value, 0, ',', '.');
@endphp

<x-page-header title="Keuangan" subtitle="Pemasukan dihitung saat pembayaran diverifikasi. Pengeluaran diambil dari harga vendor pada booking.">
    <x-slot:actions>
        <form method="GET" action="{{ route('admin.finances.index') }}" class="flex items-center gap-2">
            <label for="finance-year" class="text-sm text-gray-500">Tahun</label>
            <select id="finance-year" name="year" onchange="this.form.submit()"
                    class="rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand-200">
                @foreach($years as $availableYear)
                    <option value="{{ $availableYear }}" @selected($year === $availableYear)>{{ $availableYear }}</option>
                @endforeach
            </select>
        </form>
    </x-slot:actions>
</x-page-header>

<div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4 mb-5">
    <x-stat-card icon="fa-arrow-trend-up" label="Pemasukan" :value="$money($incomes)" color="emerald" />
    <x-stat-card icon="fa-store" label="Pengeluaran" :value="$money($expenses)" color="rose" />
    <x-stat-card icon="fa-chart-line" label="Profit" :value="$money($profit)" :color="$profit >= 0 ? 'brand' : 'rose'" />
            <x-stat-card icon="fa-percent" label="Persentase Profit" :value="$margin.'%'" color="purple" />
</div>

<x-card title="Cashflow Bulanan" title-icon="fa-chart-column">
    <div class="h-72">
        <canvas id="cashflowChart" aria-label="Grafik cashflow bulanan" role="img"></canvas>
    </div>
    <p class="mt-4 border-t border-gray-100 pt-3 text-xs text-gray-400">
        Pemasukan dihitung saat pembayaran diverifikasi. Pengeluaran diambil dari harga vendor yang tersimpan pada booking.
    </p>
</x-card>

<div class="mt-5 grid grid-cols-1 xl:grid-cols-2 gap-5">
    <x-card title="Sumber Booking" title-icon="fa-bullhorn">
        <div class="h-72"><canvas id="bookingSourceChart" aria-label="Grafik sumber booking" role="img"></canvas></div>
        <p class="mt-4 border-t border-gray-100 pt-3 text-xs text-gray-400">Berdasarkan tanggal booking dibuat, termasuk booking yang dibatalkan.</p>
    </x-card>
    <x-card title="Total Booking per Bulan" title-icon="fa-calendar-days">
        <div class="h-72"><canvas id="monthlyBookingChart" aria-label="Grafik booking bulanan" role="img"></canvas></div>
        <p class="mt-4 border-t border-gray-100 pt-3 text-xs text-gray-400">Menghitung seluruh status booking pada tahun {{ $year }}.</p>
    </x-card>
</div>
@endsection

@push('scripts')
<script>
    window.addEventListener('load', () => {
        const cashflowChart = document.getElementById('cashflowChart');

        if (!window.Chart) return;

        if (cashflowChart) new window.Chart(cashflowChart, {
            type: 'bar',
            data: {
                labels: @json($monthly->pluck('label')->values()),
                datasets: [
                    {
                        label: 'Pemasukan',
                        data: @json($monthly->pluck('income')->values()),
                        backgroundColor: '#34d399',
                        borderRadius: 6,
                        maxBarThickness: 34,
                    },
                    {
                        label: 'Pengeluaran',
                        data: @json($monthly->pluck('expense')->values()),
                        backgroundColor: '#fda4af',
                        borderRadius: 6,
                        maxBarThickness: 34,
                    },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom', labels: { usePointStyle: true, boxWidth: 8, padding: 18 } },
                    tooltip: {
                        callbacks: {
                            label: (context) => `${context.dataset.label}: Rp ${new Intl.NumberFormat('id-ID').format(context.raw)}`,
                        },
                    },
                },
                scales: {
                    x: { grid: { display: false }, border: { display: false } },
                    y: {
                        beginAtZero: true,
                        border: { display: false },
                        ticks: {
                            callback: (value) => `Rp ${new Intl.NumberFormat('id-ID', { notation: 'compact', maximumFractionDigits: 1 }).format(value)}`,
                        },
                    },
                },
            },
        });

        const sourceChart = document.getElementById('bookingSourceChart');
        if (sourceChart) new window.Chart(sourceChart, {
            type: 'bar',
            data: {
                labels: @json($bookingSources->pluck('label')),
                datasets: [{ label: 'Booking', data: @json($bookingSources->pluck('count')), backgroundColor: '#d4739a', borderRadius: 6, maxBarThickness: 42 }],
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: { x: { grid: { display: false }, border: { display: false } }, y: { beginAtZero: true, ticks: { precision: 0 }, border: { display: false } } },
            },
        });

        const monthlyBookingChart = document.getElementById('monthlyBookingChart');
        if (monthlyBookingChart) new window.Chart(monthlyBookingChart, {
            type: 'bar',
            data: {
                labels: @json($monthlyBookings->pluck('label')),
                datasets: [{ label: 'Booking', data: @json($monthlyBookings->pluck('count')), backgroundColor: '#9f7aea', borderRadius: 6, maxBarThickness: 24 }],
            },
            options: {
                indexAxis: 'y', responsive: true, maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: { x: { beginAtZero: true, ticks: { precision: 0 }, border: { display: false } }, y: { grid: { display: false }, border: { display: false } } },
            },
        });
    });
</script>
@endpush
