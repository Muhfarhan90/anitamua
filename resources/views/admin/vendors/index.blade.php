@extends('layouts.app')

@section('title', 'Master Vendor')

@section('content')
<x-page-header title="Master Vendor" subtitle="Kelola kategori, kontak, dan harga kerja sama vendor">
    <x-slot:actions>
        <x-button href="{{ route('admin.vendors.create') }}" color="primary"><i class="fas fa-plus"></i> Tambah Vendor</x-button>
    </x-slot:actions>
</x-page-header>

{{-- FILTER --}}
<form method="GET" action="{{ route('admin.vendors.index') }}" class="flex flex-wrap items-center gap-3 mb-4">
    <div class="relative flex-1 min-w-[220px]">
        <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-sm text-gray-400"></i>
        <input type="text" name="q" value="{{ request('q') }}" placeholder="Cari vendor..."
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

{{-- LIST --}}
<x-card padding="p-0">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="text-left text-gray-500 border-b border-gray-100 bg-cream/60">
                    <th class="px-5 py-2.5 font-medium">Vendor</th>
                    <th class="px-5 py-2.5 font-medium">Kategori</th>
                    <th class="px-5 py-2.5 font-medium">No HP</th>
                    <th class="px-5 py-2.5 font-medium">Instagram</th>
                    <th class="px-5 py-2.5 font-medium">Harga</th>
                    <th class="px-5 py-2.5 font-medium">Status</th>
                    <th class="px-5 py-2.5 font-medium text-center">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($vendors as $vendor)
                <tr class="border-b border-gray-50 hover:bg-brand-50/30 transition-colors">
                    <td class="px-5 py-3">
                        <div class="flex items-center gap-3">
                            @if($vendor->logo)
                                <img src="{{ asset('storage/'.$vendor->logo) }}" alt="Logo {{ $vendor->name }}" class="h-9 w-9 shrink-0 rounded-full border border-brand-100 bg-white object-cover">
                            @else
                                <div class="w-9 h-9 rounded-full bg-gradient-to-br from-brand to-brand-dark text-white flex items-center justify-center font-bold text-xs flex-shrink-0">
                                    {{ strtoupper(substr($vendor->name, 0, 1)) }}
                                </div>
                            @endif
                            <span class="font-semibold text-gray-800">{{ $vendor->name }}</span>
                        </div>
                    </td>
                    <td class="px-5 py-3"><x-badge color="info">{{ $vendor->category->name ?? '-' }}</x-badge></td>
                    <td class="px-5 py-3 text-gray-600">{{ $vendor->phone ?? '-' }}</td>
                    <td class="px-5 py-3 text-gray-600">
                        @if($vendor->instagram)
                            <a href="https://instagram.com/{{ ltrim($vendor->instagram, '@') }}" target="_blank" class="text-brand no-underline">{{ $vendor->instagram }}</a>
                        @else
                            <span class="text-gray-400">-</span>
                        @endif
                    </td>
                    <td class="px-5 py-3 font-semibold text-gray-800">Rp {{ number_format($vendor->price, 0, ',', '.') }}</td>
                    <td class="px-5 py-3">
                        <x-badge :color="$vendor->status === 'active' ? 'success' : 'gray'">{{ ucfirst($vendor->status) }}</x-badge>
                    </td>
                    <td class="px-5 py-3">
                        <div class="flex items-center justify-center gap-2">
                            <x-button size="sm" color="outline" href="{{ route('admin.vendors.edit', $vendor) }}"><i class="fas fa-pen"></i></x-button>
                            <form method="POST" action="{{ route('admin.vendors.destroy', $vendor) }}" onsubmit="return confirm('Hapus vendor ini?')">
                                @csrf @method('DELETE')
                                <x-button size="sm" color="danger" type="submit"><i class="fas fa-trash"></i></x-button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7"><x-empty-state icon="fa-store" title="Belum ada vendor" /></td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-card>

@if($vendors->hasPages())
<div class="mt-4">{{ $vendors->withQueryString()->links() }}</div>
@endif
@endsection
