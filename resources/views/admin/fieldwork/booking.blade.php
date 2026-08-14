@extends('layouts.app')

@section('title', 'Tugas Lapangan — '.$booking->name)

@section('content')
<x-page-header :title="'Tugas Lapangan — '.$booking->name">
    <x-slot:actions>
        <a href="{{ route('admin.fieldwork.index') }}" class="text-sm font-semibold text-brand hover:text-brand-dark no-underline inline-flex items-center gap-1.5">
            <i class="fas fa-arrow-left text-xs"></i> Kembali
        </a>
    </x-slot:actions>
</x-page-header>

{{-- INFO BOOKING --}}
<div class="bg-white rounded-xl shadow-sm border border-brand-100 p-5 mb-5 flex flex-wrap items-center gap-4">
    <x-badge>{{ $booking->code }}</x-badge>
    <div>
        <p class="text-sm font-semibold text-gray-800">{{ $booking->name }}</p>
        <p class="text-xs text-gray-500">{{ $booking->package?->name }} · {{ $booking->event_date ? $booking->event_date->format('d M Y') : '-' }} · {{ $booking->location ?? '-' }}</p>
    </div>
    <div class="ml-auto">
        @php $hasFitting = $booking->schedules->where('type', 'fitting')->where('status', '!=', 'cancelled')->isNotEmpty(); @endphp
        @if($hasFitting)
        <x-button size="sm" href="{{ route('admin.bookings.packing', $booking) }}">
            <i class="fas fa-clipboard-check"></i> Packing Checklist
        </x-button>
        @else
        <span class="text-xs text-gray-400 inline-flex items-center gap-1.5"><i class="fas fa-circle-info"></i> Packing muncul setelah jadwal fitting dibuat</span>
        @endif
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
    @include('admin.bookings.partials.survey-card')
    @include('admin.bookings.partials.fitting-card')
</div>
@endsection
