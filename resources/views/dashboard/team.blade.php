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
                    <th class="px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Tanggal</th>
                    <th class="px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Jenis</th>
                    <th class="px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Client</th>
                    <th class="px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Kode</th>
                    <th class="px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Lokasi</th>
                    <th class="px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse($tasks as $task)
                <tr class="hover:bg-brand-50/20 transition-colors">
                    <td class="px-5 py-3.5 text-gray-800 font-medium whitespace-nowrap">{{ $task->date->format('d M Y') }}</td>
                    <td class="px-5 py-3.5">
                        <x-badge :color="match($task->type) {
                            'survey' => 'info',
                            'fitting' => 'brand',
                            default => 'danger',
                        }">{{ \App\Models\Schedule::typeLabel($task->type) }}</x-badge>
                    </td>
                    <td class="px-5 py-3.5 text-gray-800">{{ $task->booking->name ?? '-' }}</td>
                    <td class="px-5 py-3.5"><x-badge>{{ $task->booking->code ?? '-' }}</x-badge></td>
                    <td class="px-5 py-3.5 text-gray-500 text-xs max-w-[180px] truncate">{{ $task->location ?: ($task->booking->location ?? '-') }}</td>
                    <td class="px-5 py-3.5">
                        <form action="{{ route('admin.schedules.status', $task) }}" method="POST">
                            @csrf
                            @method('PATCH')
                            <select name="status" onchange="this.form.submit()"
                                    class="rounded-lg border border-gray-200 bg-gray-50 px-2 py-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-brand/30 cursor-pointer">
                                <option value="scheduled" {{ $task->status === 'scheduled' ? 'selected' : '' }}>Terjadwal</option>
                                <option value="on_going"  {{ $task->status === 'on_going'  ? 'selected' : '' }}>Berlangsung</option>
                                <option value="finished"  {{ $task->status === 'finished'  ? 'selected' : '' }}>Selesai</option>
                            </select>
                        </form>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6"><x-empty-state icon="fa-calendar-xmark" title="Tidak ada tugas" /></td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-card>
@endsection
