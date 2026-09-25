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
</div>

<div class="space-y-5">
    @if($booking->allowsSurvey())
    @include('admin.bookings.partials.survey-card')
    @endif
    @if($booking->allowsFitting())
    @include('admin.bookings.partials.fitting-card')
    @endif
</div>
@endsection
