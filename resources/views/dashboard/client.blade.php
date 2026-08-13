@extends('layouts.app')

@section('title', 'Dashboard Client')

@section('content')
@php
    $booking     = $bookings->first() ?? null;
    $payments    = $booking ? $booking->payments : collect();
    $totalPaid   = $payments->where('status', 'verified')->sum('amount');
    $totalPaket  = $booking?->package?->price ?? 0;
    $sisaBayar   = $totalPaket - $totalPaid;
    $paymentPct  = $totalPaket > 0 ? round(($totalPaid / $totalPaket) * 100) : 0;
    $schedules   = $booking && $booking->schedules ? $booking->schedules->sortBy('date') : collect();
    $daysUntil   = $booking && $booking->event_date ? abs((int) round(\Carbon\Carbon::parse($booking->event_date)->diffInDays(now(), false))) : 0;
@endphp

{{-- HERO BANNER --}}
<div class="relative rounded-2xl overflow-hidden mb-6" style="background: linear-gradient(135deg, #d4739a 0%, #b85a82 60%, #a04575 100%);">
    <div class="absolute inset-0 opacity-10" style="background-image: url('data:image/svg+xml,%3Csvg width=\'60\' height=\'60\' viewBox=\'0 0 60 60\' xmlns=\'http://www.w3.org/2000/svg\'%3E%3Cg fill=\'none\' fill-rule=\'evenodd\'%3E%3Cg fill=\'%23ffffff\' fill-opacity=\'0.4\'%3E%3Ccircle cx=\'30\' cy=\'30\' r=\'2\'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E');"></div>
    <div class="relative px-6 py-7 flex flex-wrap items-center justify-between gap-5">
        <div>
            <h1 class="font-display text-2xl md:text-3xl font-bold text-white mb-1">Selamat Datang, {{ auth()->user()->name ?? 'Client' }}</h1>
            @if($booking)
                <p class="text-sm text-white/80">Berikut ringkasan booking dan jadwal acara Anda.</p>
            @else
                <p class="text-sm text-white/80">Anda belum memiliki booking. Mulai perjalanan Anda sekarang.</p>
            @endif
        </div>
        @if($booking)
            <div class="inline-flex items-center gap-4 rounded-2xl px-6 py-4 border border-white/20" style="background: rgba(255,255,255,.15); backdrop-filter: blur(12px);">
                <div class="text-center">
                    <div class="font-display text-4xl font-bold text-white leading-none">{{ $daysUntil }}</div>
                    <div class="text-xs text-white/75 mt-1.5 font-medium">Hari Menuju Hari H</div>
                </div>
            </div>
        @else
            <x-button href="{{ route('booking.create') }}" color="light" size="lg">
                <i class="fas fa-calendar-plus"></i> Booking Sekarang
            </x-button>
        @endif
    </div>
</div>

@if(!$booking)
    <x-card class="text-center">
        <x-empty-state icon="fa-calendar-plus" title="Belum Ada Booking" text="Pilih paket, isi formulir booking, lalu transfer DP 10% dalam 24 jam. Setelah diverifikasi, booking berstatus BOOKED." />
        <div class="text-center pb-2 mt-2">
            <x-button href="{{ route('booking.create') }}" size="lg"><i class="fas fa-calendar-plus"></i> Booking Sekarang</x-button>
        </div>
    </x-card>
@else

    {{-- STATUS BANNER --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 px-6 py-5 mb-6 flex flex-wrap items-center justify-between gap-4">
        <div class="flex items-center gap-6 flex-wrap">
            <div>
                <p class="text-[11px] text-gray-400 uppercase tracking-widest font-semibold mb-1">Kode Booking</p>
                <p class="font-display text-xl font-bold text-brand">{{ $booking->code }}</p>
            </div>
            <div class="w-px h-10 bg-gray-100 hidden sm:block"></div>
            <div>
                <p class="text-[11px] text-gray-400 uppercase tracking-widest font-semibold mb-1">Status</p>
                <x-badge :color="match($booking->status) {
                    'pending'   => 'warning',
                    'booked'    => 'success',
                    'completed' => 'info',
                    'cancelled' => 'danger',
                    default     => 'gray',
                }">{{ match($booking->status) {
                    'pending'   => 'Menunggu Verifikasi DP',
                    'booked'    => 'Booked',
                    'completed' => 'Selesai',
                    'cancelled' => 'Dibatalkan',
                    default     => ucfirst($booking->status),
                } }}</x-badge>
            </div>
            @if($booking->event_date)
            <div class="w-px h-10 bg-gray-100 hidden sm:block"></div>
            <div>
                <p class="text-[11px] text-gray-400 uppercase tracking-widest font-semibold mb-1">Hari H</p>
                <p class="text-sm font-semibold text-gray-800">{{ \Carbon\Carbon::parse($booking->event_date)->format('d F Y') }}</p>
            </div>
            @endif
        </div>
        <x-button href="{{ route('client.booking', $booking) }}">
            <i class="fas fa-receipt"></i> Detail &amp; Pembayaran
        </x-button>
    </div>

    {{-- INFO GRID --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">

        {{-- Informasi Booking --}}
        <x-card title="Informasi Booking" title-icon="fa-file-invoice">
            <table class="w-full text-sm">
                @foreach([
                    ['Paket',       $booking->package->name ?? '-'],
                    ['Tanggal Acara', $booking->event_date ? \Carbon\Carbon::parse($booking->event_date)->format('d F Y') : '-'],
                    ['Lokasi',      $booking->location ?? '-'],
                    ['Nama',        $booking->name],
                    ['No. WhatsApp', $booking->phone],
                ] as [$label, $val])
                <tr class="border-b border-gray-50 last:border-0">
                    <td class="py-2.5 pr-4 text-gray-400 text-xs font-medium uppercase tracking-wider whitespace-nowrap">{{ $label }}</td>
                    <td class="py-2.5 text-gray-800 font-semibold text-right text-sm">{{ $val }}</td>
                </tr>
                @endforeach
            </table>
        </x-card>

        {{-- Paket Saya --}}
        <x-card title="Paket Saya" title-icon="fa-gift">
            <p class="font-display text-lg font-bold text-brand mb-0.5">{{ $booking->package->name ?? 'Paket' }}</p>
            <p class="font-display text-2xl font-bold text-gray-800 mb-5">Rp {{ number_format($totalPaket, 0, ',', '.') }}</p>
            <ul class="space-y-2">
                @forelse(($booking->package->benefits ?? []) as $benefit)
                    <li class="flex items-start gap-2.5 text-sm text-gray-600">
                        <i class="fas fa-circle-check mt-0.5 text-emerald-400 text-xs flex-shrink-0"></i>
                        <span>{{ $benefit->name }}</span>
                    </li>
                @empty
                    <li class="flex items-start gap-2.5 text-sm text-gray-600">
                        <i class="fas fa-circle-check mt-0.5 text-emerald-400 text-xs flex-shrink-0"></i>
                        <span>Makeup &amp; Hairdo Pengantin</span>
                    </li>
                @endforelse
            </ul>
        </x-card>

        {{-- Pembayaran --}}
        <x-card title="Pembayaran" title-icon="fa-money-bill-wave">
            @php
                $radius        = 44;
                $circumference = 2 * M_PI * $radius;
                $offset        = $circumference - ($paymentPct / 100) * $circumference;
            @endphp
            <div class="flex items-center gap-5 mb-5">
                <div class="relative flex-shrink-0" style="width: 108px; height: 108px;">
                    <svg viewBox="0 0 108 108" style="transform: rotate(-90deg);">
                        <circle cx="54" cy="54" r="{{ $radius }}" fill="none" stroke="#f0ece8" stroke-width="10" />
                        <circle cx="54" cy="54" r="{{ $radius }}" fill="none" stroke="#d4739a" stroke-width="10"
                                stroke-linecap="round" stroke-dasharray="{{ $circumference }}"
                                stroke-dashoffset="{{ $offset }}" style="transition: stroke-dashoffset .6s ease;" />
                    </svg>
                    <div class="absolute inset-0 flex flex-col items-center justify-center">
                        <span class="font-display text-2xl font-bold leading-none text-brand">{{ $paymentPct }}%</span>
                        <span class="text-[10px] text-gray-400 mt-0.5">Lunas</span>
                    </div>
                </div>
                <div class="flex-1 space-y-2.5 text-sm">
                    <div class="flex justify-between">
                        <span class="text-gray-400">Total Paket</span>
                        <span class="font-bold text-gray-800">Rp {{ number_format($totalPaket, 0, ',', '.') }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-400">Dibayar</span>
                        <span class="font-bold text-emerald-500">Rp {{ number_format($totalPaid, 0, ',', '.') }}</span>
                    </div>
                    <div class="flex justify-between pt-2 border-t border-gray-100">
                        <span class="text-gray-400">Sisa</span>
                        <span class="font-bold" style="color: {{ $sisaBayar > 0 ? '#ef4444' : '#22c55e' }};">
                            Rp {{ number_format($sisaBayar, 0, ',', '.') }}
                        </span>
                    </div>
                </div>
            </div>
            @if($booking->status === 'pending')
                <div class="rounded-xl px-4 py-3 bg-amber-50 border border-amber-100 flex items-start gap-2.5">
                    <i class="fas fa-triangle-exclamation text-amber-500 mt-0.5 text-sm flex-shrink-0"></i>
                    <p class="text-xs text-amber-700 leading-relaxed">Transfer DP 10% lalu upload bukti untuk verifikasi booking Anda.</p>
                </div>
            @endif
        </x-card>

    </div>

    {{-- JADWAL ACARA --}}
    <x-card title="Jadwal Acara" title-icon="fa-calendar-days">
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-3">
            @forelse($schedules as $schedule)
                @php
                    $schedType  = $schedule->type ?? 'general';
                    $typeColors = [
                        'survey'  => ['dot' => '#3b82f6', 'bg' => 'bg-blue-50',   'border' => 'border-blue-100',   'badge' => 'text-blue-700'],
                        'fitting' => ['dot' => '#d4739a', 'bg' => 'bg-brand-50',  'border' => 'border-brand-100',  'badge' => 'text-brand-dark'],
                        'hari_h'  => ['dot' => '#ef4444', 'bg' => 'bg-red-50',    'border' => 'border-red-100',    'badge' => 'text-red-700'],
                    ];
                    $tc         = $typeColors[$schedType] ?? ['dot' => '#9ca3af', 'bg' => 'bg-gray-50', 'border' => 'border-gray-100', 'badge' => 'text-gray-600'];
                    $typeLabel  = match($schedType) { 'survey' => 'Survey', 'fitting' => 'Fitting', 'hari_h' => 'Hari H', default => ucfirst($schedType) };
                    $schedDate  = \Carbon\Carbon::parse($schedule->date ?? now());
                @endphp
                <div class="flex items-center gap-3.5 rounded-xl p-4 border {{ $tc['border'] }} {{ $tc['bg'] }} hover:shadow-sm transition-all duration-200">
                    <div class="text-center flex-shrink-0 w-12">
                        <div class="font-display text-2xl font-bold leading-none text-gray-800">{{ $schedDate->format('d') }}</div>
                        <div class="text-[11px] text-gray-500 uppercase font-semibold tracking-wider">{{ $schedDate->format('M') }}</div>
                    </div>
                    <div class="w-px h-10 bg-current opacity-10 flex-shrink-0"></div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-semibold text-gray-800 truncate">{{ $schedule->title ?? $typeLabel }}</p>
                        <p class="text-xs text-gray-500 truncate mt-0.5">
                            <i class="fas fa-location-dot mr-1 text-brand opacity-60"></i>{{ $schedule->location ?? '-' }}
                        </p>
                    </div>
                    <span class="text-xs font-semibold px-2.5 py-1 rounded-full {{ $tc['badge'] }} bg-white/70 flex-shrink-0">{{ $typeLabel }}</span>
                </div>
            @empty
                <div class="col-span-full">
                    <x-empty-state icon="fa-calendar-xmark" title="Belum ada jadwal" text="Jadwal akan diatur setelah DP terverifikasi" />
                </div>
            @endforelse
        </div>
    </x-card>
@endif
@endsection
