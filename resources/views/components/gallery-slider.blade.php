@props(['gallery', 'height' => '280px', 'showCategory' => false])

@php($photos = $gallery->photo_urls)

<div data-gallery-lightbox data-gallery-photos="{{ base64_encode(json_encode($photos)) }}" data-gallery-title="{{ $gallery->title ?? 'Foto galeri' }}" class="relative overflow-hidden rounded-2xl shadow-sm" style="height:{{ $height }}; cursor:pointer;">
    <img src="{{ $photos[0] }}" class="w-full h-full" style="object-fit:cover;" alt="{{ $gallery->title ?? 'Foto galeri' }}">

    @if(count($photos) > 1)
        <div class="absolute top-3 right-3 rounded-full bg-black/45 px-2.5 py-1 text-xs font-semibold text-white">{{ count($photos) }} foto</div>
    @endif

    <div class="absolute bottom-0 left-0 w-full p-3 text-white" style="background:linear-gradient(transparent, rgba(0,0,0,.65));">
        <small class="font-semibold">{{ $gallery->title }}</small>
        @if($showCategory && $gallery->category)
            <br><small style="font-size:.65rem; text-transform:uppercase; letter-spacing:1px; opacity:.8;">{{ $gallery->category }}</small>
        @endif
    </div>
</div>
