@props(['title' => null, 'titleIcon' => null, 'padding' => null])

@php
    $innerPadding = $padding ?? 'p-5';
@endphp

<div {{ $attributes->merge(['class' => 'bg-white rounded-2xl shadow-sm border border-gray-100']) }}>
    @if($title)
        <div class="flex items-center justify-between gap-3 px-5 py-3 border-b border-gray-100">
            <div class="flex min-w-0 items-center gap-2.5">
                @if($titleIcon)
                    <div class="w-8 h-8 rounded-lg bg-brand-50 flex items-center justify-center flex-shrink-0">
                        <i class="fas {{ $titleIcon }} text-xs text-brand"></i>
                    </div>
                @endif
                <h2 class="font-display font-semibold text-base text-gray-800">{{ $title }}</h2>
            </div>
            @isset($actions)
                <div class="flex shrink-0 items-center gap-2">
                    {{ $actions }}
                </div>
            @endisset
        </div>
        <div class="{{ $innerPadding }}">
            {{ $slot }}
        </div>
    @else
        <div class="{{ $innerPadding }}">
            {{ $slot }}
        </div>
    @endif
</div>
