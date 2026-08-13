@extends('layouts.app')

@section('title', 'Manajemen Booking')

@section('content')
<x-page-header title="Manajemen Booking">
    <x-slot:actions>
        <x-button href="{{ route('admin.bookings.create') }}"><i class="fas fa-plus"></i> Booking Baru</x-button>
    </x-slot:actions>
</x-page-header>

<x-card class="mb-5">
    <form method="GET" action="{{ route('admin.bookings.index') }}" class="flex flex-wrap items-end gap-4">
        <div class="flex-1 min-w-[200px]">
            <label class="block text-sm font-medium text-gray-600 mb-1">Cari Booking</label>
            <input type="text" name="q" value="{{ request('q') }}" placeholder="Kode booking, nama klien..."
                   class="w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand-200">
        </div>
        <div class="min-w-[180px]">
            <label class="block text-sm font-medium text-gray-600 mb-1">Status</label>
            <select name="status" class="w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand-200">
                <option value="">Semua Status</option>
                <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                <option value="booked" {{ request('status') === 'booked' ? 'selected' : '' }}>Booked</option>
                <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Selesai</option>
                <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>Dibatalkan</option>
            </select>
        </div>
        <x-button type="submit"><i class="fas fa-filter"></i> Filter</x-button>
    </form>
</x-card>

<x-card padding="p-0">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="text-left text-gray-500 border-b border-gray-100 bg-cream/60">
                    <th class="px-5 py-2.5 font-medium">Kode</th>
                    <th class="px-5 py-2.5 font-medium">Client</th>
                    <th class="px-5 py-2.5 font-medium">Paket</th>
                    <th class="px-5 py-2.5 font-medium">Tanggal Acara</th>
                    <th class="px-5 py-2.5 font-medium">Status</th>
                    <th class="px-5 py-2.5 font-medium text-center">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($bookings as $booking)
                <tr class="border-b border-gray-50 hover:bg-brand-50/30 transition-colors">
                    <td class="px-5 py-3 whitespace-nowrap"><x-badge>{{ $booking->code }}</x-badge></td>
                    <td class="px-5 py-3 whitespace-nowrap">
                        <div class="font-semibold text-gray-800">{{ $booking->client->name ?? '-' }}</div>
                        <div class="text-xs text-gray-500">{{ $booking->client->phone ?? '-' }}</div>
                    </td>
                    <td class="px-5 py-3 whitespace-nowrap text-gray-600">{{ $booking->package->name ?? '-' }}</td>
                    <td class="px-5 py-3 whitespace-nowrap text-gray-600">{{ $booking->event_date ? \Carbon\Carbon::parse($booking->event_date)->format('d M Y') : '-' }}</td>
                    <td class="px-5 py-3 whitespace-nowrap">
                        <x-badge :color="match($booking->status) {
                            'pending' => 'warning',
                            'booked' => 'success',
                            'completed' => 'info',
                            'cancelled' => 'danger',
                            default => 'gray',
                        }">{{ match($booking->status) {
                            'pending' => 'Pending',
                            'booked' => 'Booked',
                            'completed' => 'Selesai',
                            'cancelled' => 'Dibatalkan',
                            default => $booking->status,
                        } }}</x-badge>
                    </td>
                    <td class="px-5 py-3 whitespace-nowrap text-center">
                        <x-button size="sm" href="{{ route('admin.bookings.show', $booking) }}"><i class="fas fa-eye"></i> Detail</x-button>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6"><x-empty-state icon="fa-folder-open" title="Belum ada data booking" /></td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($bookings->hasPages())
    <div class="px-5 py-4 border-t border-gray-100">
        {{ $bookings->withQueryString()->links() }}
    </div>
    @endif
</x-card>
@endsection
