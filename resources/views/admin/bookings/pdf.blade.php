<!doctype html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <title>Detail Booking - {{ $booking->name }} - {{ now()->format('d/m/Y') }}</title>
    <style>
        @page {
            size: A4;
            margin: 12mm;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            color: #302a2d;
            font-family: DejaVu Sans, Arial, sans-serif;
            font-size: 20px;
            line-height: 1.6;
            background: #f7f3f5;
        }

        .screen-bar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            max-width: 190mm;
            margin: 16px auto 10px;
            color: #756a70;
            font-size: 20px;
        }

        .print-button {
            border: 0;
            border-radius: 8px;
            padding: 9px 13px;
            cursor: pointer;
            background: #b94f80;
            color: #fff;
            font: inherit;
            font-weight: bold;
        }

        .print-sheet {
            width: 210mm;
            min-height: 297mm;
            margin: 0 auto 20px;
            padding: 18mm 16mm;
            background: #fff;
            box-shadow: 0 8px 30px rgba(48, 42, 45, .09);
        }

        .header {
            width: 100%;
            padding-bottom: 12px;
            border-bottom: 3px solid #b94f80;
        }

        .header td {
            vertical-align: bottom;
        }

        .brand {
            color: #8e2d63;
            font-size: 20px;
            font-weight: bold;
            letter-spacing: 1px;
        }

        h1 {
            margin: 4px 0 0;
            color: #302a2d;
            font-size: 30px;
            line-height: 1.2;
        }

        .header-meta {
            color: #6e6268;
            text-align: right;
            font-size: 16px;
        }

        .code {
            color: #8e2d63;
            font-size: 20px;
            font-weight: bold;
        }

        .intro {
            margin: 12px 0 18px;
            color: #6e6268;
        }

        section {
            margin-top: 20px;
        }

        h2 {
            margin: 0 0 10px;
            padding: 0 0 6px;
            border-bottom: 1px solid #e7bfd1;
            color: #8e2d63;
            font-size: 20px;
        }

        h3 {
            margin: 14px 0 6px;
            color: #5b3548;
            font-size: 18px;
        }

        .data,
        .listing {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        .data th,
        .data td {
            padding: 6px 8px;
            border-bottom: 1px solid #f1e7ec;
            vertical-align: top;
            text-align: left;
            overflow-wrap: anywhere;
        }

        .data th {
            width: 32%;
            color: #756a70;
            font-weight: normal;
        }

        .listing th,
        .listing td {
            padding: 6px 8px;
            border-bottom: 1px solid #f1e7ec;
            vertical-align: top;
            text-align: left;
            overflow-wrap: anywhere;
        }

        .listing thead th {
            background: #f8eaf1;
            color: #69354e;
            font-weight: bold;
        }

        tr,
        .photo-block,
        .entry {
            page-break-inside: avoid;
        }

        .amount {
            text-align: right !important;
            white-space: nowrap;
        }

        .summary {
            margin-top: 10px;
            padding: 10px 12px;
            background: #fff5f9;
            border-left: 3px solid #b94f80;
        }

        .summary p {
            margin: 3px 0;
        }

        .muted {
            color: #85777e;
        }

        .entry {
            padding: 8px 0;
            border-bottom: 1px solid #f1e7ec;
        }

        .entry-title {
            font-weight: bold;
        }

        .notes {
            white-space: pre-line;
        }

        .photo-section {
            display: flex;
            flex-direction: column;
            gap: 8px;
            margin-top: 10px;
        }

        .photo-section .photo-block {
            display: inline-block;
            width: auto;
            margin: 0 8px 8px 0;
            vertical-align: top;
        }

        .photo-section .photo-block img {
            display: block;
            width: auto;
            height: 300px;
            max-width: 100%;
            object-fit: contain;
        }

        .photo-gallery {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }

        .photo-gallery .photo-block {
            margin: 0;
        }

        .photo-gallery img {
            height: 300px;
            width: auto;
            max-width: 100%;
            object-fit: contain;
        }

        .photo-block .caption {
            color: #756a70;
            font-size: 18px;
        }

        .footer {
            margin-top: 20px;
            padding-top: 5px;
            border-top: 1px solid #ead6e1;
            color: #998b91;
            font-size: 16px;
        }

        @media (max-width: 720px) {
            .screen-bar {
                margin: 12px;
            }

            .print-sheet {
                width: 100%;
                min-height: 0;
                margin: 0;
                padding: 18px;
                box-shadow: none;
            }
        }

        @media print {
            @page {
                size: A4;
                margin: 12mm;
            }

            html,
            body {
                width: auto;
                margin: 0;
                padding: 0;
                background: #fff;
                font-size: 18px20 -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            .screen-bar {
                display: none !important;
            }

            .print-sheet {
                width: auto;
                min-height: 0;
                margin: 0;
                padding: 0;
                box-shadow: none;
            }
        }
    </style>
</head>

<body>
    <div class="screen-bar">
        <span>Dialog cetak akan terbuka. Di ponsel, pilih Print atau Simpan sebagai PDF.</span>
        <button type="button" class="print-button" onclick="window.print()">Cetak Booking</button>
    </div>

    <main class="print-sheet">
        @php
            $survey = $booking->survey;

            $choice = function (string $field) use ($survey): string {
                $value = $survey?->{$field};

                return $value === 'Lainnya' && filled($survey?->{$field . '_other'})
                    ? 'Lainnya - ' . $survey->{$field . '_other'}
                    : (filled($value)
                        ? $value
                        : '');
            };

            $tentItems = function ($items, $quantities, $other): string {
                $values = collect((array) $items)
                    ->map(function ($item) use ($quantities) {
                        $quantity = $quantities[$item] ?? null;

                        return $item . ($quantity !== null && $quantity !== '' ? ' x ' . $quantity : '');
                    })
                    ->all();

                if (in_array('Lainnya', (array) $items, true) && filled($other)) {
                    $values[] = 'Lainnya: ' . $other;
                }

                return $values ? implode(', ', $values) : '';
            };

            $imageSrc = function (?string $path): ?string {
                if (!$path || !\Illuminate\Support\Facades\Storage::disk('public')->exists($path)) {
                    return null;
                }

                $disk = \Illuminate\Support\Facades\Storage::disk('public');
                $mime = $disk->mimeType($path);

                return in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true)
                    ? 'data:' . $mime . ';base64,' . base64_encode($disk->get($path))
                    : null;
            };

            $equipmentFields = [
                'gallery_booth' => 'Gallery booth',
                'envelope_box' => 'Kotak amplop',
                'fruit_shed' => 'Saung buah',
                'akad_table' => 'Meja akad',
                'diesel_lights' => 'Diesel + lampu',
                'photo_stand' => 'Stand foto',
                'carpet' => 'Karpet',
                'vip_table' => 'Meja VIP',
                'snack_shed' => 'Saung jajan',
                'blower' => 'Blower',
                'welcome_sign' => 'Welcome sign',
                'center_point' => 'Center point',
            ];

            $clientPhone = filled($booking->client?->phone) ? $booking->client->phone : $booking->phone;

            $clientEmail = filled($booking->email) ? $booking->email : $booking->client?->email;

            $eventDateTime = trim(
                ($booking->event_date?->format('d M Y') ?? '') .
                    ($booking->event_time ? ' - ' . substr($booking->event_time, 0, 5) . ' WIB' : ''),
                ' -',
            );

            $bookingRows = [
                ['Nama pengantin', $booking->name],
                ['Telepon', $clientPhone],
                ['Email', $clientEmail],
                ['Jenis acara', $booking->event_type],
                ['Tanggal dan jam acara', $eventDateTime],
                ['Lokasi', $booking->location],
                ['Link Maps', $booking->maps_url],
            ];

            $bookingRows = array_filter($bookingRows, fn($row) => filled($row[1]));

            $surveyRows = [];

            if ($survey) {
                $surveyDatePic = implode(
                    ' / ',
                    array_filter([$booking->survey_date?->format('d M Y'), $survey->pic], fn($value) => filled($value)),
                );

                $flowerFabric = implode(
                    ' / ',
                    array_filter([$survey->flower_color, $survey->fabric_color], fn($value) => filled($value)),
                );

                $chairStage = implode(
                    ' / ',
                    array_filter([$choice('chair_option'), $choice('stage_option')], fn($value) => filled($value)),
                );

                $buffetTableware = implode(
                    ' / ',
                    array_filter([$choice('buffet'), $choice('tableware')], fn($value) => filled($value)),
                );

                $surveyRows = [
                    ['Tanggal survey / PIC', $surveyDatePic],
                    ['Pelaminan', $survey->weddingStage?->name],
                    ['Warna bunga / kain', $flowerFabric],
                    ['Ukuran pelaminan', $choice('stage_size')],
                    ['Kursi / panggung', $chairStage],
                    ['Model tenda', $survey->tent?->name ?: $choice('tent_shape')],
                    [
                        'Ukuran tenda',
                        $tentItems($survey->tent_sizes, $survey->tent_size_quantities, $survey->tent_sizes_other),
                    ],
                    [
                        'Tambahan tenda',
                        $tentItems(
                            $survey->tent_additions,
                            $survey->tent_addition_quantities,
                            $survey->tent_additions_other,
                        ),
                    ],
                    ['Pintu masuk', $survey->entranceGate?->name ?: $choice('entrance')],
                    ['Prasmanan / alat makan', $buffetTableware],
                ];

                foreach ($equipmentFields as $field => $label) {
                    $surveyRows[] = [$label, $choice($field)];
                }

                $surveyRows[] = ['Catatan survey', $survey->notes];
                $surveyRows = array_filter($surveyRows, fn($row) => filled($row[1]));
            }
        @endphp

        <table class="header">
            <tr>
                <td>
                    <div class="brand">{{ $companyName }}</div>
                    <h1>Detail Booking</h1>
                </td>
                <td class="header-meta">
                    <div class="code">Kode Booking: {{ $booking->code }}</div>
                    <div>Dicetak {{ now('Asia/Jakarta')->format('d M Y H:i') }} WIB</div>
                </td>
            </tr>
        </table>

        @if (count($bookingRows))
            <section>
                <h2>Informasi Booking dan Klien</h2>
                <table class="data">
                    @foreach ($bookingRows as [$label, $value])
                        <tr>
                            <th>{{ $label }}</th>
                            <td>{{ $value }}</td>
                        </tr>
                    @endforeach
                </table>
            </section>
        @endif

        <section>
            <h2>Data Survey</h2>

            @if (!$survey)
                <p class="muted">Belum ada data survey.</p>
            @else
                @if (count($surveyRows))
                    <table class="data">
                        @foreach ($surveyRows as [$label, $value])
                            <tr>
                                <th>{{ $label }}</th>
                                <td class="{{ $label === 'Catatan survey' ? 'notes' : '' }}">{{ $value }}</td>
                            </tr>
                        @endforeach
                    </table>
                @endif

                @foreach ([
        'Pelaminan' => $survey->wedding_stage_photo_path,
        'Tenda' => $survey->tent_photo_path,
        'Gapura' => $survey->entrance_gate_photo_path,
    ] as $label => $path)
                    @if ($src = $imageSrc($path))
                        <div class="photo-block">
                            <div class="caption">Foto {{ $label }} pilihan</div>
                            <img src="{{ $src }}" alt="{{ $label }}">
                        </div>
                    @endif
                @endforeach

                @if (!empty($survey->photos))
                    <div class="photo-section">
                        <div class="caption">Dokumentasi survey</div>

                        <div class="photo-gallery">
                            @foreach ($survey->photos as $photo)
                                @if ($src = $imageSrc($photo))
                                    <div class="photo-block">
                                        <img src="{{ $src }}" alt="Foto survey">
                                    </div>
                                @endif
                            @endforeach
                        </div>
                    </div>
                @endif

            @endif
        </section>
    </main>

    <script>
        window.addEventListener('load', () => window.setTimeout(() => window.print(), 300));
    </script>
</body>

</html>
