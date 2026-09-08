@extends('layouts.app')

@section('title', $vendor ? 'Edit Vendor' : 'Tambah Vendor')

@section('content')
<x-page-header :title="$vendor ? 'Edit Vendor — '.$vendor->name : 'Tambah Vendor Baru'">
    <x-slot:actions>
        <x-button href="{{ route('admin.vendors.index') }}" color="ghost"><i class="fas fa-arrow-left"></i> Kembali</x-button>
    </x-slot:actions>
</x-page-header>

<x-card class="max-w-2xl">
    <form method="POST" action="{{ $vendor ? route('admin.vendors.update', $vendor) : route('admin.vendors.store') }}" enctype="multipart/form-data" class="space-y-4">
        @csrf
        @if($vendor) @method('PUT') @endif

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <x-input name="name" label="Nama Vendor" :value="$vendor->name ?? ''" required />
            <div>
                <label for="vendor_category_id" class="block text-sm font-medium text-gray-600 mb-1">Kategori <span class="text-red-500">*</span></label>
                <select name="vendor_category_id" id="vendor_category_id" required
                        class="w-full rounded-lg border bg-gray-50 px-3 py-2 text-sm text-gray-800 focus:outline-none focus:ring-2 focus:ring-brand-200 border-gray-200">
                    <option value="">— Pilih Kategori —</option>
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}" @selected(old('vendor_category_id', $vendor->vendor_category_id ?? '') == $category->id)>{{ $category->name }}</option>
                    @endforeach
                </select>
            </div>
            <x-input name="phone" label="No HP" :value="$vendor->phone ?? ''" placeholder="08xxxxxxxxxx" />
            <x-input name="instagram" label="Instagram" :value="$vendor->instagram ?? ''" placeholder="@username" />
            <x-input name="address" label="Alamat" :value="$vendor->address ?? ''" />
            <div>
                <label for="logo" class="mb-1 block text-sm font-medium text-gray-600">{{ $vendor?->logo ? 'Ganti Logo Vendor' : 'Logo Vendor' }}</label>
                <input id="logo" name="logo" type="file" accept="image/jpeg,image/png,image/webp"
                       class="w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm text-gray-800 focus:outline-none focus:ring-2 focus:ring-brand-200">
                @error('logo') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                @if($vendor?->logo)
                    <img src="{{ asset('storage/'.$vendor->logo) }}" alt="Logo {{ $vendor->name }}" class="mt-2 h-16 w-16 rounded-lg object-cover">
                @endif
            </div>
            <x-input name="price" label="Harga (Rp)" currency :value="$vendor?->price ?? ''" min="0" step="1000" placeholder="Kosongkan jika belum ada" />
            <div>
                <label for="status" class="block text-sm font-medium text-gray-600 mb-1">Status</label>
                <select name="status" id="status"
                        class="w-full rounded-lg border bg-gray-50 px-3 py-2 text-sm text-gray-800 focus:outline-none focus:ring-2 focus:ring-brand-200 border-gray-200">
                    <option value="active" @selected(old('status', $vendor->status ?? '') === 'active')>Active</option>
                    <option value="inactive" @selected(old('status', $vendor->status ?? '') === 'inactive')>Inactive</option>
                </select>
            </div>
        </div>

        <x-textarea name="notes" label="Catatan" rows="3">{{ old('notes', $vendor->notes ?? '') }}</x-textarea>

        <div class="flex gap-2 pt-2">
            <x-button color="primary" type="submit"><i class="fas fa-save"></i> Simpan</x-button>
            <x-button href="{{ route('admin.vendors.index') }}" color="outline">Batal</x-button>
        </div>
    </form>
</x-card>
@endsection
