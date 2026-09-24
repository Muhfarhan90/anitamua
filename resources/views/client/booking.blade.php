@extends('layouts.app')

@section('title', 'Detail Booking')

@section('content')
@php
    $totalPrice = $booking->total_price;
    $totalPaid = $booking->payments->where('status', 'verified')->sum('amount');
    $remaining = max(0, $totalPrice - $totalPaid);
    $percentage = $totalPrice > 0 ? min(100, round(($totalPaid / $totalPrice) * 100)) : 0;
    $circumference = 2 * M_PI * 42;
    $offset = $circumference - ($percentage / 100 * $circumference);
    $pendingPayments = $booking->payments->where('status', 'pending')->where('amount', '>', 0);
@endphp

{{-- HEADER --}}
<div class="rounded-2xl p-6 md:p-8 mb-4 text-white text-center" style="background: linear-gradient(135deg, #d4739a 0%, #b85a82 60%, #a04575 100%);">
    <h1 class="font-display text-2xl md:text-3xl font-bold mb-1">Pembayaran Booking</h1>
    <p class="text-sm md:text-base" style="opacity:.9;font-weight:300;">Pantau seluruh riwayat pembayaran dan lakukan pembayaran sesuai jadwal yang telah ditentukan.</p>
</div>

<div class="max-w-[1280px] mx-auto pb-10">
    <div class="grid grid-cols-1 lg:grid-cols-[1fr_340px] gap-4 items-start">

        {{-- MAIN --}}
        <div class="space-y-4">
            {{-- DETAIL PEMBAYARAN --}}
            <x-card title="Detail Pembayaran" title-icon="fa-credit-card" padding="p-0">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="text-left text-gray-500 border-b border-gray-100 bg-cream/60">
                                <th class="px-5 py-2.5 font-medium">Tahap Pembayaran</th>
                                <th class="px-5 py-2.5 font-medium">Nominal</th>
                                <th class="px-5 py-2.5 font-medium">Waktu Transaksi</th>
                                <th class="px-5 py-2.5 font-medium">Status</th>
                                <th class="px-5 py-2.5 font-medium text-center">Bukti</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($booking->payments as $payment)
                            <tr class="border-b border-gray-50 hover:bg-brand-50/30 transition-colors">
                                <td class="px-5 py-3">
                                    <div class="flex items-center gap-3">
                                        <div class="w-9 h-9 rounded-full flex items-center justify-center flex-shrink-0 text-sm
                                                    {{ $payment->status === 'verified' ? 'bg-emerald-100 text-emerald-600' : ($payment->status === 'pending' ? 'bg-yellow-100 text-yellow-600' : 'bg-gray-100 text-gray-400') }}">
                                            <i class="fas {{ $payment->status === 'verified' ? 'fa-check' : ($payment->status === 'pending' ? 'fa-hourglass-half' : 'fa-lock') }}"></i>
                                        </div>
                                        <div>
                                            <div class="font-semibold text-gray-800">{{ \App\Models\Payment::typeLabel($payment->type) }}</div>
                                            <div class="text-xs text-gray-400">Tahap {{ $loop->iteration }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-5 py-3 font-semibold text-gray-800">Rp {{ number_format($payment->amount, 0, ',', '.') }}</td>
                                <td class="px-5 py-3 text-gray-600">{{ ($payment->paid_at ?? $payment->created_at)?->format('d M Y H:i') }}</td>
                                <td class="px-5 py-3">
                                    <x-badge :color="match($payment->status) {
                                        'verified' => 'success',
                                        'pending' => 'warning',
                                        'cancelled' => 'danger',
                                        default => 'gray',
                                    }">{{ match($payment->status) {
                                        'verified' => 'Verified',
                                        'pending' => 'Menunggu',
                                        'cancelled' => 'Batal',
                                        default => ucfirst($payment->status),
                                    } }}</x-badge>
                                </td>
                                <td class="px-5 py-3 text-center">
                                    @if($payment->proof)
                                        <a href="{{ asset('storage/'.$payment->proof) }}" onclick="openProof(event, this.href)" class="text-xs text-brand hover:underline">Lihat bukti</a>
                                    @else
                                        <span class="text-gray-400 text-xs">—</span>
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5"><x-empty-state icon="fa-credit-card" title="Belum ada tahap pembayaran" text="Jadwal pembayaran dibuat setelah DP1 diverifikasi" /></td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </x-card>

            {{-- UPLOAD BUKTI / TAMBAH TAHAP --}}
            <x-card title="Bayar / Tambah Tahap Pembayaran" title-icon="fa-cloud-arrow-up" id="upload">
                <form id="proofForm" enctype="multipart/form-data" action="{{ route('client.booking.proof', $booking->id) }}" method="POST" class="space-y-4">
                    @csrf
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <x-input name="type" id="paymentType" label="Tahap Pembayaran" placeholder="DP1 / DP2 / DP3 / Pelunasan" :value="old('type', 'DP'.($booking->payments->count() + 1))" required />
                        <x-select name="method" label="Metode Pembayaran" required>
                            <option value="transfer">Transfer Bank</option>
                            <option value="qris">QRIS</option>
                            <option value="cash">Cash</option>
                        </x-select>
                    </div>
                    <x-input name="amount" id="paymentAmount" label="Nominal" currency min="0" step="1000" placeholder="Contoh: 2.500.000" :value="old('amount')" required />
                    @if($pendingPayments->isNotEmpty())
                    <div>
                        <input type="hidden" name="payment_id" id="existingPaymentId" value="{{ old('payment_id') }}">
                        <p class="block text-sm font-medium text-gray-600 mb-2">Atau pilih tahap yang sudah dijadwalkan</p>
                        <div class="flex flex-wrap gap-2">
                            @foreach($pendingPayments as $p)
                                <button type="button" data-pending-payment data-payment-id="{{ $p->id }}" data-type="{{ $p->type }}" data-amount="{{ $p->amount }}" class="rounded-lg border border-gray-200 bg-white px-3 py-2 text-left text-sm text-gray-700 transition-colors hover:border-brand hover:bg-brand-50">
                                    {{ \App\Models\Payment::typeLabel($p->type) }} — Rp {{ number_format($p->amount, 0, ',', '.') }}
                                </button>
                            @endforeach
                            <button type="button" id="newPaymentStage" class="rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-600 transition-colors hover:border-brand hover:bg-brand-50">Tahap baru</button>
                        </div>
                        <p class="mt-2 text-xs text-gray-400">Pilih tahap yang ada untuk mengirim bukti pembayaran pada tahap tersebut.</p>
                    </div>
                    @endif
                    <div>
                        <label class="block text-sm font-medium text-gray-600 mb-1">Bukti Transfer <span class="text-red-500">*</span></label>
                        <div id="dropZone" class="border-2 border-dashed border-brand-200 rounded-xl bg-brand-50/30 p-6 text-center cursor-pointer hover:bg-brand-50/60 transition-colors">
                            <input type="file" id="fileInput" name="proof" accept="image/*" required class="hidden">
                            <i class="fas fa-cloud-arrow-up text-2xl text-brand mb-2 block"></i>
                            <p class="font-semibold text-gray-700 text-sm">Seret & Letakkan File di Sini</p>
                            <p class="text-xs text-gray-400 mt-1">atau klik untuk memilih file (JPG, PNG)</p>
                            <div id="uploadPreview" class="hidden mt-3">
                                <button type="button" onclick="openProof(event, this.querySelector('img').src)" aria-label="Perbesar foto bukti pembayaran" class="mx-auto block cursor-zoom-in">
                                    <img id="previewImage" src="" alt="Preview" class="max-h-40 mx-auto rounded-lg shadow-sm cursor-zoom-in">
                                </button>
                                <p id="fileName" class="text-xs text-gray-500 mt-2"></p>
                            </div>
                        </div>
                    </div>
                    <x-button type="submit" class="w-full justify-center">
                        <i class="fas fa-paper-plane"></i> Kirim Bukti Pembayaran
                    </x-button>
                    <p class="text-xs text-gray-400">Untuk tahap baru, isi nominal sesuai bukti. Admin akan memeriksa dan dapat mengoreksinya saat verifikasi.</p>
                </form>
            </x-card>

            @if($booking->survey)
                @include('admin.bookings.partials.survey-summary')
            @endif

            @if($booking->fittings->isNotEmpty())
                @include('admin.bookings.partials.fitting-summary')
            @endif

        </div>

        {{-- SIDEBAR --}}
        <div class="space-y-4">
            <x-card title="Ringkasan Booking" title-icon="fa-file-invoice">
                <div class="space-y-2 text-sm">
                    <div class="flex justify-between border-b border-gray-100 pb-2">
                        <span class="text-gray-500">Nama Client</span>
                        <span class="font-semibold text-gray-800">{{ $booking->client->name ?? $booking->name ?? '-' }}</span>
                    </div>
                    <div class="flex justify-between border-b border-gray-100 pb-2">
                        <span class="text-gray-500">Nama Paket</span>
                        <span class="font-semibold text-gray-800">{{ $booking->package->name ?? '-' }}</span>
                    </div>
                    @if($booking->addons->isNotEmpty())
                    <div class="border-b border-gray-100 pb-2">
                        <span class="text-gray-500">Paket Tambahan</span>
                        <div class="mt-1 space-y-1 text-right">
                            @foreach($booking->addons as $addon)
                                <div class="font-semibold text-gray-800">{{ $addon->name }} — Rp {{ number_format($addon->price, 0, ',', '.') }}</div>
                            @endforeach
                        </div>
                    </div>
                    @endif
                    <div class="flex justify-between border-b border-gray-100 pb-2">
                        <span class="text-gray-500">Tanggal Acara</span>
                        <span class="font-semibold text-gray-800">{{ $booking->event_date ? $booking->event_date->format('d M Y') : '-' }}</span>
                    </div>
                    <div class="flex justify-between border-b border-gray-100 pb-2">
                        <span class="text-gray-500">Lokasi Acara</span>
                        <span class="font-semibold text-gray-800 text-right">{{ $booking->location ?? '-' }}</span>
                    </div>
                    <div class="flex justify-between border-b border-gray-100 pb-2">
                        <span class="text-gray-500">No. WhatsApp</span>
                        <span class="font-semibold text-gray-800">{{ $booking->phone ?? '-' }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">Status Booking</span>
                        <x-badge :color="match($booking->status) {
                            'pending' => 'warning',
                            'booked' => 'success',
                            'completed' => 'info',
                            'cancelled' => 'danger',
                            default => 'gray',
                        }">{{ match($booking->status) {
                            'pending' => 'Pending',
                            'booked' => 'Booked',
                            'completed' => 'Completed',
                            'cancelled' => 'Cancelled',
                            default => ucfirst($booking->status ?? 'Unknown'),
                        } }}</x-badge>
                    </div>
                </div>
            </x-card>

            @if($booking->bookingVendors->isNotEmpty())
            <x-card title="Vendor yang Digunakan" title-icon="fa-store">
                <p class="mb-3 text-xs text-gray-500">Tim vendor yang dialokasikan untuk acara Anda.</p>
                <div class="divide-y divide-brand-100">
                    @foreach($booking->bookingVendors as $bookingVendor)
                    @php $vendor = $bookingVendor->vendor; @endphp
                    <div class="flex items-start gap-3 py-3 first:pt-0 last:pb-0">
                        @if($vendor?->logo)
                            <a href="{{ asset('storage/'.$vendor->logo) }}" onclick="openProof(event, this.href)" aria-label="Perbesar logo {{ $vendor->name }}" class="shrink-0 cursor-zoom-in">
                                <img src="{{ asset('storage/'.$vendor->logo) }}" alt="Logo {{ $vendor->name }}" class="h-9 w-9 rounded-full border border-brand-100 bg-white object-cover">
                            </a>
                        @else
                            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-brand-50 text-brand">
                                <i class="fas fa-store text-sm"></i>
                            </div>
                        @endif
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-semibold text-gray-800">{{ $vendor?->name ?? 'Vendor' }}</p>
                            <p class="mt-0.5 text-xs font-medium text-brand">{{ $bookingVendor->role ?: $vendor?->category?->name ?: 'Vendor acara' }}</p>
                            @if($vendor?->phone || $vendor?->instagram)
                            <p class="mt-1 text-xs text-gray-500">{{ $vendor?->phone }}{{ $vendor?->phone && $vendor?->instagram ? ' · ' : '' }}{{ $vendor?->instagram }}</p>
                            @endif
                        </div>
                    </div>
                    @endforeach
                </div>
            </x-card>
            @endif

            <x-card title="Ringkasan Keuangan" title-icon="fa-wallet">
                <div class="space-y-2 text-sm mb-4">
                    @if($booking->discount_amount > 0)
                    <div class="flex justify-between">
                        <span class="text-gray-500">Subtotal Booking</span>
                        <span class="font-bold text-gray-800">Rp {{ number_format($booking->subtotal_price, 0, ',', '.') }}</span>
                    </div>
                    @foreach($booking->discount_line_items as $discount)
                    <div class="flex justify-between gap-4">
                        <span class="text-gray-500">{{ $discount['label'] }}</span>
                        <span class="whitespace-nowrap font-bold text-red-500">-Rp {{ number_format($discount['amount'], 0, ',', '.') }}</span>
                    </div>
                    @endforeach
                    @endif
                    <div class="flex justify-between">
                        <span class="text-gray-500">Total Tagihan</span>
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

            <x-card title="Informasi Penting" title-icon="fa-circle-info">
                <div class="space-y-3 text-sm text-gray-600">
                    <div class="flex gap-2 items-start"><i class="fas fa-circle-check text-emerald-500 mt-0.5 text-xs"></i> Booking sah setelah DP1 diverifikasi admin.</div>
                    <div class="flex gap-2 items-start"><i class="fas fa-circle-xmark text-red-500 mt-0.5 text-xs"></i> Pembatalan sepihak menyebabkan DP hangus.</div>
                    <div class="flex gap-2 items-start"><i class="fas fa-clock text-brand mt-0.5 text-xs"></i> Pembayaran diverifikasi maksimal 1&times;24 jam pada hari kerja.</div>
                </div>
            </x-card>

            <div class="rounded-2xl p-5 text-center text-white" style="background: linear-gradient(135deg, #d4739a, #b85a82);">
                <i class="fas fa-heart text-2xl mb-2 block"></i>
                <h4 class="font-display text-lg font-bold">Terima Kasih!</h4>
                <p class="text-sm mt-1 mb-4" style="opacity:.9;">Jika ada pertanyaan, jangan ragu menghubungi admin kami.</p>
                <a href="https://wa.me/{{ $settings['whatsapp'] ?? '6281234567890' }}?text=Halo%20Admin%20ANITA%2C%20saya%20ingin%20bertanya%20tentang%20booking%20{{ $booking->id }}"
                   target="_blank"
                   class="inline-flex items-center gap-2 rounded-xl bg-white text-emerald-600 font-semibold text-sm px-5 py-2.5 no-underline hover:shadow-lg transition-shadow">
                    <i class="fab fa-whatsapp text-lg"></i> Hubungi Admin
                </a>
            </div>
        </div>
    </div>

    {{-- AKTIVITAS --}}
    @if($booking->activityLogs && $booking->activityLogs->count() > 0)
    <div class="mt-4">
        <x-card title="Aktivitas" title-icon="fa-clock-rotate-left">
            <div class="max-h-48 overflow-y-auto overscroll-contain pr-2">
              <div class="relative pl-6 border-l-2 border-brand-100 space-y-5">
                @foreach($booking->activityLogs->sortByDesc('created_at') as $log)
                <div class="relative">
                    <div class="absolute -left-[31px] top-1 w-3 h-3 rounded-full border-2 border-white shadow
                                {{ str_contains($log->description ?? '', 'verifikasi') ? 'bg-emerald-500' : (str_contains($log->description ?? '', 'upload') || str_contains($log->description ?? '', 'bukti') ? 'bg-brand' : 'bg-gray-300') }}"></div>
                    <p class="text-xs text-gray-400">{{ \Carbon\Carbon::parse($log->created_at)->format('d M Y H:i') }} WIB @if($log->user)&middot; <span class="font-semibold text-brand">{{ $log->user->name }}</span>@endif</p>
                    <p class="text-sm text-gray-700">{{ $log->description }}</p>
                </div>
                @endforeach
              </div>
            </div>
        </x-card>
    </div>
    @endif
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const dropZone = document.getElementById('dropZone');
    const fileInput = document.getElementById('fileInput');
    const preview = document.getElementById('uploadPreview');
    const previewImg = document.getElementById('previewImage');
    const fileName = document.getElementById('fileName');

    dropZone.addEventListener('click', () => fileInput.click());

    ['dragenter', 'dragover'].forEach(evt => {
        dropZone.addEventListener(evt, e => {
            e.preventDefault();
            dropZone.classList.add('bg-brand-50/60', 'border-brand');
        });
    });

    ['dragleave', 'drop'].forEach(evt => {
        dropZone.addEventListener(evt, e => {
            e.preventDefault();
            dropZone.classList.remove('bg-brand-50/60', 'border-brand');
        });
    });

    dropZone.addEventListener('drop', e => {
        if (e.dataTransfer.files.length) handleFile(e.dataTransfer.files[0]);
    });

    fileInput.addEventListener('change', e => {
        if (e.target.files.length) handleFile(e.target.files[0]);
    });

    function handleFile(file) {
        fileName.textContent = file.name;
        if (file.type.startsWith('image/')) {
            const reader = new FileReader();
            reader.onload = e => {
                previewImg.src = e.target.result;
                previewImg.style.display = 'block';
            };
            reader.readAsDataURL(file);
        } else {
            previewImg.style.display = 'none';
        }
        preview.classList.remove('hidden');
    }

    document.getElementById('proofForm').addEventListener('submit', function (e) {
        if (!fileInput.files.length) {
            e.preventDefault();
            alert('Pilih file bukti transfer terlebih dahulu.');
        }
    });

    const paymentType = document.getElementById('paymentType');
    const paymentAmount = document.getElementById('paymentAmount');
    const existingPaymentId = document.getElementById('existingPaymentId');
    const pendingPaymentButtons = [...document.querySelectorAll('[data-pending-payment]')];
    const nextDpLabel = @json('DP'.($booking->payments->count() + 1));

    function clearPendingPayment() {
        existingPaymentId.value = '';
        paymentType.readOnly = false;
        paymentAmount.readOnly = false;
        pendingPaymentButtons.forEach(button => button.classList.remove('border-brand', 'bg-brand-50', 'ring-2', 'ring-brand-200'));
    }

    pendingPaymentButtons.forEach(button => button.addEventListener('click', () => {
        clearPendingPayment();
        existingPaymentId.value = button.dataset.paymentId;
        paymentType.value = button.dataset.type;
        paymentAmount.value = button.dataset.amount;
        paymentType.readOnly = true;
        paymentAmount.readOnly = true;
        button.classList.add('border-brand', 'bg-brand-50', 'ring-2', 'ring-brand-200');
    }));

    document.getElementById('newPaymentStage')?.addEventListener('click', () => {
        clearPendingPayment();
        paymentType.value = nextDpLabel;
        paymentAmount.value = '';
        paymentAmount.focus();
    });

    pendingPaymentButtons.find(button => button.dataset.paymentId === existingPaymentId?.value)?.click();
});
</script>
@endpush
@endsection
