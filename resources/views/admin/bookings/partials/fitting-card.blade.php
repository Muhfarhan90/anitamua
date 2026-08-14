@php $fitting = $booking->fittings->first(); @endphp

<x-card title="Sesi Fitting" title-icon="fa-shirt">
    @if($fitting)
    <div class="flex items-center justify-between gap-4 border border-gray-100 rounded-xl px-4 py-3 mb-4 {{ $fitting->status === 'finished' ? 'bg-emerald-50/50' : 'bg-gray-50/60' }}">
        <div>
            <p class="text-sm font-semibold text-gray-800">
                {{ $fitting->date ? $fitting->date->format('d M Y') : '-' }}
                @if($fitting->time)<span class="text-gray-400 font-normal"> · {{ $fitting->time->format('H:i') }}</span>@endif
            </p>
            <p class="text-xs text-gray-500">{{ $fitting->pic ? 'PIC: '.$fitting->pic : '' }}{{ $fitting->notes ? ' · '.$fitting->notes : '' }}</p>
        </div>
        <div class="flex items-center gap-2 flex-shrink-0">
            @if($fitting->photos)
                @foreach($fitting->photos as $photo)
                <a href="{{ asset('storage/'.$photo) }}" onclick="openProof(event, this.href)" class="cursor-pointer">
                    <img src="{{ asset('storage/'.$photo) }}" alt="Foto fitting" class="w-10 h-10 object-cover rounded-lg border border-gray-100">
                </a>
                @endforeach
            @endif
            <x-badge :color="match($fitting->status) {
                'finished' => 'success',
                'on_going' => 'warning',
                default => 'gray',
            }">{{ ucfirst(str_replace('_', ' ', $fitting->status)) }}</x-badge>
        </div>
    </div>
    @else
    <x-empty-state icon="fa-shirt" title="Belum ada data fitting" />
    @endif

    {{-- Fitting hanya 1 per booking — form memperbarui record yang sama --}}
    <form action="{{ route('admin.fieldwork.fitting') }}" method="POST" enctype="multipart/form-data" class="border-t border-dashed border-brand-200 pt-4 mt-3">
        @csrf
        <input type="hidden" name="booking_id" value="{{ $booking->id }}">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
            <x-input name="date" label="Tanggal" type="date" required :value="$fitting?->date?->format('Y-m-d') ?? ''" />
            <x-input name="time" label="Jam" type="time" :value="$fitting?->time?->format('H:i') ?? ''" />
            <x-select name="pic" label="PIC (Tim Lapangan)">
                <option value="">— Pilih Tim Lapangan —</option>
                @foreach($teamMembers as $member)
                    <option value="{{ $member->name }}" @selected(($fitting?->pic ?? '') === $member->name)>{{ $member->name }}</option>
                @endforeach
            </x-select>
            <div>
                <label for="fitting_status" class="block text-sm font-medium text-gray-600 mb-1">Status</label>
                <select name="status" id="fitting_status" required
                        class="w-full rounded-lg border bg-gray-50 px-3 py-2 text-sm text-gray-800 focus:outline-none focus:ring-2 focus:ring-brand-200 border-gray-200">
                    <option value="scheduled" @selected($fitting && $fitting->status === 'scheduled')>Scheduled</option>
                    <option value="on_going" @selected($fitting && $fitting->status === 'on_going')>On Going</option>
                    <option value="finished" @selected($fitting && $fitting->status === 'finished')>Finished</option>
                </select>
            </div>
        </div>
        <div class="mt-3">
            <x-textarea name="notes" label="Catatan" rows="2">{{ $fitting?->notes ?? '' }}</x-textarea>
        </div>
        <div class="mt-3">
            <x-input name="photos[]" label="Foto" type="file" multiple accept="image/*" />
        </div>
        <div class="mt-4">
            <x-button color="primary" type="submit"><i class="fas fa-save"></i> {{ $fitting ? 'Perbarui Fitting' : 'Simpan Fitting' }}</x-button>
        </div>
    </form>
</x-card>
