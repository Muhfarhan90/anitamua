@extends('layouts.app')

@section('title', 'Master Jenis Paket')

@section('content')
<x-page-header title="Master Jenis Paket" subtitle="Jenis paket yang tersedia untuk paket dan form booking">
    <x-slot:actions>
        <x-button color="outline" href="{{ route('admin.packages.index') }}">Kembali ke Paket</x-button>
        <x-button color="primary" onclick="document.getElementById('addPackageTypeModal').classList.remove('hidden')"><i class="fas fa-plus"></i> Tambah Jenis Paket</x-button>
    </x-slot:actions>
</x-page-header>

<x-card padding="p-0">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead><tr class="border-b border-gray-100 bg-cream/60 text-left text-gray-500">
                <th class="px-5 py-3 font-medium">Jenis Paket</th>
                <th class="px-5 py-3 font-medium">Jumlah Paket</th>
                <th class="px-5 py-3 font-medium">Data Survey</th>
                <th class="px-5 py-3 font-medium">Data Fitting</th>
                <th class="px-5 py-3 text-center font-medium">Aksi</th>
            </tr></thead>
            <tbody>
                @forelse($packageTypes as $packageType)
                    <tr class="border-b border-gray-50 transition-colors hover:bg-brand-50/30">
                        <td class="px-5 py-3 font-semibold text-gray-800">{{ $packageType->name }}</td>
                        <td class="px-5 py-3"><x-badge :color="$packageType->packages_count ? 'info' : 'gray'">{{ $packageType->packages_count }} paket</x-badge></td>
                        <td class="px-5 py-3"><x-badge :color="$packageType->is_data_survey ? 'success' : 'gray'">{{ $packageType->is_data_survey ? 'Aktif' : 'Nonaktif' }}</x-badge></td>
                        <td class="px-5 py-3"><x-badge :color="$packageType->is_data_fitting ? 'success' : 'gray'">{{ $packageType->is_data_fitting ? 'Aktif' : 'Nonaktif' }}</x-badge></td>
                        <td class="px-5 py-3"><div class="flex items-center justify-center gap-2">
                            <x-button size="sm" color="outline" onclick="document.getElementById('editPackageType-{{ $packageType->id }}').classList.remove('hidden')"><i class="fas fa-pen"></i></x-button>
                            @if($packageType->packages_count === 0)
                                <form method="POST" action="{{ route('admin.packages.types.destroy', $packageType) }}" onsubmit="return confirm('Hapus jenis paket ini?')">
                                    @csrf @method('DELETE')
                                    <x-button size="sm" color="danger" type="submit"><i class="fas fa-trash"></i></x-button>
                                </form>
                            @else
                                <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-gray-100 text-gray-400" title="Tidak dapat dihapus karena masih dipakai paket"><i class="fas fa-lock text-xs"></i></span>
                            @endif
                        </div></td>
                    </tr>
                @empty
                    <tr><td colspan="5"><x-empty-state icon="fa-tags" title="Belum ada jenis paket" /></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-card>

<div id="addPackageTypeModal" class="hidden fixed inset-0 z-[1100] flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-black/50" onclick="this.parentElement.classList.add('hidden')"></div>
    <div class="relative w-full max-w-md rounded-2xl bg-white p-6 shadow-xl">
        <div class="mb-4 flex items-center justify-between"><h3 class="font-display font-bold text-gray-800"><i class="fas fa-tags mr-2 text-brand"></i>Tambah Jenis Paket</h3><button type="button" class="text-gray-400" onclick="document.getElementById('addPackageTypeModal').classList.add('hidden')"><i class="fas fa-xmark"></i></button></div>
        <form method="POST" action="{{ route('admin.packages.types.store') }}" class="space-y-3">
            @csrf
            <x-input name="name" label="Nama Jenis Paket" required placeholder="Contoh: Paket Engagement" />
            <input type="hidden" name="is_data_survey" value="0">
            <label class="flex items-center gap-2 text-sm text-gray-700"><input type="checkbox" name="is_data_survey" value="1" checked class="rounded border-gray-300 text-brand"> Tampilkan Data Survey di booking</label>
            <input type="hidden" name="is_data_fitting" value="0">
            <label class="flex items-center gap-2 text-sm text-gray-700"><input type="checkbox" name="is_data_fitting" value="1" checked class="rounded border-gray-300 text-brand"> Tampilkan Data Fitting di booking</label>
            <x-button color="primary" type="submit" class="w-full"><i class="fas fa-save"></i> Simpan</x-button>
        </form>
    </div>
</div>

@foreach($packageTypes as $packageType)
    <div id="editPackageType-{{ $packageType->id }}" class="hidden fixed inset-0 z-[1100] flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/50" onclick="this.parentElement.classList.add('hidden')"></div>
        <div class="relative w-full max-w-md rounded-2xl bg-white p-6 shadow-xl">
            <div class="mb-4 flex items-center justify-between"><h3 class="font-display font-bold text-gray-800"><i class="fas fa-pen-to-square mr-2 text-brand"></i>Edit Jenis Paket</h3><button type="button" class="text-gray-400" onclick="document.getElementById('editPackageType-{{ $packageType->id }}').classList.add('hidden')"><i class="fas fa-xmark"></i></button></div>
            <form method="POST" action="{{ route('admin.packages.types.update', $packageType) }}" class="space-y-3">
                @csrf @method('PUT')
                <x-input name="name" label="Nama Jenis Paket" :value="$packageType->name" required />
                <input type="hidden" name="is_data_survey" value="0">
                <label class="flex items-center gap-2 text-sm text-gray-700"><input type="checkbox" name="is_data_survey" value="1" @checked($packageType->is_data_survey) class="rounded border-gray-300 text-brand"> Tampilkan Data Survey di booking</label>
                <input type="hidden" name="is_data_fitting" value="0">
                <label class="flex items-center gap-2 text-sm text-gray-700"><input type="checkbox" name="is_data_fitting" value="1" @checked($packageType->is_data_fitting) class="rounded border-gray-300 text-brand"> Tampilkan Data Fitting di booking</label>
                <x-button color="primary" type="submit" class="w-full"><i class="fas fa-save"></i> Simpan Perubahan</x-button>
            </form>
        </div>
    </div>
@endforeach
@endsection
