@props(['title', 'subtitle' => null])

<div class="flex flex-wrap items-end justify-between gap-4 pb-4 mb-4 border-b border-gray-100">
    <div>
        <h1 class="font-display text-xl md:text-2xl font-bold text-gray-800 leading-tight">{{ $title }}</h1>
        @if($subtitle)<p class="text-sm text-gray-500 mt-1">{{ $subtitle }}</p>@endif
    </div>
    @isset($actions)
        <div class="flex items-center gap-2">{{ $actions }}</div>
    @endisset
</div>
