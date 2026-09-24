<x-card title="Referensi Klien" title-icon="fa-images" data-summary-collapse>
    <x-slot:actions>
        <div class="flex items-center gap-3">
            <button type="button" onclick="document.getElementById('addBookingReferenceModal').classList.remove('hidden')" class="text-xs font-semibold text-brand hover:text-brand-dark"><i class="fas fa-plus mr-1"></i>Tambah</button>
            <button type="button" data-summary-toggle aria-expanded="true" class="text-xs font-semibold text-brand hover:text-brand-dark"><i class="fas fa-eye-slash mr-1"></i><span>Hide</span></button>
        </div>
    </x-slot:actions>

    <div data-summary-content>
        @if($booking->references->isEmpty())
            <x-empty-state icon="fa-images" title="Belum ada referensi klien" />
        @else
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                @foreach($booking->references->sortByDesc('created_at') as $reference)
                    <article class="rounded-xl border border-gray-100 bg-gray-50/60 p-3">
                        <x-gallery-slider :photos="$reference->photo_urls" :title="$reference->referenceType->name" height="180px" />
                        <div class="mt-3 flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <h3 class="font-semibold text-gray-800">{{ $reference->referenceType->name }}</h3>
                                @if($reference->notes)<p class="mt-2 text-sm leading-6 text-gray-600">{{ $reference->notes }}</p>@endif
                            </div>
                            <div class="flex shrink-0 gap-2">
                                <x-button type="button" color="outline" size="sm" onclick="document.getElementById('editBookingReference-{{ $reference->id }}').classList.remove('hidden')"><i class="fas fa-pen"></i></x-button>
                                <form method="POST" action="{{ route('admin.bookings.references.destroy', [$booking, $reference]) }}" onsubmit="return confirm('Hapus referensi ini?')">
                                    @csrf @method('DELETE')
                                    <x-button type="submit" color="danger" size="sm"><i class="fas fa-trash"></i></x-button>
                                </form>
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>
        @endif
    </div>
</x-card>

<div id="addBookingReferenceModal" class="fixed inset-0 z-[1100] hidden flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-black/50" onclick="document.getElementById('addBookingReferenceModal').classList.add('hidden')"></div>
    <div class="relative w-full max-w-lg rounded-2xl bg-white p-6 shadow-xl">
        <div class="mb-5 flex items-center justify-between">
            <h3 class="font-display text-lg font-bold text-gray-800"><i class="fas fa-images mr-2 text-brand"></i>Tambah Referensi</h3>
            <button type="button" class="text-gray-400" onclick="document.getElementById('addBookingReferenceModal').classList.add('hidden')"><i class="fas fa-xmark"></i></button>
        </div>
        <form action="{{ route('admin.bookings.references.store', $booking) }}" method="POST" enctype="multipart/form-data" class="space-y-4">
            @csrf
            <x-select name="reference_type_id" label="Jenis Referensi" required>
                <option value="">Pilih jenis referensi</option>
                @foreach($referenceTypes as $referenceType)<option value="{{ $referenceType->id }}">{{ $referenceType->name }}</option>@endforeach
            </x-select>
            <x-textarea name="notes" label="Catatan" rows="3" />
            <div>
                <label for="adminReferencePhotos" class="mb-1 block text-sm font-medium text-gray-600">Foto Referensi <span class="text-red-500">*</span></label>
                <input id="adminReferencePhotos" name="photos[]" type="file" accept="image/*" multiple required class="w-full rounded-xl border border-gray-200 bg-gray-50 px-3 py-2.5 text-sm text-gray-700 file:mr-3 file:rounded-lg file:border-0 file:bg-brand-50 file:px-3 file:py-1.5 file:font-semibold file:text-brand">
            </div>
            <x-button color="primary" type="submit" class="w-full"><i class="fas fa-save"></i> Simpan Referensi</x-button>
        </form>
    </div>
</div>

@foreach($booking->references as $reference)
    @php $photoPaths = $reference->photos ?? []; @endphp
    <div id="editBookingReference-{{ $reference->id }}" class="fixed inset-0 z-[1100] hidden flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/50" onclick="document.getElementById('editBookingReference-{{ $reference->id }}').classList.add('hidden')"></div>
        <div class="relative max-h-[90vh] w-full max-w-2xl overflow-y-auto rounded-2xl bg-white p-6 shadow-xl">
            <div class="mb-5 flex items-center justify-between">
                <h3 class="font-display text-lg font-bold text-gray-800"><i class="fas fa-pen-to-square mr-2 text-brand"></i>Edit Referensi</h3>
                <button type="button" class="text-gray-400" onclick="document.getElementById('editBookingReference-{{ $reference->id }}').classList.add('hidden')"><i class="fas fa-xmark"></i></button>
            </div>
            <form action="{{ route('admin.bookings.references.update', [$booking, $reference]) }}" method="POST" enctype="multipart/form-data" class="space-y-4">
                @csrf @method('PUT')
                <x-select name="reference_type_id" label="Jenis Referensi" required>
                    @foreach($referenceTypes as $referenceType)<option value="{{ $referenceType->id }}" @selected($reference->reference_type_id == $referenceType->id)>{{ $referenceType->name }}</option>@endforeach
                </x-select>
                <div>
                    <label for="adminReferenceNotes-{{ $reference->id }}" class="mb-1 block text-sm font-medium text-gray-600">Catatan</label>
                    <textarea id="adminReferenceNotes-{{ $reference->id }}" name="notes" rows="3" class="w-full resize-none rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm text-gray-800 focus:outline-none focus:ring-2 focus:ring-brand-200">{{ $reference->notes }}</textarea>
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
                    <label for="adminReferenceNewPhotos-{{ $reference->id }}" class="mb-1 block text-sm font-medium text-gray-600">Tambah Foto</label>
                    <input id="adminReferenceNewPhotos-{{ $reference->id }}" name="photos[]" type="file" accept="image/*" multiple class="w-full rounded-xl border border-gray-200 bg-gray-50 px-3 py-2.5 text-sm text-gray-700 file:mr-3 file:rounded-lg file:border-0 file:bg-brand-50 file:px-3 file:py-1.5 file:font-semibold file:text-brand">
                </div>
                <x-button color="primary" type="submit" class="w-full"><i class="fas fa-save"></i> Simpan Perubahan</x-button>
            </form>
        </div>
    </div>
@endforeach
