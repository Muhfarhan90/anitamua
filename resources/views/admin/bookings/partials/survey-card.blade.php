@php
    $embedded = $embedded ?? false;
    $showScheduleDate = $showScheduleDate ?? false;
    $bookingContext = $booking ?? null;
    $survey = $bookingContext?->survey;
    $fieldName = fn (string $key) => ($embedded ? 'survey_' : '').$key;
    $value = fn (string $key, mixed $fallback = '') => old($fieldName($key), $survey?->{$key} ?? $fallback);
    $tentSizes = (array) old($fieldName('tent_sizes'), $survey?->tent_sizes ?? []);
    $tentAdditions = (array) old($fieldName('tent_additions'), $survey?->tent_additions ?? []);
    $tentSizeQuantities = (array) old($fieldName('tent_size_quantities'), $survey?->tent_size_quantities ?? []);
    $tentAdditionQuantities = (array) old($fieldName('tent_addition_quantities'), $survey?->tent_addition_quantities ?? []);
    $tents = $tents ?? collect();
    $entranceGates = $entranceGates ?? collect();
    $selectedWeddingStage = $weddingStages->firstWhere('id', (int) $value('wedding_stage_id'));
    $selectedTent = $tents->firstWhere('id', (int) $value('tent_id'));
    $selectedEntranceGate = $entranceGates->firstWhere('id', (int) $value('entrance_gate_id'));
    $masterPhotos = function ($master): array {
        return collect($master?->photos ?: array_filter([$master?->photo_path]))
            ->filter()
            ->map(fn (string $path) => [
                'path' => $path,
                'url' => str_starts_with($path, 'http') ? $path : asset('storage/'.$path),
            ])
            ->values()
            ->all();
    };
    $selectedWeddingStagePhotos = $masterPhotos($selectedWeddingStage);
    $selectedTentPhotos = $masterPhotos($selectedTent);
    $selectedEntranceGatePhotos = $masterPhotos($selectedEntranceGate);
    $equipmentFields = [
        'gallery_booth' => 'Galery booth', 'envelope_box' => 'Kotak amplop', 'fruit_shed' => 'Saung buah',
        'akad_table' => 'Meja akad', 'diesel_lights' => 'Diesel + lampu', 'photo_stand' => 'Stand photo',
        'carpet' => 'Karpet', 'vip_table' => 'Meja VIP', 'snack_shed' => 'Saung jajan',
        'blower' => 'Blower', 'welcome_sign' => 'Welcome sign', 'center_point' => 'Center point',
    ];
@endphp

<x-card title="Data Survey" title-icon="fa-map-location-dot" class="survey-card" data-fieldwork-card>
    <x-slot:actions>
        <button type="button" data-fieldwork-reset class="inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1.5 text-xs font-semibold text-gray-500 hover:bg-gray-100 hover:text-gray-700"><i class="fas fa-rotate-left"></i> Reset</button>
        <button type="button" data-fieldwork-toggle aria-expanded="true" class="inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1.5 text-xs font-semibold text-brand hover:bg-brand-50"><i class="fas fa-eye-slash" data-fieldwork-toggle-icon></i> <span data-fieldwork-toggle-label>Hide</span></button>
    </x-slot:actions>
    <div data-fieldwork-content>
    @if($survey && !$embedded)
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm mb-4">
        <div><span class="text-gray-500">Lokasi</span><p class="font-semibold text-gray-800">{{ $survey->location ?? '-' }}</p></div>
        <div><span class="text-gray-500">Maps</span>@if($survey->maps_url)<a href="{{ $survey->maps_url }}" target="_blank" class="text-brand no-underline font-medium">Buka Maps</a>@else<p class="font-semibold text-gray-800">-</p>@endif</div>
        <div><span class="text-gray-500">PIC</span><p class="font-semibold text-gray-800">{{ $survey->pic ?? '-' }}</p></div>
        <div><span class="text-gray-500">Pelaminan</span><p class="font-semibold text-gray-800">{{ $survey->weddingStage?->name ?? '-' }}</p></div>
    </div>
    @if($survey->photos)<div class="flex flex-wrap gap-2 mb-4">@foreach($survey->photos as $photo)<a href="{{ asset('storage/'.$photo) }}" onclick="openProof(event, this.href)" class="cursor-pointer"><img src="{{ asset('storage/'.$photo) }}" alt="Foto survey" class="w-16 h-16 object-cover rounded-xl border border-gray-100"></a>@endforeach</div>@endif
    @endif

    @if(!$embedded)
    <form action="{{ route('admin.fieldwork.survey') }}" method="POST" enctype="multipart/form-data" class="pt-4 space-y-5">
        @csrf
        <input type="hidden" name="booking_id" value="{{ $booking->id }}">
    @else
    <div class="pt-5 space-y-6">
    @endif
        <section>
            <div class="flex items-center gap-3 mb-4"><span class="w-7 h-7 rounded-lg bg-brand-50 text-brand flex items-center justify-center text-xs font-bold">01</span><div><h4 class="font-semibold text-gray-800">Informasi survey</h4><p class="text-xs text-gray-400">Lokasi, PIC, dan catatan lapangan.</p></div></div>
            <div class="grid grid-cols-1 {{ ($embedded || $showScheduleDate) ? 'md:grid-cols-4' : 'md:grid-cols-3' }} gap-4">
                @if($embedded || $showScheduleDate)
                    <x-input name="survey_date" label="Tanggal Survey" type="date" :value="old('survey_date', $bookingContext?->survey_date?->format('Y-m-d') ?? '')" />
                @endif
                <x-input name="{{ $fieldName('location') }}" label="Lokasi" :value="$value('location')" placeholder="Alamat tempat acara" />
                <x-input name="{{ $fieldName('maps_url') }}" label="Link Maps" :value="$value('maps_url')" placeholder="https://maps.app.goo.gl/..." />
                <x-select name="{{ $fieldName('pic') }}" label="PIC (Tim Lapangan)"><option value="">— Pilih Tim Lapangan —</option>@foreach($teamMembers as $member)<option value="{{ $member->name }}" @selected($value('pic') === $member->name)>{{ $member->name }}</option>@endforeach</x-select>
            </div>
        </section>
        <section class="border-t border-gray-100 pt-4">
            <div class="flex items-center gap-3 mb-4"><span class="w-7 h-7 rounded-lg bg-brand-50 text-brand flex items-center justify-center text-xs font-bold">02</span><div><h4 class="font-semibold text-gray-800">Dekorasi utama</h4><p class="text-xs text-gray-400">Pilihan jenis dan detail pelaminan.</p></div></div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div data-master-photo-field data-master-photo-name="{{ $fieldName('wedding_stage_photo_path') }}" data-master-selected-photo="{{ $value('wedding_stage_photo_path') }}">
                    <x-select name="{{ $fieldName('wedding_stage_id') }}" label="Jenis Pelaminan" data-master-photo-select>
                        <option value="">— Pilih Jenis Pelaminan —</option>
                        @foreach($weddingStages as $weddingStage)
                            <option value="{{ $weddingStage->id }}" data-photos="{{ base64_encode(json_encode($masterPhotos($weddingStage))) }}" @selected((string) $value('wedding_stage_id') === (string) $weddingStage->id)>{{ $weddingStage->name }}{{ !$weddingStage->is_active ? ' (nonaktif)' : '' }}</option>
                        @endforeach
                    </x-select>
                    <div class="{{ $selectedWeddingStagePhotos ? '' : 'hidden' }} mt-3 rounded-xl border border-gray-100 bg-gray-50 p-3" data-master-photo-preview>
                        <div class="grid max-h-56 grid-cols-3 gap-2 overflow-y-auto" data-master-photo-preview-images>
                            @foreach($selectedWeddingStagePhotos as $photo)
                                <label class="block cursor-pointer">
                                    <input type="radio" name="{{ $fieldName('wedding_stage_photo_path') }}" value="{{ $photo['path'] }}" @checked($value('wedding_stage_photo_path') === $photo['path']) class="peer sr-only">
                                    <span class="relative block overflow-hidden rounded-lg border-2 border-transparent bg-white p-1 transition peer-checked:border-brand peer-checked:ring-2 peer-checked:ring-brand-200">
                                        <img src="{{ $photo['url'] }}" alt="Foto pelaminan {{ $loop->iteration }}" class="h-24 w-full rounded-md object-cover">
                                        <span class="absolute right-2 top-2 hidden h-5 w-5 items-center justify-center rounded-full bg-brand text-xs text-white shadow peer-checked:flex"><i class="fas fa-check"></i></span>
                                    </span>
                                </label>
                            @endforeach
                        </div>
                        <p class="mt-2 text-xs text-gray-500" data-master-photo-preview-count>Pilih satu dari {{ count($selectedWeddingStagePhotos) }} foto pelaminan.</p>
                    </div>
                    @error($fieldName('wedding_stage_id'))<p class="mt-1 text-xs text-red-600"><i class="fas fa-circle-exclamation mr-1"></i>{{ $message }}</p>@enderror
                    @error($fieldName('wedding_stage_photo_path'))<p class="mt-1 text-xs text-red-600"><i class="fas fa-circle-exclamation mr-1"></i>{{ $message }}</p>@enderror
                </div>
                <x-input name="{{ $fieldName('flower_color') }}" label="Warna Bunga" :value="$value('flower_color')" placeholder="Contoh: putih dan sage" />
                <x-input name="{{ $fieldName('fabric_color') }}" label="Warna Kain" :value="$value('fabric_color')" placeholder="Contoh: dusty pink" />
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-5">
                <div><p class="text-sm font-medium text-gray-600 mb-2">Ukuran Pelaminan</p><div class="flex flex-wrap gap-2">@foreach(['4m', '6m', '8m', '12m', 'Lainnya'] as $option)<label class="inline-flex items-center gap-2 text-sm text-gray-600"><input type="radio" name="{{ $fieldName('stage_size') }}" value="{{ $option }}" data-other-group="stage_size" @checked($value('stage_size') === $option) class="text-brand focus:ring-brand">{{ $option }}</label>@endforeach</div><div data-other-wrapper="stage_size" class="{{ $value('stage_size') === 'Lainnya' ? '' : 'hidden' }} mt-2"><input type="number" min="0" step="any" name="{{ $fieldName('stage_size_other') }}" value="{{ $value('stage_size_other') }}" {{ $value('stage_size') === 'Lainnya' ? '' : 'disabled' }} class="w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm" placeholder="Isi ukuran pelaminan"></div></div>
                <div><p class="text-sm font-medium text-gray-600 mb-2">Kursi</p><div class="flex flex-wrap gap-2">@foreach(['100', '150', '200', '250', 'Opsi 5', 'Lainnya'] as $option)<label class="inline-flex items-center gap-2 text-sm text-gray-600"><input type="radio" name="{{ $fieldName('chair_option') }}" value="{{ $option }}" data-other-group="chair_option" @checked($value('chair_option') === $option) class="text-brand focus:ring-brand">{{ $option }}</label>@endforeach</div><div data-other-wrapper="chair_option" class="{{ $value('chair_option') === 'Lainnya' ? '' : 'hidden' }} mt-2"><input name="{{ $fieldName('chair_option_other') }}" value="{{ $value('chair_option_other') }}" {{ $value('chair_option') === 'Lainnya' ? '' : 'disabled' }} class="w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm" placeholder="Keterangan kursi lainnya"></div></div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-5">
                <div><p class="text-sm font-medium text-gray-600 mb-2">Panggung</p><div class="flex flex-wrap gap-2">@foreach(['Melamin', 'Karpet permadani', 'Karpet warna', 'Lainnya'] as $option)<label class="inline-flex items-center gap-2 text-sm text-gray-600"><input type="radio" name="{{ $fieldName('stage_option') }}" value="{{ $option }}" data-other-group="stage_option" @checked($value('stage_option') === $option) class="text-brand focus:ring-brand">{{ $option }}</label>@endforeach</div><div data-other-wrapper="stage_option" class="{{ $value('stage_option') === 'Lainnya' ? '' : 'hidden' }} mt-2"><input name="{{ $fieldName('stage_option_other') }}" value="{{ $value('stage_option_other') }}" {{ $value('stage_option') === 'Lainnya' ? '' : 'disabled' }} class="w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm" placeholder="Keterangan panggung lainnya"></div></div>
                <div data-master-photo-field data-master-photo-name="{{ $fieldName('tent_photo_path') }}" data-master-selected-photo="{{ $value('tent_photo_path') }}">
                    <x-select name="{{ $fieldName('tent_id') }}" label="Model Tenda" data-master-photo-select>
                        <option value="">— Pilih Model Tenda —</option>
                        @foreach($tents as $tent)<option value="{{ $tent->id }}" data-photos="{{ base64_encode(json_encode($masterPhotos($tent))) }}" @selected((string) $value('tent_id') === (string) $tent->id)>{{ $tent->name }}{{ !$tent->is_active ? ' (nonaktif)' : '' }}</option>@endforeach
                    </x-select>
                    <div class="{{ $selectedTentPhotos ? '' : 'hidden' }} mt-3 rounded-xl border border-gray-100 bg-gray-50 p-3" data-master-photo-preview>
                        <div class="grid max-h-56 grid-cols-3 gap-2 overflow-y-auto" data-master-photo-preview-images>
                            @foreach($selectedTentPhotos as $photo)
                                <label class="block cursor-pointer">
                                    <input type="radio" name="{{ $fieldName('tent_photo_path') }}" value="{{ $photo['path'] }}" @checked($value('tent_photo_path') === $photo['path']) class="peer sr-only">
                                    <span class="relative block overflow-hidden rounded-lg border-2 border-transparent bg-white p-1 transition peer-checked:border-brand peer-checked:ring-2 peer-checked:ring-brand-200">
                                        <img src="{{ $photo['url'] }}" alt="Foto tenda {{ $loop->iteration }}" class="h-24 w-full rounded-md object-cover">
                                        <span class="absolute right-2 top-2 hidden h-5 w-5 items-center justify-center rounded-full bg-brand text-xs text-white shadow peer-checked:flex"><i class="fas fa-check"></i></span>
                                    </span>
                                </label>
                            @endforeach
                        </div>
                        <p class="mt-2 text-xs text-gray-500" data-master-photo-preview-count>Pilih satu dari {{ count($selectedTentPhotos) }} foto tenda.</p>
                    </div>
                    @error($fieldName('tent_id'))<p class="mt-1 text-xs text-red-600"><i class="fas fa-circle-exclamation mr-1"></i>{{ $message }}</p>@enderror
                    @error($fieldName('tent_photo_path'))<p class="mt-1 text-xs text-red-600"><i class="fas fa-circle-exclamation mr-1"></i>{{ $message }}</p>@enderror
                </div>
            </div>
        </section>
        <section class="border-t border-gray-100 pt-4">
            <div class="flex items-center gap-3 mb-4"><span class="w-7 h-7 rounded-lg bg-brand-50 text-brand flex items-center justify-center text-xs font-bold">03</span><div><h4 class="font-semibold text-gray-800">Tenda dan perlengkapan</h4><p class="text-xs text-gray-400">Checklist mengikuti pilihan pada Google Form.</p></div></div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <p class="text-sm font-medium text-gray-600 mb-2">Ukuran Tenda</p>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        @foreach(['3X4', '4X4', '4X6', '4X8', '5X5', '8X8', 'Lainnya'] as $option)
                            <div class="flex min-w-0 items-center justify-between gap-2 rounded-lg border border-gray-100 bg-white px-2.5 py-2">
                                <label class="inline-flex min-w-0 items-center gap-2 text-sm text-gray-600"><input type="checkbox" name="{{ $fieldName('tent_sizes') }}[]" value="{{ $option }}" data-other-group="tent_sizes" @checked(in_array($option, $tentSizes, true)) class="rounded text-brand focus:ring-brand"><span class="truncate">{{ $option }}</span></label>
                                @if($option !== 'Lainnya')
                                    <label class="flex shrink-0 items-center gap-1 text-xs text-gray-500">Jumlah<input type="number" min="0" step="1" name="{{ $fieldName('tent_size_quantities') }}[{{ $option }}]" value="{{ $tentSizeQuantities[$option] ?? '' }}" class="w-16 rounded-md border border-gray-200 bg-gray-50 px-2 py-1.5 text-sm text-gray-800 focus:outline-none focus:ring-2 focus:ring-brand-200" placeholder="0"></label>
                                @endif
                            </div>
                        @endforeach
                    </div>
                    <div data-other-wrapper="tent_sizes" class="{{ in_array('Lainnya', $tentSizes, true) ? '' : 'hidden' }} mt-2"><input name="{{ $fieldName('tent_sizes_other') }}" value="{{ $value('tent_sizes_other') }}" {{ in_array('Lainnya', $tentSizes, true) ? '' : 'disabled' }} class="w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm" placeholder="Ukuran tenda lainnya"></div>
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-600 mb-2">Tambahan Tenda</p>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        @foreach(['3X4', '4X4', '4X6', '4X8', '5X5', '8X8', 'Lainnya'] as $option)
                            <div class="flex min-w-0 items-center justify-between gap-2 rounded-lg border border-gray-100 bg-white px-2.5 py-2">
                                <label class="inline-flex min-w-0 items-center gap-2 text-sm text-gray-600"><input type="checkbox" name="{{ $fieldName('tent_additions') }}[]" value="{{ $option }}" data-other-group="tent_additions" @checked(in_array($option, $tentAdditions, true)) class="rounded text-brand focus:ring-brand"><span class="truncate">{{ $option }}</span></label>
                                @if($option !== 'Lainnya')
                                    <label class="flex shrink-0 items-center gap-1 text-xs text-gray-500">Jumlah<input type="number" min="0" step="1" name="{{ $fieldName('tent_addition_quantities') }}[{{ $option }}]" value="{{ $tentAdditionQuantities[$option] ?? '' }}" class="w-16 rounded-md border border-gray-200 bg-gray-50 px-2 py-1.5 text-sm text-gray-800 focus:outline-none focus:ring-2 focus:ring-brand-200" placeholder="0"></label>
                                @endif
                            </div>
                        @endforeach
                    </div>
                    <div data-other-wrapper="tent_additions" class="{{ in_array('Lainnya', $tentAdditions, true) ? '' : 'hidden' }} mt-2"><input name="{{ $fieldName('tent_additions_other') }}" value="{{ $value('tent_additions_other') }}" {{ in_array('Lainnya', $tentAdditions, true) ? '' : 'disabled' }} class="w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm" placeholder="Tambahan tenda lainnya"></div>
                </div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-5">
                <div data-master-photo-field data-master-photo-name="{{ $fieldName('entrance_gate_photo_path') }}" data-master-selected-photo="{{ $value('entrance_gate_photo_path') }}">
                    <x-select name="{{ $fieldName('entrance_gate_id') }}" label="Pintu Masuk" data-master-photo-select>
                        <option value="">— Pilih Model Gapura —</option>
                        @foreach($entranceGates as $gate)<option value="{{ $gate->id }}" data-photos="{{ base64_encode(json_encode($masterPhotos($gate))) }}" @selected((string) $value('entrance_gate_id') === (string) $gate->id)>{{ $gate->name }}{{ !$gate->is_active ? ' (nonaktif)' : '' }}</option>@endforeach
                    </x-select>
                    <div class="{{ $selectedEntranceGatePhotos ? '' : 'hidden' }} mt-3 rounded-xl border border-gray-100 bg-gray-50 p-3" data-master-photo-preview>
                        <div class="grid max-h-56 grid-cols-3 gap-2 overflow-y-auto" data-master-photo-preview-images>
                            @foreach($selectedEntranceGatePhotos as $photo)
                                <label class="block cursor-pointer">
                                    <input type="radio" name="{{ $fieldName('entrance_gate_photo_path') }}" value="{{ $photo['path'] }}" @checked($value('entrance_gate_photo_path') === $photo['path']) class="peer sr-only">
                                    <span class="relative block overflow-hidden rounded-lg border-2 border-transparent bg-white p-1 transition peer-checked:border-brand peer-checked:ring-2 peer-checked:ring-brand-200">
                                        <img src="{{ $photo['url'] }}" alt="Foto gapura {{ $loop->iteration }}" class="h-24 w-full rounded-md object-cover">
                                        <span class="absolute right-2 top-2 hidden h-5 w-5 items-center justify-center rounded-full bg-brand text-xs text-white shadow peer-checked:flex"><i class="fas fa-check"></i></span>
                                    </span>
                                </label>
                            @endforeach
                        </div>
                        <p class="mt-2 text-xs text-gray-500" data-master-photo-preview-count>Pilih satu dari {{ count($selectedEntranceGatePhotos) }} foto gapura.</p>
                    </div>
                    @error($fieldName('entrance_gate_id'))<p class="mt-1 text-xs text-red-600"><i class="fas fa-circle-exclamation mr-1"></i>{{ $message }}</p>@enderror
                    @error($fieldName('entrance_gate_photo_path'))<p class="mt-1 text-xs text-red-600"><i class="fas fa-circle-exclamation mr-1"></i>{{ $message }}</p>@enderror
                </div>
                @foreach(['buffet' => ['Prasmanan', ['Standar', 'Rolltop', 'Lainnya']], 'tableware' => ['Piring / Sendok / Garpu', ['Keramik', 'Rotan', 'Lainnya']]] as $field => [$label, $options])
                <div><p class="text-sm font-medium text-gray-600 mb-2">{{ $label }}</p><div class="flex flex-wrap gap-2">@foreach($options as $option)<label class="inline-flex items-center gap-2 text-sm text-gray-600"><input type="radio" name="{{ $fieldName($field) }}" value="{{ $option }}" data-other-group="{{ $field }}" @checked($value($field) === $option) class="text-brand focus:ring-brand">{{ $option }}</label>@endforeach</div><div data-other-wrapper="{{ $field }}" class="{{ $value($field) === 'Lainnya' ? '' : 'hidden' }} mt-2"><input name="{{ $fieldName($field.'_other') }}" value="{{ $value($field.'_other') }}" {{ $value($field) === 'Lainnya' ? '' : 'disabled' }} class="w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm" placeholder="Keterangan lainnya"></div></div>
                @endforeach
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4 mt-5">
                @foreach($equipmentFields as $field => $label)
                <div class="min-w-0 rounded-xl border border-gray-100 bg-gray-50/70 px-3 py-3">
                    <p class="block text-sm font-medium text-gray-700 mb-2">{{ $label }}</p>
                    <div class="flex flex-wrap gap-x-4 gap-y-2">
                        @foreach(['Ya', 'Tidak', 'Lainnya'] as $option)
                            <label class="inline-flex items-center gap-2 text-sm text-gray-600">
                                <input type="radio" name="{{ $fieldName($field) }}" value="{{ $option }}" data-other-group="{{ $field }}" @checked($value($field) === $option) class="text-brand focus:ring-brand">
                                {{ $option }}
                            </label>
                        @endforeach
                    </div>
                    <div data-other-wrapper="{{ $field }}" class="{{ $value($field) === 'Lainnya' ? '' : 'hidden' }} mt-2">
                        <input name="{{ $fieldName($field.'_other') }}" value="{{ $value($field.'_other') }}" {{ $value($field) === 'Lainnya' ? '' : 'disabled' }} class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm" placeholder="Keterangan lainnya">
                    </div>
                </div>
                @endforeach
            </div>
        </section>
        <section class="border-t border-gray-100 pt-5 space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4"><x-input name="{{ $fieldName('photos') }}[]" label="Foto Survey" type="file" multiple accept="image/*" /><x-input name="{{ $fieldName('videos') }}[]" label="Video Survey" type="file" multiple accept="video/*" /></div>
            <x-textarea name="{{ $fieldName('notes') }}" label="Catatan Survey" rows="2">{{ $value('notes') }}</x-textarea>
        </section>
        @if(!$embedded)<x-button color="primary" type="submit"><i class="fas fa-save"></i> {{ $survey ? 'Perbarui Survey' : 'Simpan Survey' }}</x-button>@endif
    @if(!$embedded)</form>@else</div>@endif
    </div>
</x-card>

@once
    <style>
        .survey-card label:has(> input[data-other-group]) {
            padding-block: 0.25rem;
        }
    </style>
    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                document.querySelectorAll('[data-other-wrapper]').forEach(function (wrapper) {
                    const group = wrapper.dataset.otherWrapper;
                    const controls = wrapper.closest('[data-fieldwork-card]').querySelectorAll('[data-other-group="' + group + '"]');
                    const input = wrapper.querySelector('input');

                    function syncOtherField() {
                        const show = Array.from(controls).some(control => control.checked && control.value === 'Lainnya');
                        wrapper.classList.toggle('hidden', !show);
                        if (input) input.disabled = !show;
                    }

                    controls.forEach(control => control.addEventListener('change', syncOtherField));
                    syncOtherField();
                });

                document.querySelectorAll('[data-master-photo-field]').forEach(function (field) {
                    const select = field.querySelector('[data-master-photo-select]');
                    const preview = field.querySelector('[data-master-photo-preview]');
                    const images = field.querySelector('[data-master-photo-preview-images]');
                    const count = field.querySelector('[data-master-photo-preview-count]');

                    if (!select || !preview || !images) return;

                    function syncMasterPreview() {
                        const option = select.selectedOptions[0];
                        const label = option?.textContent.trim() || 'foto master';
                        let photos = [];

                        try {
                            photos = JSON.parse(atob(option?.dataset.photos || ''));
                        } catch (error) {
                            photos = [];
                        }

                        if (!Array.isArray(photos)) photos = [];
                        photos = photos.filter(photo => photo && (typeof photo === 'string' || (photo.path && photo.url)));

                        images.replaceChildren(...photos.map(function (photo, index) {
                            const item = typeof photo === 'string' ? { path: photo, url: photo } : photo;
                            const choice = document.createElement('label');
                            choice.className = 'block cursor-pointer';

                            const input = document.createElement('input');
                            input.type = 'radio';
                            input.name = field.dataset.masterPhotoName;
                            input.value = item.path;
                            input.checked = field.dataset.masterSelectedPhoto === item.path;
                            input.className = 'peer sr-only';
                            input.addEventListener('change', function () {
                                field.dataset.masterSelectedPhoto = input.value;
                            });

                            const frame = document.createElement('span');
                            frame.className = 'relative block overflow-hidden rounded-lg border-2 border-transparent bg-white p-1 transition peer-checked:border-brand peer-checked:ring-2 peer-checked:ring-brand-200';

                            const image = document.createElement('img');
                            image.src = item.url;
                            image.alt = `${label} - foto ${index + 1}`;
                            image.className = 'h-24 w-full rounded-md object-cover';

                            const check = document.createElement('span');
                            check.className = 'absolute right-2 top-2 hidden h-5 w-5 items-center justify-center rounded-full bg-brand text-xs text-white shadow peer-checked:flex';
                            check.innerHTML = '<i class="fas fa-check"></i>';

                            frame.append(image, check);
                            choice.append(input, frame);

                            return choice;
                        }));
                        preview.classList.toggle('hidden', photos.length === 0);
                        if (count) count.textContent = photos.length ? `Pilih satu dari ${photos.length} foto ${label}.` : '';
                    }

                    select.addEventListener('change', function () {
                        field.dataset.masterSelectedPhoto = '';
                        syncMasterPreview();
                    });
                    syncMasterPreview();
                });

                document.querySelectorAll('[data-fieldwork-card]').forEach(function (card) {
                    const content = card.querySelector('[data-fieldwork-content]');
                    const toggle = card.querySelector('[data-fieldwork-toggle]');
                    const reset = card.querySelector('[data-fieldwork-reset]');
                    const resetScope = card.querySelector('[data-fieldwork-reset-scope]') || content || card;

                    toggle?.addEventListener('click', function () {
                        const hidden = content.classList.toggle('hidden');
                        toggle.setAttribute('aria-expanded', String(!hidden));
                        toggle.querySelector('[data-fieldwork-toggle-label]').textContent = hidden ? 'Show' : 'Hide';
                        toggle.querySelector('[data-fieldwork-toggle-icon]').className = hidden ? 'fas fa-eye' : 'fas fa-eye-slash';
                    });

                    reset?.addEventListener('click', function () {
                        if (!window.confirm('Kosongkan semua input pada bagian ini? Data tersimpan tidak berubah sebelum tombol Simpan ditekan.')) return;

                        resetScope.querySelectorAll('input, select, textarea').forEach(function (control) {
                            if (control.type === 'hidden') return;

                            if (control.type === 'checkbox' || control.type === 'radio') {
                                control.checked = false;
                            } else {
                                control.value = control.dataset.resetValue ?? '';
                            }

                            control.dispatchEvent(new Event('input', { bubbles: true }));
                            control.dispatchEvent(new Event('change', { bubbles: true }));
                        });
                    });
                });
            });
        </script>
    @endpush
@endonce
