@extends('layouts.landing')

@section('title', 'Booking Berhasil')

@push('styles')
<style>
    .success-hero {
        background: linear-gradient(rgba(212,115,154,.8), rgba(184,92,133,.8)), url('{{ $landingImages['hero'] }}') center/cover no-repeat;
        padding: 3.5rem 0 2.5rem;
        color: #fff;
        text-align: center;
    }
    .success-icon {
        width: 100px;
        height: 100px;
        border-radius: 50%;
        background: rgba(255,255,255,.2);
        backdrop-filter: blur(6px);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 1rem;
        border: 3px solid rgba(255,255,255,.35);
    }
    .success-icon i { font-size: 3rem; }
    .booking-code-badge {
        display: inline-block;
        background: rgba(255,255,255,.2);
        backdrop-filter: blur(4px);
        border: 1px solid rgba(255,255,255,.3);
        border-radius: 10px;
        padding: .5rem 1.5rem;
        font-size: 1.35rem;
        font-weight: 700;
        letter-spacing: 4px;
        font-family: 'Playfair Display', serif;
    }
    .detail-card, .payment-card, .info-card {
        background: #fff;
        border: none;
        border-radius: 14px;
        box-shadow: 0 2px 16px rgba(0,0,0,.06);
    }
    .detail-card .card-body, .payment-card .card-body, .info-card .card-body {
        padding: 1.25rem;
    }
    .summary-line {
        display: flex;
        justify-content: space-between;
        padding: .6rem 0;
        border-bottom: 1px solid #f0ece8;
        font-size: .9rem;
    }
    .summary-line:last-child { border-bottom: none; }
    .summary-line .label { color: #8a8075; }
    .summary-line .value { font-weight: 600; color: #2d2521; }
    .payment-highlight {
        background: #fef9e7;
        border: 1px solid #ffe082;
        border-radius: 10px;
        padding: 1rem 1.25rem;
    }
    .payment-highlight .amount {
        font-size: 1.3rem;
        font-weight: 700;
        color: #d4739a;
    }
    .bank-info {
        background: #faf6f2;
        border-radius: 10px;
        padding: 1rem 1.25rem;
        margin-top: .75rem;
    }
    .bank-info .label {
        font-size: .78rem;
        color: #8a8075;
        text-transform: uppercase;
        letter-spacing: 1px;
        font-weight: 600;
    }
    .ref-code {
        background: #2d2521;
        color: #ffc107;
        padding: .35rem .85rem;
        border-radius: 6px;
        font-weight: 700;
        letter-spacing: 3px;
        font-size: 1.05rem;
    }
    .btn-pink-outline {
        border: 2px solid #d4739a;
        color: #d4739a;
        background: transparent;
        font-weight: 600;
        padding: .7rem 2rem;
        border-radius: 50px;
        transition: .3s;
    }
    .btn-pink-outline:hover {
        background: #d4739a;
        color: #fff;
    }
    .btn-pink-solid {
        background: #d4739a;
        color: #fff;
        border: none;
        font-weight: 600;
        padding: .7rem 2rem;
        border-radius: 50px;
        transition: .3s;
    }
    .btn-pink-solid:hover {
        background: #b85c85;
        color: #fff;
    }
    .steps-note {
        font-size: .82rem;
        color: #8a8075;
    }
    .steps-note i { color: #d4739a; }
</style>
@endpush

@section('content')

{{-- HERO --}}
<section class="success-hero">
    <div class="container text-center">
        <div class="success-icon">
            <i class="fa-solid fa-circle-check text-white"></i>
        </div>
        <h1 class="font-display font-bold mb-3">Booking Berhasil!</h1>
        <p class="mb-3" style="opacity:.9">Kode booking Anda</p>
        <div class="booking-code-badge">{{ $booking->code }}</div>
    </div>
</section>

<section class="py-16">
    <div class="container" style="max-width: 640px">

        {{-- Ringkasan Booking --}}
        <div class="detail-card">
            <div class="card-body">
                <h5 class="font-display font-bold mb-4" style="color:#d4739a">
                    <i class="fa-solid fa-receipt mr-2"></i>Ringkasan Booking
                </h5>
                <div class="summary-line">
                    <span class="label">Kode Booking</span>
                    <span class="value">{{ $booking->code }}</span>
                </div>
                <div class="summary-line">
                    <span class="label">Nama</span>
                    <span class="value">{{ $booking->name }}</span>
                </div>
                <div class="summary-line">
                    <span class="label">Paket</span>
                    <span class="value">{{ $booking->package->name }}</span>
                </div>
                <div class="summary-line">
                    <span class="label">Harga Paket</span>
                    <span class="value">Rp {{ number_format($booking->package->price, 0, ',', '.') }}</span>
                </div>
                <div class="summary-line">
                    <span class="label">Tanggal Acara</span>
                    <span class="value">{{ $booking->event_date->format('d F Y') }}</span>
                </div>
                @if($booking->location)
                <div class="summary-line">
                    <span class="label">Lokasi</span>
                    <span class="value">{{ $booking->location }}</span>
                </div>
                @endif
                <div class="summary-line">
                    <span class="label">Status</span>
                    <span class="value"><span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold" style="background:#ffc107;color:#2d2521">Menunggu Verifikasi DP</span></span>
                </div>
            </div>
        </div>

        <div class="mt-4 text-center text-sm" style="color:var(--muted);">
            <i class="fa-solid fa-circle-info mr-1" style="color:var(--primary);"></i>
            Booking Anda menunggu verifikasi admin. Status akan berubah <strong>BOOKED</strong> setelah DP1 terverifikasi.
        </div>

        {{-- Action Buttons --}}
        <div class="text-center mt-6 d-flex flex-wrap justify-content-center gap-3">
            @php
                $waMsg = 'Halo Admin ANITA MUA, saya '.$booking->name
                    .' (kode booking '.$booking->code.')'
                    .' sudah submit form booking paket '.$booking->package->name
                    .' untuk tanggal '.$booking->event_date->format('d/m/Y')
                    .'. Mohon konfirmasi verifikasi DP-nya. Terima kasih.';
                $waPhone = $settings['whatsapp'] ?? '6281234567890';
                $waUrl = 'https://wa.me/'.preg_replace('/[^0-9]/', '', $waPhone).'?text='.rawurlencode($waMsg);
            @endphp
            <a href="{{ $waUrl }}" target="_blank" rel="noopener" class="btn px-5 py-2.5 text-base" style="background:#25D366; color:#fff; font-weight:600; border-radius:50px;">
                <i class="fa-brands fa-whatsapp mr-2"></i>Konfirmasi via WhatsApp
            </a>
            @auth
                <a href="{{ route('client.booking', $booking) }}" class="btn-pink-solid px-5 py-2.5 text-base">
                    <i class="fa-solid fa-door-open mr-2"></i>Buka Detail di Portal
                </a>
            @endauth
            <a href="{{ route('home') }}" class="btn-pink-outline px-5 py-2.5 text-base">
                <i class="fa-solid fa-house mr-2"></i>Kembali ke Beranda
            </a>
        </div>

    </div>
</section>
@endsection
