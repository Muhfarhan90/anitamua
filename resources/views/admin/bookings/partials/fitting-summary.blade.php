@php
    $fitting = $booking->fittings->first();
    $statusLabel = ['scheduled' => 'Scheduled', 'on_going' => 'On Going', 'finished' => 'Finished'];
@endphp

<x-card title="Data Fitting" title-icon="fa-shirt">
    @if(!$fitting)
        <x-empty-state icon="fa-shirt" title="Belum ada data fitting" />
    @else
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-x-5 gap-y-4 text-sm">
            <div><span class="text-xs text-gray-400">Tanggal Fitting</span><p class="mt-1 font-semibold text-gray-800">{{ $fitting->date?->format('d M Y') ?? '-' }}</p></div>
            <div><span class="text-xs text-gray-400">Jam</span><p class="mt-1 font-semibold text-gray-800">{{ $fitting->time?->format('H:i') ?? '-' }}</p></div>
            <div><span class="text-xs text-gray-400">PIC</span><p class="mt-1 font-semibold text-gray-800">{{ $fitting->pic ?: '-' }}</p></div>
            <div><span class="text-xs text-gray-400">Status</span><p class="mt-1"><x-badge :color="match($fitting->status) { 'finished' => 'success', 'on_going' => 'warning', default => 'gray' }">{{ $statusLabel[$fitting->status] ?? ucfirst($fitting->status) }}</x-badge></p></div>
        </div>

        <div class="mt-6 space-y-6">
            @foreach(App\Models\Fitting::CHECKLIST as $category => $checklist)
                <section class="border-t border-gray-100 pt-5">
                    <h4 class="text-sm font-semibold text-gray-800">{{ match($category) { 'cpw' => 'CPW', 'cpp' => 'CPP', 'ukuran' => 'TULIS / ISI BB / TB / LD', 'among_hajat' => 'Among Hajat', default => ucfirst($category) } }}</h4>
                    <div class="mt-3 grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-3">
                        @foreach($checklist as $itemKey => $label)
                            @php
                                $notes = $fitting->{$itemKey.'_notes'};
                                $photo = $fitting->{$itemKey.'_photo_path'};
                                $allowsPhoto = str_contains($itemKey, 'busana_') || str_starts_with($itemKey, 'among_');
                            @endphp
                            <article class="min-w-0 bg-gray-50/70 p-3">
                                <p class="text-sm font-semibold leading-5 text-gray-700">{{ $label }}</p>
                                @if($allowsPhoto && $photo)
                                    <a href="{{ asset('storage/'.$photo) }}" onclick="openProof(event, this.href)" class="mt-2 block"><img src="{{ asset('storage/'.$photo) }}" alt="{{ $label }}" class="h-28 w-full rounded-lg object-contain bg-white"></a>
                                @else
                                    <p class="mt-2 whitespace-pre-line text-sm text-gray-600">{{ $notes ?: '-' }}</p>
                                @endif
                            </article>
                        @endforeach
                    </div>
                </section>
            @endforeach
        </div>

        <section class="mt-6 border-t border-gray-100 pt-5">
            <span class="text-xs text-gray-400">Catatan Fitting</span>
            <p class="mt-1 whitespace-pre-line text-sm text-gray-700">{{ $fitting->notes ?: '-' }}</p>
        </section>
    @endif
</x-card>
