@extends('layouts.app')

@section('title', $title)

@section('content')
<x-page-header :title="$title" :subtitle="'Nama dan foto '.strtolower($singular).' yang tersedia untuk survey lapangan'">
    <x-slot:actions>
        <x-button color="primary" onclick="document.getElementById('addMasterPhotoModal').classList.remove('hidden')">
            <i class="fas fa-plus"></i> Tambah {{ $singular }}
        </x-button>
    </x-slot:actions>
</x-page-header>

<x-card padding="p-0">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead><tr class="text-left text-gray-500 border-b border-gray-100 bg-cream/60">
                <th class="px-5 py-3 font-medium">Foto</th><th class="px-5 py-3 font-medium">Nama {{ $singular }}</th><th class="px-5 py-3 font-medium">Status</th><th class="px-5 py-3 font-medium text-center">Aksi</th>
            </tr></thead>
            <tbody>
                @forelse($items as $item)
                <tr class="border-b border-gray-50 hover:bg-brand-50/30 transition-colors">
                    <td class="px-5 py-3">@if($item->photo_path)<a href="{{ asset('storage/'.$item->photo_path) }}" onclick="openProof(event, this.href)"><img src="{{ asset('storage/'.$item->photo_path) }}" alt="{{ $item->name }}" class="h-14 w-20 object-cover rounded-xl border border-gray-100"></a>@else<span class="inline-flex h-14 w-20 items-center justify-center rounded-xl bg-brand-50 text-brand"><i class="fas {{ $icon }}"></i></span>@endif</td>
                    <td class="px-5 py-3 font-semibold text-gray-800">{{ $item->name }}</td>
                    <td class="px-5 py-3"><x-badge :color="$item->is_active ? 'success' : 'gray'">{{ $item->is_active ? 'Aktif' : 'Nonaktif' }}</x-badge></td>
                    <td class="px-5 py-3"><div class="flex items-center justify-center gap-2">
                        <x-button size="sm" color="outline" onclick="document.getElementById('editMasterPhoto-{{ $item->id }}').classList.remove('hidden')"><i class="fas fa-pen"></i></x-button>
                        <form method="POST" action="{{ route($routeName.'.destroy', $item) }}" onsubmit="return confirm('Hapus {{ strtolower($singular) }} ini?')">@csrf @method('DELETE')<x-button size="sm" color="danger" type="submit"><i class="fas fa-trash"></i></x-button></form>
                    </div></td>
                </tr>
                @empty
                <tr><td colspan="4"><x-empty-state :icon="$icon" :title="'Belum ada '.strtolower($singular)" /></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-card>

<div id="addMasterPhotoModal" class="hidden fixed inset-0 z-[1100] flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-black/50" onclick="this.parentElement.classList.add('hidden')"></div>
    <div class="relative bg-white rounded-2xl shadow-xl w-full max-w-md p-6">
        <div class="flex items-center justify-between mb-4"><h3 class="font-display font-bold text-gray-800"><i class="fas {{ $icon }} text-brand mr-2"></i>Tambah {{ $singular }}</h3><button type="button" class="text-gray-400" onclick="document.getElementById('addMasterPhotoModal').classList.add('hidden')"><i class="fas fa-xmark"></i></button></div>
        <form action="{{ route($routeName.'.store') }}" method="POST" enctype="multipart/form-data" class="space-y-3">
            @csrf
            <x-input name="name" :label="'Nama '.$singular" required />
            <x-input name="photo" :label="'Foto '.$singular" type="file" accept="image/*" required />
            <input type="hidden" name="is_active" value="1">
            <x-button color="primary" type="submit" class="w-full"><i class="fas fa-save"></i> Simpan</x-button>
        </form>
    </div>
</div>

@foreach($items as $item)
<div id="editMasterPhoto-{{ $item->id }}" class="hidden fixed inset-0 z-[1100] flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-black/50" onclick="this.parentElement.classList.add('hidden')"></div>
    <div class="relative bg-white rounded-2xl shadow-xl w-full max-w-md p-6">
        <div class="flex items-center justify-between mb-4"><h3 class="font-display font-bold text-gray-800"><i class="fas fa-pen-to-square text-brand mr-2"></i>Edit {{ $singular }}</h3><button type="button" class="text-gray-400" onclick="document.getElementById('editMasterPhoto-{{ $item->id }}').classList.add('hidden')"><i class="fas fa-xmark"></i></button></div>
        <form action="{{ route($routeName.'.update', $item) }}" method="POST" enctype="multipart/form-data" class="space-y-3">
            @csrf @method('PUT')
            <x-input name="name" :label="'Nama '.$singular" :value="$item->name" required />
            @if($item->photo_path)<img src="{{ asset('storage/'.$item->photo_path) }}" alt="{{ $item->name }}" class="h-28 w-full object-cover rounded-xl border border-gray-100">@endif
            <x-input name="photo" label="Ganti Foto (opsional)" type="file" accept="image/*" />
            <div><label class="block text-sm font-medium text-gray-600 mb-1">Status</label><select name="is_active" class="w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm"><option value="1" @selected($item->is_active)>Aktif</option><option value="0" @selected(!$item->is_active)>Nonaktif</option></select></div>
            <x-button color="primary" type="submit" class="w-full"><i class="fas fa-save"></i> Simpan Perubahan</x-button>
        </form>
    </div>
</div>
@endforeach
@endsection
