@php
    $embedded = $embedded ?? false;
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
            <div class="grid grid-cols-1 {{ $embedded ? 'md:grid-cols-4' : 'md:grid-cols-3' }} gap-4">
                @if($embedded)
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
                <div data-master-photo-field>
                    <x-select name="{{ $fieldName('wedding_stage_id') }}" label="Jenis Pelaminan" data-master-photo-select>
                        <option value="">— Pilih Jenis Pelaminan —</option>
                        @foreach($weddingStages as $weddingStage)
                            <option value="{{ $weddingStage->id }}" data-photo="{{ $weddingStage->photo_path ? asset('storage/'.$weddingStage->photo_path) : '' }}" @selected((string) $value('wedding_stage_id') === (string) $weddingStage->id)>{{ $weddingStage->name }}{{ !$weddingStage->is_active ? ' (nonaktif)' : '' }}</option>
                        @endforeach
                    </x-select>
                    <div class="{{ $selectedWeddingStage?->photo_path ? '' : 'hidden' }} mt-3 rounded-xl border border-gray-100 bg-gray-50 p-2" data-master-photo-preview>
                        <img src="{{ $selectedWeddingStage?->photo_path ? asset('storage/'.$selectedWeddingStage->photo_path) : '' }}" alt="{{ $selectedWeddingStage?->name ?? 'Preview pelaminan' }}" class="h-36 w-full rounded-lg object-contain" style="max-width:100%;display:block" data-master-photo-preview-image>
                        <p class="mt-2 text-xs text-gray-500">Preview foto pelaminan</p>
                    </div>
                    @error($fieldName('wedding_stage_id'))<p class="mt-1 text-xs text-red-600"><i class="fas fa-circle-exclamation mr-1"></i>{{ $message }}</p>@enderror
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
                <div data-master-photo-field>
                    <x-select name="{{ $fieldName('tent_id') }}" label="Model Tenda" data-master-photo-select>
                        <option value="">— Pilih Model Tenda —</option>
                        @foreach($tents as $tent)<option value="{{ $tent->id }}" data-photo="{{ $tent->photo_path ? asset('storage/'.$tent->photo_path) : '' }}" @selected((string) $value('tent_id') === (string) $tent->id)>{{ $tent->name }}{{ !$tent->is_active ? ' (nonaktif)' : '' }}</option>@endforeach
                    </x-select>
                    <div class="{{ $selectedTent?->photo_path ? '' : 'hidden' }} mt-3 rounded-xl border border-gray-100 bg-gray-50 p-2" data-master-photo-preview><img src="{{ $selectedTent?->photo_path ? asset('storage/'.$selectedTent->photo_path) : '' }}" alt="{{ $selectedTent?->name ?? 'Preview tenda' }}" class="h-36 w-full rounded-lg object-contain" data-master-photo-preview-image><p class="mt-2 text-xs text-gray-500">Preview foto tenda</p></div>
                    @error($fieldName('tent_id'))<p class="mt-1 text-xs text-red-600"><i class="fas fa-circle-exclamation mr-1"></i>{{ $message }}</p>@enderror
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
                <div data-master-photo-field>
                    <x-select name="{{ $fieldName('entrance_gate_id') }}" label="Pintu Masuk" data-master-photo-select>
                        <option value="">— Pilih Model Gapura —</option>
                        @foreach($entranceGates as $gate)<option value="{{ $gate->id }}" data-photo="{{ $gate->photo_path ? asset('storage/'.$gate->photo_path) : '' }}" @selected((string) $value('entrance_gate_id') === (string) $gate->id)>{{ $gate->name }}{{ !$gate->is_active ? ' (nonaktif)' : '' }}</option>@endforeach
                    </x-select>
                    <div class="{{ $selectedEntranceGate?->photo_path ? '' : 'hidden' }} mt-3 rounded-xl border border-gray-100 bg-gray-50 p-2" data-master-photo-preview><img src="{{ $selectedEntranceGate?->photo_path ? asset('storage/'.$selectedEntranceGate->photo_path) : '' }}" alt="{{ $selectedEntranceGate?->name ?? 'Preview gapura' }}" class="h-36 w-full rounded-lg object-contain" data-master-photo-preview-image><p class="mt-2 text-xs text-gray-500">Preview foto gapura</p></div>
                    @error($fieldName('entrance_gate_id'))<p class="mt-1 text-xs text-red-600"><i class="fas fa-circle-exclamation mr-1"></i>{{ $message }}</p>@enderror
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
                    const image = field.querySelector('[data-master-photo-preview-image]');

                    if (!select || !preview || !image) return;

                    function syncMasterPreview() {
                        const option = select.selectedOptions[0];
                        const photo = option?.dataset.photo || '';
                        const label = option?.textContent.trim() || 'Preview pelaminan';

                        if (photo) {
                            image.src = photo;
                            image.alt = label;
                            preview.classList.remove('hidden');
                        } else {
                            image.removeAttribute('src');
                            preview.classList.add('hidden');
                        }
                    }

                    select.addEventListener('change', syncMasterPreview);
                    syncMasterPreview();
                });

                document.querySelectorAll('[data-fieldwork-card]').forEach(function (card) {
                    const content = card.querySelector('[data-fieldwork-content]');
                    const toggle = card.querySelector('[data-fieldwork-toggle]');
                    const reset = card.querySelector('[data-fieldwork-reset]');

                    toggle?.addEventListener('click', function () {
                        const hidden = content.classList.toggle('hidden');
                        toggle.setAttribute('aria-expanded', String(!hidden));
                        toggle.querySelector('[data-fieldwork-toggle-label]').textContent = hidden ? 'Show' : 'Hide';
                        toggle.querySelector('[data-fieldwork-toggle-icon]').className = hidden ? 'fas fa-eye' : 'fas fa-eye-slash';
                    });

                    reset?.addEventListener('click', function () {
                        if (!window.confirm('Kosongkan semua input pada bagian ini? Data tersimpan tidak berubah sebelum tombol Simpan ditekan.')) return;

                        card.querySelectorAll('input, select, textarea').forEach(function (control) {
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
