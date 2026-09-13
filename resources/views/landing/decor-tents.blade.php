@extends('layouts.landing')

@section('title', 'Dekor & Tenda')

@section('content')
<section class="page-header" style="background: linear-gradient(rgba(212,115,154,.8), rgba(184,92,133,.8)), url('{{ $landingImages['hero'] }}') center/cover no-repeat; padding: 64px 0; text-align:center; color:#fff;">
    <div class="section-container" style="max-width:820px; margin:0 auto; padding:0 3rem;">
        <p class="section-eyebrow mb-2" style="color:#fde8ef;">Dekor & Tenda</p>
        <h1 class="font-display font-bold mb-3" style="font-size:2.8rem;">Pilihan Dekorasi untuk Hari Istimewa</h1>
        <p style="opacity:.9;">Lihat pilihan pelaminan, gapura, dan tenda yang tersedia untuk melengkapi acara Anda.</p>
    </div>
</section>

@foreach([
    ['title' => 'Pelaminan & Dekor', 'description' => 'Pilihan dekorasi utama untuk melengkapi konsep acara Anda.', 'items' => $weddingStages, 'icon' => 'fa-panorama'],
    ['title' => 'Gapura Pintu Masuk', 'description' => 'Pilihan tampilan pintu masuk untuk menyambut tamu di hari istimewa.', 'items' => $entranceGates, 'icon' => 'fa-door-open'],
    ['title' => 'Model Tenda', 'description' => 'Pilihan model tenda untuk menyesuaikan kebutuhan lokasi acara.', 'items' => $tents, 'icon' => 'fa-campground'],
] as $section)
<section class="landing-section py-16" style="background:{{ $loop->odd ? 'var(--bg)' : '#fff' }};">
    <div class="section-container">
        <div class="mb-6 flex flex-wrap items-end justify-between gap-3">
            <div>
                <p class="section-eyebrow mb-2">Katalog Dekorasi</p>
                <h2 class="font-display text-3xl font-bold text-gray-800">{{ $section['title'] }}</h2>
                <p class="mt-2 text-sm text-gray-500">{{ $section['description'] }}</p>
            </div>
            <span class="inline-flex h-11 w-11 items-center justify-center rounded-full bg-pink-50 text-brand" aria-hidden="true"><i class="fas {{ $section['icon'] }}"></i></span>
        </div>
        <div class="grid grid-cols-2 gap-3 md:grid-cols-3 lg:grid-cols-4">
            @forelse($section['items'] as $item)
                @if($item->photo_urls)
                    <x-gallery-slider :photos="$item->photo_urls" :title="$item->name" />
                @else
                    <div class="relative flex h-[280px] items-center justify-center overflow-hidden rounded-2xl bg-pink-50 text-4xl text-brand shadow-sm">
                        <i class="fas {{ $section['icon'] }}" aria-hidden="true"></i>
                        <div class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-black/75 to-transparent px-3 pb-3 pt-10 text-sm font-semibold text-white">{{ $item->name }}</div>
                    </div>
                @endif
            @empty
                <div class="col-span-full py-10 text-center text-sm text-gray-400">Belum ada pilihan {{ strtolower($section['title']) }} yang aktif.</div>
            @endforelse
        </div>
    </div>
</section>
@endforeach
@endsection

@push('scripts')
    <x-gallery-lightbox />
@endpush
