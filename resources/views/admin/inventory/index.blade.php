@extends('layouts.app')

@section('title', 'Inventory Wardrobe')

@section('content')
<x-page-header title="Inventory Wardrobe" subtitle="Kelola seluruh gaun, aksesoris, dan perlengkapan makeup">
    <x-slot:actions>
        <x-button color="primary" onclick="document.getElementById('addModal').classList.remove('hidden')"><i class="fas fa-plus"></i> Tambah Barang</x-button>
    </x-slot:actions>
</x-page-header>

{{-- STATISTIK --}}
<div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-5 gap-4 mb-5">
    <x-stat-card icon="fa-box" label="Total Barang" :value="$items->total()" color="brand" />
    <x-stat-card icon="fa-circle-check" label="Tersedia" :value="$statusCounts['available'] ?? 0" color="emerald" />
    <x-stat-card icon="fa-spa" label="Sedang Dipakai" :value="$statusCounts['in_use'] ?? 0" color="rose" />
    <x-stat-card icon="fa-triangle-exclamation" label="Rusak" :value="$statusCounts['damaged'] ?? 0" color="amber" />
    <x-stat-card icon="fa-compass" label="Hilang" :value="$statusCounts['lost'] ?? 0" color="gray" />
</div>

{{-- FILTER --}}
<form method="GET" action="{{ route('admin.inventory.index') }}" class="flex flex-wrap items-center gap-3 mb-4">
    <div class="relative flex-1 min-w-[220px]">
        <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-sm text-gray-400"></i>
        <input type="text" name="q" value="{{ request('q') }}" placeholder="Cari nama atau kode barang..."
               class="w-full rounded-lg border border-gray-200 bg-gray-50 pl-9 pr-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand-200">
    </div>
    <select name="category_id" onchange="this.form.submit()"
            class="rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand-200">
        <option value="">Semua Kategori</option>
        @foreach($categories as $cat)
            <option value="{{ $cat->id }}" @selected(request('category_id') == $cat->id)>{{ $cat->name }}</option>
        @endforeach
    </select>
    <select name="status" onchange="this.form.submit()"
            class="rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand-200">
        <option value="">Semua Status</option>
        @foreach(['available' => 'Tersedia', 'in_use' => 'Sedang Dipakai', 'damaged' => 'Rusak', 'lost' => 'Hilang'] as $val => $label)
            <option value="{{ $val }}" @selected(request('status') == $val)>{{ $label }}</option>
        @endforeach
    </select>
    <select name="condition" onchange="this.form.submit()"
            class="rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand-200">
        <option value="">Semua Kondisi</option>
        @foreach(['good' => 'Baik', 'fair' => 'Cukup', 'damaged' => 'Rusak'] as $val => $label)
            <option value="{{ $val }}" @selected(request('condition') == $val)>{{ $label }}</option>
        @endforeach
    </select>
</form>

{{-- TABEL --}}
<x-card padding="p-0">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="text-left text-gray-500 border-b border-gray-100 bg-cream/60">
                    <th class="px-5 py-2.5 font-medium">Foto</th>
                    <th class="px-5 py-2.5 font-medium">Kode</th>
                    <th class="px-5 py-2.5 font-medium">Nama Barang</th>
                    <th class="px-5 py-2.5 font-medium">Kategori</th>
                    <th class="px-5 py-2.5 font-medium">Detail</th>
                    <th class="px-5 py-2.5 font-medium">Kondisi</th>
                    <th class="px-5 py-2.5 font-medium">Status</th>
                    <th class="px-5 py-2.5 font-medium text-center">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($items as $item)
                <tr class="border-b border-gray-50 hover:bg-brand-50/30 transition-colors">
                    <td class="px-5 py-3">
                        @if($item->photo)
                            <a href="{{ asset('storage/'.$item->photo) }}" onclick="openProof(event, this.href)" class="block w-12 h-12 cursor-pointer" title="Klik untuk perbesar">
                                <img src="{{ asset('storage/'.$item->photo) }}" alt="{{ $item->name }}" class="w-12 h-12 object-cover rounded-xl border border-gray-100">
                            </a>
                        @else
                            <div class="w-12 h-12 rounded-xl bg-brand-50 text-brand flex items-center justify-center">
                                <i class="fas fa-image"></i>
                            </div>
                        @endif
                    </td>
                    <td class="px-5 py-3">
                        <span class="font-mono text-xs font-semibold text-gray-600 bg-gray-50 border border-gray-100 rounded px-1.5 py-0.5">{{ $item->code }}</span>
                    </td>
                    <td class="px-5 py-3">
                        <p class="font-semibold text-gray-800">{{ $item->name }}</p>
                        @if($item->notes)<p class="text-xs text-gray-400 max-w-[220px] truncate">{{ $item->notes }}</p>@endif
                    </td>
                    <td class="px-5 py-3"><x-badge color="info">{{ $item->category->name ?? '-' }}</x-badge></td>
                    <td class="px-5 py-3 text-gray-600">
                        <div class="flex flex-wrap gap-1.5 text-xs">
                            @if($item->color)<span class="inline-flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-full" style="background: {{ $item->color }}"></span>{{ $item->color }}</span>@endif
                            @if($item->size)<span>{{ $item->size }}</span>@endif
                            @if($item->brand)<span class="text-gray-400">{{ $item->brand }}</span>@endif
                            @if($item->storage_location)<span class="text-gray-400"><i class="fas fa-location-dot text-[10px]"></i> {{ $item->storage_location }}</span>@endif
                        </div>
                    </td>
                    <td class="px-5 py-3">
                        <x-badge :color="match($item->condition) {
                            'good' => 'success',
                            'fair' => 'warning',
                            'damaged' => 'danger',
                            default => 'gray',
                        }">{{ ucfirst($item->condition) }}</x-badge>
                    </td>
                    <td class="px-5 py-3">
                        <x-badge :color="match($item->status) {
                            'available' => 'success',
                            'in_use' => 'warning',
                            'lost', 'damaged' => 'danger',
                            default => 'gray',
                        }">{{ str_replace('_', ' ', ucfirst($item->status)) }}</x-badge>
                    </td>
                    <td class="px-5 py-3">
                        <div class="flex items-center justify-center gap-2">
                            <x-button size="sm" color="outline" onclick="openEdit({{ $item->id }})"><i class="fas fa-pen"></i></x-button>
                            <form method="POST" action="{{ route('admin.inventory.destroy', $item) }}" onsubmit="return confirm('Hapus barang ini?')">
                                @csrf @method('DELETE')
                                <x-button size="sm" color="danger" type="submit"><i class="fas fa-trash"></i></x-button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8"><x-empty-state icon="fa-box" title="Belum ada barang inventory" /></td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-card>

@if($items->hasPages())
<div class="mt-4">{{ $items->withQueryString()->links() }}</div>
@endif

{{-- MODAL TAMBAH --}}
<div id="addModal" class="hidden fixed inset-0 z-[1100] flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-black/50" onclick="document.getElementById('addModal').classList.add('hidden')"></div>
    <div class="relative bg-white rounded-2xl shadow-xl w-full max-w-2xl max-h-[90vh] overflow-y-auto p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="font-display font-bold text-gray-800"><i class="fas fa-circle-plus text-brand mr-2"></i>Tambah Barang Baru</h3>
            <button type="button" class="text-gray-400 hover:text-gray-600" onclick="document.getElementById('addModal').classList.add('hidden')">
                <i class="fas fa-xmark"></i>
            </button>
        </div>
        <form action="{{ route('admin.inventory.store') }}" method="POST" enctype="multipart/form-data" class="space-y-3">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <x-input name="name" label="Nama Barang" required placeholder="cth: Gaun Gold" />
                <div>
                    <label for="add_category_id" class="block text-sm font-medium text-gray-600 mb-1">Kategori <span class="text-red-500">*</span></label>
                    <select name="inventory_category_id" id="add_category_id" required
                            class="w-full rounded-lg border bg-gray-50 px-3 py-2 text-sm text-gray-800 focus:outline-none focus:ring-2 focus:ring-brand-200 border-gray-200">
                        <option value="">— Pilih Kategori —</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                        @endforeach
                    </select>
                </div>
                <x-input name="color" label="Warna" placeholder="cth: Merah" />
                <x-input name="size" label="Ukuran" placeholder="cth: L, XL" />
                <x-input name="brand" label="Brand" placeholder="cth: Zoya" />
                <x-input name="storage_location" label="Lokasi Penyimpanan" placeholder="cth: Rak A3" />
                <x-input name="photo" label="Foto Barang" type="file" accept="image/*" />
                <div>
                    <label for="add_condition" class="block text-sm font-medium text-gray-600 mb-1">Kondisi <span class="text-red-500">*</span></label>
                    <select name="condition" id="add_condition" required
                            class="w-full rounded-lg border bg-gray-50 px-3 py-2 text-sm text-gray-800 focus:outline-none focus:ring-2 focus:ring-brand-200 border-gray-200">
                        <option value="good">Baik</option>
                        <option value="fair">Cukup</option>
                        <option value="damaged">Rusak</option>
                    </select>
                </div>
                <div>
                    <label for="add_status" class="block text-sm font-medium text-gray-600 mb-1">Status <span class="text-red-500">*</span></label>
                    <select name="status" id="add_status" required
                            class="w-full rounded-lg border bg-gray-50 px-3 py-2 text-sm text-gray-800 focus:outline-none focus:ring-2 focus:ring-brand-200 border-gray-200">
                        @foreach(['available' => 'Tersedia', 'in_use' => 'Sedang Dipakai', 'damaged' => 'Rusak', 'lost' => 'Hilang'] as $val => $label)
                            <option value="{{ $val }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <x-textarea name="notes" label="Catatan" rows="2"></x-textarea>
            <p class="text-xs text-gray-400">Kode barang dibuat otomatis dari kategori (cth: GAU-001).</p>
            <div class="flex gap-2 pt-2">
                <x-button color="primary" type="submit"><i class="fas fa-save"></i> Simpan</x-button>
                <x-button color="outline" onclick="document.getElementById('addModal').classList.add('hidden')">Batal</x-button>
            </div>
        </form>
    </div>
</div>

{{-- MODAL EDIT --}}
@foreach($items as $item)
<div id="edit-{{ $item->id }}" class="hidden fixed inset-0 z-[1100] flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-black/50" onclick="document.getElementById('edit-{{ $item->id }}').classList.add('hidden')"></div>
    <div class="relative bg-white rounded-2xl shadow-xl w-full max-w-2xl max-h-[90vh] overflow-y-auto p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="font-display font-bold text-gray-800"><i class="fas fa-pen-to-square text-brand mr-2"></i>Edit: {{ $item->name }}</h3>
            <button type="button" class="text-gray-400 hover:text-gray-600" onclick="document.getElementById('edit-{{ $item->id }}').classList.add('hidden')">
                <i class="fas fa-xmark"></i>
            </button>
        </div>
        <form action="{{ route('admin.inventory.update', $item) }}" method="POST" enctype="multipart/form-data" class="space-y-3">
            @csrf @method('PUT')
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <x-input name="name" label="Nama Barang" :value="$item->name" required />
                <div>
                    <label for="edit_category_id_{{ $item->id }}" class="block text-sm font-medium text-gray-600 mb-1">Kategori <span class="text-red-500">*</span></label>
                    <select name="inventory_category_id" id="edit_category_id_{{ $item->id }}" required
                            class="w-full rounded-lg border bg-gray-50 px-3 py-2 text-sm text-gray-800 focus:outline-none focus:ring-2 focus:ring-brand-200 border-gray-200">
                        @foreach($categories as $cat)
                            <option value="{{ $cat->id }}" @selected($item->inventory_category_id == $cat->id)>{{ $cat->name }}</option>
                        @endforeach
                    </select>
                </div>
                <x-input name="color" label="Warna" :value="$item->color" />
                <x-input name="size" label="Ukuran" :value="$item->size" />
                <x-input name="brand" label="Brand" :value="$item->brand" />
                <x-input name="storage_location" label="Lokasi Penyimpanan" :value="$item->storage_location" />
                <div>
                    <label for="edit_photo_{{ $item->id }}" class="block text-sm font-medium text-gray-600 mb-1">Foto Barang</label>
                    @if($item->photo)
                        <a href="{{ asset('storage/'.$item->photo) }}" onclick="openProof(event, this.href)" class="inline-block mb-2 cursor-pointer">
                            <img src="{{ asset('storage/'.$item->photo) }}" alt="{{ $item->name }}" class="w-16 h-16 object-cover rounded-xl border border-gray-100">
                        </a>
                        <p class="text-[11px] text-gray-400 mb-1">Klik foto untuk perbesar. Upload foto baru untuk mengganti.</p>
                    @endif
                    <input type="file" name="photo" id="edit_photo_{{ $item->id }}" accept="image/*"
                           class="w-full rounded-lg border bg-gray-50 px-3 py-2 text-sm text-gray-800 focus:outline-none focus:ring-2 focus:ring-brand-200 border-gray-200">
                </div>
                <div>
                    <label for="edit_condition_{{ $item->id }}" class="block text-sm font-medium text-gray-600 mb-1">Kondisi <span class="text-red-500">*</span></label>
                    <select name="condition" id="edit_condition_{{ $item->id }}" required
                            class="w-full rounded-lg border bg-gray-50 px-3 py-2 text-sm text-gray-800 focus:outline-none focus:ring-2 focus:ring-brand-200 border-gray-200">
                        @foreach(['good' => 'Baik', 'fair' => 'Cukup', 'damaged' => 'Rusak'] as $val => $label)
                            <option value="{{ $val }}" @selected($item->condition == $val)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="edit_status_{{ $item->id }}" class="block text-sm font-medium text-gray-600 mb-1">Status <span class="text-red-500">*</span></label>
                    <select name="status" id="edit_status_{{ $item->id }}" required
                            class="w-full rounded-lg border bg-gray-50 px-3 py-2 text-sm text-gray-800 focus:outline-none focus:ring-2 focus:ring-brand-200 border-gray-200">
                        @foreach(['available' => 'Tersedia', 'in_use' => 'Sedang Dipakai', 'damaged' => 'Rusak', 'lost' => 'Hilang'] as $val => $label)
                            <option value="{{ $val }}" @selected($item->status == $val)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <x-textarea name="notes" label="Catatan" rows="2">{{ $item->notes }}</x-textarea>
            <p class="text-xs text-gray-400">Kode: <span class="font-mono">{{ $item->code }}</span></p>
            <div class="flex gap-2 pt-2">
                <x-button color="primary" type="submit"><i class="fas fa-save"></i> Simpan</x-button>
                <x-button color="outline" onclick="document.getElementById('edit-{{ $item->id }}').classList.add('hidden')">Batal</x-button>
            </div>
        </form>
    </div>
</div>
@endforeach

<script>
    function openEdit(id) {
        document.getElementById('edit-' + id).classList.remove('hidden');
    }
</script>
@endsection
