@extends('layouts.app')

@section('title', 'Checklist Packing H-1')

@section('content')
@php
    $items = $fitting?->packingSourceItems() ?? [];
    $packingState = $fitting?->packingChecklistState() ?? ['checked' => [], 'conditions' => []];
@endphp

<x-page-header title="Checklist Packing H-1" subtitle="Checklist seluruh perlengkapan berdasarkan data fitting terbaru">
    <x-slot:actions>
        <a href="{{ route('admin.fieldwork.booking', $booking) }}" class="inline-flex items-center gap-1.5 text-sm font-semibold text-brand no-underline hover:text-brand-dark">
            <i class="fas fa-arrow-left text-xs"></i> Kembali ke Tugas
        </a>
    </x-slot:actions>
</x-page-header>

<div class="space-y-5">
    @if(!$fitting)
        <x-empty-state icon="fa-shirt" title="Data fitting belum tersedia" text="Lengkapi dan simpan data fitting terlebih dahulu." />
    @else
        <form method="POST" action="{{ route('admin.packing.update', $booking) }}" class="space-y-5">
            @csrf
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
                @foreach($items as $item)
                    @php
                        $packed = in_array($item['key'], $packingState['checked'], true);
                        $packingCondition = $packingState['conditions'][$item['key']] ?? '';
                    @endphp
                <article class="min-w-0 bg-gray-50/50 p-4 space-y-4">
                    <div class="flex items-start justify-between gap-3">
                        <p class="min-w-0 font-semibold text-gray-800">{{ $item['label'] }}</p>
                        <label class="flex shrink-0 items-center gap-2 text-xs font-semibold text-emerald-700" for="packing-item-{{ $item['key'] }}">
                            <span>Sudah dipacking</span>
                            <input id="packing-item-{{ $item['key'] }}" type="checkbox" name="items[{{ $item['key'] }}][packed]" value="1" @checked($packed)
                                   class="h-5 w-5 cursor-pointer rounded border-gray-300 accent-emerald-600 focus:ring-2 focus:ring-emerald-500" style="accent-color: #16a34a">
                        </label>
                    </div>

                    @if($item['photo_path'])
                        <div class="my-3 flex justify-center">
                            <a href="{{ asset('storage/'.$item['photo_path']) }}" onclick="openProof(event, this.href)">
                                <img src="{{ asset('storage/'.$item['photo_path']) }}" alt="{{ $item['label'] }}" class="h-36 max-w-full rounded-lg border border-gray-100 object-contain">
                            </a>
                        </div>
                    @endif

                    <div class="mt-2 space-y-1 text-xs leading-relaxed text-gray-500">
                        <p><span class="font-medium text-gray-400">Keterangan fitting:</span> {{ $item['notes'] ?: '-' }}</p>
                        <p><span class="font-medium text-gray-400">Ukuran:</span> {{ $item['size'] ?: '-' }}</p>
                    </div>

                    <div class="mt-3">
                        <label for="packing-condition-{{ $item['key'] }}" class="mb-1 block text-xs font-medium text-gray-500">Kondisi</label>
                        <select id="packing-condition-{{ $item['key'] }}" name="items[{{ $item['key'] }}][condition]" class="w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm text-gray-700 focus:border-emerald-500 focus:ring-emerald-500">
                            <option value="">Pilih kondisi</option>
                            @foreach(\App\Models\Fitting::PACKING_CONDITIONS as $value => $label)
                                <option value="{{ $value }}" @selected($packingCondition === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                </article>
                @endforeach
            </div>
            <div class="sticky bottom-4 flex justify-end border-t border-gray-100 bg-white/95 pt-4 backdrop-blur">
                <x-button type="submit" color="success"><i class="fas fa-save"></i> Simpan Checklist</x-button>
            </div>
        </form>
    @endif
</div>
@endsection
