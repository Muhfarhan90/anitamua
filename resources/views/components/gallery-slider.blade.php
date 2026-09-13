@props(['gallery' => null, 'photos' => null, 'title' => null, 'category' => null, 'height' => '280px', 'showCategory' => false])

@php
    $photos = $photos ?? ($gallery?->photo_urls ?? []);
    $title = $title ?? ($gallery?->title ?? $gallery?->name ?? 'Galeri');
    $category = $category ?? ($gallery?->category ?? null);
@endphp

<div role="button" tabindex="0" aria-label="Lihat foto {{ $title }}" data-gallery-lightbox data-gallery-photos="{{ base64_encode(json_encode($photos)) }}" data-gallery-title="{{ $title }}" class="relative overflow-hidden rounded-2xl shadow-sm cursor-zoom-in focus:outline-none focus-visible:ring-2 focus-visible:ring-brand" style="height:{{ $height }};">
    <img src="{{ $photos[0] }}" class="w-full h-full" style="object-fit:cover;" alt="{{ $title }}">

    <div class="absolute bottom-0 left-0 w-full p-3 text-white" style="background:linear-gradient(transparent, rgba(0,0,0,.65));">
        <small class="font-semibold">{{ $title }}</small>
        @if($showCategory && $category)
            <br><small style="font-size:.65rem; text-transform:uppercase; letter-spacing:1px; opacity:.8;">{{ $category }}</small>
        @endif
    </div>
</div>
