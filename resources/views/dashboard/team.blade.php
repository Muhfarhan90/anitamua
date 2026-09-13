@extends('layouts.app')

@section('title', 'Dashboard Tim')

@section('content')
<x-page-header title="Dashboard Tim Lapangan" subtitle="Tugas dan jadwal yang ditugaskan kepada Anda" />

{{-- QUICK STATS --}}
@php
    $totalTasks    = $tasks->count();
    $doneTasks     = $tasks->where('status', 'finished')->count();
    $ongoingTasks  = $tasks->where('status', 'on_going')->count();
    $scheduledTasks = $tasks->where('status', 'scheduled')->count();
@endphp

<div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-6">
    <x-stat-card icon="fa-list-check"      color="brand"   label="Total Tugas"   value="{{ $totalTasks }}" />
    <x-stat-card icon="fa-hourglass-half"  color="amber"   label="Terjadwal"     value="{{ $scheduledTasks }}" />
    <x-stat-card icon="fa-circle-play"     color="blue"    label="Berlangsung"   value="{{ $ongoingTasks }}" />
    <x-stat-card icon="fa-circle-check"    color="emerald" label="Selesai"       value="{{ $doneTasks }}" />
</div>

{{-- TABEL TUGAS --}}
<x-card title="Jadwal Tugas Saya" title-icon="fa-calendar-check" padding="p-0">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="text-left border-b border-gray-100 bg-gray-50">
                    <th class="px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Kode</th>
                    <th class="px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Client</th>
                    <th class="px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Paket</th>
                    <th class="px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Tanggal Acara</th>
                    <th class="px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Lokasi</th>
                    <th class="px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                    <th class="px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider text-center">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse($bookings as $booking)
                <tr class="hover:bg-brand-50/20 transition-colors">
                    <td class="px-5 py-3.5"><x-badge>{{ $booking->code }}</x-badge></td>
                    <td class="px-5 py-3.5 text-gray-800">{{ $booking->name }}</td>
                    <td class="px-5 py-3.5 text-gray-600">{{ $booking->package?->name ?? '-' }}</td>
                    <td class="px-5 py-3.5 text-gray-800 font-medium whitespace-nowrap">{{ $booking->event_date?->format('d M Y') ?? '-' }}</td>
                    <td class="px-5 py-3.5 text-gray-500 text-xs max-w-[180px] truncate">{{ $booking->location ?? '-' }}</td>
                    <td class="px-5 py-3.5">
                        <x-badge :color="match($booking->status) {
                            'booked' => 'success',
                            'pending' => 'warning',
                            'completed' => 'info',
                            'cancelled' => 'danger',
                            default => 'gray',
                        }">{{ ucfirst($booking->status) }}</x-badge>
                    </td>
                    <td class="px-5 py-3.5 text-center">
                        <div class="flex items-center justify-center gap-2">
                            <x-button size="sm" color="success" href="{{ route('admin.bookings.packing', $booking) }}">
                                <i class="fas fa-list-check"></i> Checklist
                            </x-button>
                            <x-button size="sm" href="{{ route('admin.fieldwork.booking', $booking) }}">
                                <i class="fas fa-clipboard-list"></i> Buka Tugas
                            </x-button>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7"><x-empty-state icon="fa-calendar-xmark" title="Tidak ada tugas" /></td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-card>
@endsection
