@extends('layouts.app')

@section('title', 'Kategori Benefit')

@section('content')
<x-page-header title="Kategori Benefit" subtitle="Kategorikan benefit (Makeup & Attire, Decoration, Tenda & Peralatan, Dokumentasi, Free, dll)">
    <x-slot:actions>
        <x-button href="{{ route('admin.benefits.index') }}" color="ghost"><i class="fas fa-circle-check"></i> Master Benefit</x-button>
    </x-slot:actions>
</x-page-header>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
    {{-- FORM TAMBAH --}}
    <div>
        <x-card title="Tambah Kategori" title-icon="fa-tags">
            <form action="{{ route('admin.benefit-categories.store') }}" method="POST" class="space-y-3">
                @csrf
                <x-input name="name" label="Nama Kategori" placeholder="cth: Makeup & Attire" required />
                <x-button color="primary" type="submit" class="w-full"><i class="fas fa-plus"></i> Tambah</x-button>
            </form>
        </x-card>
    </div>

    {{-- DAFTAR KATEGORI --}}
    <div class="lg:col-span-2">
        <x-card padding="p-0">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left text-gray-500 border-b border-gray-100 bg-cream/60">
                            <th class="px-5 py-2.5 font-medium">Kategori</th>
                            <th class="px-5 py-2.5 font-medium">Jumlah Benefit</th>
                            <th class="px-5 py-2.5 font-medium text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($categories as $category)
                        <tr class="border-b border-gray-50 hover:bg-brand-50/30 transition-colors">
                            <td class="px-5 py-3">
                                <div class="flex items-center gap-3">
                                    <span class="w-8 h-8 rounded-full bg-brand-50 text-brand flex items-center justify-center flex-shrink-0">
                                        <i class="fas fa-tag text-xs"></i>
                                    </span>
                                    <span class="font-semibold text-gray-800">{{ $category->name }}</span>
                                </div>
                            </td>
                            <td class="px-5 py-3"><x-badge color="info">{{ $category->benefits_count }} benefit</x-badge></td>
                            <td class="px-5 py-3">
                                <div class="flex items-center justify-center gap-2">
                                    <x-button size="sm" color="outline" onclick="document.getElementById('edit-{{ $category->id }}').classList.remove('hidden')"><i class="fas fa-pen"></i></x-button>
                                    <form method="POST" action="{{ route('admin.benefit-categories.destroy', $category) }}" onsubmit="return confirm('Hapus kategori ini?')">
                                        @csrf @method('DELETE')
                                        <x-button size="sm" color="danger" type="submit"><i class="fas fa-trash"></i></x-button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="3"><x-empty-state icon="fa-tags" title="Belum ada kategori" /></td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-card>
    </div>
</div>

{{-- MODAL EDIT --}}
@foreach($categories as $category)
<div id="edit-{{ $category->id }}" class="hidden fixed inset-0 z-[1100] flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-black/50" onclick="document.getElementById('edit-{{ $category->id }}').classList.add('hidden')"></div>
    <div class="relative bg-white rounded-2xl shadow-xl w-full max-w-sm p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="font-display font-bold text-gray-800">Edit Kategori</h3>
            <button type="button" class="text-gray-400 hover:text-gray-600" onclick="document.getElementById('edit-{{ $category->id }}').classList.add('hidden')">
                <i class="fas fa-xmark"></i>
            </button>
        </div>
        <form action="{{ route('admin.benefit-categories.update', $category) }}" method="POST" class="space-y-3">
            @csrf @method('PUT')
            <x-input name="name" label="Nama Kategori" :value="$category->name" required />
            <x-button color="primary" type="submit" class="w-full"><i class="fas fa-save"></i> Simpan</x-button>
        </form>
    </div>
</div>
@endforeach
@endsection
