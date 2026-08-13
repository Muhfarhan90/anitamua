@extends('layouts.app')

@section('title', 'Master Benefit')

@section('content')
<x-page-header title="Master Benefit" subtitle="Benefit yang dipakai ulang di banyak paket — dikelompokkan per kategori">
    <x-slot:actions>
        <x-button href="{{ route('admin.benefit-categories.index') }}" color="ghost"><i class="fas fa-tags"></i> Kat. Benefit</x-button>
        <x-button href="{{ route('admin.packages.index') }}" color="ghost"><i class="fas fa-box"></i> Paket</x-button>
        <x-button color="primary" onclick="document.getElementById('addModal').classList.remove('hidden')"><i class="fas fa-plus"></i> Tambah Benefit</x-button>
    </x-slot:actions>
</x-page-header>

{{-- FILTER --}}
<form method="GET" action="{{ route('admin.benefits.index') }}" class="flex flex-wrap items-center gap-3 mb-4">
    <div class="relative flex-1 min-w-[220px]">
        <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-sm text-gray-400"></i>
        <input type="text" name="q" value="{{ request('q') }}" placeholder="Cari benefit..."
               class="w-full rounded-lg border border-gray-200 bg-gray-50 pl-9 pr-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand-200">
    </div>
    <select name="category_id" onchange="this.form.submit()"
            class="rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand-200">
        <option value="">Semua Kategori</option>
        @foreach($categories as $category)
            <option value="{{ $category->id }}" @selected(request('category_id') == $category->id)>{{ $category->name }}</option>
        @endforeach
    </select>
</form>

{{-- DAFTAR --}}
<x-card padding="p-0">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="text-left text-gray-500 border-b border-gray-100 bg-cream/60">
                    <th class="px-5 py-2.5 font-medium">Kategori</th>
                    <th class="px-5 py-2.5 font-medium">Benefit</th>
                    <th class="px-5 py-2.5 font-medium">Dipakai Paket</th>
                    <th class="px-5 py-2.5 font-medium">Status</th>
                    <th class="px-5 py-2.5 font-medium text-center">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($benefits as $benefit)
                <tr class="border-b border-gray-50 hover:bg-brand-50/30 transition-colors">
                    <td class="px-5 py-3"><x-badge color="info">{{ $benefit->category->name ?? '-' }}</x-badge></td>
                    <td class="px-5 py-3 font-semibold text-gray-800">{{ $benefit->name }}</td>
                    <td class="px-5 py-3"><x-badge color="gray">{{ $benefit->packages_count }} paket</x-badge></td>
                    <td class="px-5 py-3">
                        <x-badge :color="$benefit->status === 'active' ? 'success' : 'gray'">{{ ucfirst($benefit->status) }}</x-badge>
                    </td>
                    <td class="px-5 py-3">
                        <div class="flex items-center justify-center gap-2">
                            <x-button size="sm" color="outline" onclick="openEdit({{ $benefit->id }})"><i class="fas fa-pen"></i></x-button>
                            <form method="POST" action="{{ route('admin.benefits.destroy', $benefit) }}" onsubmit="return confirm('Hapus benefit ini?')">
                                @csrf @method('DELETE')
                                <x-button size="sm" color="danger" type="submit"><i class="fas fa-trash"></i></x-button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5"><x-empty-state icon="fa-circle-check" title="Belum ada benefit" /></td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-card>

@if($benefits->hasPages())
<div class="mt-4">{{ $benefits->withQueryString()->links() }}</div>
@endif

{{-- MODAL TAMBAH --}}
<div id="addModal" class="hidden fixed inset-0 z-[1100] flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-black/50" onclick="document.getElementById('addModal').classList.add('hidden')"></div>
    <div class="relative bg-white rounded-2xl shadow-xl w-full max-w-md p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="font-display font-bold text-gray-800"><i class="fas fa-circle-plus text-brand mr-2"></i>Tambah Benefit</h3>
            <button type="button" class="text-gray-400 hover:text-gray-600" onclick="document.getElementById('addModal').classList.add('hidden')">
                <i class="fas fa-xmark"></i>
            </button>
        </div>
        <form action="{{ route('admin.benefits.store') }}" method="POST" class="space-y-3">
            @csrf
            <x-input name="name" label="Nama Benefit" required placeholder="cth: Makeup & Retouch" />
            <div>
                <label for="add_category_id" class="block text-sm font-medium text-gray-600 mb-1">Kategori <span class="text-red-500">*</span></label>
                <select name="benefit_category_id" id="add_category_id" required
                        class="w-full rounded-lg border bg-gray-50 px-3 py-2 text-sm text-gray-800 focus:outline-none focus:ring-2 focus:ring-brand-200 border-gray-200">
                    <option value="">— Pilih Kategori —</option>
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}">{{ $category->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="status" class="block text-sm font-medium text-gray-600 mb-1">Status</label>
                <select name="status" id="status"
                        class="w-full rounded-lg border bg-gray-50 px-3 py-2 text-sm text-gray-800 focus:outline-none focus:ring-2 focus:ring-brand-200 border-gray-200">
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
            </div>
            <x-button color="primary" type="submit" class="w-full"><i class="fas fa-save"></i> Simpan</x-button>
        </form>
    </div>
</div>

{{-- MODAL EDIT --}}
@foreach($benefits as $benefit)
<div id="edit-{{ $benefit->id }}" class="hidden fixed inset-0 z-[1100] flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-black/50" onclick="document.getElementById('edit-{{ $benefit->id }}').classList.add('hidden')"></div>
    <div class="relative bg-white rounded-2xl shadow-xl w-full max-w-md p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="font-display font-bold text-gray-800"><i class="fas fa-pen-to-square text-brand mr-2"></i>Edit Benefit</h3>
            <button type="button" class="text-gray-400 hover:text-gray-600" onclick="document.getElementById('edit-{{ $benefit->id }}').classList.add('hidden')">
                <i class="fas fa-xmark"></i>
            </button>
        </div>
        <form action="{{ route('admin.benefits.update', $benefit) }}" method="POST" class="space-y-3">
            @csrf @method('PUT')
            <x-input name="name" label="Nama Benefit" :value="$benefit->name" required />
            <div>
                <label for="edit_category_id_{{ $benefit->id }}" class="block text-sm font-medium text-gray-600 mb-1">Kategori <span class="text-red-500">*</span></label>
                <select name="benefit_category_id" id="edit_category_id_{{ $benefit->id }}" required
                        class="w-full rounded-lg border bg-gray-50 px-3 py-2 text-sm text-gray-800 focus:outline-none focus:ring-2 focus:ring-brand-200 border-gray-200">
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}" @selected($benefit->benefit_category_id == $category->id)>{{ $category->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="status" class="block text-sm font-medium text-gray-600 mb-1">Status</label>
                <select name="status" id="status"
                        class="w-full rounded-lg border bg-gray-50 px-3 py-2 text-sm text-gray-800 focus:outline-none focus:ring-2 focus:ring-brand-200 border-gray-200">
                    <option value="active" @selected($benefit->status === 'active')>Active</option>
                    <option value="inactive" @selected($benefit->status === 'inactive')>Inactive</option>
                </select>
            </div>
            <x-button color="primary" type="submit" class="w-full"><i class="fas fa-save"></i> Simpan</x-button>
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
