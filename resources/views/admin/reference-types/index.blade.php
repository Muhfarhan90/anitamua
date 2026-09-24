@extends('layouts.app')

@section('title', 'Jenis Referensi')

@section('content')
<x-page-header title="Jenis Referensi" subtitle="Pilihan referensi yang dapat diunggah oleh klien">
    <x-slot:actions>
        <x-button color="primary" onclick="document.getElementById('addReferenceTypeModal').classList.remove('hidden')"><i class="fas fa-plus"></i> Tambah Jenis Referensi</x-button>
    </x-slot:actions>
</x-page-header>

<x-card padding="p-0">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead><tr class="border-b border-gray-100 bg-cream/60 text-left text-gray-500">
                <th class="px-5 py-3 font-medium">Jenis Referensi</th>
                <th class="px-5 py-3 text-center font-medium">Aksi</th>
            </tr></thead>
            <tbody>
                @forelse($referenceTypes as $referenceType)
                    <tr class="border-b border-gray-50 transition-colors hover:bg-brand-50/30">
                        <td class="px-5 py-3 font-semibold text-gray-800">{{ $referenceType->name }}</td>
                        <td class="px-5 py-3"><div class="flex items-center justify-center gap-2">
                            <x-button size="sm" color="outline" onclick="document.getElementById('editReferenceType-{{ $referenceType->id }}').classList.remove('hidden')"><i class="fas fa-pen"></i></x-button>
                            @if($referenceType->references_count === 0)
                                <form method="POST" action="{{ route('admin.reference-types.destroy', $referenceType) }}" onsubmit="return confirm('Hapus jenis referensi ini?')">
                                    @csrf @method('DELETE')
                                    <x-button size="sm" color="danger" type="submit"><i class="fas fa-trash"></i></x-button>
                                </form>
                            @else
                                <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-gray-100 text-gray-400" title="Tidak dapat dihapus karena masih digunakan"><i class="fas fa-lock text-xs"></i></span>
                            @endif
                        </div></td>
                    </tr>
                @empty
                    <tr><td colspan="2"><x-empty-state icon="fa-lightbulb" title="Belum ada jenis referensi" text="Tambahkan dekor, tenda, foto prewedding, atau jenis lainnya." /></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-card>

<div id="addReferenceTypeModal" class="fixed inset-0 z-[1100] hidden flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-black/50" onclick="this.parentElement.classList.add('hidden')"></div>
    <div class="relative w-full max-w-md rounded-2xl bg-white p-6 shadow-xl">
        <div class="mb-4 flex items-center justify-between"><h3 class="font-display font-bold text-gray-800"><i class="fas fa-lightbulb mr-2 text-brand"></i>Tambah Jenis Referensi</h3><button type="button" class="text-gray-400" onclick="document.getElementById('addReferenceTypeModal').classList.add('hidden')"><i class="fas fa-xmark"></i></button></div>
        <form method="POST" action="{{ route('admin.reference-types.store') }}" class="space-y-3">
            @csrf
            <x-input name="name" label="Nama Jenis Referensi" required placeholder="Contoh: Dekor, Tenda, Foto Prewedding" />
            <x-button color="primary" type="submit" class="w-full"><i class="fas fa-save"></i> Simpan</x-button>
        </form>
    </div>
</div>

@foreach($referenceTypes as $referenceType)
    <div id="editReferenceType-{{ $referenceType->id }}" class="fixed inset-0 z-[1100] hidden flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/50" onclick="this.parentElement.classList.add('hidden')"></div>
        <div class="relative w-full max-w-md rounded-2xl bg-white p-6 shadow-xl">
            <div class="mb-4 flex items-center justify-between"><h3 class="font-display font-bold text-gray-800"><i class="fas fa-pen-to-square mr-2 text-brand"></i>Edit Jenis Referensi</h3><button type="button" class="text-gray-400" onclick="document.getElementById('editReferenceType-{{ $referenceType->id }}').classList.add('hidden')"><i class="fas fa-xmark"></i></button></div>
            <form method="POST" action="{{ route('admin.reference-types.update', $referenceType) }}" class="space-y-3">
                @csrf @method('PUT')
                <x-input name="name" label="Nama Jenis Referensi" :value="$referenceType->name" required />
                <x-button color="primary" type="submit" class="w-full"><i class="fas fa-save"></i> Simpan Perubahan</x-button>
            </form>
        </div>
    </div>
@endforeach
@endsection
