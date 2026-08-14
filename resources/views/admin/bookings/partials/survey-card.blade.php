@php $survey = $booking->survey; @endphp

<x-card title="Survey Lokasi" title-icon="fa-map-location-dot">
    @if($survey)
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm mb-4">
        <div>
            <span class="text-gray-500">Lokasi</span>
            <p class="font-semibold text-gray-800">{{ $survey->location ?? '-' }}</p>
        </div>
        <div>
            <span class="text-gray-500">Maps</span>
            @if($survey->maps_url)
                <a href="{{ $survey->maps_url }}" target="_blank" class="text-brand no-underline font-medium">Buka Maps</a>
            @else
                <p class="font-semibold text-gray-800">-</p>
            @endif
        </div>
        <div>
            <span class="text-gray-500">PIC</span>
            <p class="font-semibold text-gray-800">{{ $survey->pic ?? '-' }}</p>
        </div>
        <div>
            <span class="text-gray-500">Oleh</span>
            <p class="font-semibold text-gray-800">{{ $survey->creator->name ?? '-' }}</p>
        </div>
    </div>
    @if($survey->notes)
    <div class="bg-cream/60 rounded-xl px-4 py-3 text-sm text-gray-600 mb-4">{{ $survey->notes }}</div>
    @endif
    @if($survey->photos)
    <div class="flex flex-wrap gap-2 mb-4">
        @foreach($survey->photos as $photo)
            <a href="{{ asset('storage/'.$photo) }}" onclick="openProof(event, this.href)" class="cursor-pointer">
                <img src="{{ asset('storage/'.$photo) }}" alt="Foto survey" class="w-20 h-20 object-cover rounded-xl border border-gray-100">
            </a>
        @endforeach
    </div>
    @endif
    @endif

    <form action="{{ route('admin.fieldwork.survey') }}" method="POST" enctype="multipart/form-data" class="border-t border-dashed border-brand-200 pt-4">
        @csrf
        <input type="hidden" name="booking_id" value="{{ $booking->id }}">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
            <x-input name="location" label="Lokasi" :value="$survey?->location ?? ''" placeholder="Alamat tempat acara" />
            <x-input name="maps_url" label="Link Maps" :value="$survey?->maps_url ?? ''" placeholder="https://maps.app.goo.gl/..." />
            <x-select name="pic" label="PIC (Tim Lapangan)">
                <option value="">— Pilih Tim Lapangan —</option>
                @foreach($teamMembers as $member)
                    <option value="{{ $member->name }}" @selected(($survey?->pic ?? '') === $member->name)>{{ $member->name }}</option>
                @endforeach
            </x-select>
        </div>
        <div class="mt-3">
            <x-textarea name="notes" label="Catatan" rows="2">{{ $survey?->notes ?? '' }}</x-textarea>
        </div>
        <div class="mt-3 grid grid-cols-1 md:grid-cols-2 gap-3">
            <x-input name="photos[]" label="Foto" type="file" multiple accept="image/*" />
            <x-input name="videos[]" label="Video" type="file" multiple accept="video/*" />
        </div>
        <div class="mt-4">
            <x-button color="primary" type="submit"><i class="fas fa-save"></i> {{ $survey ? 'Perbarui Survey' : 'Simpan Survey' }}</x-button>
        </div>
    </form>
</x-card>
