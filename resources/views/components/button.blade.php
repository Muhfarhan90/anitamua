@props([
    'color' => 'primary',
    'size' => 'md',
    'type' => 'button',
    'href' => null,
])

@php
    $colors = [
        'primary' => 'bg-brand text-white hover:bg-brand-dark shadow-sm hover:shadow',
        'outline' => 'border-2 border-brand text-brand hover:bg-brand hover:text-white',
        'danger' => 'bg-red-500 text-white hover:bg-red-600 shadow-sm',
        'success' => 'bg-emerald-500 text-white hover:bg-emerald-600 shadow-sm',
        'gold' => 'bg-gold text-white hover:bg-gold-dark shadow-sm',
        'ghost' => 'text-gray-600 hover:bg-gray-100',
        'light' => 'bg-white text-brand-dark hover:bg-brand-50 shadow-sm',
    ];
    $sizes = [
        'sm' => 'px-3 py-1.5 text-xs rounded-lg',
        'md' => 'px-4 py-2 text-sm rounded-lg',
        'lg' => 'px-6 py-3 text-base rounded-xl',
    ];
    $classes = 'inline-flex items-center justify-center gap-2 font-semibold transition-all duration-200 cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed no-underline '.$colors[$color].' '.$sizes[$size];
@endphp

@if($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>{{ $slot }}</a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => $classes]) }}>{{ $slot }}</button>
@endif
