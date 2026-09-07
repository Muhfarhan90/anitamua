@extends('layouts.app')

@section('title', 'Invoice '.$invoice->invoice_number)

@push('styles')
<style>
    .invoice-preview {
        --invoice-plum: #8e2d63;
        --invoice-magenta: #c53b82;
        --invoice-rose: #f8e4ee;
        --invoice-cream: #fffaf6;
        --invoice-ink: #302a2d;
        --invoice-muted: #8b7d84;
        color: var(--invoice-ink);
    }
    .invoice-layout { display: grid; grid-template-columns: minmax(0, 1fr); gap: 1.25rem; align-items: start; }
    .invoice-sheet { position: relative; overflow: hidden; padding: 2rem; background: var(--invoice-cream); border: 1px solid #ecd8e2; border-radius: 1.75rem; box-shadow: 0 18px 45px rgba(142,45,99,.10); }
    .invoice-sheet::before { content: ''; position: absolute; inset: 0 0 auto; height: 8px; background: linear-gradient(90deg, var(--invoice-plum), var(--invoice-magenta), #e8a0bf); }
    .invoice-sheet::after { content: ''; position: absolute; right: -74px; top: 76px; width: 170px; height: 170px; border: 1px solid rgba(197,59,130,.12); border-radius: 50%; box-shadow: 0 0 0 18px rgba(197,59,130,.035), 0 0 0 36px rgba(197,59,130,.025); pointer-events: none; }
    .invoice-masthead { display: flex; justify-content: space-between; align-items: flex-start; gap: 1rem; padding-bottom: 1.5rem; position: relative; z-index: 1; }
    .invoice-logo { display: block; max-width: 190px; max-height: 72px; object-fit: contain; object-position: left center; }
    .invoice-brand-mark { color: var(--invoice-plum); font-family: 'Playfair Display', serif; font-size: 1.6rem; line-height: 1; font-weight: 700; letter-spacing: .04em; }
    .invoice-brand-tagline { color: var(--invoice-muted); font-family: 'Plus Jakarta Sans', sans-serif; font-size: .68rem; font-weight: 700; letter-spacing: .08em; }
    .invoice-brand-address { max-width: 290px; margin-top: .5rem; color: var(--invoice-muted); font-size: .7rem; font-weight: 700; line-height: 1.5; white-space: pre-line; }
    .invoice-brand-contact { margin-top: .15rem; color: var(--invoice-muted); font-size: .65rem; font-weight: 700; line-height: 1.5; }
    .invoice-heading { text-align: right; }
    .invoice-heading h2 { color: var(--invoice-plum); font-family: 'Plus Jakarta Sans', sans-serif; font-size: 2rem; font-weight: 800; line-height: 1; letter-spacing: .08em; }
    .invoice-code { display: inline-block; margin-top: .55rem; padding: .45rem .7rem; border-radius: .65rem; background: var(--invoice-plum); color: #fff; font-size: .7rem; font-weight: 700; letter-spacing: .04em; }
    .invoice-rule { height: 2px; margin-bottom: 1.25rem; background: var(--invoice-plum); }
    .invoice-meta-grid, .invoice-event-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .85rem; margin-bottom: .85rem; position: relative; z-index: 1; }
    .invoice-event-grid { grid-template-columns: repeat(3, minmax(0, 1fr)); }
    .invoice-box { padding: 1rem; border: 1px solid #efd9e4; border-radius: 1rem; background: rgba(255,255,255,.78); }
    .invoice-label { color: var(--invoice-magenta); font-size: .65rem; font-weight: 800; letter-spacing: .13em; text-transform: uppercase; }
    .invoice-box h3 { margin-top: .35rem; color: var(--invoice-ink); font-family: 'Playfair Display', serif; font-size: 1.35rem; line-height: 1.2; }
    .invoice-box p { margin-top: .35rem; color: var(--invoice-muted); font-size: .76rem; font-weight: 700; line-height: 1.55; }
    .invoice-box strong { color: var(--invoice-ink); font-weight: 700; }
    .invoice-contact-field { margin-top: .5rem; }
    .invoice-contact-label { display: block; color: var(--invoice-muted); font-size: .6rem; font-weight: 800; letter-spacing: .08em; text-transform: uppercase; }
    .invoice-contact-value { display: block; margin-top: .12rem; color: var(--invoice-ink); font-size: .76rem; font-weight: 800; line-height: 1.4; }
    .invoice-contact-value--name { font-family: 'Plus Jakarta Sans', sans-serif; font-size: 1.25rem; line-height: 1.2; }
    .invoice-due-date-editor { margin-top: .55rem; padding-top: .65rem; border-top: 1px dashed #e8cbd9; }
    .invoice-due-date-editor label { display: block; color: var(--invoice-magenta); font-size: .62rem; font-weight: 800; letter-spacing: .08em; text-transform: uppercase; }
    .invoice-due-date-editor .invoice-due-date-controls { display: flex; align-items: center; gap: .45rem; margin-top: .35rem; }
    .invoice-due-date-editor input { min-width: 0; flex: 1; padding: .42rem .5rem; border: 1px solid #e9bfd3; border-radius: .55rem; background: #fff; color: var(--invoice-ink); font-size: .72rem; }
    .invoice-due-date-editor button { padding: .42rem .6rem; border-radius: .55rem; background: var(--invoice-plum); color: #fff; font-size: .68rem; font-weight: 700; }
    .invoice-due-date-editor button:hover { background: #74234f; }
    .invoice-due-date-error { margin-top: .35rem; color: #b42318; font-size: .65rem; }
    .invoice-service-box { padding: 1rem; margin-top: .85rem; border: 1px solid #ead6e1; border-radius: 1rem; background: #fff; position: relative; z-index: 1; }
    .invoice-service-heading { display: flex; align-items: center; justify-content: space-between; gap: 1rem; margin-bottom: .7rem; }
    .invoice-service-heading h3 { color: var(--invoice-plum); font-family: 'Plus Jakarta Sans', sans-serif; font-size: .85rem; font-weight: 800; letter-spacing: .03em; }
    .invoice-service-heading span { padding: .3rem .6rem; border-radius: 999px; background: var(--invoice-rose); color: var(--invoice-plum); font-size: .62rem; font-weight: 700; }
    .invoice-table { width: 100%; border-collapse: separate; border-spacing: 0; overflow: hidden; font-size: .78rem; }
    .invoice-table th { padding: .65rem .7rem; background: var(--invoice-plum); color: #fff; font-size: .62rem; font-weight: 800; letter-spacing: .08em; text-align: left; text-transform: uppercase; }
    .invoice-table th:first-child { border-radius: .65rem 0 0 .65rem; }
    .invoice-table th:last-child { border-radius: 0 .65rem .65rem 0; }
    .invoice-table td { padding: .78rem .7rem; border-bottom: 1px solid #f1e7ec; color: #50474c; }
    .invoice-table tr:last-child td { border-bottom: 0; }
    .invoice-table .text-right { text-align: right; }
    .invoice-table .text-center { text-align: center; }
    .invoice-table td:last-child { color: var(--invoice-plum); font-weight: 800; }
    .invoice-bottom { display: grid; grid-template-columns: minmax(0, 1fr) 290px; gap: 1rem; margin-top: 1rem; align-items: stretch; position: relative; z-index: 1; }
    .invoice-note { display: flex; align-items: flex-end; padding: .8rem .2rem; color: var(--invoice-muted); font-size: .72rem; line-height: 1.55; white-space: pre-line; }
    .invoice-totals { padding: 1.1rem; border-radius: 1rem; background: #fff; border: 1px solid #ead6e1; }
    .invoice-total-row { display: flex; justify-content: space-between; gap: 1rem; padding: .35rem 0; color: var(--invoice-muted); font-size: .76rem; }
    .invoice-total-row--grand { padding-bottom: .65rem; border-bottom: 2px solid #d4b896; color: var(--invoice-ink); font-weight: 800; }
    .invoice-total-row--grand strong { color: var(--invoice-ink); }
    .invoice-total-row strong { color: var(--invoice-ink); }
    .invoice-total-row--paid strong { color: #16834b; }
    .invoice-paid-list { margin: .75rem 0; padding: .35rem .75rem; border: 1px solid #86efac; border-radius: .7rem; background: #f0fdf4; }
    .invoice-paid-row { display: flex; justify-content: space-between; gap: .75rem; padding: .45rem 0; border-bottom: 1px solid #d1fae5; color: #166534; font-size: .74rem; font-weight: 700; }
    .invoice-paid-row:last-child { border-bottom: 0; }
    .invoice-paid-row strong { color: #16a34a; white-space: nowrap; }
    .invoice-paid-empty { padding: .45rem 0; color: var(--invoice-muted); font-size: .7rem; }
    .invoice-total-row--due { margin: .75rem 0 0; padding: .45rem .75rem; border: 1px solid #f6c58d; border-radius: .7rem; background: #fff4e8; color: #9a3412; font-size: .74rem; font-weight: 800; }
    .invoice-total-row--due strong { color: #ea580c; font-size: .74rem; }
    .invoice-finance-grid { display: grid; grid-template-columns: minmax(0, 1fr); gap: 1rem; margin-top: 1rem; position: relative; z-index: 1; }
    .invoice-finance-box { padding: 1rem; border: 1px solid #ead6e1; border-radius: 1rem; background: #fff; }
    .invoice-finance-heading { display: flex; align-items: center; justify-content: space-between; gap: 1rem; margin-bottom: .8rem; }
    .invoice-finance-heading h3 { color: var(--invoice-plum); font-family: 'Plus Jakarta Sans', sans-serif; font-size: .82rem; font-weight: 800; }
    .invoice-status-inline { padding: .3rem .55rem; border-radius: 999px; background: #fff7e9; color: #a65b12; font-size: .6rem; font-weight: 800; }
    .invoice-finance-box .invoice-payment-list { gap: .7rem; }
    .invoice-finance-box .invoice-payment-row { padding-left: .85rem; }
    .invoice-bank-box { padding: .75rem 0 0; border: 0; border-top: 1px solid #ead6e1; border-radius: 0; background: transparent; }
    .invoice-bank-name { margin-top: .6rem; color: var(--invoice-ink); font-family: 'Playfair Display', serif; font-size: 1.2rem; font-weight: 700; }
    .invoice-bank-detail { margin-top: .4rem; color: var(--invoice-muted); font-size: .76rem; line-height: 1.7; }
    .invoice-side-panel { padding: 1.35rem; border: 1px solid #ead6e1; border-radius: 1.5rem; background: #fff; box-shadow: 0 14px 35px rgba(142,45,99,.08); }
    .invoice-side-title { display: flex; align-items: center; gap: .7rem; padding-bottom: 1rem; border-bottom: 1px solid #f1e7ec; }
    .invoice-side-icon { display: inline-flex; align-items: center; justify-content: center; width: 2.1rem; height: 2.1rem; border-radius: .7rem; background: var(--invoice-rose); color: var(--invoice-plum); }
    .invoice-side-title h3 { color: var(--invoice-ink); font-family: 'Playfair Display', serif; font-size: 1.15rem; }
    .invoice-status { margin: 1rem 0; padding: .85rem; border-radius: .9rem; background: #fff7e9; border: 1px solid #f4d19a; }
    .invoice-status-label { color: #a65b12; font-size: .62rem; font-weight: 800; letter-spacing: .12em; text-transform: uppercase; }
    .invoice-status-value { margin-top: .2rem; color: #9a4e0a; font-size: 1.25rem; font-weight: 800; }
    .invoice-payment-list { display: grid; gap: .85rem; }
    .invoice-payment-row { position: relative; padding-left: 1rem; border-left: 2px solid #edc0d4; }
    .invoice-payment-row::before { content: ''; position: absolute; left: -.36rem; top: .2rem; width: .58rem; height: .58rem; border-radius: 50%; background: var(--invoice-magenta); box-shadow: 0 0 0 3px #fff, 0 0 0 4px #edc0d4; }
    .invoice-payment-main { display: flex; align-items: flex-start; justify-content: space-between; gap: .5rem; }
    .invoice-payment-main strong { color: var(--invoice-ink); font-size: .76rem; }
    .invoice-payment-main span { color: var(--invoice-plum); font-size: .72rem; font-weight: 800; white-space: nowrap; }
    .invoice-payment-date { margin-top: .2rem; color: var(--invoice-muted); font-size: .65rem; }
    .invoice-payment-state { display: inline-block; margin-top: .35rem; padding: .2rem .45rem; border-radius: 999px; background: #f5f1f3; color: #766b71; font-size: .59rem; font-weight: 700; }
    .invoice-payment-state--verified { background: #e9f8ef; color: #16834b; }
    @media (max-width: 1023px) { .invoice-layout { grid-template-columns: 1fr; } }
    @media (max-width: 639px) { .invoice-sheet { padding: 1.1rem; border-radius: 1.15rem; } .invoice-masthead { flex-direction: column; } .invoice-heading { text-align: left; } .invoice-heading h2 { font-size: 1.65rem; } .invoice-meta-grid, .invoice-event-grid, .invoice-bottom, .invoice-finance-grid { grid-template-columns: 1fr; } .invoice-table { min-width: 510px; } .invoice-service-box { overflow-x: auto; } }
    @media print {
        @page { size: A4; margin: 10mm; }
        body { background: #fff !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        #sidebar, #sidebarOverlay, .invoice-screen-header, .invoice-cancel-alert,
        body > div.min-h-screen > header, body > div.min-h-screen > .fixed,
        .invoice-side-panel { display: none !important; }
        body > div.min-h-screen { margin-left: 0 !important; min-height: 0 !important; }
        body > div.min-h-screen > main { padding: 0 !important; }
        .invoice-layout { display: block !important; }
        .invoice-sheet { width: 100%; border-radius: 0 !important; box-shadow: none !important; }
        .invoice-sheet::after { display: none; }
        .invoice-due-date-editor { display: none !important; }
    }
</style>
@endpush

@section('content')
@php
    $booking = $invoice->booking;
    $settings = $settings ?? [];
    $invoiceGreeting = $settings['invoice_greeting'] ?? 'Terima kasih telah mempercayakan momen spesial Anda kepada ANITA. Invoice ini mengikuti status pembayaran yang sudah diverifikasi.';
    $payments = $booking->payments->sortBy('created_at');
    $verifiedPayments = $payments->where('status', 'verified');
@endphp

<div class="invoice-screen-header">
<x-page-header :title="$invoice->invoice_number" subtitle="Invoice booking {{ $booking->code }}">
    <x-slot:actions>
        <button type="button" onclick="window.print()" class="inline-flex items-center gap-2 rounded-xl bg-brand-dark px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-darker focus:outline-none focus:ring-2 focus:ring-brand-200">
            <i class="fas fa-print"></i> Cetak Invoice
        </button>
    </x-slot:actions>
</x-page-header>
</div>

@if($booking->status === \App\Models\Booking::STATUS_CANCELLED)
<div class="invoice-cancel-alert mb-5 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
    Booking dibatalkan. DP yang sudah dibayar dinyatakan hangus.
</div>
@endif

<div class="invoice-preview">
    <div class="invoice-layout">
        <article class="invoice-sheet">
            <header class="invoice-masthead">
                <div>
                    @if(!empty($settings['logo']))
                    <img class="invoice-logo" src="{{ asset('storage/'.$settings['logo']) }}" alt="{{ $settings['company_name'] ?? 'ANITA' }}">
                    <div class="invoice-brand-address">{{ $settings['address'] ?? '-' }}</div>
                    <div class="invoice-brand-contact">Tel: {{ $settings['phone'] ?? '-' }}<br>Email: {{ $settings['email'] ?? '-' }}</div>
                    @else
                    <div class="invoice-brand-mark">{{ $settings['company_name'] ?? 'ANITA' }} <span class="invoice-brand-tagline">- {{ $settings['tagline'] ?? 'Makeup Artist' }}</span></div>
                    <div class="invoice-brand-address">{{ $settings['address'] ?? '-' }}</div>
                    <div class="invoice-brand-contact">Tel: {{ $settings['phone'] ?? '-' }}<br>Email: {{ $settings['email'] ?? '-' }}</div>
                    @endif
                </div>
                <div class="invoice-heading">
                    <h2>INVOICE</h2>
                    <span class="invoice-code">{{ $invoice->invoice_number }}</span>
                </div>
            </header>

            <div class="invoice-rule"></div>

            <div class="invoice-meta-grid">
                <section class="invoice-box">
                    <div class="invoice-label">Diterbitkan kepada</div>
                    <div class="invoice-contact-field">
                        <span class="invoice-contact-label">Nama pengantin</span>
                        <strong class="invoice-contact-value invoice-contact-value--name">{{ $booking->name ?: '-' }}</strong>
                    </div>
                    <div class="invoice-contact-field">
                        <span class="invoice-contact-label">No. WhatsApp</span>
                        <strong class="invoice-contact-value">{{ $booking->phone ?: '-' }}</strong>
                    </div>
                    <div class="invoice-contact-field">
                        <span class="invoice-contact-label">Email</span>
                        <strong class="invoice-contact-value">{{ $booking->email ?: '-' }}</strong>
                    </div>
                </section>
                <section class="invoice-box">
                    <div class="invoice-label">Detail invoice</div>
                    <p>Tanggal terbit<br><strong>{{ $invoice->issue_date?->format('d M Y') ?: '-' }}</strong></p>
                    <p class="invoice-due-date-print">Jatuh tempo pelunasan<br><strong>{{ $invoice->due_date?->format('d M Y') ?: '-' }}</strong></p>
                    @if(in_array(auth()->user()->role ?? '', ['owner', 'admin'], true))
                    <form method="POST" action="{{ route('admin.invoices.due-date.update', $invoice) }}" class="invoice-due-date-editor">
                        @csrf
                        @method('PATCH')
                        <label for="due_date">Ubah jatuh tempo</label>
                        <div class="invoice-due-date-controls">
                            <input id="due_date" name="due_date" type="date" value="{{ old('due_date', $invoice->due_date?->format('Y-m-d')) }}" required>
                            <button type="submit">Simpan</button>
                        </div>
                        @error('due_date')<div class="invoice-due-date-error">{{ $message }}</div>@enderror
                    </form>
                    @endif
                    <p>Kode booking<br><strong>{{ $booking->code }}</strong></p>
                </section>
            </div>

            <div class="invoice-event-grid">
                <div class="invoice-box"><div class="invoice-label">Tanggal acara</div><p><strong>{{ $booking->event_date?->format('d M Y') ?: '-' }}</strong></p></div>
                <div class="invoice-box"><div class="invoice-label">Lokasi</div><p><strong>{{ $booking->location ?: '-' }}</strong></p></div>
                <div class="invoice-box"><div class="invoice-label">Paket</div><p><strong>{{ $booking->package?->name ?: '-' }}</strong></p></div>
            </div>

            <section class="invoice-service-box">
                <div class="invoice-service-heading">
                    <h3>Rincian layanan</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="invoice-table">
                        <thead><tr><th>Layanan</th><th class="text-center">Qty</th><th class="text-right">Harga</th><th class="text-right">Total</th></tr></thead>
                        <tbody>
                            @foreach($invoice->items ?? [] as $item)
                            <tr><td>{{ $item['name'] }}</td><td class="text-center">{{ $item['quantity'] ?? 1 }}</td><td class="text-right">Rp {{ number_format($item['price'] ?? 0, 0, ',', '.') }}</td><td class="text-right">Rp {{ number_format($item['total'] ?? 0, 0, ',', '.') }}</td></tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>

            <div class="invoice-bottom">
                    <div class="invoice-note">{{ $invoiceGreeting }}</div>
                <section class="invoice-totals">
                    <div class="invoice-total-row invoice-total-row--grand"><span>Jumlah total</span><strong>Rp {{ number_format($invoice->total_amount, 0, ',', '.') }}</strong></div>
                    <div class="invoice-paid-list">
                        @forelse($verifiedPayments as $payment)
                        <div class="invoice-paid-row"><span>{{ \App\Models\Payment::typeLabel($payment->type) }}</span><strong>Rp {{ number_format($payment->amount, 0, ',', '.') }}</strong></div>
                        @empty
                        <div class="invoice-paid-empty">Belum ada pembayaran terverifikasi.</div>
                        @endforelse
                    </div>
                    <div class="invoice-total-row invoice-total-row--due"><span>Sisa tagihan</span><strong>Rp {{ number_format($invoice->remaining_amount, 0, ',', '.') }}</strong></div>
                </section>
            </div>

            <div class="invoice-finance-grid">
                <section class="invoice-finance-box invoice-bank-box">
                    <div class="invoice-label">Informasi rekening</div>
                    <div class="invoice-bank-name">{{ $settings['bank_name'] ?? '-' }}</div>
                    <div class="invoice-bank-detail">No. rekening: <strong>{{ $settings['bank_account_number'] ?? '-' }}</strong><br>A/N: <strong>{{ $settings['bank_account_name'] ?? '-' }}</strong></div>
                </section>
            </div>
        </article>
    </div>
</div>
@endsection
