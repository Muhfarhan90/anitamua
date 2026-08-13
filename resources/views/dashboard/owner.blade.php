@extends('layouts.app')

@section('title', 'Dashboard Owner')

@section('content')
<x-page-header title="Dashboard Owner" subtitle="Ringkasan bisnis dan performa terkini" />

{{-- STAT CARDS --}}
<div class="grid grid-cols-2 md:grid-cols-3 gap-4 mb-6">
    <x-stat-card icon="fa-calendar-plus"    color="amber"   label="Booking Baru"        value="{{ $stats['newBookings'] }}" />
    <x-stat-card icon="fa-users"            color="blue"    label="Client Aktif"        value="{{ $stats['activeClients'] }}" />
    <x-stat-card icon="fa-calendar-week"    color="emerald" label="Acara Minggu Ini"    value="{{ $stats['eventsThisWeek'] }}" />
    <x-stat-card icon="fa-hand-holding-usd" color="rose"    label="DP Menunggu"         value="Rp {{ number_format($stats['dpIncoming'], 0, ',', '.') }}" />
    <x-stat-card icon="fa-money-bill-wave"  color="purple"  label="Pelunasan Menunggu"  value="Rp {{ number_format($stats['pelunasan'], 0, ',', '.') }}" />
    <x-stat-card icon="fa-calendar-check"   color="brand"   label="Booking Aktif"       value="{{ $stats['bookedEvents'] }}" />
</div>

{{-- JADWAL MENDATANG --}}
<x-card title="Jadwal Acara Mendatang" title-icon="fa-calendar-days" padding="p-0" class="mb-6">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="text-left border-b border-gray-100 bg-gray-50">
                    <th class="px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Client</th>
                    <th class="px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Tanggal</th>
                    <th class="px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Paket</th>
                    <th class="px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Lokasi</th>
                    <th class="px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Kode</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse($upcomingEvents as $event)
                <tr class="hover:bg-brand-50/20 transition-colors">
                    <td class="px-5 py-3.5 text-gray-800 font-medium">{{ $event->name }}</td>
                    <td class="px-5 py-3.5 text-gray-600">{{ $event->event_date?->format('d M Y') ?? '-' }}</td>
                    <td class="px-5 py-3.5 text-gray-600">{{ $event->package?->name ?? '-' }}</td>
                    <td class="px-5 py-3.5 text-gray-500 text-xs">{{ $event->location ?? '-' }}</td>
                    <td class="px-5 py-3.5"><x-badge>{{ $event->code }}</x-badge></td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="py-8"><x-empty-state icon="fa-calendar-xmark" title="Tidak ada jadwal mendatang" /></td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="px-5 py-3.5 border-t border-gray-100">
        <a href="{{ route('admin.calendar') }}" class="text-sm font-semibold text-brand hover:text-brand-dark inline-flex items-center gap-1">
            Lihat Kalender <i class="fas fa-arrow-right text-xs"></i>
        </a>
    </div>
</x-card>

{{-- BOOKING TERBARU --}}
<x-card title="Booking Terbaru" title-icon="fa-list-check" padding="p-0">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="text-left border-b border-gray-100 bg-gray-50">
                    <th class="px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Client</th>
                    <th class="px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Tanggal Acara</th>
                    <th class="px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Paket</th>
                    <th class="px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse($recentBookings as $booking)
                <tr class="hover:bg-brand-50/20 transition-colors">
                    <td class="px-5 py-3.5 text-gray-800 font-medium">{{ $booking->name }}</td>
                    <td class="px-5 py-3.5 text-gray-600">{{ $booking->event_date?->format('d M Y') ?? '-' }}</td>
                    <td class="px-5 py-3.5 text-gray-600">{{ $booking->package?->name ?? '-' }}</td>
                    <td class="px-5 py-3.5">
                        <x-badge :color="match($booking->status) {
                            'pending'   => 'warning',
                            'booked'    => 'success',
                            'completed' => 'info',
                            'cancelled' => 'danger',
                            default     => 'gray',
                        }">{{ ucfirst($booking->status) }}</x-badge>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="4" class="py-8"><x-empty-state icon="fa-folder-open" title="Belum ada booking" /></td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="px-5 py-3.5 border-t border-gray-100">
        <a href="{{ route('admin.bookings.index') }}" class="text-sm font-semibold text-brand hover:text-brand-dark inline-flex items-center gap-1">
            Lihat Semua <i class="fas fa-arrow-right text-xs"></i>
        </a>
    </div>
</x-card>
@endsection
