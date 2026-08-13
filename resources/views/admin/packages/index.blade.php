@extends('layouts.app')

@section('title', 'Paket')

@section('content')
<x-page-header title="Manajemen Paket">
    <x-slot:actions>
        <x-button href="{{ route('admin.packages.create') }}" color="primary"><i class="fas fa-plus"></i> Buat Paket</x-button>
    </x-slot:actions>
</x-page-header>

<div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
    @forelse($packages as $package)
    <div class="bg-white rounded-xl shadow-sm border border-brand-100 overflow-hidden flex flex-col">
        <div class="px-5 py-3.5 border-b border-gray-100 flex items-center justify-between">
            <span class="flex items-center gap-2 font-semibold text-gray-800">
                <span class="inline-block rounded-full" style="width: 12px; height: 12px; background: {{ $package->color }}"></span>
                <span class="font-display text-lg">{{ $package->name }}</span>
            </span>
            <span class="flex items-center gap-2">
                <x-badge color="info">{{ \App\Models\Package::typeLabel($package->type) }}</x-badge>
                <x-badge :color="$package->status === 'active' ? 'success' : 'gray'">{{ $package->status }}</x-badge>
            </span>
        </div>
        <div class="p-5 flex-1">
            <div class="font-display text-2xl font-bold text-gold mb-2">Rp {{ number_format($package->price, 0, ',', '.') }}</div>
            <small class="text-gray-500">{{ $package->description }}</small>
            <hr class="my-3 border-gray-100">
            <div class="text-sm font-semibold text-gray-700 mb-1">{{ $package->benefits->count() }} Benefit</div>
            <ul class="text-sm text-gray-600 ps-4 mb-2 space-y-0.5" style="max-height: 110px; overflow-y: auto">
                @foreach($package->benefits as $benefit)
                    <li class="flex items-start gap-1.5"><i class="fas fa-circle-check mt-1 text-emerald-500 text-xs"></i>{{ $benefit->name }}</li>
                @endforeach
            </ul>
        </div>
        <div class="px-5 py-3.5 border-t border-gray-100 bg-cream/40 flex gap-2">
            <x-button size="sm" color="outline" href="{{ route('admin.packages.edit', $package) }}" class="flex-1 justify-center">Edit</x-button>
            <form method="POST" action="{{ route('admin.packages.destroy', $package) }}" onsubmit="return confirm('Hapus paket ini?')" class="flex">
                @csrf @method('DELETE')
                <x-button size="sm" color="danger" type="submit" class="h-full"><i class="fas fa-trash"></i></x-button>
            </form>
        </div>
    </div>
    @empty
    <div class="col-span-full">
        <x-card><x-empty-state icon="fa-box-open" title="Belum ada paket" /></x-card>
    </div>
    @endforelse
</div>

@if($packages->hasPages())
<div class="mt-4">{{ $packages->links() }}</div>
@endif
@endsection
