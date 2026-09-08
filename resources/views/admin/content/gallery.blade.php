@extends('layouts.app')

@section('title', 'Galeri')

@section('content')
<x-page-header title="Konten Website — Galeri" subtitle="Setiap galeri berisi minimal tiga foto yang dapat digeser di landing">
    <x-slot:actions>
        <x-button href="{{ route('admin.content.testimonials') }}" color="ghost"><i class="fas fa-quote-right"></i> Testimoni</x-button>
        <x-button href="{{ route('admin.content.faqs') }}" color="ghost"><i class="fas fa-circle-question"></i> FAQ</x-button>
        <x-button href="{{ route('admin.content.settings') }}" color="ghost"><i class="fas fa-gear"></i> Pengaturan</x-button>
    </x-slot:actions>
</x-page-header>

<div class="grid grid-cols-1 lg:grid-cols-4 gap-5">
    {{-- FORM TAMBAH --}}
    <div>
        <x-card title="Tambah Galeri" title-icon="fa-images">
            <form id="galleryForm" action="{{ route('admin.content.gallery.store') }}" method="POST" enctype="multipart/form-data" class="space-y-3">
                @csrf
                <div>
                    <label for="galleryPhotos" class="block text-sm font-medium text-gray-600 mb-1">Foto Galeri <span class="text-red-500">*</span></label>
                    <input id="galleryPhotos" name="photos[]" type="file" accept="image/*" multiple required class="w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm text-gray-800 focus:outline-none focus:ring-2 focus:ring-brand-200">
                    <p id="galleryPhotoHint" class="mt-1 text-xs text-gray-400">Pilih minimal 3 foto. Foto pertama menjadi cover galeri.</p>
                    @error('photos')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    @error('photos.*')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <x-input name="title" label="Judul" placeholder="cth: Wedding Adinda & Raka" />
                <x-input name="category" label="Kategori" placeholder="cth: wedding, prewedding" />
                <x-button color="primary" type="submit" class="w-full"><i class="fas fa-upload"></i> Simpan Galeri</x-button>
            </form>
        </x-card>
    </div>

    {{-- GRID --}}
    <div class="lg:col-span-3">
        <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
            @forelse($gallery as $item)
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                @php($photos = $item->photo_urls)
                <div role="button" tabindex="0" data-gallery-lightbox data-gallery-photos="{{ base64_encode(json_encode($photos)) }}" data-gallery-title="{{ $item->title ?? 'Foto galeri' }}" class="relative h-44 overflow-hidden bg-gray-50 cursor-pointer">
                    <img src="{{ $photos[0] }}" alt="{{ $item->title ?? 'Foto galeri' }}" class="w-full h-44 object-cover">
                    @if(count($photos) > 1)
                    <span class="absolute bottom-2 right-2 rounded-full bg-black/55 px-2 py-0.5 text-xs font-medium text-white">{{ count($photos) }} foto</span>
                    @endif
                </div>
                <div class="px-4 py-3">
                    <p class="text-sm font-semibold text-gray-800 truncate">{{ $item->title ?? 'Tanpa judul' }}</p>
                    <div class="flex items-center justify-between mt-1">
                        <span class="text-xs text-gray-500">{{ $item->category ?? '-' }} · {{ count($item->photo_urls) }} foto</span>
                        <form method="POST" action="{{ route('admin.content.gallery.destroy', $item) }}" onsubmit="return confirm('Hapus galeri ini beserta seluruh fotonya?')">
                            @csrf @method('DELETE')
                            <x-button size="sm" color="danger" type="submit"><i class="fas fa-trash"></i></x-button>
                        </form>
                    </div>
                </div>
            </div>
            @empty
            <div class="col-span-full">
                <x-card><x-empty-state icon="fa-image" title="Belum ada foto galeri" /></x-card>
            </div>
            @endforelse
        </div>
        @if($gallery->hasPages())
        <div class="mt-4">{{ $gallery->links() }}</div>
        @endif
    </div>
</div>

@push('scripts')
<script>
    const galleryPhotos = document.getElementById('galleryPhotos');
    const galleryPhotoHint = document.getElementById('galleryPhotoHint');
    const galleryForm = document.getElementById('galleryForm');

    galleryPhotos.addEventListener('change', () => {
        const count = galleryPhotos.files.length;
        galleryPhotoHint.textContent = count ? `${count} foto dipilih${count < 3 ? ' — minimal 3 foto.' : '.'}` : 'Pilih minimal 3 foto. Foto pertama menjadi cover galeri.';
        galleryPhotoHint.classList.toggle('text-red-600', count > 0 && count < 3);
        galleryPhotoHint.classList.toggle('text-gray-400', count === 0 || count >= 3);
    });

    galleryForm.addEventListener('submit', (event) => {
        if (galleryPhotos.files.length < 3) {
            event.preventDefault();
            galleryPhotoHint.textContent = 'Pilih minimal 3 foto sebelum menyimpan galeri.';
            galleryPhotoHint.classList.add('text-red-600');
        }
    });

</script>
<x-gallery-lightbox />
@endpush
@endsection
