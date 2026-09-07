@extends('layouts.landing')

@section('title', 'Booking Paket Anda')

@push('styles')
<style>
    .booking-hero {
        background: linear-gradient(rgba(212,115,154,.8), rgba(184,92,133,.8)), url('https://images.unsplash.com/photo-1519741497674-611481863552?q=80&w=1920&auto=format&fit=crop') center/cover no-repeat;
        padding: 3.5rem 0 2.5rem;
        color: #fff;
        text-align: center;
    }
    .booking-hero h1 {
        font-size: 2.4rem;
    }
    .booking-hero p {
        opacity: .9;
        max-width: 640px;
    }
    .form-card, .summary-card, .info-card {
        background: #fff;
        border: none;
        border-radius: 14px;
        box-shadow: 0 2px 16px rgba(0,0,0,.06);
    }
    .form-card .card-body, .summary-card .card-body, .info-card .card-body {
        padding: .75rem 1.25rem 1.25rem;
    }
    .form-label {
        font-weight: 600;
        font-size: .82rem;
        color: #2d2521;
        margin-bottom: .6rem;
        display: block;
    }
    .input-group-icon {
        position: relative;
    }
    .input-group-icon .input-icon {
        position: absolute;
        left: 14px;
        top: 50%;
        transform: translateY(-50%);
        color: #d4739a;
        font-size: 1rem;
        z-index: 4;
        pointer-events: none;
    }
    .input-group-icon .form-control,
    .input-group-icon .form-select,
    .input-group-icon textarea {
        padding-left: 2.6rem;
    }
    .form-control, .form-select, textarea {
        width: 100%;
        border: 1px solid #e5e1dc;
        background: #fff;
        border-radius: .5rem;
        padding: .55rem .75rem;
        font-size: .9rem;
        font-family: 'Plus Jakarta Sans', sans-serif;
        color: #2d2521;
        transition: border-color .15s, box-shadow .15s;
    }
    .form-control:focus, .form-select:focus, textarea:focus {
        outline: none;
        border-color: #d4739a;
        box-shadow: 0 0 0 .2rem rgba(212,115,154,.15);
    }
    .summary-line {
        display: flex;
        justify-content: space-between;
        padding: .55rem 0;
        border-bottom: 1px solid #f0ece8;
        font-size: .88rem;
    }
    .summary-line:last-child { border-bottom: none; }
    .summary-line .label { color: #8a8075; }
    .summary-line .value { font-weight: 600; color: #2d2521; }
    .summary-total {
        background: linear-gradient(135deg, #d4739a, #e8a0bf);
        color: #fff;
        border-radius: 10px;
        padding: 1rem 1.25rem;
        margin-top: .75rem;
    }
    .summary-total .amount {
        font-size: 1.45rem;
        font-weight: 700;
    }
    .vendor-check {
        display: flex;
        align-items: center;
        gap: .5rem;
        font-size: .84rem;
        padding: .3rem 0;
    }
    .vendor-check i { color: #198754; font-size: .75rem; }
    .timeline-step {
        text-align: center;
        position: relative;
        flex: 1;
    }
    .timeline-step .step-icon {
        width: 52px;
        height: 52px;
        border-radius: 50%;
        background: #f5d5e0;
        color: #d4739a;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 1.25rem;
        margin-bottom: .6rem;
        transition: .3s;
    }
    .timeline-step:hover .step-icon {
        background: #d4739a;
        color: #fff;
    }
    .timeline-step .step-title {
        font-weight: 700;
        font-size: .78rem;
        color: #2d2521;
    }
    .timeline-step .step-desc {
        font-size: .72rem;
        color: #8a8075;
        margin-top: .15rem;
    }
    .timeline-connector {
        flex: 0 0 40px;
        height: 2px;
        background: #f5d5e0;
        margin-top: 26px;
    }
    .btn-booking-cta {
        background: #d4739a;
        color: #fff;
        font-weight: 700;
        font-size: 1rem;
        padding: .85rem 2rem;
        border: none;
        border-radius: 50px;
        transition: .3s;
        letter-spacing: .5px;
    }
    .btn-booking-cta:hover {
        background: #b85c85;
        color: #fff;
        transform: translateY(-2px);
        box-shadow: 0 8px 24px rgba(212,115,154,.35);
    }
    .info-card ul li {
        padding: .4rem 0;
        font-size: .88rem;
        color: #555;
    }
    .info-card ul li i {
        color: #d4739a;
        margin-right: .4rem;
        font-size: .75rem;
    }
    .bank-account {
        background: linear-gradient(135deg, #d4739a, #b85c85);
        color: #fff;
        border-radius: 12px;
        padding: 1.1rem 1.25rem;
    }
    .bank-account .bank-account-name {
        font-size: .78rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 1.5px;
        opacity: .85;
    }
    .bank-account .bank-account-number {
        font-family: 'Playfair Display', serif;
        font-size: 1.35rem;
        font-weight: 700;
        letter-spacing: 2px;
        margin: .15rem 0;
    }
    .bank-account .bank-account-holder {
        font-size: .85rem;
        opacity: .9;
    }
    .alert-guest {
        background: #fff8e1;
        border: 1px solid #ffe082;
        border-radius: 10px;
        padding: 1rem 1.25rem;
    }
    .alert-guest i { color: #f9a825; }
</style>
@endpush

@section('content')

{{-- HERO --}}
<section class="booking-hero" style="text-align:center;">
    <div class="container" style="text-align:center;">
        <h1 style="font-family:'Playfair Display',serif; font-size:2.4rem; font-weight:700; margin-bottom:12px; text-align:center;">Booking Paket Anda</h1>
        <p style="opacity:.9; max-width:640px; margin:0 auto; font-size:.95rem; text-align:center;">
            Isi data berikut untuk memulai reservasi. Tim ANITA akan segera menghubungi Anda untuk proses konfirmasi dan pembayaran DP.
        </p>
    </div>
</section>

<section class="py-16">
    <div class="container" style="max-width: 1140px">

        @guest
            <div class="alert-guest flex items-center gap-3 mb-4">
                <i class="fa-solid fa-circle-info text-xl"></i>
                <div>
                    <strong class="text-gray-900">Belum punya akun?</strong>
                    <span class="text-gray-500">Tidak perlu mendaftar. Setelah DP diverifikasi admin, akun dashboard Anda akan dibuat otomatis dan Anda bisa login menggunakan email ini.</span>
                </div>
            </div>
        @endguest

        <div class="grid grid-cols-12 gap-4">

            {{-- ══════════ KIRI: FORM ══════════ --}}
            <div class="col-span-12 lg:col-span-7">
                <div class="form-card">
                    <div class="card-body">
                        <h5 class="font-display font-bold text-xl" style="color:#d4739a; margin-bottom: 16px;">
                            <i class="fa-solid fa-pen-to-square mr-2"></i>Formulir Booking
                        </h5>
                        <form method="POST" action="{{ route('booking.store') }}" enctype="multipart/form-data">
                            @csrf

                            {{-- Nama Pengantin --}}
                            <div class="mb-3">
                                <label class="form-label">Nama Pengantin <span class="text-red-600">*</span></label>
                                <div class="input-group-icon">
                                    <i class="fa-solid fa-user input-icon"></i>
                                    <input type="text" name="name" class="form-control" value="{{ old('name', auth()->user()->name ?? '') }}" placeholder="Contoh: Reno & Dewi" required>
                                </div>
                            </div>

                            {{-- No. WhatsApp --}}
                            <div class="mb-3">
                                <label class="form-label">Nomor WhatsApp <span class="text-red-600">*</span></label>
                                <div class="input-group-icon">
                                    <i class="fa-solid fa-phone input-icon"></i>
                                    <input type="text" name="phone" class="form-control" value="{{ old('phone', auth()->user()->phone ?? '') }}" placeholder="08xxxxxxxxxx" required>
                                </div>
                            </div>

                            {{-- Email --}}
                            <div class="mb-3">
                                <label class="form-label">Email <span class="text-red-600">*</span></label>
                                <div class="input-group-icon">
                                    <i class="fa-solid fa-envelope input-icon"></i>
                                    <input type="email" name="email" class="form-control" value="{{ old('email', auth()->user()->email ?? '') }}" placeholder="email@anda.com" required>
                                </div>
                                @error('email')
                                    <div class="rounded-lg bg-red-50 text-red-700 text-sm px-3 py-2 mt-2 mb-0"><i class="fa-solid fa-circle-exclamation mr-1"></i>{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Username Instagram</label>
                                <div class="input-group-icon">
                                    <i class="fa-brands fa-instagram input-icon"></i>
                                    <input type="text" name="instagram" class="form-control" value="{{ old('instagram', auth()->user()->instagram ?? '') }}" placeholder="@username">
                                </div>
                                @error('instagram')
                                    <div class="rounded-lg bg-red-50 text-red-700 text-sm px-3 py-2 mt-2 mb-0"><i class="fa-solid fa-circle-exclamation mr-1"></i>{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="grid grid-cols-12 gap-3">
                                {{-- Tanggal Acara --}}
                                <div class="col-span-12 md:col-span-6 mb-3">
                                    <label class="form-label">Tanggal Acara <span class="text-red-600">*</span></label>
                                    <div class="input-group-icon">
                                        <i class="fa-regular fa-calendar input-icon"></i>
                                        <input type="date" name="event_date" class="form-control" value="{{ old('event_date') }}" min="{{ date('Y-m-d') }}" required>
                                    </div>
                                </div>
                            </div>

                            {{-- Lokasi Acara --}}
                            <div class="mb-3">
                                <label class="form-label">Lokasi Acara <span class="text-red-600">*</span></label>
                                <div class="input-group-icon">
                                    <i class="fa-solid fa-location-dot input-icon"></i>
                                    <input type="text" name="location" class="form-control" value="{{ old('location') }}" placeholder="Nama gedung / hotel / alamat" required>
                                </div>
                            </div>

                            {{-- Pilih Jenis Paket --}}
                            <div class="mb-3">
                                <label class="form-label">Jenis Paket <span class="text-red-600">*</span></label>
                                <div class="input-group-icon">
                                    <i class="fa-solid fa-layer-group input-icon"></i>
                                    <select id="package_type" class="form-select" required>
                                        <option value="">— Pilih Jenis —</option>
                                        <option value="makeup">Make Up & Attire</option>
                                        <option value="full">Full WO Package</option>
                                    </select>
                                </div>
                            </div>

                            {{-- Pilih Paket --}}
                            <div class="mb-3">
                                <label class="form-label">Pilih Paket <span class="text-red-600">*</span></label>
                                <div class="input-group-icon">
                                    <i class="fa-solid fa-box input-icon"></i>
                                    <select name="package_id" id="package_id" class="form-select" required disabled>
                                        <option value="">— Pilih Jenis Paket dahulu —</option>
                                        @foreach($packages as $package)
                                            <option value="{{ $package->id }}" data-type="{{ $package->type }}"
                                                @selected(old('package_id', request('package')) == $package->id)>
                                                {{ $package->name }}@if($package->sub_type) ({{ $subTypeLabels[$package->sub_type] ?? $package->sub_type }})@endif — Rp {{ number_format($package->price, 0, ',', '.') }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            {{-- Nominal DP --}}
                            <div class="mb-3">
                                <label class="form-label">Nominal DP yang ditransfer <span class="text-red-600">*</span></label>
                                <div class="input-group-icon">
                                    <i class="fa-solid fa-money-bill-wave input-icon"></i>
                                    <input type="number" name="amount" class="form-control" value="{{ old('amount') }}" min="1000" step="1000" placeholder="Contoh: 2500000" required>
                                </div>
                                <small style="color:var(--muted);">Masukkan nominal sesuai bukti transfer. Admin akan memeriksa dan dapat menyesuaikannya saat verifikasi.</small>
                                @error('amount')
                                    <div class="rounded-lg bg-red-50 text-red-700 text-sm px-3 py-2 mt-2 mb-0"><i class="fa-solid fa-circle-exclamation mr-1"></i>{{ $message }}</div>
                                @enderror
                            </div>

                            {{-- Bukti Transfer DP (Wajib) --}}
                            <div class="mb-3">
                                <label class="form-label">Bukti Transfer DP <span class="text-red-600">*</span></label>
                                <div class="input-group-icon" style="align-items:flex-start">
                                    <i class="fa-solid fa-cloud-arrow-up input-icon" style="top:1.1rem"></i>
                                    <input type="file" name="proof" accept="image/jpeg,image/png,image/webp" class="form-control" style="padding-left:2.6rem; padding-top:.55rem;" required>                                </div>
                                <small style="color:var(--muted);">JPG/JPEG/PNG/WebP, maks 3 MB. Booking akan menunggu verifikasi admin setelah dikirim.</small>
                                @error('proof')
                                    <div class="rounded-lg bg-red-50 text-red-700 text-sm px-3 py-2 mt-2 mb-0"><i class="fa-solid fa-circle-exclamation mr-1"></i>{{ $message }}</div>
                                @enderror
                            </div>

                            {{-- Catatan Tambahan --}}
                            <div class="mb-3">
                                <label class="form-label">Catatan Tambahan</label>
                                <div class="input-group-icon" style="align-items:flex-start">
                                    <i class="fa-solid fa-pencil input-icon" style="top:1.1rem"></i>
                                    <textarea name="notes" rows="4" class="form-control" style="padding-left:2.6rem" placeholder="Tema warna, jumlah undangan, request khusus...">{{ old('notes') }}</textarea>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            {{-- ══════════ KANAN: RINGKASAN ══════════ --}}
            <div class="col-span-12 lg:col-span-5">
                <div class="summary-card mb-4">
                    <div class="card-body">
                        <h5 class="font-display font-bold text-xl" style="color:#d4739a; margin-bottom: 16px;">
                            <i class="fa-solid fa-receipt mr-2"></i>Ringkasan Reservasi
                        </h5>
                        <div class="summary-line">
                            <span class="label">Nama Paket</span>
                            <span class="value" id="summary-paket">-</span>
                        </div>
                        <div class="summary-line">
                            <span class="label">Harga Paket</span>
                            <span class="value" id="summary-harga">Rp 0</span>
                        </div>
                        <div class="summary-line">
                            <span class="label">Tanggal Acara</span>
                            <span class="value" id="summary-tanggal">-</span>
                        </div>
                        <div class="summary-line">
                            <span class="label">Lokasi Acara</span>
                            <span class="value" id="summary-lokasi">-</span>
                        </div>

                        <button type="button" class="btn-booking-cta w-full mt-3" onclick="validateThenSubmit()">
                            <i class="fa-solid fa-circle-arrow-right mr-2"></i>Lanjutkan Booking
                        </button>
                    </div>
                </div>

                {{-- Instruksi Pembayaran --}}
                <div class="info-card mb-4">
                    <div class="card-body">
                        <h6 class="font-display font-bold text-xl" style="color:#d4739a; margin-bottom: 16px;">
                            <i class="fa-solid fa-building-columns mr-2"></i>Instruksi Pembayaran
                        </h6>
                        <div class="bank-account">
                            <div class="flex items-center justify-between gap-2">
                                <div class="bank-account-name">{{ $settings['bank_name'] ?? 'Bank BCA' }}</div>
                            </div>
                            <div class="flex items-center gap-2 mt-1.5">
                                <span id="bank-account-copy-text" class="bank-account-number">{{ $settings['bank_account_number'] ?? '1234567890' }}</span>
                                <button type="button" data-copy-to-clipboard-target="bank-account-copy-text"
                                        data-copy-to-clipboard-content-type="textContent"
                                        class="flex-shrink-0 flex items-center text-white bg-white/20 border border-white/30 hover:bg-white/30 rounded-lg text-xs font-semibold px-2.5 py-1.5 transition-colors">
                                    <span id="copy-default-message" class="flex items-center">
                                        <svg class="w-3.5 h-3.5 me-1" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 4h3a1 1 0 0 1 1 1v15a1 1 0 0 1-1 1H6a1 1 0 0 1-1-1V5a1 1 0 0 1 1-1h3m0 3h6m-6 5h6m-6 4h6M10 3v4h4V3h-4Z"/></svg>
                                        <span class="text-xs font-semibold">Salin</span>
                                    </span>
                                    <span id="copy-success-message" class="hidden items-center">
                                        <svg class="w-3.5 h-3.5 me-1" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 4h3a1 1 0 0 1 1 1v15a1 1 0 0 1-1 1H6a1 1 0 0 1-1-1V5a1 1 0 0 1 1-1h3m0 3h6m-6 7 2 2 4-4m-5-9v4h4V3h-4Z"/></svg>
                                        <span class="text-xs font-semibold">Tersalin</span>
                                    </span>
                                </button>
                            </div>
                            <div class="bank-account-holder">a.n. {{ $settings['bank_account_name'] ?? 'ANITA MUA' }}</div>
                        </div>
                        <ul class="list-none mb-0 mt-3" style="color:#555; font-size:.88rem;">
                            <li class="flex items-start gap-2"><i class="fa-solid fa-circle-check mt-1" style="color:#198754; font-size:.7rem;"></i> Transfer DP (lihat estimasi di Ringkasan Reservasi) ke rekening di atas sebelum mengirim booking.</li>
                            <li class="flex items-start gap-2"><i class="fa-solid fa-circle-check mt-1" style="color:#198754; font-size:.7rem;"></i> Kode booking yang dikirim setelah submit dipakai sebagai berita transfer.</li>
                            <li class="flex items-start gap-2"><i class="fa-solid fa-circle-check mt-1" style="color:#198754; font-size:.7rem;"></i> Upload bukti di formulir ini — booking langsung menunggu verifikasi admin.</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

    </div>
</section>

{{-- KONFIRMASI BOOKING --}}
<div id="confirmBookingModal" tabindex="-1" aria-hidden="true"
     class="hidden overflow-y-auto overflow-x-hidden fixed inset-0 z-50 justify-center items-center w-full bg-black/50">
    <div class="flex items-center justify-center min-h-screen px-4 py-8 text-center sm:p-0">
        <div class="relative bg-white rounded-2xl shadow-xl max-w-md w-full mx-auto p-8">
            <div class="flex items-center justify-center w-16 h-16 mx-auto mb-4 rounded-full" style="background:#f5d5e0;">
                <i class="fas fa-circle-question text-2xl" style="color:#d4739a;"></i>
            </div>
            <h3 class="font-display text-xl font-bold text-center mb-2" style="color:#2d2521;">Konfirmasi Booking</h3>
            <p class="text-sm text-center mb-6" style="color:#8a8075;">
                Apakah data Anda sudah benar dan bukti transfer sudah diupload?
            </p>
            <div class="flex gap-3">
                <button type="button" data-confirm-booking-close
                        class="flex-1 px-6 py-2.5 rounded-xl text-sm font-semibold border-2 border-gray-200 text-gray-600 hover:bg-gray-50 transition-all">
                    Kembali
                </button>
                <button type="button" data-confirm-booking-submit
                        class="flex-1 px-6 py-2.5 rounded-xl text-sm font-semibold text-white transition-all"
                        style="background:#d4739a;" onmouseover="this.style.background='#b85c85'" onmouseout="this.style.background='#d4739a'">
                    Ya, Lanjutkan
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const confirmModal = document.getElementById('confirmBookingModal');

    function openConfirmBookingModal() {
        confirmModal.classList.remove('hidden');
        confirmModal.classList.add('flex');
        confirmModal.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
    }

    function closeConfirmBookingModal() {
        confirmModal.classList.add('hidden');
        confirmModal.classList.remove('flex');
        confirmModal.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';
    }

    /* Validasi form, jika lolos baru tampilkan popup konfirmasi */
    window.validateThenSubmit = function () {
        const form = document.querySelector('form');
        if (!form.reportValidity()) return;
        openConfirmBookingModal();
    };

    document.querySelector('[data-confirm-booking-close]')?.addEventListener('click', closeConfirmBookingModal);
    document.querySelector('[data-confirm-booking-submit]')?.addEventListener('click', function () {
        document.querySelector('form').submit();
    });
    confirmModal?.addEventListener('click', function (event) {
        if (event.target === confirmModal) closeConfirmBookingModal();
    });
    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && !confirmModal.classList.contains('hidden')) {
            closeConfirmBookingModal();
        }
    });

    const selectPaket  = document.querySelector('select[name="package_id"]');
    const inputTanggal = document.querySelector('input[name="event_date"]');
    const inputLokasi  = document.querySelector('input[name="location"]');
    const selectJenis  = document.getElementById('package_type');
    const paketOptions = Array.from(selectPaket.options).slice(1);

    /* Filter daftar paket berdasarkan jenis yang dipilih */
    function applyJenisFilter() {
        const jenis = selectJenis.value;
        paketOptions.forEach(opt => { opt.hidden = jenis !== '' && opt.dataset.type !== jenis; });
        if (selectPaket.value && selectPaket.selectedOptions[0]?.hidden) {
            selectPaket.value = '';
        }
        selectPaket.disabled = jenis === '';
        updateSummary();
    }
    selectJenis.addEventListener('change', applyJenisFilter);

    /* Preselect jenis jika paket sudah terpilih (dari query ?package= atau old) */
    const preselected = selectPaket.selectedOptions[0];
    if (preselected && preselected.value) {
        selectJenis.value = preselected.dataset.type || '';
        applyJenisFilter();
    }

    @php
        $pkgData = $packages->map(fn($p) => ['id' => $p->id, 'name' => $p->name, 'price' => $p->price]);
    @endphp
    const packages = @json($pkgData);

    function fmtRp(n) {
        return 'Rp ' + parseInt(n, 10).toLocaleString('id-ID');
    }

    function updateSummary() {
        const pkg = packages.find(p => p.id == selectPaket.value);
        const namaPaket = document.getElementById('summary-paket');
        const harga     = document.getElementById('summary-harga');
        const tgl       = document.getElementById('summary-tanggal');
        const lok       = document.getElementById('summary-lokasi');

        if (pkg) {
            namaPaket.textContent = pkg.name;
            harga.textContent     = fmtRp(pkg.price);
        } else {
            namaPaket.textContent = '-';
            harga.textContent     = fmtRp(0);
        }
    }

    function updateTanggal() {
        const el = document.getElementById('summary-tanggal');
        if (inputTanggal.value) {
            const d = new Date(inputTanggal.value + 'T00:00:00');
            el.textContent = d.toLocaleDateString('id-ID', { day:'numeric', month:'long', year:'numeric' });
        } else {
            el.textContent = '-';
        }
    }

    function updateLokasi() {
        const el = document.getElementById('summary-lokasi');
        el.textContent = inputLokasi.value || '-';
    }

    if (selectPaket)  selectPaket.addEventListener('change', updateSummary);
    if (inputTanggal) inputTanggal.addEventListener('change', updateTanggal);
    if (inputLokasi)  inputLokasi.addEventListener('input', updateLokasi);

    updateSummary();
    updateTanggal();
    updateLokasi();
});

/* Copy Clipboard no. rekening (flowbite) */
window.addEventListener('load', function () {
    const $defaultMessage = document.getElementById('copy-default-message');
    const $successMessage = document.getElementById('copy-success-message');
    if (!$defaultMessage || !$successMessage) return;

    const clipboard = window.FlowbiteInstances?.getInstance('CopyClipboard', 'bank-account-copy-text');

    if (clipboard) {
        clipboard.updateOnCopyCallback(function () {
            $defaultMessage.classList.add('hidden');
            $successMessage.classList.remove('hidden');
            $successMessage.classList.add('flex');
            setTimeout(() => {
                $defaultMessage.classList.remove('hidden');
                $successMessage.classList.add('hidden');
                $successMessage.classList.remove('flex');
            }, 2000);
        });
    } else {
        // Fallback: salin manual + ganti pesan
        document.querySelector('[data-copy-to-clipboard-target="bank-account-copy-text"]')
            .addEventListener('click', function () {
                const span = document.getElementById('bank-account-copy-text');
                navigator.clipboard?.writeText(span.textContent.trim());
                $defaultMessage.classList.add('hidden');
                $successMessage.classList.remove('hidden');
                $successMessage.classList.add('flex');
                setTimeout(() => {
                    $defaultMessage.classList.remove('hidden');
                    $successMessage.classList.add('hidden');
                    $successMessage.classList.remove('flex');
                }, 2000);
            });
    }
});
</script>
@endpush
