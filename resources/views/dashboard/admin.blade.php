@extends('layouts.app')

@section('title', 'Dashboard Admin')

@section('content')
<x-page-header title="Dashboard Admin" subtitle="Kelola booking dan pembayaran yang masuk" />

{{-- STAT CARDS --}}
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    <x-stat-card icon="fa-clock"         color="amber"   label="Booking Menunggu"              value="{{ $pendingBookings->count() }}" />
    <x-stat-card icon="fa-credit-card"   color="rose"    label="Pembayaran Perlu Verifikasi"   value="{{ $pendingPayments->count() }}" />
    <x-stat-card icon="fa-calendar-check" color="emerald" label="Jadwal Minggu Ini"            value="{{ $schedules->flatten()->count() }}" />
    <x-stat-card icon="fa-star"           color="brand"   label="Acara Hari Ini"               value="{{ isset($schedules[today()->format('Y-m-d')]) ? $schedules[today()->format('Y-m-d')]->count() : 0 }}" />
</div>

{{-- TABLES GRID --}}
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">

    {{-- Booking Baru --}}
    <x-card title="Booking Baru" title-icon="fa-calendar-plus" padding="p-0">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left border-b border-gray-100 bg-gray-50">
                        <th class="px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Kode</th>
                        <th class="px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Client</th>
                        <th class="px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Tanggal</th>
                        <th class="px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse($pendingBookings as $booking)
                    <tr class="hover:bg-brand-50/20 transition-colors">
                        <td class="px-5 py-3.5"><x-badge>{{ $booking->code }}</x-badge></td>
                        <td class="px-5 py-3.5 text-gray-800 font-medium">{{ $booking->name }}</td>
                        <td class="px-5 py-3.5 text-gray-500 text-xs">{{ $booking->event_date->format('d M Y') }}</td>
                        <td class="px-5 py-3.5 text-center">
                            <x-button size="sm" color="success" type="button" data-modal-target="verifyBookingModal-{{ $booking->id }}" data-modal-toggle="verifyBookingModal-{{ $booking->id }}">Verifikasi</x-button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4"><x-empty-state icon="fa-calendar-check" title="Tidak ada booking menunggu" /></td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-card>

    {{-- Pembayaran Menunggu Verifikasi --}}
    <x-card title="Pembayaran Menunggu Verifikasi" title-icon="fa-credit-card" padding="p-0">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left border-b border-gray-100 bg-gray-50">
                        <th class="px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Booking</th>
                        <th class="px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Tipe</th>
                        <th class="px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Nominal</th>
                        <th class="px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse($pendingPayments as $payment)
                    <tr class="hover:bg-brand-50/20 transition-colors">
                        <td class="px-5 py-3.5"><x-badge>{{ $payment->booking->code }}</x-badge></td>
                        <td class="px-5 py-3.5 text-gray-800">{{ \App\Models\Payment::typeLabel($payment->type) }}</td>
                        <td class="px-5 py-3.5 text-gray-600 font-medium">Rp {{ number_format($payment->amount, 0, ',', '.') }}</td>
                        <td class="px-5 py-3.5 text-center">
                            <form action="{{ route('admin.payments.verify', $payment->id) }}" method="POST" class="inline">
                                @csrf
                                <x-button size="sm" color="success" type="submit">Verifikasi</x-button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4"><x-empty-state icon="fa-money-check" title="Tidak ada pembayaran menunggu" /></td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-card>

</div>

@foreach($pendingBookings as $booking)
<div id="verifyBookingModal-{{ $booking->id }}" tabindex="-1" aria-hidden="true"
     class="hidden overflow-y-auto overflow-x-hidden fixed top-0 right-0 left-0 z-50 justify-center items-center w-full md:inset-0 h-[calc(100%-1rem)] max-h-full">
    <div class="relative p-4 w-full max-w-lg max-h-full">
        <div class="relative bg-white rounded-2xl shadow-xl p-8">
            <div class="flex items-center justify-center w-16 h-16 mx-auto mb-4 rounded-full bg-brand-100">
                <i class="fas fa-shield-halved text-2xl text-brand"></i>
            </div>
            <h3 class="font-display text-xl font-bold text-center mb-2 text-gray-900">Konfirmasi Verifikasi DP1</h3>
            <p class="text-sm text-center text-gray-500 mb-4">
                Verifikasi DP1 untuk booking <strong>{{ $booking->code }}</strong> atas nama <strong>{{ $booking->name }}</strong>.
            </p>
            <form action="{{ route('admin.bookings.verify-dp', $booking) }}" method="POST" class="space-y-4">
                @csrf
                <x-input name="amount" label="Nominal DP1" type="number" placeholder="500000" required />
                <input type="hidden" name="method" value="transfer">

                <div class="flex gap-3 pt-2">
                    <button type="button" data-modal-hide="verifyBookingModal-{{ $booking->id }}"
                            class="flex-1 px-6 py-2.5 rounded-xl text-sm font-semibold border-2 border-gray-200 text-gray-600 hover:bg-gray-50 transition-all">
                        Batal
                    </button>
                    <x-button type="submit" class="flex-1 justify-center bg-emerald-600 hover:bg-emerald-700 text-white shadow-sm"><i class="fas fa-check"></i> Ya, Verifikasi</x-button>
                </div>
            </form>
        </div>
    </div>
</div>
@endforeach

{{-- JADWAL MINGGU INI --}}
<x-card title="Jadwal Minggu Ini" title-icon="fa-calendar-days">
    @forelse($schedules as $date => $daySchedules)
    <div class="mb-4 last:mb-0">
        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2.5">{{ $date }}</p>
        <div class="space-y-2">
            @foreach($daySchedules as $schedule)
            <div class="flex items-center gap-3 py-2.5 px-4 rounded-xl bg-brand-50/40 border border-brand-100/50">
                <span class="text-xs text-gray-400 w-12 flex-shrink-0">{{ $schedule->time }}</span>
                <x-badge>{{ $schedule->booking->code }}</x-badge>
                <span class="text-sm font-medium text-gray-800">{{ $schedule->booking->name }}</span>
                <span class="text-xs text-gray-400 ml-auto truncate max-w-[140px]">
                    <i class="fas fa-location-dot mr-1 text-brand opacity-60"></i>{{ $schedule->location ?: $schedule->booking->location }}
                </span>
            </div>
            @endforeach
        </div>
    </div>
    @empty
    <x-empty-state icon="fa-calendar-xmark" title="Tidak ada jadwal minggu ini" />
    @endforelse
</x-card>
@endsection
