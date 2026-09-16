@extends('layouts.app')

@section('title', 'Master Pelaminan')

@section('content')
<x-page-header title="Master Pelaminan" subtitle="Nama, ukuran, dan foto pelaminan yang tersedia untuk survey lapangan">
    <x-slot:actions>
        <x-button color="primary" onclick="document.getElementById('addWeddingStageModal').classList.remove('hidden')">
            <i class="fas fa-plus"></i> Tambah Pelaminan
        </x-button>
    </x-slot:actions>
</x-page-header>

<x-card padding="p-0">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="text-left text-gray-500 border-b border-gray-100 bg-cream/60">
                    <th class="px-5 py-3 font-medium">Foto</th>
                    <th class="px-5 py-3 font-medium">Nama Pelaminan</th>
                    <th class="px-5 py-3 font-medium">Ukuran</th>
                    <th class="px-5 py-3 font-medium">Status</th>
                    <th class="px-5 py-3 font-medium text-center">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($weddingStages as $weddingStage)
                <tr class="border-b border-gray-50 hover:bg-brand-50/30 transition-colors">
                    <td class="px-5 py-3">
                        @if($weddingStage->photo_urls)
                            <button type="button" data-gallery-lightbox data-gallery-photos="{{ base64_encode(json_encode($weddingStage->photo_urls)) }}" data-gallery-title="{{ $weddingStage->name }}" aria-label="Lihat foto {{ $weddingStage->name }}" class="relative inline-block cursor-zoom-in">
                                <img src="{{ $weddingStage->photo_urls[0] }}" alt="{{ $weddingStage->name }}" class="h-14 w-20 object-cover rounded-xl border border-gray-100">
                                @if(count($weddingStage->photo_urls) > 1)<span class="absolute -right-2 -top-2 rounded-full bg-brand px-1.5 py-0.5 text-[10px] font-semibold text-white">{{ count($weddingStage->photo_urls) }}</span>@endif
                            </button>
                        @else
                            <span class="inline-flex h-14 w-20 items-center justify-center rounded-xl bg-brand-50 text-brand"><i class="fas fa-panorama"></i></span>
                        @endif
                    </td>
                    <td class="px-5 py-3 font-semibold text-gray-800">{{ $weddingStage->name }}</td>
                    <td class="px-5 py-3 text-gray-600">{{ $weddingStage->size ?: '-' }}</td>
                    <td class="px-5 py-3"><x-badge :color="$weddingStage->is_active ? 'success' : 'gray'">{{ $weddingStage->is_active ? 'Aktif' : 'Nonaktif' }}</x-badge></td>
                    <td class="px-5 py-3">
                        <div class="flex items-center justify-center gap-2">
                            <x-button size="sm" color="outline" onclick="openWeddingStageEdit({{ $weddingStage->id }})"><i class="fas fa-pen"></i></x-button>
                            <form method="POST" action="{{ route('admin.wedding-stages.destroy', $weddingStage) }}" onsubmit="return confirm('Hapus pelaminan ini?')">
                                @csrf @method('DELETE')
                                <x-button size="sm" color="danger" type="submit"><i class="fas fa-trash"></i></x-button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="5"><x-empty-state icon="fa-panorama" title="Belum ada pelaminan" /></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-card>

<div id="addWeddingStageModal" class="hidden fixed inset-0 z-[1100] flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-black/50" onclick="this.parentElement.classList.add('hidden')"></div>
    <div class="relative bg-white rounded-2xl shadow-xl w-full max-w-md max-h-[90vh] overflow-y-auto p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="font-display font-bold text-gray-800"><i class="fas fa-panorama text-brand mr-2"></i>Tambah Pelaminan</h3>
            <button type="button" class="text-gray-400 hover:text-gray-600" onclick="document.getElementById('addWeddingStageModal').classList.add('hidden')"><i class="fas fa-xmark"></i></button>
        </div>
        <form action="{{ route('admin.wedding-stages.store') }}" method="POST" enctype="multipart/form-data" class="space-y-3">
            @csrf
            <x-input name="name" label="Nama Pelaminan" required placeholder="Contoh: Garden Modern" />
            <x-input name="size" label="Ukuran Pelaminan" placeholder="Contoh: 6 x 3 m" />
            <div>
                <label class="block text-sm font-medium text-gray-600 mb-1">Foto Pelaminan (bisa pilih beberapa)</label>
                <input name="photos[]" type="file" accept="image/*" multiple required class="w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm text-gray-800 focus:outline-none focus:ring-2 focus:ring-brand-200">
                <p class="mt-1 text-xs text-gray-400">Pilih satu atau beberapa foto, maksimal 5 MB per foto.</p>
                @error('photos')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                @error('photos.*')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>
            <input type="hidden" name="is_active" value="1">
            <x-button color="primary" type="submit" class="w-full"><i class="fas fa-save"></i> Simpan</x-button>
        </form>
    </div>
</div>

@foreach($weddingStages as $weddingStage)
<div id="editWeddingStage-{{ $weddingStage->id }}" class="hidden fixed inset-0 z-[1100] flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-black/50" onclick="this.parentElement.classList.add('hidden')"></div>
    <div class="relative bg-white rounded-2xl shadow-xl w-full max-w-md max-h-[90vh] overflow-y-auto p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="font-display font-bold text-gray-800"><i class="fas fa-pen-to-square text-brand mr-2"></i>Edit Pelaminan</h3>
            <button type="button" class="text-gray-400 hover:text-gray-600" onclick="document.getElementById('editWeddingStage-{{ $weddingStage->id }}').classList.add('hidden')"><i class="fas fa-xmark"></i></button>
        </div>
        <form action="{{ route('admin.wedding-stages.update', $weddingStage) }}" method="POST" enctype="multipart/form-data" class="space-y-3" onsubmit="return confirmPhotoRemoval(this)">
            @csrf @method('PUT')
            <x-input name="name" label="Nama Pelaminan" :value="$weddingStage->name" required />
            <x-input name="size" label="Ukuran Pelaminan" :value="$weddingStage->size" placeholder="Contoh: 6 x 3 m" />
            @if($weddingStage->photo_urls)
                @php($photoPaths = array_values(array_filter($weddingStage->photos ?: [$weddingStage->photo_path])))
                <div class="grid grid-cols-3 gap-2">
                    @foreach($weddingStage->photo_urls as $photoIndex => $photoUrl)
                        @php($photoPath = $photoPaths[$photoIndex] ?? $photoUrl)
                        <div class="relative overflow-hidden rounded-lg">
                        <button type="button" data-gallery-lightbox data-gallery-photos="{{ base64_encode(json_encode($weddingStage->photo_urls)) }}" data-gallery-title="{{ $weddingStage->name }}" aria-label="Lihat foto {{ $weddingStage->name }}" class="block w-full cursor-zoom-in">
                            <img src="{{ $photoUrl }}" alt="{{ $weddingStage->name }} - foto {{ $loop->iteration }}" class="h-20 w-full rounded-lg border border-gray-100 object-cover">
                        </button>
                        <label class="absolute inset-x-1 bottom-1 flex cursor-pointer items-center justify-center gap-1 rounded-lg bg-red-100/95 px-2 py-1 text-[10px] font-medium text-red-700 shadow-sm hover:bg-red-200">
                            <input type="checkbox" name="remove_photos[]" value="{{ $photoPath }}" class="h-3.5 w-3.5 rounded border-gray-300 text-red-600 focus:ring-red-500">
                            <span><i class="fas fa-trash-can mr-0.5"></i> Hapus</span>
                        </label>
                        </div>
                    @endforeach
                </div>
                <p class="text-xs text-gray-500">Centang foto yang ingin dihapus, lalu simpan perubahan.</p>
            @endif
            <div>
                <label class="block text-sm font-medium text-gray-600 mb-1">Tambah foto lain (opsional)</label>
                <input name="photos[]" type="file" accept="image/*" multiple class="w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm text-gray-800 focus:outline-none focus:ring-2 focus:ring-brand-200">
                @error('photos')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                @error('photos.*')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="wedding-stage-status-{{ $weddingStage->id }}" class="block text-sm font-medium text-gray-600 mb-1">Status</label>
                <select name="is_active" id="wedding-stage-status-{{ $weddingStage->id }}" class="w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm text-gray-800 focus:outline-none focus:ring-2 focus:ring-brand-200">
                    <option value="1" @selected($weddingStage->is_active)>Aktif</option>
                    <option value="0" @selected(!$weddingStage->is_active)>Nonaktif</option>
                </select>
            </div>
            <x-button color="primary" type="submit" class="w-full"><i class="fas fa-save"></i> Simpan Perubahan</x-button>
        </form>
    </div>
</div>
@endforeach

<script>
    function openWeddingStageEdit(id) {
        document.getElementById('editWeddingStage-' + id).classList.remove('hidden');
    }
</script>
@endsection

@push('scripts')
    <x-gallery-lightbox />
    <script>
        function confirmPhotoRemoval(form) {
            const count = form.querySelectorAll('input[name="remove_photos[]"]:checked').length;
            return count === 0 || confirm(`Hapus ${count} foto yang dipilih? Foto tidak dapat dipulihkan.`);
        }
    </script>
@endpush
