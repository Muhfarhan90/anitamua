@php
    $survey = $booking->survey;
    $equipmentFields = [
        'gallery_booth' => 'Galery booth', 'envelope_box' => 'Kotak amplop', 'fruit_shed' => 'Saung buah',
        'akad_table' => 'Meja akad', 'diesel_lights' => 'Diesel + lampu', 'photo_stand' => 'Stand photo',
        'carpet' => 'Karpet', 'vip_table' => 'Meja VIP', 'snack_shed' => 'Saung jajan',
        'blower' => 'Blower', 'welcome_sign' => 'Welcome sign', 'center_point' => 'Center point',
    ];
    $choice = function (string $field, string $otherField) use ($survey): string {
        $value = $survey?->{$field};

        return $value === 'Lainnya' && filled($survey?->{$otherField})
            ? 'Lainnya — '.$survey->{$otherField}
            : ($value ?: '-');
    };
    $tentSummary = function ($items, $quantities, $other): string {
        $values = collect((array) $items)->map(function ($item) use ($quantities) {
            $quantity = $quantities[$item] ?? null;

            return $item.($quantity !== null && $quantity !== '' ? ' × '.$quantity : '');
        })->values()->all();

        if (in_array('Lainnya', (array) $items, true) && filled($other)) {
            $values[] = 'Lainnya: '.$other;
        }

        return $values ? implode(', ', $values) : '-';
    };
@endphp

<x-card title="Data Survey" title-icon="fa-map-location-dot">
    @if(!$survey)
        <x-empty-state icon="fa-map-location-dot" title="Belum ada data survey" />
    @else
        <section class="space-y-4">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-x-5 gap-y-4 text-sm">
                <div><span class="text-xs text-gray-400">Tanggal Survey</span><p class="mt-1 font-semibold text-gray-800">{{ $booking->survey_date?->format('d M Y') ?? '-' }}</p></div>
                <div><span class="text-xs text-gray-400">Lokasi</span><p class="mt-1 font-semibold text-gray-800">{{ $survey->location ?: '-' }}</p></div>
                <div><span class="text-xs text-gray-400">PIC</span><p class="mt-1 font-semibold text-gray-800">{{ $survey->pic ?: '-' }}</p></div>
                <div><span class="text-xs text-gray-400">Link Maps</span><p class="mt-1">@if($survey->maps_url)<a href="{{ $survey->maps_url }}" target="_blank" class="font-semibold text-brand hover:underline">Buka Maps</a>@else<span class="font-semibold text-gray-800">-</span>@endif</p></div>
            </div>
        </section>

        <section class="mt-6 border-t border-gray-100 pt-5">
            <h4 class="text-sm font-semibold text-gray-800">Dekorasi utama</h4>
            <div class="mt-3 grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                <div class="rounded-xl bg-brand-50/50 p-3">
                    <span class="text-xs text-gray-400">Jenis Pelaminan</span>
                    <p class="mt-1 font-semibold text-gray-800">{{ $survey->weddingStage?->name ?? '-' }}</p>
                    @if($survey->weddingStage?->photo_path)
                        <img src="{{ asset('storage/'.$survey->weddingStage->photo_path) }}" alt="{{ $survey->weddingStage->name }}" class="mt-3 h-32 w-full rounded-lg object-contain bg-white">
                    @endif
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div><span class="text-xs text-gray-400">Warna Bunga</span><p class="mt-1 font-medium text-gray-700">{{ $survey->flower_color ?: '-' }}</p></div>
                    <div><span class="text-xs text-gray-400">Warna Kain</span><p class="mt-1 font-medium text-gray-700">{{ $survey->fabric_color ?: '-' }}</p></div>
                    <div><span class="text-xs text-gray-400">Ukuran Pelaminan</span><p class="mt-1 font-medium text-gray-700">{{ $survey->stage_size === 'Lainnya' && filled($survey->stage_size_other) ? 'Lainnya — '.$survey->stage_size_other : ($survey->stage_size ?: '-') }}</p></div>
                    <div><span class="text-xs text-gray-400">Kursi</span><p class="mt-1 font-medium text-gray-700">{{ $choice('chair_option', 'chair_option_other') }}</p></div>
                    <div><span class="text-xs text-gray-400">Panggung</span><p class="mt-1 font-medium text-gray-700">{{ $choice('stage_option', 'stage_option_other') }}</p></div>
                    <div><span class="text-xs text-gray-400">Model Tenda</span><p class="mt-1 font-medium text-gray-700">{{ $survey->tent?->name ?? $choice('tent_shape', 'tent_shape_other') }}</p></div>
                </div>
            </div>
        </section>

        <section class="mt-6 border-t border-gray-100 pt-5">
            <h4 class="text-sm font-semibold text-gray-800">Tenda dan perlengkapan</h4>
            <div class="mt-3 grid grid-cols-1 md:grid-cols-2 gap-x-5 gap-y-4 text-sm">
                <div><span class="text-xs text-gray-400">Ukuran Tenda</span><p class="mt-1 font-medium text-gray-700">{{ $tentSummary($survey->tent_sizes, $survey->tent_size_quantities, $survey->tent_sizes_other) }}</p></div>
                <div><span class="text-xs text-gray-400">Tambahan Tenda</span><p class="mt-1 font-medium text-gray-700">{{ $tentSummary($survey->tent_additions, $survey->tent_addition_quantities, $survey->tent_additions_other) }}</p></div>
                <div><span class="text-xs text-gray-400">Pintu Masuk</span><p class="mt-1 font-medium text-gray-700">{{ $survey->entranceGate?->name ?? $choice('entrance', 'entrance_other') }}</p></div>
                <div><span class="text-xs text-gray-400">Prasmanan</span><p class="mt-1 font-medium text-gray-700">{{ $choice('buffet', 'buffet_other') }}</p></div>
                <div><span class="text-xs text-gray-400">Piring / Sendok / Garpu</span><p class="mt-1 font-medium text-gray-700">{{ $choice('tableware', 'tableware_other') }}</p></div>
            </div>
            <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-3">
                @foreach($equipmentFields as $field => $label)
                    <div class="rounded-xl bg-gray-50/70 px-3 py-3">
                        <span class="text-xs text-gray-400">{{ $label }}</span>
                        <p class="mt-1 text-sm font-medium text-gray-700">{{ $choice($field, $field.'_other') }}</p>
                    </div>
                @endforeach
            </div>
        </section>

        @if($survey->photos || $survey->videos)
            <section class="mt-6 border-t border-gray-100 pt-5">
                <h4 class="text-sm font-semibold text-gray-800">Dokumentasi survey</h4>
                @if($survey->photos)
                    <div class="mt-3 flex flex-wrap gap-2">
                        @foreach($survey->photos as $photo)
                            <a href="{{ asset('storage/'.$photo) }}" onclick="openProof(event, this.href)" class="cursor-pointer"><img src="{{ asset('storage/'.$photo) }}" alt="Foto survey" class="h-16 w-16 rounded-lg object-cover"></a>
                        @endforeach
                    </div>
                @endif
                @if($survey->videos)
                    <div class="mt-3 flex flex-wrap gap-2">
                        @foreach($survey->videos as $video)
                            <a href="{{ asset('storage/'.$video) }}" target="_blank" class="inline-flex items-center gap-2 rounded-lg bg-gray-50 px-3 py-2 text-xs font-medium text-brand hover:bg-brand-50"><i class="fas fa-video"></i> Buka video</a>
                        @endforeach
                    </div>
                @endif
            </section>
        @endif

        <section class="mt-6 border-t border-gray-100 pt-5">
            <span class="text-xs text-gray-400">Catatan Survey</span>
            <p class="mt-1 whitespace-pre-line text-sm text-gray-700">{{ $survey->notes ?: '-' }}</p>
        </section>
    @endif
</x-card>
