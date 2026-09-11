@php
    $embedded = $embedded ?? false;
    $bookingContext = $booking ?? null;
    $fitting = $bookingContext?->fittings?->first();
    $fieldName = fn (string $key) => $embedded ? 'fitting_'.$key : $key;
    $dateField = $embedded ? 'fitting_date' : 'date';
    $statusField = $embedded ? 'fitting_status' : 'status';
@endphp

<div class="rounded-2xl border border-brand-100 bg-white p-5 shadow-sm space-y-6" data-fieldwork-card>
    <div>
        <div class="flex items-center justify-between gap-3">
            <h3 class="text-lg font-semibold text-gray-800"><i class="fas fa-shirt text-brand mr-2"></i>Data Fitting</h3>
            <div class="flex shrink-0 items-center gap-2">
                <button type="button" data-fieldwork-reset class="inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1.5 text-xs font-semibold text-gray-500 hover:bg-gray-100 hover:text-gray-700"><i class="fas fa-rotate-left"></i> Reset</button>
                <button type="button" data-fieldwork-toggle aria-expanded="true" class="inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1.5 text-xs font-semibold text-brand hover:bg-brand-50"><i class="fas fa-eye-slash" data-fieldwork-toggle-icon></i> <span data-fieldwork-toggle-label>Hide</span></button>
            </div>
        </div>
        <p class="mt-1 text-sm text-gray-500">Lengkapi detail busana, ukuran, dan foto fitting sesuai kebutuhan booking.</p>
    </div>
    <div class="space-y-6" data-fieldwork-content>
    @if(!$embedded)
    @if($fitting)
    <div class="flex items-center justify-between gap-4 px-4 py-3 mb-4 {{ $fitting->status === 'finished' ? 'bg-emerald-50/50' : 'bg-gray-50/60' }}">
        <div><p class="text-sm font-semibold text-gray-800">{{ $fitting->date ? $fitting->date->format('d M Y') : '-' }} @if($fitting->time)<span class="text-gray-400 font-normal">· {{ $fitting->time->format('H:i') }}</span>@endif</p><p class="text-xs text-gray-500">{{ $fitting->pic ? 'PIC: '.$fitting->pic : '' }}{{ $fitting->notes ? ' · '.$fitting->notes : '' }}</p></div>
        <div class="flex items-center gap-2 flex-shrink-0">@if($fitting->photos) @foreach($fitting->photos as $photo)<a href="{{ asset('storage/'.$photo) }}" onclick="openProof(event, this.href)" class="cursor-pointer"><img src="{{ asset('storage/'.$photo) }}" alt="Foto fitting" class="w-10 h-10 object-cover rounded-lg border border-gray-100"></a>@endforeach @endif<x-badge :color="match($fitting->status) {'finished' => 'success', 'on_going' => 'warning', default => 'gray'}">{{ ucfirst(str_replace('_', ' ', $fitting->status)) }}</x-badge></div>
    </div>
    @else
    <x-empty-state icon="fa-shirt" title="Belum ada data fitting" />
    @endif
@endif

    @if(!$embedded)
    <form action="{{ route('admin.fieldwork.fitting') }}" method="POST" enctype="multipart/form-data" class="pt-4 mt-3 space-y-5">
        @csrf
        <input type="hidden" name="booking_id" value="{{ $booking->id }}">
    @else
    <div class="space-y-5">
    @endif
        <div class="grid grid-cols-1 {{ $embedded ? 'md:grid-cols-3' : 'md:grid-cols-4' }} gap-4 items-start">
            <div>
                <x-input :name="$dateField" id="{{ $embedded ? 'fitting_date' : 'fieldwork_fitting_date' }}" label="Tanggal Fitting" type="date" :required="!$embedded" :value="old($dateField, $fitting?->date?->format('Y-m-d') ?? $bookingContext?->fitting_date?->format('Y-m-d') ?? '')" />
                @if($embedded)<p class="mt-1 text-xs text-gray-400">Otomatis H-1 bulan, tetapi tetap bisa diedit.</p>@endif
            </div>
            @if(!$embedded)
                <x-input name="time" label="Jam" type="time" :value="$fitting?->time?->format('H:i') ?? ''" />
            @endif
            <x-select :name="$fieldName('pic')" label="PIC (Tim Lapangan)"><option value="">— Pilih Tim Lapangan —</option>@foreach($teamMembers as $member)<option value="{{ $member->name }}" @selected(($fitting?->pic ?? '') === $member->name)>{{ $member->name }}</option>@endforeach</x-select>
            <div><label for="{{ $statusField }}" class="block text-sm font-medium text-gray-600 mb-1">Status</label><select name="{{ $statusField }}" id="{{ $statusField }}" required data-reset-value="scheduled" class="w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm text-gray-800 focus:outline-none focus:ring-2 focus:ring-brand-200"><option value="scheduled" @selected(($fitting?->status ?? 'scheduled') === 'scheduled')>Scheduled</option><option value="on_going" @selected(($fitting?->status ?? '') === 'on_going')>On Going</option><option value="finished" @selected(($fitting?->status ?? '') === 'finished')>Finished</option></select></div>
        </div>
        @foreach(App\Models\Fitting::CHECKLIST as $category => $checklist)
        <section class="overflow-hidden">
            <div class="bg-brand-50/50 px-4 py-3"><h4 class="font-semibold text-gray-800">{{ match($category) { 'cpw' => 'CPW', 'cpp' => 'CPP', 'ukuran' => 'TULIS / ISI BB / TB / LD', 'among_hajat' => 'Among Hajat', default => ucfirst($category) } }}</h4><p class="text-xs text-gray-400">Upload foto untuk busana, atau isi keterangan untuk item lainnya.</p></div>
                    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4 p-4">
                @foreach($checklist as $itemKey => $label)
                    @php
                        $notesColumn = $itemKey.'_notes';
                        $photoColumn = $itemKey.'_photo_path';
                        $itemNotes = $fitting?->{$notesColumn};
                        $itemPhoto = $fitting?->{$photoColumn};
                        $itemSize = $fitting?->item_sizes[$itemKey] ?? null;
                        $allowsPhoto = str_contains($itemKey, 'busana_') || str_starts_with($itemKey, 'among_');
                        $detailLabel = str_contains($itemKey, 'stylist') ? 'Nama stylist / hijab' : 'Detail / catatan';
                        $detailPlaceholder = str_contains($itemKey, 'stylist') ? 'Tulis nama stylist atau hijab' : 'Tulis detail item';
                    @endphp
                    <article class="min-w-0 bg-gray-50/50 p-4 space-y-4">
                        <div class="min-w-0"><p class="text-sm font-semibold leading-5 text-gray-700 break-words">{{ $label }}</p>@if($allowsPhoto && $itemPhoto)<a href="{{ asset('storage/'.$itemPhoto) }}" onclick="openProof(event, this.href)" class="inline-flex items-center gap-1 text-xs text-brand mt-1 no-underline"><i class="fas fa-image"></i> Lihat foto</a>@endif</div>
                        @if($allowsPhoto)
                            <div><label for="item-{{ $itemKey }}-photo" class="block text-xs font-medium text-gray-500 mb-1">Foto busana</label><input id="item-{{ $itemKey }}-photo" type="file" name="items[{{ $itemKey }}][photo]" accept="image/*" class="w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm text-gray-800 focus:outline-none focus:ring-2 focus:ring-brand-200"></div>
                            <div><label for="item-{{ $itemKey }}-notes" class="block text-xs font-medium text-gray-500 mb-1">Keterangan</label><textarea id="item-{{ $itemKey }}-notes" name="items[{{ $itemKey }}][notes]" maxlength="2000" rows="2" placeholder="Keterangan busana" class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-800 focus:outline-none focus:ring-2 focus:ring-brand-200">{{ old('items.'.$itemKey.'.notes', $itemNotes ?? '') }}</textarea></div>
                            <div><label for="item-{{ $itemKey }}-size" class="block text-xs font-medium text-gray-500 mb-1">Ukuran</label><input id="item-{{ $itemKey }}-size" type="text" name="items[{{ $itemKey }}][size]" maxlength="255" value="{{ old('items.'.$itemKey.'.size', $itemSize ?? '') }}" placeholder="Contoh: M atau LD 92 / PB 140" class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-800 focus:outline-none focus:ring-2 focus:ring-brand-200"></div>
                        @else
                            <div><label for="item-{{ $itemKey }}-notes" class="block text-xs font-medium text-gray-500 mb-1">{{ $detailLabel }}</label><input id="item-{{ $itemKey }}-notes" type="text" name="items[{{ $itemKey }}][notes]" maxlength="2000" value="{{ old('items.'.$itemKey.'.notes', $itemNotes ?? '') }}" placeholder="{{ $detailPlaceholder }}" class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-800 focus:outline-none focus:ring-2 focus:ring-brand-200"></div>
                        @endif
                    </article>
                @endforeach
            </div>
        </section>
        @endforeach

        <div class="border-t border-gray-100 pt-5">
            <x-textarea :name="$fieldName('notes')" label="Catatan Umum" rows="2">{{ old($fieldName('notes'), $fitting?->notes ?? '') }}</x-textarea>
        </div>
        @if(!$embedded)
            <x-button color="primary" type="submit"><i class="fas fa-save"></i> {{ $fitting ? 'Perbarui Fitting' : 'Simpan Fitting' }}</x-button>
        @endif
    @if(!$embedded)
    </form>
    @else
    </div>
    @endif
    </div>
</div>
