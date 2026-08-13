@extends('layouts.app')

@section('title', 'Galeri')

@section('content')
<x-page-header title="Konten Website — Galeri" subtitle="Foto yang tampil di halaman galeri landing">
    <x-slot:actions>
        <x-button href="{{ route('admin.content.testimonials') }}" color="ghost"><i class="fas fa-quote-right"></i> Testimoni</x-button>
        <x-button href="{{ route('admin.content.faqs') }}" color="ghost"><i class="fas fa-circle-question"></i> FAQ</x-button>
        <x-button href="{{ route('admin.content.settings') }}" color="ghost"><i class="fas fa-gear"></i> Pengaturan</x-button>
    </x-slot:actions>
</x-page-header>

<div class="grid grid-cols-1 lg:grid-cols-4 gap-5">
    {{-- FORM TAMBAH --}}
    <div>
        <x-card title="Tambah Foto" title-icon="fa-image">
            <form action="{{ route('admin.content.gallery.store') }}" method="POST" enctype="multipart/form-data" class="space-y-3">
                @csrf
                <x-input name="photo" label="Foto" type="file" accept="image/*" required />
                <x-input name="title" label="Judul" placeholder="cth: Wedding Adinda & Raka" />
                <x-input name="category" label="Kategori" placeholder="cth: wedding, prewedding" />
                <x-button color="primary" type="submit" class="w-full"><i class="fas fa-upload"></i> Upload</x-button>
            </form>
        </x-card>
    </div>

    {{-- GRID --}}
    <div class="lg:col-span-3">
        <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
            @forelse($gallery as $photo)
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <a href="{{ $photo->image_url }}" target="_blank">
                    <img src="{{ $photo->image_url }}" alt="{{ $photo->title ?? 'Foto galeri' }}" class="w-full h-44 object-cover">
                </a>
                <div class="px-4 py-3">
                    <p class="text-sm font-semibold text-gray-800 truncate">{{ $photo->title ?? 'Tanpa judul' }}</p>
                    <div class="flex items-center justify-between mt-1">
                        <span class="text-xs text-gray-500">{{ $photo->category ?? '-' }}</span>
                        <form method="POST" action="{{ route('admin.content.gallery.destroy', $photo) }}" onsubmit="return confirm('Hapus foto ini?')">
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
@endsection
