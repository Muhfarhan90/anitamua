@extends('layouts.app')

@section('title', 'Promo Banner')

@section('content')
<x-page-header title="Promo Banner" subtitle="Banner popup tengah yang tampil di landing page (ada tombol silang untuk menutup)">
    <x-slot:actions>
        <x-button onclick="openBannerModal()"><i class="fas fa-bullhorn"></i> Tambah Promo</x-button>
    </x-slot:actions>
</x-page-header>

<x-card padding="p-0">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="text-left text-gray-500 border-b border-gray-100 bg-cream/60">
                    <th class="px-5 py-2.5 font-medium">Gambar</th>
                    <th class="px-5 py-2.5 font-medium">Judul</th>
                    <th class="px-5 py-2.5 font-medium">Periode</th>
                    <th class="px-5 py-2.5 font-medium">Status</th>
                    <th class="px-5 py-2.5 font-medium text-center">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($banners as $banner)
                <tr class="border-b border-gray-50 hover:bg-brand-50/30 transition-colors">
                    <td class="px-5 py-3">
                        @if($banner->image)
                            <a href="{{ asset('storage/'.$banner->image) }}" onclick="openProof(event, this.href)" class="cursor-pointer">
                                <img src="{{ asset('storage/'.$banner->image) }}" alt="Banner" class="w-20 h-12 object-cover rounded-lg border border-gray-100">
                            </a>
                        @else
                            <span class="text-gray-400 text-xs">-</span>
                        @endif
                    </td>
                    <td class="px-5 py-3">
                        <p class="font-semibold text-gray-800">{{ $banner->title ?? 'Tanpa judul' }}</p>
                        <p class="text-xs text-gray-500 max-w-[250px] truncate">{{ $banner->description ?? '' }}</p>
                        @if($banner->link)
                            <a href="{{ $banner->link }}" target="_blank" class="text-xs text-brand no-underline">Lihat link →</a>
                        @endif
                    </td>
                    <td class="px-5 py-3 text-gray-600 text-xs">
                        {{ $banner->starts_at ? $banner->starts_at->format('d M Y') : 'Selalu' }}
                        → {{ $banner->ends_at ? $banner->ends_at->format('d M Y') : 'Selalu' }}
                    </td>
                    <td class="px-5 py-3">
                        <x-badge :color="$banner->is_active ? 'success' : 'gray'">{{ $banner->is_active ? 'Aktif' : 'Nonaktif' }}</x-badge>
                    </td>
                    <td class="px-5 py-3 text-center">
                        <div class="flex items-center justify-center gap-2">
                            <x-button size="sm" color="outline" onclick="openBannerModal({{ $banner->id }})"><i class="fas fa-pen"></i> Edit</x-button>
                            <form action="{{ route('admin.promo-banners.destroy', $banner) }}" method="POST" class="inline"
                                  onsubmit="return confirm('Hapus promo banner ini?')">
                                @csrf
                                @method('DELETE')
                                <x-button size="sm" color="danger" type="submit"><i class="fas fa-trash"></i></x-button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5"><x-empty-state icon="fa-bullhorn" title="Belum ada promo banner" text="Tambahkan promo pertama untuk menampilkan popup di landing page" /></td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($banners->hasPages())
    <div class="px-5 py-4 border-t border-gray-100">
        {{ $banners->withQueryString()->links() }}
    </div>
    @endif
</x-card>

{{-- MODAL TAMBAH/EDIT PROMO --}}
<div id="bannerModal" class="hidden fixed inset-0 bg-black/40 backdrop-blur-sm z-[1100] flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg flex flex-col max-h-[85vh] overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between flex-shrink-0">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-xl bg-brand-50 flex items-center justify-center">
                    <i class="fas fa-bullhorn text-brand text-sm"></i>
                </div>
                <h3 class="font-display text-lg font-bold text-gray-800" id="bannerModalTitle">Tambah Promo Banner</h3>
            </div>
            <button onclick="closeBannerModal()"
                    class="w-8 h-8 flex items-center justify-center rounded-lg text-gray-400 hover:bg-gray-100 hover:text-gray-600 transition-colors">
                <i class="fas fa-xmark"></i>
            </button>
        </div>
        <form id="bannerForm" method="POST" enctype="multipart/form-data" class="p-5 space-y-3 overflow-y-auto">
            @csrf
            <x-input name="title" label="Judul" placeholder="Contoh: Promo Spesial Bulan Ini" />
            <x-textarea name="description" label="Deskripsi" rows="2" placeholder="Teks promo singkat..." />
            <div>
                <label class="block text-sm font-medium text-gray-600 mb-1">Gambar Banner</label>
                <div class="w-40 h-24 rounded-lg border border-gray-100 bg-gray-50 flex items-center justify-center overflow-hidden mb-2 hidden" id="bannerImageBox">
                    <img id="bannerImagePreview" src="" alt="Preview" class="max-h-full max-w-full object-contain p-1">
                </div>
                <input type="file" id="bannerImageInput" name="image" accept="image/*" onchange="previewBannerImage(this)"
                       class="w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand-200">
                <div class="mt-2 rounded-lg bg-brand-50 border border-brand-100 px-3 py-2 flex items-center gap-3">
                    <div class="w-6 h-8 rounded border-2 border-brand flex-shrink-0" style="border-color:#d4739a;"></div>
                    <p class="text-xs text-gray-600 leading-snug">
                        <strong class="text-gray-800">Rekomendasi ukuran:</strong> 768 × 1024 px<br>
                        <span class="text-gray-500">Rasio <strong>3:4</strong> (potret) — pas dengan popup landing</span>
                    </p>
                </div>
            </div>
            <x-input name="link" label="Link (opsional)" placeholder="https://wa.me/... atau halaman promo" />
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <x-input name="starts_at" label="Mulai" type="date" />
                <x-input name="ends_at" label="Selesai" type="date" />
            </div>
            <label class="flex items-center gap-2 cursor-pointer">
                <input type="checkbox" name="is_active" value="1" checked class="accent-[#d4739a]">
                <span class="text-sm text-gray-700">Aktif (tampil di landing page)</span>
            </label>
            <div class="flex justify-end gap-3 pt-2 border-t border-gray-100">
                <x-button color="ghost" type="button" onclick="closeBannerModal()">Batal</x-button>
                <x-button type="submit"><i class="fas fa-save"></i> Simpan</x-button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
@php
    $bannerData = $banners->map(fn ($b) => [
        'id' => $b->id,
        'title' => $b->title,
        'description' => $b->description,
        'image' => $b->image ? asset('storage/'.$b->image) : null,
        'link' => $b->link,
        'starts_at' => $b->starts_at ? $b->starts_at->format('Y-m-d') : '',
        'ends_at' => $b->ends_at ? $b->ends_at->format('Y-m-d') : '',
        'is_active' => $b->is_active,
    ])->values();
@endphp
<script>
    const banners = @json($bannerData);

    function openBannerModal(id) {
        const form = document.getElementById('bannerForm');
        const title = document.getElementById('bannerModalTitle');
        const box = document.getElementById('bannerImageBox');
        const preview = document.getElementById('bannerImagePreview');

        form.reset();
        form.querySelector('input[name="is_active"]').checked = true;
        box.classList.add('hidden');
        form.action = '{{ route('admin.promo-banners.store') }}';
        form.querySelector('input[name="_method"]')?.remove();
        title.textContent = 'Tambah Promo Banner';

        if (id) {
            const b = banners.find(x => x.id === id);
            if (!b) return;
            form.action = '{{ route('admin.promo-banners.update', ':id') }}'.replace(':id', id);
            const put = document.createElement('input');
            put.type = 'hidden';
            put.name = '_method';
            put.value = 'PUT';
            form.appendChild(put);

            form.querySelector('input[name="title"]').value = b.title || '';
            form.querySelector('textarea[name="description"]').value = b.description || '';
            form.querySelector('input[name="link"]').value = b.link || '';
            form.querySelector('input[name="starts_at"]').value = b.starts_at;
            form.querySelector('input[name="ends_at"]').value = b.ends_at;
            form.querySelector('input[name="is_active"]').checked = b.is_active;
            if (b.image) {
                preview.src = b.image;
                box.classList.remove('hidden');
            }
            title.textContent = 'Edit Promo Banner';
        }

        document.getElementById('bannerModal').classList.remove('hidden');
    }

    function closeBannerModal() {
        document.getElementById('bannerModal').classList.add('hidden');
    }

    function previewBannerImage(input) {
        const file = input.files[0];
        if (!file) return;
        const reader = new FileReader();
        reader.onload = function (e) {
            document.getElementById('bannerImagePreview').src = e.target.result;
            document.getElementById('bannerImageBox').classList.remove('hidden');
        };
        reader.readAsDataURL(file);
    }

    document.getElementById('bannerModal').addEventListener('click', function (e) {
        if (e.target === this) closeBannerModal();
    });
</script>
@endpush
@endsection
