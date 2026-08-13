@props(['icon', 'label', 'value', 'sub' => null, 'color' => 'brand', 'layout' => 'horizontal'])

@php
    $colorMap = [
        'brand' => ['bg' => 'bg-brand-50', 'icon' => 'text-brand', 'border' => 'border-l-brand'],
        'emerald' => ['bg' => 'bg-emerald-50', 'icon' => 'text-emerald-500', 'border' => 'border-l-emerald-400'],
        'amber' => ['bg' => 'bg-amber-50', 'icon' => 'text-amber-500', 'border' => 'border-l-amber-400'],
        'blue' => ['bg' => 'bg-blue-50', 'icon' => 'text-blue-500', 'border' => 'border-l-blue-400'],
        'rose' => ['bg' => 'bg-rose-50', 'icon' => 'text-rose-500', 'border' => 'border-l-rose-400'],
        'purple' => ['bg' => 'bg-purple-50', 'icon' => 'text-purple-500', 'border' => 'border-l-purple-400'],
        'indigo' => ['bg' => 'bg-indigo-50', 'icon' => 'text-indigo-500', 'border' => 'border-l-indigo-400'],
        'gray' => ['bg' => 'bg-gray-50', 'icon' => 'text-gray-500', 'border' => 'border-l-gray-400'],
    ];
    $c = $colorMap[$color] ?? $colorMap['brand'];
@endphp

@if ($layout === 'vertical')
    <div
        {{ $attributes->merge(['class' => 'bg-white rounded-2xl shadow-sm border border-gray-100 border-l-4 ' . $c['border'] . ' p-2 hover:shadow-md transition-shadow duration-200']) }}>
        <div class="h-full flex flex-col items-center justify-center text-center gap-2.5">
            <p class="text-xs font-medium text-gray-500">{{ $label }}</p>
            <div class="w-11 h-11 rounded-xl flex items-center justify-center flex-shrink-0 {{ $c['bg'] }}">
                <i class="fas {{ $icon }} text-base {{ $c['icon'] }}"></i>
            </div>
            <p class="text-2xl font-bold text-gray-800 leading-none truncate">{{ $value }}</p>
            @if ($sub)
                <p class="text-[11px] text-gray-400">{{ $sub }}</p>
            @endif
        </div>
    </div>
@else
    <div
        {{ $attributes->merge(['class' => 'bg-white rounded-2xl shadow-sm border border-gray-100 border-l-4 ' . $c['border'] . ' p-5 hover:shadow-md transition-shadow duration-200']) }}>
        <div class="flex items-center gap-4">
            <div class="w-11 h-11 rounded-xl flex items-center justify-center flex-shrink-0 {{ $c['bg'] }}">
                <i class="fas {{ $icon }} text-base {{ $c['icon'] }}"></i>
            </div>
            <div class="min-w-0 flex-1">
            <p class="text-xs font-medium text-gray-500 leading-tight">{{ $label }}</p>
            <p class="text-2xl font-bold text-gray-800 leading-none truncate">{{ $value }}</p>
                @if ($sub)
                    <p class="text-[11px] text-gray-400 mt-1">{{ $sub }}</p>
                @endif
            </div>
        </div>
    </div>
@endif
