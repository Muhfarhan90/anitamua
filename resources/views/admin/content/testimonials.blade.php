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
                <x-input name="photo" label="Foto Client" type="file" accept="image/*" />
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
        <x-card padding="p-0">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left text-gray-500 border-b border-gray-100 bg-cream/60">
                            <th class="px-5 py-2.5 font-medium">Client</th>
                            <th class="px-5 py-2.5 font-medium">Rating</th>
                            <th class="px-5 py-2.5 font-medium">Testimoni</th>
                            <th class="px-5 py-2.5 font-medium">Status</th>
                            <th class="px-5 py-2.5 font-medium text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($testimonials as $testimonial)
                        <tr class="border-b border-gray-50 hover:bg-brand-50/30 transition-colors">
                            <td class="px-5 py-3">
                                <div class="flex items-center gap-3">
                                    @if($testimonial->photo)
                                        <img src="{{ asset('storage/'.$testimonial->photo) }}" class="w-9 h-9 rounded-full object-cover border border-gray-100" alt="">
                                    @else
                                        <div class="w-9 h-9 rounded-full bg-brand text-white flex items-center justify-center text-xs font-bold flex-shrink-0">{{ strtoupper(substr($testimonial->client_name, 0, 1)) }}</div>
                                    @endif
                                    <span class="font-semibold text-gray-800">{{ $testimonial->client_name }}</span>
                                </div>
                            </td>
                            <td class="px-5 py-3">
                                <span class="text-amber-500 text-sm">@for($i = 1; $i <= 5; $i++)<i class="fas fa-star {{ $i <= $testimonial->rating ? '' : 'text-gray-200' }}"></i>@endfor</span>
                            </td>
                            <td class="px-5 py-3 text-gray-600 max-w-[260px] truncate">{{ $testimonial->content }}</td>
                            <td class="px-5 py-3">
                                <x-badge :color="$testimonial->status === 'published' ? 'success' : 'gray'">{{ $testimonial->status }}</x-badge>
                            </td>
                            <td class="px-5 py-3 text-center">
                                <form method="POST" action="{{ route('admin.content.testimonials.destroy', $testimonial) }}" onsubmit="return confirm('Hapus testimoni ini?')">
                                    @csrf @method('DELETE')
                                    <x-button size="sm" color="danger" type="submit"><i class="fas fa-trash"></i></x-button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5"><x-empty-state icon="fa-quote-right" title="Belum ada testimoni" /></td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-card>
        @if($testimonials->hasPages())
        <div class="mt-4">{{ $testimonials->links() }}</div>
        @endif
    </div>
</div>
@endsection
