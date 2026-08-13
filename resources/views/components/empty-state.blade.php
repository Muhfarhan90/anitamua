@props(['icon' => 'fa-inbox', 'title' => 'Belum ada data', 'text' => null])

<div class="text-center py-10">
    <div class="w-14 h-14 mx-auto mb-3 rounded-full bg-brand-50 flex items-center justify-center">
        <i class="fas {{ $icon }} text-xl text-brand-light"></i>
    </div>
    <p class="font-medium text-gray-600">{{ $title }}</p>
    @if($text)<p class="text-sm text-gray-400 mt-1">{{ $text }}</p>@endif
</div>
