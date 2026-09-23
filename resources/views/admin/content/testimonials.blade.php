@extends('layouts.app')

@section('title', 'Testimoni')

@section('content')
<x-page-header title="Konten Website — Testimoni" subtitle="Testimoni yang tampil di halaman landing (status published)">
    <x-slot:actions>
        <x-button href="{{ route('admin.content.gallery') }}" color="ghost"><i class="fas fa-image"></i> Galeri</x-button>
        <x-button href="{{ route('admin.content.faqs') }}" color="ghost"><i class="fas fa-circle-question"></i> FAQ</x-button>
        <x-button href="{{ route('admin.content.settings') }}" color="ghost"><i class="fas fa-gear"></i> Pengaturan</x-button>
    </x-slot:actions>
</x-page-header>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
    {{-- FORM TAMBAH --}}
    <div>
        <x-card title="Tambah Testimoni" title-icon="fa-quote-right">
            <form action="{{ route('admin.content.testimonials.store') }}" method="POST" enctype="multipart/form-data" class="space-y-3">
                @csrf
                <x-input name="client_name" label="Nama Client" required placeholder="cth: Siska" />
                <div>
                    <label for="rating" class="block text-sm font-medium text-gray-600 mb-1">Rating <span class="text-red-500">*</span></label>
                    <select name="rating" id="rating" required
                            class="w-full rounded-lg border bg-gray-50 px-3 py-2 text-sm text-gray-800 focus:outline-none focus:ring-2 focus:ring-brand-200 border-gray-200">
                        @for($i = 5; $i >= 1; $i--)
                            <option value="{{ $i }}">{{ $i }} {{ $i === 1 ? 'bintang' : 'bintang' }}</option>
                        @endfor
                    </select>
                </div>
                <x-textarea name="content" label="Isi Testimoni" rows="4" required></x-textarea>
                <div>
                    <label for="testimonialPhotos" class="block text-sm font-medium text-gray-600 mb-1">Foto Klien</label>
                    <input id="testimonialPhotos" name="photos[]" type="file" accept="image/*" multiple class="w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm text-gray-800 focus:outline-none focus:ring-2 focus:ring-brand-200">
                    <p class="mt-1 text-xs text-gray-400">Bisa pilih beberapa foto, maksimal 3 MB per foto.</p>
                    @error('photos')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    @error('photos.*')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="status" class="block text-sm font-medium text-gray-600 mb-1">Status</label>
                    <select name="status" id="status"
                            class="w-full rounded-lg border bg-gray-50 px-3 py-2 text-sm text-gray-800 focus:outline-none focus:ring-2 focus:ring-brand-200 border-gray-200">
                        <option value="published">Published</option>
                        <option value="hidden">Hidden</option>
                    </select>
                </div>
                <x-button color="primary" type="submit" class="w-full"><i class="fas fa-plus"></i> Tambah</x-button>
            </form>
        </x-card>
    </div>

    {{-- DAFTAR --}}
    <div class="lg:col-span-2">
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            @forelse($testimonials as $testimonial)
                @php
                    $photos = $testimonial->photo_urls;
                @endphp
                <x-card padding="p-0" class="flex h-full flex-col overflow-hidden">
                    @if($photos)
                        <button type="button" data-gallery-lightbox data-gallery-photos="{{ base64_encode(json_encode($photos)) }}" data-gallery-title="Foto {{ $testimonial->client_name }}" aria-label="Lihat foto {{ $testimonial->client_name }}" class="relative h-36 w-full cursor-zoom-in overflow-hidden bg-gray-50">
                            <img src="{{ $photos[0] }}" class="h-full w-full object-cover" alt="Foto {{ $testimonial->client_name }}">
                            @if(count($photos) > 1)<span class="absolute bottom-2 right-2 rounded-full bg-black/55 px-2 py-0.5 text-xs font-medium text-white">{{ count($photos) }} foto</span>@endif
                        </button>
                    @else
                        <div class="flex h-36 items-center justify-center bg-brand-50 text-3xl font-bold text-brand">{{ strtoupper(substr($testimonial->client_name, 0, 1)) }}</div>
                    @endif
                    <div class="flex flex-1 flex-col p-4">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <h3 class="font-semibold text-gray-800">{{ $testimonial->client_name }}</h3>
                                @if($testimonial->booking)
                                    <p class="mt-0.5 text-xs font-medium text-gray-400">Booking {{ $testimonial->booking->code }}</p>
                                @endif
                                <div class="mt-1 text-sm text-amber-500">@for($i = 1; $i <= 5; $i++)<i class="fas fa-star {{ $i <= $testimonial->rating ? '' : 'text-gray-200' }}"></i>@endfor</div>
                            </div>
                            <x-badge :color="$testimonial->status === 'published' ? 'success' : 'gray'">{{ $testimonial->status }}</x-badge>
                        </div>
                        <p class="mt-3 flex-1 text-sm leading-6 text-gray-600">{{ $testimonial->content }}</p>
                        <div class="mt-4 flex justify-end gap-2 border-t border-gray-100 pt-3">
                            <x-button size="sm" color="outline" type="button" onclick="document.getElementById('editTestimonial-{{ $testimonial->id }}').classList.remove('hidden')"><i class="fas fa-pen"></i> Edit</x-button>
                            <form method="POST" action="{{ route('admin.content.testimonials.destroy', $testimonial) }}" onsubmit="return confirm('Hapus testimoni ini?')">
                                @csrf @method('DELETE')
                                <x-button size="sm" color="danger" type="submit"><i class="fas fa-trash"></i> Hapus</x-button>
                            </form>
                        </div>
                    </div>
                </x-card>
            @empty
                <div class="md:col-span-2">
                    <x-card><x-empty-state icon="fa-quote-right" title="Belum ada testimoni" /></x-card>
                </div>
            @endforelse
        </div>
        @if($testimonials->hasPages())
        <div class="mt-4">{{ $testimonials->links() }}</div>
        @endif
    </div>
</div>

@foreach($testimonials as $testimonial)
    @php
        $photos = $testimonial->photo_urls;
        $photoPaths = array_values(array_filter($testimonial->photos ?: [$testimonial->photo]));
    @endphp
    <div id="editTestimonial-{{ $testimonial->id }}" class="fixed inset-0 z-[1100] hidden flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/50" onclick="document.getElementById('editTestimonial-{{ $testimonial->id }}').classList.add('hidden')"></div>
        <div class="relative max-h-[90vh] w-full max-w-lg overflow-y-auto rounded-2xl bg-white p-6 shadow-xl">
            <div class="mb-4 flex items-center justify-between">
                <h3 class="font-display font-bold text-gray-800"><i class="fas fa-pen-to-square mr-2 text-brand"></i>Edit Testimoni</h3>
                <button type="button" class="text-gray-400 hover:text-gray-600" onclick="document.getElementById('editTestimonial-{{ $testimonial->id }}').classList.add('hidden')"><i class="fas fa-xmark"></i></button>
            </div>
            <form action="{{ route('admin.content.testimonials.update', $testimonial) }}" method="POST" enctype="multipart/form-data" class="space-y-3">
                @csrf @method('PUT')
                <x-input name="client_name" label="Nama Klien" :value="$testimonial->client_name" required />
                <div>
                    <label for="testimonial-rating-{{ $testimonial->id }}" class="mb-1 block text-sm font-medium text-gray-600">Rating <span class="text-red-500">*</span></label>
                    <select name="rating" id="testimonial-rating-{{ $testimonial->id }}" required class="w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm text-gray-800 focus:outline-none focus:ring-2 focus:ring-brand-200">
                        @for($i = 5; $i >= 1; $i--)
                            <option value="{{ $i }}" @selected($testimonial->rating === $i)>{{ $i }} bintang</option>
                        @endfor
                    </select>
                </div>
                <x-textarea name="content" label="Isi Testimoni" rows="4" required>{{ $testimonial->content }}</x-textarea>
                @if($photos)
                    <div>
                        <p class="mb-1 text-sm font-medium text-gray-600">Foto saat ini</p>
                        <div class="grid grid-cols-3 gap-2">
                            @foreach($photos as $index => $photoUrl)
                                <div class="relative overflow-hidden rounded-lg">
                                    <button type="button" data-gallery-lightbox data-gallery-photos="{{ base64_encode(json_encode($photos)) }}" data-gallery-title="Foto {{ $testimonial->client_name }}" aria-label="Lihat foto {{ $testimonial->client_name }}" class="block w-full cursor-zoom-in">
                                        <img src="{{ $photoUrl }}" alt="Foto {{ $testimonial->client_name }}" class="h-20 w-full rounded-lg border border-gray-100 object-cover">
                                    </button>
                                    <label class="absolute inset-x-1 bottom-1 flex cursor-pointer items-center justify-center gap-1 rounded-lg bg-red-100/95 px-2 py-1 text-[10px] font-medium text-red-700 shadow-sm hover:bg-red-200">
                                        <input type="checkbox" name="remove_photos[]" value="{{ $photoPaths[$index] ?? '' }}" class="h-3.5 w-3.5 rounded border-gray-300 text-red-600 focus:ring-red-500">
                                        <span><i class="fas fa-trash-can mr-0.5"></i> Hapus</span>
                                    </label>
                                </div>
                            @endforeach
                        </div>
                        <p class="mt-1 text-xs text-gray-400">Centang foto yang ingin dihapus.</p>
                    </div>
                @endif
                <div>
                    <label for="testimonial-photos-{{ $testimonial->id }}" class="mb-1 block text-sm font-medium text-gray-600">Tambah Foto</label>
                    <input id="testimonial-photos-{{ $testimonial->id }}" name="photos[]" type="file" accept="image/*" multiple class="w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm text-gray-800 focus:outline-none focus:ring-2 focus:ring-brand-200">
                    <p class="mt-1 text-xs text-gray-400">Bisa pilih beberapa foto, maksimal 3 MB per foto.</p>
                </div>
                <div>
                    <label for="testimonial-status-{{ $testimonial->id }}" class="mb-1 block text-sm font-medium text-gray-600">Status</label>
                    <select name="status" id="testimonial-status-{{ $testimonial->id }}" class="w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm text-gray-800 focus:outline-none focus:ring-2 focus:ring-brand-200">
                        <option value="published" @selected($testimonial->status === 'published')>Published</option>
                        <option value="hidden" @selected($testimonial->status === 'hidden')>Hidden</option>
                    </select>
                </div>
                <x-button color="primary" type="submit" class="w-full"><i class="fas fa-save"></i> Simpan Perubahan</x-button>
            </form>
        </div>
    </div>
@endforeach

@push('scripts')
<x-gallery-lightbox />
@endpush
@endsection
