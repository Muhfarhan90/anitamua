@extends('layouts.app')

@section('title', 'Detail Booking')

@section('content')
@php
    $totalPrice = $booking->total_price;
    $totalPaid = $booking->payments->where('status', \App\Models\Payment::STATUS_VERIFIED)->sum('amount');
    $paymentFormOpen = $errors->hasAny(['type', 'amount', 'proof', 'method']);
    $remaining = max(0, $totalPrice - $totalPaid);
    $percentage = $totalPrice > 0 ? min(100, round(($totalPaid / $totalPrice) * 100)) : 0;
    $circumference = 2 * M_PI * 42;
    $offset = $circumference - ($percentage / 100 * $circumference);
@endphp

<x-page-header :title="'Detail Booking — '.($booking->client->name ?? $booking->name)">
    <x-slot:actions>
        <x-button href="{{ route('admin.bookings.edit', $booking) }}" color="ghost"><i class="fas fa-pen"></i> Edit Booking</x-button>
        @if($booking->status === \App\Models\Booking::STATUS_BOOKED || $booking->status === \App\Models\Booking::STATUS_COMPLETED)
        @if($booking->invoice)
        <a href="{{ route('admin.invoices.show', $booking->invoice) }}" class="inline-flex items-center gap-2 rounded-xl bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-700"><i class="fas fa-file-invoice"></i> Lihat Invoice</a>
        @endif
        @endif
        @php $hasFitting = $booking->schedules->where('type', 'fitting')->where('status', '!=', 'cancelled')->isNotEmpty(); @endphp
        @if($hasFitting)
        <x-button href="{{ route('admin.bookings.packing', $booking) }}" color="primary"><i class="fas fa-box"></i> Packing Checklist</x-button>
        @endif
        @if($booking->status !== 'completed' && $booking->status !== 'cancelled')
        <form action="{{ route('admin.bookings.complete', $booking) }}" method="POST" class="inline">
            @csrf
            <x-button color="success" type="submit" onclick="return confirm('Tandai booking ini sebagai selesai?')"><i class="fas fa-check"></i> Tandai Selesai</x-button>
        </form>
        <x-button color="danger" onclick="document.getElementById('cancelModal').classList.remove('hidden')"><i class="fas fa-ban"></i> Batalkan Booking</x-button>
        @endif
    </x-slot:actions>
</x-page-header>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
    {{-- MAIN --}}
    <div class="lg:col-span-2 space-y-5">

        <x-card title="Informasi Booking" title-icon="fa-file-invoice">
            <div class="grid grid-cols-2 md:grid-cols-5 gap-4 text-sm">
                <div>
                    <span class="text-gray-500">Kode</span>
                    <p class="font-semibold text-gray-800"><x-badge>{{ $booking->code }}</x-badge></p>
                </div>
                <div>
                    <span class="text-gray-500">Tanggal Acara</span>
                    <p class="font-semibold text-gray-800">{{ $booking->event_date ? $booking->event_date->format('d M Y') : '-' }}</p>
                </div>
                <div>
                    <span class="text-gray-500">Lokasi</span>
                    <p class="font-semibold text-gray-800">{{ $booking->location ?? '-' }}</p>
                </div>
                <div>
                    <span class="text-gray-500">Telepon</span>
                    <p class="font-semibold text-gray-800">{{ $booking->client->phone ?? '-' }}</p>
                </div>
                <div>
                    <span class="text-gray-500">Instagram</span>
                    <p class="font-semibold text-gray-800">{{ $booking->instagram ?? '-' }}</p>
                </div>
            </div>
        </x-card>

        {{-- VERIFICATION --}}
        @if($booking->status === 'pending')
        <div class="bg-yellow-50 border-l-4 border-yellow-500 rounded-xl shadow-sm p-5">
            <div class="flex items-start gap-4">
                <div class="flex-shrink-0 w-9 h-9 rounded-full bg-yellow-100 flex items-center justify-center">
                    <i class="fas fa-exclamation-triangle text-yellow-600"></i>
                </div>
                <div class="flex-1">
                    <h4 class="font-display text-lg font-bold text-yellow-800">Verifikasi DP1</h4>
                    <p class="text-sm text-yellow-700 mt-1">Booking ini belum diverifikasi. Silakan verifikasi pembayaran DP. Setelah diverifikasi, akun dashboard client dibuat otomatis.</p>
                    <div class="mt-4 flex flex-wrap gap-3">
                        <x-button color="success" type="button" data-modal-target="verifyDpModal" data-modal-toggle="verifyDpModal">
                            <i class="fas fa-check"></i> Verifikasi DP
                        </x-button>
                        @php $dp1Payment = $booking->payments->sortBy('id')->first(); @endphp
                        <a href="{{ asset('storage/' . ($dp1Payment?->proof ?? '')) }}" onclick="openProof(event, this.href)"
                           class="inline-flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium border-2 border-yellow-400/60 text-yellow-800 hover:bg-yellow-100/70 transition-all {{ $dp1Payment?->proof ? '' : 'opacity-40 pointer-events-none' }}">
                            <i class="fas fa-image"></i> Lihat Bukti
                        </a>
                    </div>
                </div>
            </div>
        </div>
        @endif

        {{-- PAYMENT TABLE --}}
        <x-card title="Pembayaran" title-icon="fa-credit-card" padding="p-0">
            <x-slot:actions>
                <x-button type="button" id="toggleAdminPaymentForm" aria-expanded="{{ $paymentFormOpen ? 'true' : 'false' }}">
                    <i class="fas {{ $paymentFormOpen ? 'fa-xmark' : 'fa-plus' }}" data-admin-payment-button-icon></i>
                    <span data-admin-payment-button-label>{{ $paymentFormOpen ? 'Tutup Form' : 'Tambah Pembayaran' }}</span>
                </x-button>
            </x-slot:actions>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left text-gray-500 border-b border-gray-100 bg-cream/60">
                            <th class="px-5 py-2.5 font-medium">Tahap</th>
                            <th class="px-5 py-2.5 font-medium">Nominal</th>
                            <th class="px-5 py-2.5 font-medium">Waktu Transaksi</th>
                            <th class="px-5 py-2.5 font-medium">Bukti</th>
                            <th class="px-5 py-2.5 font-medium">Status</th>
                            <th class="px-5 py-2.5 font-medium text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($booking->payments as $payment)
                        <tr class="border-b border-gray-50 hover:bg-brand-50/30 transition-colors">
                            <td class="px-5 py-3"><x-badge>{{ \App\Models\Payment::typeLabel($payment->type) }}</x-badge></td>
                            <td class="px-5 py-3 font-semibold text-gray-800">Rp {{ number_format($payment->amount, 0, ',', '.') }}</td>
                            <td class="px-5 py-3 text-gray-600">{{ ($payment->paid_at ?? $payment->created_at)?->format('d M Y H:i') }}</td>
                            <td class="px-5 py-3">
                                @if($payment->proof)
                                    <a href="{{ asset('storage/' . $payment->proof) }}" onclick="openProof(event, this.href)" class="text-brand underline text-sm cursor-pointer">Lihat</a>
                                @else
                                    <span class="text-gray-400">-</span>
                                @endif
                            </td>
                            <td class="px-5 py-3">
                                <x-badge :color="match($payment->status) {
                                    'pending' => 'warning',
                                    'verified' => 'success',
                                    'cancelled' => 'danger',
                                    default => 'gray',
                                }">{{ ucfirst($payment->status) }}</x-badge>
                            </td>
                            <td class="px-5 py-3 text-center">
                                @if($payment->status === 'pending')
                                <x-button size="sm" color="success" type="button" data-modal-target="verifyPayModal-{{ $payment->id }}" data-modal-toggle="verifyPayModal-{{ $payment->id }}">Verifikasi</x-button>
                                @else
                                <span class="text-gray-400 text-xs">-</span>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6"><x-empty-state icon="fa-credit-card" title="Belum ada data pembayaran" /></td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-card>

        <x-card title="Tambah Pembayaran" title-icon="fa-cloud-arrow-up" id="adminPaymentPanel" class="{{ $paymentFormOpen ? '' : 'hidden' }}">
            <form id="adminPaymentForm" action="{{ route('admin.bookings.payment', $booking) }}" method="POST" enctype="multipart/form-data" class="space-y-4">
                @csrf
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <x-input name="type" label="Tahap Pembayaran" placeholder="DP1 / DP2 / DP3 / Pelunasan" required />
                    <x-select name="method" label="Metode Pembayaran" required>
                        <option value="transfer">Transfer Bank</option>
                        <option value="qris">QRIS</option>
                        <option value="cash">Cash</option>
                    </x-select>
                </div>
                <x-input name="amount" label="Nominal" type="number" min="1000" step="1000" placeholder="1000000" required />
                <div>
                    <label for="adminProof" class="block text-sm font-medium text-gray-600 mb-1">Bukti Pembayaran <span class="text-red-500">*</span></label>
                    <input id="adminProof" type="file" name="proof" accept="image/*" required class="w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm text-gray-800 focus:outline-none focus:ring-2 focus:ring-brand-200">
                    <p class="mt-1 text-xs text-gray-400">JPG, PNG, atau WebP. Maksimal 5 MB.</p>
                </div>
                <x-button type="submit" class="w-full justify-center"><i class="fas fa-paper-plane"></i> Simpan Pembayaran</x-button>
                <p class="text-xs text-gray-400">Pembayaran baru akan berstatus pending sampai diverifikasi.</p>
            </form>
        </x-card>

        {{-- SCHEDULE --}}
        <x-card title="Jadwal" title-icon="fa-calendar-days" padding="p-0">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left text-gray-500 border-b border-gray-100 bg-cream/60">
                            <th class="px-5 py-2.5 font-medium">Jenis</th>
                            <th class="px-5 py-2.5 font-medium">Tanggal</th>
                            <th class="px-5 py-2.5 font-medium">Jam</th>
                            <th class="px-5 py-2.5 font-medium">PIC</th>
                            <th class="px-5 py-2.5 font-medium">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($booking->schedules as $schedule)
                        <tr class="border-b border-gray-50 hover:bg-brand-50/30 transition-colors">
                            <td class="px-5 py-3">
                                <x-badge :color="match($schedule->type) {
                                    'survey' => 'info',
                                    'fitting' => 'brand',
                                    'hari_h' => 'danger',
                                    default => 'gray',
                                }">{{ ucfirst(str_replace('_', ' ', $schedule->type)) }}</x-badge>
                            </td>
                            <td class="px-5 py-3 text-gray-600">{{ $schedule->date ? $schedule->date->format('d M Y') : '-' }}</td>
                            <td class="px-5 py-3 text-gray-600">{{ $schedule->time?->format('H:i') ?? '-' }}</td>
                            <td class="px-5 py-3 text-gray-600">{{ $schedule->picUser?->name ?? '— Belum ada PIC —' }}</td>
                            <td class="px-5 py-3">
                                <x-badge :color="match($schedule->status) {
                                    'scheduled' => 'info',
                                    'on_going' => 'warning',
                                    'finished' => 'success',
                                    'cancelled' => 'danger',
                                    default => 'gray',
                                }">{{ ucfirst(str_replace('_', ' ', $schedule->status)) }}</x-badge>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5"><x-empty-state icon="fa-calendar-xmark" title="Belum ada jadwal" /></td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

        </x-card>

        {{-- SURVEY (READ ONLY) --}}
        @include('admin.bookings.partials.survey-summary')

        {{-- FITTING (READ ONLY) --}}
        @include('admin.bookings.partials.fitting-summary')

        {{-- ACTIVITY --}}
        <x-card title="Aktivitas" title-icon="fa-clock-rotate-left">
            <div class="relative pl-6 border-l-2 border-brand-100 space-y-5">
                @forelse($booking->activityLogs ?? [] as $activity)
                <div class="relative">
                    <div class="absolute -left-[31px] top-1 w-3 h-3 rounded-full border-2 border-white shadow bg-brand"></div>
                    <p class="text-xs text-gray-400">{{ $activity->created_at ? $activity->created_at->format('d M Y H:i') : '' }} Â· <span class="font-semibold text-brand">{{ $activity->user->name ?? 'Sistem' }}</span></p>
                    <p class="text-sm text-gray-700">{{ $activity->description }}</p>
                </div>
                @empty
                <x-empty-state icon="fa-clock-rotate-left" title="Belum ada aktivitas" />
                @endforelse
            </div>
        </x-card>
    </div>

    {{-- SIDEBAR --}}
    <div class="space-y-5">
        <x-card title="Ringkasan Keuangan" title-icon="fa-wallet">
            <div class="space-y-2 text-sm mb-4">
                <div class="flex justify-between">
                    <span class="text-gray-500">Total Harga Paket</span>
                    <span class="font-bold text-gray-800">Rp {{ number_format($totalPrice, 0, ',', '.') }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-500">Total Sudah Dibayar</span>
                    <span class="font-bold text-emerald-500">Rp {{ number_format($totalPaid, 0, ',', '.') }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-500">Sisa Pembayaran</span>
                    <span class="font-bold text-brand">Rp {{ number_format($remaining, 0, ',', '.') }}</span>
                </div>
            </div>
            <div class="flex items-center justify-center gap-6">
                <div class="relative w-[100px] h-[100px]">
                    <svg viewBox="0 0 100 100" style="transform: rotate(-90deg);">
                        <circle cx="50" cy="50" r="42" fill="none" stroke="#f0ece8" stroke-width="9" />
                        <circle cx="50" cy="50" r="42" fill="none" stroke="#d4739a" stroke-width="9" stroke-linecap="round"
                                stroke-dasharray="{{ $circumference }}" stroke-dashoffset="{{ $offset }}" />
                    </svg>
                    <div class="absolute inset-0 flex flex-col items-center justify-center">
                        <span class="font-display text-xl font-bold text-brand leading-none">{{ $percentage }}%</span>
                        <span class="text-[10px] text-gray-500 mt-0.5">Dibayar</span>
                    </div>
                </div>
                <div class="text-xs space-y-1.5">
                    <div class="flex items-center gap-2 text-gray-600"><span class="w-2.5 h-2.5 rounded-full bg-brand inline-block"></span> Sudah Dibayar ({{ $percentage }}%)</div>
                    <div class="flex items-center gap-2 text-gray-600"><span class="w-2.5 h-2.5 rounded-full bg-gray-200 inline-block"></span> Belum Dibayar ({{ 100 - $percentage }}%)</div>
                </div>
            </div>
        </x-card>

        <x-card title="Paket" title-icon="fa-gift">
            @if($booking->addons->isNotEmpty())
            <div class="mb-3 rounded-xl border border-brand-100 bg-white p-4">
                <p class="text-xs font-semibold uppercase tracking-wider text-gray-400 mb-2">Paket Tambahan</p>
                <div class="space-y-2">
                    @foreach($booking->addons as $addon)
                        <div class="flex items-center justify-between gap-3 text-sm">
                            <span class="text-gray-700">{{ $addon->name }}</span>
                            <span class="font-semibold text-gray-800 whitespace-nowrap">Rp {{ number_format($addon->price, 0, ',', '.') }}</span>
                        </div>
                    @endforeach
                </div>
                <div class="flex items-center justify-between gap-3 mt-3 pt-3 border-t border-gray-100 text-sm">
                    <span class="font-semibold text-gray-700">Total Tagihan</span>
                    <span class="font-bold text-brand">Rp {{ number_format($totalPrice, 0, ',', '.') }}</span>
                </div>
            </div>
            @endif
            @if(isset($booking->package))
            <div class="p-4 rounded-xl bg-brand-50">
                <h4 class="font-display font-bold text-brand">{{ $booking->package->name }}</h4>
                <p class="font-display text-lg font-bold text-gray-800 mt-1">Rp {{ number_format($booking->package->price, 0, ',', '.') }}</p>
                @if(isset($booking->package->benefits))
                <ul class="mt-3 space-y-1">
                    @foreach($booking->package->benefits as $benefit)
                    <li class="flex items-center gap-2 text-xs text-gray-700">
                        <i class="fas fa-circle-check text-emerald-500 text-xs"></i>{{ $benefit->name }}
                    </li>
                    @endforeach
                </ul>
                @endif
            </div>
            @endif
        </x-card>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const toggle = document.getElementById('toggleAdminPaymentForm');
    const panel = document.getElementById('adminPaymentPanel');
    const label = toggle?.querySelector('[data-admin-payment-button-label]');
    const icon = toggle?.querySelector('[data-admin-payment-button-icon]');

    if (!toggle || !panel || !label || !icon) return;

    toggle.addEventListener('click', function () {
        const isHidden = panel.classList.toggle('hidden');
        toggle.setAttribute('aria-expanded', String(!isHidden));
        label.textContent = isHidden ? 'Tambah Pembayaran' : 'Tutup Form';
        icon.classList.toggle('fa-plus', isHidden);
        icon.classList.toggle('fa-xmark', !isHidden);
    });
});
</script>
@endpush

{{-- VERIFY DP MODAL (pending booking) --}}
<div id="verifyDpModal" tabindex="-1" aria-hidden="true"
     class="hidden overflow-y-auto overflow-x-hidden fixed top-0 right-0 left-0 z-50 justify-center items-center w-full md:inset-0 h-[calc(100%-1rem)] max-h-full">
    <div class="relative p-4 w-full max-w-lg max-h-full">
        <div class="relative bg-white rounded-2xl shadow-xl p-8">
            <div class="flex items-center justify-center w-16 h-16 mx-auto mb-4 rounded-full bg-emerald-100">
                <i class="fas fa-check-circle text-2xl text-emerald-600"></i>
            </div>
            <h3 class="font-display text-xl font-bold text-center mb-2 text-gray-900">Konfirmasi Verifikasi DP1</h3>
            <p class="text-sm text-center text-gray-500 mb-4">Yakin ingin memverifikasi DP1 booking <strong>{{ $booking->code }}</strong>?</p>
            <form action="{{ route('admin.bookings.verify-dp', $booking) }}" method="POST" class="space-y-4">
                @csrf

                <div>
                    <span class="text-xs font-semibold uppercase tracking-wide text-gray-400">Bukti Transfer</span>
                    @php $dp1Payment = $booking->payments->sortBy('id')->first(); @endphp
                    @if($dp1Payment?->proof)
                        <img src="{{ asset('storage/' . $dp1Payment->proof) }}" alt="Bukti DP"
                             class="mt-2 w-full max-h-64 object-contain rounded-xl border border-brand-100 bg-brand-50/40">
                    @else
                        <p class="mt-2 text-sm text-yellow-700 bg-yellow-50 rounded-xl px-4 py-2.5 border border-yellow-200">
                            <i class="fas fa-circle-info mr-1"></i> Tidak ada bukti transfer diupload oleh client.
                        </p>
                    @endif
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <x-input name="amount" label="Nominal DP1" type="number" placeholder="500000" :value="$dp1Payment?->amount > 0 ? $dp1Payment->amount : ''" />
                    <x-select name="method" label="Metode">
                        <option value="transfer">Transfer</option>
                        <option value="cash">Cash</option>
                        <option value="qris">QRIS</option>
                    </x-select>
                </div>

                <div class="flex gap-3 pt-2">
                    <button type="button" data-modal-hide="verifyDpModal"
                            class="flex-1 px-6 py-2.5 rounded-xl text-sm font-semibold border-2 border-gray-200 text-gray-600 hover:bg-gray-50 transition-all">
                        Batal
                    </button>
                    <x-button type="submit" class="flex-1 justify-center bg-emerald-600 hover:bg-emerald-700 text-white shadow-sm"><i class="fas fa-check"></i> Ya, Verifikasi</x-button>
                </div>
            </form>
        </div>
    </div>
</div>

@foreach($booking->payments as $payment)
    @if($payment->status === 'pending')
        {{-- VERIFY MODAL PER TAHAP PEMBAYARAN --}}
        <div id="verifyPayModal-{{ $payment->id }}" tabindex="-1" aria-hidden="true"
             class="hidden overflow-y-auto overflow-x-hidden fixed top-0 right-0 left-0 z-50 justify-center items-center w-full md:inset-0 h-[calc(100%-1rem)] max-h-full">
            <div class="relative p-4 w-full max-w-lg max-h-full">
                <div class="relative bg-white rounded-2xl shadow-xl p-8">
                    <div class="flex items-center justify-center w-16 h-16 mx-auto mb-4 rounded-full bg-brand-100">
                        <i class="fas fa-shield-halved text-2xl text-brand"></i>
                    </div>
                    <h3 class="font-display text-xl font-bold text-center mb-2 text-gray-900">Konfirmasi Verifikasi</h3>
                    <p class="text-sm text-center text-gray-500 mb-4">
                        Tahap <strong>{{ \App\Models\Payment::typeLabel($payment->type) }}</strong>
                        sebesar <strong>Rp {{ number_format($payment->amount, 0, ',', '.') }}</strong> untuk booking {{ $booking->code }}?
                    </p>
                    <form action="{{ route('admin.payments.verify', $payment->id) }}" method="POST" class="space-y-4">
                        @csrf

                        <div>
                            <span class="text-xs font-semibold uppercase tracking-wide text-gray-400">Bukti Transfer</span>
                            @if($payment->proof)
                                <img src="{{ asset('storage/' . $payment->proof) }}" alt="Bukti Transfer"
                                     class="mt-2 w-full max-h-64 object-contain rounded-xl border border-brand-100 bg-brand-50/40">
                            @else
                                <p class="mt-2 text-sm text-yellow-700 bg-yellow-50 rounded-xl px-4 py-2.5 border border-yellow-200">
                                    <i class="fas fa-circle-info mr-1"></i> Tidak ada bukti transfer diupload untuk tahap ini.
                                </p>
                            @endif
                        </div>

                        <div class="flex gap-3 pt-2">
                            <button type="button" data-modal-hide="verifyPayModal-{{ $payment->id }}"
                                    class="flex-1 px-6 py-2.5 rounded-xl text-sm font-semibold border-2 border-gray-200 text-gray-600 hover:bg-gray-50 transition-all">
                                Batal
                            </button>
                            <x-button type="submit" class="flex-1 justify-center bg-emerald-600 hover:bg-emerald-700 text-white shadow-sm"><i class="fas fa-check"></i> Ya, Verifikasi</x-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
@endforeach

{{-- CANCEL MODAL --}}
<div id="cancelModal" class="hidden fixed inset-0 z-50 overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
        <div class="fixed inset-0 bg-black/50 transition-opacity" onclick="document.getElementById('cancelModal').classList.add('hidden')"></div>
        <div class="relative bg-white rounded-2xl shadow-xl max-w-lg w-full mx-auto p-8">
            <div class="flex items-center justify-center w-16 h-16 mx-auto mb-4 rounded-full bg-red-100">
                <i class="fas fa-ban text-2xl text-red-600"></i>
            </div>
            <h3 class="font-display text-xl font-bold text-center mb-2 text-gray-900">Batalkan Booking?</h3>
            <p class="text-sm text-center text-gray-500 mb-6">Booking <strong>{{ $booking->code }}</strong> akan dibatalkan. DP dinyatakan hangus. Tindakan ini tidak dapat dibatalkan.</p>
            <form action="{{ route('admin.bookings.cancel', $booking) }}" method="POST">
                @csrf
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Alasan Pembatalan</label>
                    <textarea name="reason" rows="3" required placeholder="Masukkan alasan pembatalan..."
                              class="w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-red-200"></textarea>
                </div>
                <div class="flex gap-3">
                    <button type="button" onclick="document.getElementById('cancelModal').classList.add('hidden')"
                            class="flex-1 px-6 py-2.5 rounded-xl text-sm font-semibold border-2 border-gray-300 text-gray-600 hover:bg-gray-50 transition-all">
                        Batal
                    </button>
                    <x-button color="danger" type="submit" class="flex-1 justify-center">Ya, Batalkan</x-button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
