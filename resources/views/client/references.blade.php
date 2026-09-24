@extends('layouts.app')

@section('title', 'Referensi Saya')

@section('content')
<x-page-header title="Referensi Saya" subtitle="Kirim contoh visual yang ingin digunakan sebagai acuan tim ANITA." />

<div class="grid grid-cols-1 items-start gap-5 xl:grid-cols-[360px_1fr]">
    <x-card title="Upload Referensi" title-icon="fa-cloud-arrow-up">
        @if(!$booking)
            <x-empty-state icon="fa-calendar-xmark" title="Belum ada booking aktif" text="Referensi dapat diunggah setelah Anda memiliki booking aktif." />
        @elseif($referenceTypes->isEmpty())
            <x-empty-state icon="fa-lightbulb" title="Jenis referensi belum tersedia" text="Owner/Admin perlu menambahkan jenis referensi terlebih dahulu." />
        @else
            <form action="{{ route('client.references.store') }}" method="POST" enctype="multipart/form-data" class="space-y-4">
                @csrf
                <x-select name="reference_type_id" label="Jenis Referensi" required>
                    <option value="">Pilih jenis referensi</option>
                    @foreach($referenceTypes as $referenceType)
                        <option value="{{ $referenceType->id }}" @selected(old('reference_type_id') == $referenceType->id)>{{ $referenceType->name }}</option>
                    @endforeach
                </x-select>
                <x-textarea name="notes" label="Catatan" rows="3" placeholder="Contoh: warna pastel, tanpa bunga terlalu ramai">{{ old('notes') }}</x-textarea>
                <div>
                    <label for="referencePhotos" class="mb-1 block text-sm font-medium text-gray-600">Foto Referensi <span class="text-red-500">*</span></label>
                    <input id="referencePhotos" name="photos[]" type="file" accept="image/*" multiple required class="w-full rounded-xl border border-gray-200 bg-gray-50 px-3 py-2.5 text-sm text-gray-700 file:mr-3 file:rounded-lg file:border-0 file:bg-brand-50 file:px-3 file:py-1.5 file:font-semibold file:text-brand hover:file:bg-brand-100 focus:outline-none focus:ring-2 focus:ring-brand-200">
                    @error('photos')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    @error('photos.*')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <x-button color="primary" type="submit" class="w-full"><i class="fas fa-cloud-arrow-up"></i> Upload Referensi</x-button>
            </form>
        @endif
    </x-card>

    <div>
        <h2 class="mb-3 font-display text-lg font-bold text-gray-800">Referensi Terkirim</h2>
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            @forelse($references as $reference)
                <x-card padding="p-4" class="overflow-hidden">
                    <x-gallery-slider :photos="$reference->photo_urls" :title="$reference->referenceType->name" height="190px" />
                    <div class="mt-4 flex items-start justify-between gap-3">
                        <h3 class="min-w-0 font-display font-semibold text-gray-800">{{ $reference->referenceType->name }}</h3>
                        <div class="flex shrink-0 gap-2">
                            <x-button type="button" color="outline" size="sm" onclick="document.getElementById('editReference-{{ $reference->id }}').classList.remove('hidden')"><i class="fas fa-pen"></i></x-button>
                            <form method="POST" action="{{ route('client.references.destroy', $reference) }}" onsubmit="return confirm('Hapus referensi ini?')">
                                @csrf @method('DELETE')
                                <x-button type="submit" color="danger" size="sm" aria-label="Hapus referensi"><i class="fas fa-trash"></i></x-button>
                            </form>
                        </div>
                    </div>
                    @if($reference->notes)
                        <p class="mt-3 border-t border-gray-100 pt-3 text-sm leading-6 text-gray-600">{{ $reference->notes }}</p>
                    @endif
                </x-card>
            @empty
                <div class="md:col-span-2">
                    <x-card><x-empty-state icon="fa-images" title="Belum ada referensi" text="Pilih jenis referensi, lalu unggah foto acuan Anda." /></x-card>
                </div>
            @endforelse
        </div>
    </div>
</div>

@foreach($references as $reference)
    @php $photoPaths = $reference->photos ?? []; @endphp
    <div id="editReference-{{ $reference->id }}" class="fixed inset-0 z-[1100] {{ old('reference_id') == $reference->id ? '' : 'hidden' }} flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/50" onclick="document.getElementById('editReference-{{ $reference->id }}').classList.add('hidden')"></div>
        <div class="relative max-h-[90vh] w-full max-w-2xl overflow-y-auto rounded-2xl bg-white p-6 shadow-xl">
            <div class="mb-5 flex items-center justify-between">
                <h3 class="font-display text-lg font-bold text-gray-800"><i class="fas fa-pen-to-square mr-2 text-brand"></i>Edit Referensi</h3>
                <button type="button" class="text-gray-400" onclick="document.getElementById('editReference-{{ $reference->id }}').classList.add('hidden')"><i class="fas fa-xmark"></i></button>
            </div>
            <form action="{{ route('client.references.update', $reference) }}" method="POST" enctype="multipart/form-data" class="space-y-4">
                @csrf @method('PUT')
                <input type="hidden" name="reference_id" value="{{ $reference->id }}">
                <x-select name="reference_type_id" label="Jenis Referensi" required>
                    @foreach($referenceTypes as $referenceType)
                        <option value="{{ $referenceType->id }}" @selected((old('reference_id') == $reference->id ? old('reference_type_id') : $reference->reference_type_id) == $referenceType->id)>{{ $referenceType->name }}</option>
                    @endforeach
                </x-select>
                <div>
                    <label for="referenceNotes-{{ $reference->id }}" class="mb-1 block text-sm font-medium text-gray-600">Catatan</label>
                    <textarea id="referenceNotes-{{ $reference->id }}" name="notes" rows="3" class="w-full resize-none rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm text-gray-800 focus:outline-none focus:ring-2 focus:ring-brand-200">{{ old('reference_id') == $reference->id ? old('notes') : $reference->notes }}</textarea>
                </div>
                <div>
                    <p class="mb-2 text-sm font-medium text-gray-600">Foto saat ini</p>
                    <div class="grid grid-cols-2 gap-3 sm:grid-cols-3">
                        @foreach($reference->photo_urls as $index => $photoUrl)
                            <label class="overflow-hidden rounded-xl border border-gray-100 bg-gray-50">
                                <img src="{{ $photoUrl }}" alt="Foto referensi {{ $loop->iteration }}" class="h-28 w-full object-cover">
                                <span class="flex cursor-pointer items-center justify-center gap-1.5 border-t border-red-100 bg-red-50 px-2 py-2 text-xs font-medium text-red-700">
                                    <input type="checkbox" name="remove_photos[]" value="{{ $photoPaths[$index] ?? '' }}" class="rounded border-gray-300 text-red-600 focus:ring-red-500"> Hapus
                                </span>
                            </label>
                        @endforeach
                    </div>
                </div>
                <div>
                    <label for="referenceNewPhotos-{{ $reference->id }}" class="mb-1 block text-sm font-medium text-gray-600">Tambah Foto</label>
                    <input id="referenceNewPhotos-{{ $reference->id }}" name="photos[]" type="file" accept="image/*" multiple class="w-full rounded-xl border border-gray-200 bg-gray-50 px-3 py-2.5 text-sm text-gray-700 file:mr-3 file:rounded-lg file:border-0 file:bg-brand-50 file:px-3 file:py-1.5 file:font-semibold file:text-brand">
                    @if(old('reference_id') == $reference->id) @error('photos')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror @endif
                </div>
                <x-button color="primary" type="submit" class="w-full"><i class="fas fa-save"></i> Simpan Perubahan</x-button>
            </form>
        </div>
    </div>
@endforeach

<x-gallery-lightbox />
@endsection
