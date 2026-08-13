@props(['color' => 'brand'])

@php
    $colors = [
        'brand' => 'bg-brand-100 text-brand-dark',
        'success' => 'bg-emerald-100 text-emerald-700',
        'warning' => 'bg-yellow-100 text-yellow-700',
        'danger' => 'bg-red-100 text-red-700',
        'info' => 'bg-blue-100 text-blue-700',
        'gray' => 'bg-gray-100 text-gray-600',
    ];
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium whitespace-nowrap '.$colors[$color]]) }}>{{ $slot }}</span>
