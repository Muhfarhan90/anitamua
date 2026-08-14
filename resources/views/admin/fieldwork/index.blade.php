@extends('layouts.app')

@section('title', 'Tugas Lapangan')

@section('content')
<x-page-header title="Tugas Lapangan" subtitle="Pilih booking untuk mengisi survey, fitting, dan packing checklist" />

<x-card padding="p-0">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="text-left text-gray-500 border-b border-gray-100 bg-cream/60">
                    <th class="px-5 py-2.5 font-medium">Kode</th>
                    <th class="px-5 py-2.5 font-medium">Client</th>
                    <th class="px-5 py-2.5 font-medium">Paket</th>
                    <th class="px-5 py-2.5 font-medium">Tanggal Acara</th>
                    <th class="px-5 py-2.5 font-medium">Lokasi</th>
                    <th class="px-5 py-2.5 font-medium">Status</th>
                    <th class="px-5 py-2.5 font-medium text-center">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($bookings as $booking)
                <tr class="border-b border-gray-50 hover:bg-brand-50/30 transition-colors">
                    <td class="px-5 py-3"><x-badge>{{ $booking->code }}</x-badge></td>
                    <td class="px-5 py-3 font-medium text-gray-800">{{ $booking->name }}</td>
                    <td class="px-5 py-3 text-gray-600">{{ $booking->package?->name ?? '-' }}</td>
                    <td class="px-5 py-3 text-gray-600">{{ $booking->event_date ? $booking->event_date->format('d M Y') : '-' }}</td>
                    <td class="px-5 py-3 text-gray-500 text-xs max-w-[200px] truncate">{{ $booking->location ?? '-' }}</td>
                    <td class="px-5 py-3">
                        <x-badge :color="match($booking->status) {
                            'booked' => 'success',
                            'pending' => 'warning',
                            'completed' => 'info',
                            'cancelled' => 'danger',
                            default => 'gray',
                        }">{{ ucfirst($booking->status) }}</x-badge>
                    </td>
                    <td class="px-5 py-3 text-center">
                        <x-button size="sm" href="{{ route('admin.fieldwork.booking', $booking) }}">
                            <i class="fas fa-clipboard-list"></i> Buka Tugas
                        </x-button>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7"><x-empty-state icon="fa-clipboard-list" title="Belum ada booking" /></td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-card>
@endsection
