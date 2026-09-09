<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 20px; }
        * { box-sizing: border-box; }
        body { margin: 0; background: #f8f1f5; color: #302a2d; font-family: DejaVu Sans, sans-serif; font-size: 9px; line-height: 1.4; }
        .paper { position: relative; padding: 20px 22px; border: 1px solid #ead4df; background: #fffaf6; }
        .paper:before { content: ''; position: absolute; top: 0; left: 0; right: 0; height: 7px; background: #8e2d63; }
        .table { display: table; width: 100%; }
        .cell { display: table-cell; vertical-align: top; }
        .right { text-align: right; }
        .logo { max-width: 150px; max-height: 58px; margin-bottom: 5px; object-fit: contain; object-position: left center; }
        .masthead { padding: 8px 0 14px; border-bottom: 2px solid #8e2d63; }
        .brand { color: #8e2d63; font-family: DejaVu Sans, sans-serif; font-size: 17px; font-weight: bold; letter-spacing: 1px; }
        .brand-tagline { color: #8b7d84; font-family: DejaVu Sans, sans-serif; font-size: 7px; font-weight: bold; letter-spacing: .5px; }
        .brand-address { max-width: 280px; margin-top: 3px; color: #8b7d84; font-size: 7px; font-weight: bold; line-height: 1.5; }
        .brand-contact { margin-top: 1px; color: #8b7d84; font-size: 7px; font-weight: bold; line-height: 1.5; }
        .invoice-title { display: inline-block; padding: 9px 13px; border-radius: 6px; background: #8e2d63; color: #fff; font-size: 20px; font-weight: bold; letter-spacing: 1px; }
        .invoice-code { margin-top: 5px; color: #8e2d63; font-size: 8px; font-weight: bold; }
        .section-gap { height: 12px; }
        .box { padding: 9px 10px; border: 1px solid #efd9e4; border-radius: 7px; background: #fff; }
        .box-pink { background: #fff0f6; border-color: #e9b4ce; }
        .box + .box { margin-left: 8px; }
        .label { color: #c53b82; font-size: 7px; font-weight: bold; letter-spacing: 1px; text-transform: uppercase; }
        .client-name { margin-top: 4px; color: #302a2d; font-family: DejaVu Sans, sans-serif; font-size: 14px; font-weight: bold; }
        .muted { color: #8b7d84; }
        .contact-field { margin-top: 5px; }
        .contact-label { color: #8b7d84; font-size: 7px; font-weight: bold; letter-spacing: .5px; text-transform: uppercase; }
        .contact-value { margin-top: 1px; color: #302a2d; font-size: 9px; font-weight: bold; }
        .contact-value.name { font-family: DejaVu Sans, sans-serif; font-size: 14px; }
        .detail { margin-top: 5px; color: #8b7d84; font-weight: bold; line-height: 1.65; }
        .detail strong { color: #302a2d; }
        .event-box { width: 33.333%; }
        .event-box .box { min-height: 42px; }
        .service { padding: 9px 10px 10px; border: 1px solid #ead6e1; border-radius: 7px; background: #fff; }
        .service-title { color: #8e2d63; font-size: 9px; font-weight: bold; }
        .service-count { display: inline-block; padding: 3px 6px; border-radius: 10px; background: #f8e4ee; color: #8e2d63; font-size: 7px; font-weight: bold; }
        table.items { width: 100%; border-collapse: separate; border-spacing: 0; margin-top: 8px; }
        .items th { padding: 6px 5px; background: #8e2d63; color: #fff; font-size: 7px; font-weight: bold; letter-spacing: .5px; text-align: left; text-transform: uppercase; }
        .items th:first-child { border-radius: 5px 0 0 5px; }
        .items th:last-child { border-radius: 0 5px 5px 0; }
        .items td { padding: 7px 5px; border-bottom: 1px solid #f1e7ec; color: #50474c; }
        .items tr:last-child td { border-bottom: 0; }
        .summary-wrap { margin-top: 10px; }
        .summary-note { width: 57%; padding: 8px 0; color: #8b7d84; font-size: 7.5px; vertical-align: bottom; }
        .summary-box { width: 43%; padding: 9px; border: 1px solid #ead6e1; border-radius: 7px; background: #fff; }
        .summary-row { padding: 3px 0; color: #8b7d84; }
        .summary-row.total td { padding-bottom: 7px; border-bottom: 2px solid #d4b896; color: #302a2d; font-weight: bold; }
        .summary-row td:last-child { color: #302a2d; font-weight: bold; text-align: right; }
        .paid-list { margin: 8px 0; padding: 3px 8px; border: 1px solid #86efac; border-radius: 5px; background: #f0fdf4; }
        .paid-item { padding: 5px 0; border-bottom: 1px solid #d1fae5; color: #166534; font-weight: bold; }
        .paid-item:last-child { border-bottom: 0; }
        .paid-item .cell:last-child { color: #16a34a; font-weight: bold; text-align: right; }
        .paid-empty { padding: 5px 0; color: #8b7d84; font-size: 7px; }
        .summary-row.due td { padding: 5px 7px; border-top: 1px solid #f6c58d; border-bottom: 1px solid #f6c58d; background: #fff4e8; color: #9a3412; font-size: 7px; font-weight: bold; }
        .summary-row.due td:first-child { border-radius: 5px 0 0 5px; }
        .summary-row.due td:last-child { border-radius: 0 5px 5px 0; color: #ea580c; font-size: 8px; }
        .bank { margin-top: 14px; padding: 8px 0 0; border-top: 1px solid #ead6e1; color: #50474c; }
        .bank strong { color: #302a2d; }
        .footer { margin-top: 14px; padding-top: 9px; border-top: 1px solid #ead6e1; color: #9b8d94; font-size: 7px; text-align: center; }
    </style>
</head>
<body>
@php
    $booking = $invoice->booking;
    $logoSrc = $logoSrc ?? null;
    $payments = $booking->payments->sortBy('created_at');
    $verifiedPayments = $payments->where('status', 'verified');
    $invoiceGreeting = $settings['invoice_greeting'] ?? 'Terima kasih telah mempercayakan momen spesial Anda kepada ANITA. Invoice ini mengikuti status pembayaran yang sudah diverifikasi.';
@endphp
<div class="paper">
    <div class="masthead table">
        <div class="cell">
            @if($logoSrc)
            <img class="logo" src="{{ $logoSrc }}" alt="Logo">
            <div class="brand-address">{{ $settings['address'] ?? '-' }}</div>
            <div class="brand-contact">Tel: {{ $settings['phone'] ?? '-' }}<br>Email: {{ $settings['email'] ?? '-' }}</div>
            @else
            <div class="brand">{{ $settings['company_name'] ?? 'ANITA MUA' }} <span class="brand-tagline">- {{ $settings['tagline'] ?? 'Makeup Artist' }}</span></div>
            <div class="brand-address">{{ $settings['address'] ?? '-' }}</div>
            <div class="brand-contact">Tel: {{ $settings['phone'] ?? '-' }}<br>Email: {{ $settings['email'] ?? '-' }}</div>
            @endif
        </div>
        <div class="cell right">
            <div class="invoice-title">INVOICE</div>
            <div class="invoice-code">{{ $invoice->invoice_number }}</div>
        </div>
    </div>

    @if($booking->status === \App\Models\Booking::STATUS_CANCELLED)
    <div class="section-gap"></div><div class="box box-pink"><strong>BOOKING DIBATALKAN</strong><br>DP yang sudah dibayar dinyatakan hangus.</div>
    @endif

    <div class="section-gap"></div>
    <div class="table">
        <div class="cell box" style="width:55%;">
            <div class="label">Diterbitkan kepada</div>
            <div class="contact-field"><div class="contact-label">Nama pengantin</div><div class="contact-value name">{{ $booking->name ?: '-' }}</div></div>
            <div class="contact-field"><div class="contact-label">No. WhatsApp</div><div class="contact-value">{{ $booking->phone ?: '-' }}</div></div>
            <div class="contact-field"><div class="contact-label">Email</div><div class="contact-value">{{ $booking->email ?: '-' }}</div></div>
        </div>
        <div class="cell box right" style="width:45%;">
            <div class="label">Detail invoice</div>
            <div class="detail">Tanggal terbit<br><strong>{{ $invoice->issue_date?->format('d M Y') ?: '-' }}</strong><br>Jatuh tempo <strong>{{ $invoice->due_date?->format('d M Y') ?: '-' }}</strong><br>Kode booking <strong>{{ $booking->code }}</strong></div>
        </div>
    </div>

    <div class="section-gap"></div>
    <div class="table">
        <div class="cell event-box"><div class="box"><div class="label">Tanggal acara</div><div class="detail"><strong>{{ $booking->event_date?->format('d M Y') ?: '-' }}</strong></div></div></div>
        <div class="cell event-box"><div class="box"><div class="label">Lokasi</div><div class="detail"><strong>{{ $booking->location ?: '-' }}</strong></div></div></div>
        <div class="cell event-box"><div class="box"><div class="label">Paket</div><div class="detail"><strong>{{ $booking->package?->name ?: '-' }}</strong></div></div></div>
    </div>

    <div class="section-gap"></div>
    <div class="service">
        <div class="service-title">Rincian layanan</div>
        <table class="items"><thead><tr><th>Layanan</th><th style="width:42px;text-align:center;">Qty</th><th style="width:100px;text-align:right;">Harga</th><th style="width:100px;text-align:right;">Total</th></tr></thead><tbody>
            @foreach($invoice->items ?? [] as $item)<tr><td>{{ $item['name'] }}</td><td style="text-align:center;">{{ $item['quantity'] ?? 1 }}</td><td style="text-align:right;">Rp {{ number_format($item['price'] ?? 0, 0, ',', '.') }}</td><td style="text-align:right;font-weight:bold;color:#8e2d63;">Rp {{ number_format($item['total'] ?? 0, 0, ',', '.') }}</td></tr>@endforeach
        </tbody></table>
    </div>

    <div class="summary-wrap table">
        <div class="cell summary-note">{!! nl2br(e($invoiceGreeting)) !!}</div>
        <div class="cell summary-box">
            <table style="width:100%;border-collapse:collapse;"><tr class="summary-row total"><td>Jumlah Total</td><td>Rp {{ number_format($invoice->total_amount, 0, ',', '.') }}</td></tr></table>
            <div class="paid-list">
                @forelse($verifiedPayments as $payment)
                <div class="paid-item table"><div class="cell">{{ \App\Models\Payment::typeLabel($payment->type) }}</div><div class="cell">Rp {{ number_format($payment->amount, 0, ',', '.') }}</div></div>
                @empty
                <div class="paid-empty">Belum ada pembayaran terverifikasi.</div>
                @endforelse
            </div>
            <table style="width:100%;border-collapse:collapse;"><tr class="summary-row due"><td>Sisa Tagihan</td><td>Rp {{ number_format($invoice->remaining_amount, 0, ',', '.') }}</td></tr></table>
        </div>
    </div>

    <div class="bank"><div class="label">Informasi rekening</div><strong>{{ $settings['bank_name'] ?? '-' }}</strong><br>{{ $settings['bank_account_number'] ?? '-' }}<br>a/n {{ $settings['bank_account_name'] ?? '-' }}</div>
    <div class="footer">Dicetak {{ now()->format('d M Y H:i') }} WIB</div>
</div>
</body>
</html>
