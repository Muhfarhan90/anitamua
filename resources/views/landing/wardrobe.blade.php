@extends('layouts.landing')

@section('title', 'Wardrobe')

@php
    $firstCategory = $categories->first();
    $whatsapp = preg_replace('/\D/', '', $settings['whatsapp'] ?? '');
@endphp

@section('content')
<section class="page-header" style="background: linear-gradient(rgba(212,115,154,.8), rgba(184,92,133,.8)), url('{{ $landingImages['hero'] }}') center/cover no-repeat; padding: 64px 0; text-align:center; color:#fff;">
    <div class="section-container" style="max-width:820px; margin:0 auto; padding:0 3rem;">
        <p class="section-eyebrow mb-2" style="color:#fde8ef;">Wardrobe</p>
        <h1 class="font-display font-bold mb-3" style="font-size:2.8rem;">Koleksi Wardrobe Kami</h1>
        <p style="opacity:.9;">Pilih busana dan aksesori untuk melengkapi momen istimewa Anda.</p>
    </div>
</section>

<section class="py-16 landing-section" style="background:var(--bg);" x-data="{ activeCategory: '{{ $firstCategory?->id ?? '' }}' }">
    <div class="section-container">
        @if($categories->isNotEmpty())
            <div class="flex justify-center mb-10" role="tablist" aria-label="Kategori wardrobe">
                <div class="inline-flex max-w-full overflow-x-auto rounded-full border-2 border-gray-400 bg-white shadow-sm">
                    @foreach($categories as $category)
                        <button type="button" id="wardrobe-tab-{{ $category->id }}" role="tab" @click="activeCategory = '{{ $category->id }}'" :aria-selected="activeCategory === '{{ $category->id }}'" :class="activeCategory === '{{ $category->id }}' ? 'bg-brand text-white shadow' : 'text-gray-800 hover:bg-gray-50'" class="cursor-pointer whitespace-nowrap border-0 px-6 py-3 text-sm font-semibold tracking-wide transition-all duration-200 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-inset">{{ $category->name }}</button>
                        @if(! $loop->last)<span class="w-px self-stretch bg-gray-400"></span>@endif
                    @endforeach
                </div>
            </div>

            @foreach($categories as $category)
                <div id="wardrobe-panel-{{ $category->id }}" role="tabpanel" aria-labelledby="wardrobe-tab-{{ $category->id }}" x-show="activeCategory === '{{ $category->id }}'" x-cloak x-transition.opacity.duration.200ms>
                    <div class="mb-5 text-center">
                        <p class="section-eyebrow mb-2">{{ $category->name }}</p>
                        <p class="text-sm text-gray-500">Klik foto untuk melihat lebih dekat.</p>
                    </div>
                    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3">
                        @foreach($category->items as $item)
                            @php
                                $message = rawurlencode('Halo '.($settings['company_name'] ?? 'ANITA MUA').', apakah wardrobe '.$item->name.' masih tersedia?');
                            @endphp
                            <div class="space-y-2">
                                @if($item->photo)
                                    <x-gallery-slider :photos="[asset('storage/'.$item->photo)]" :title="$item->name" />
                                @else
                                    <div class="relative flex h-[280px] items-center justify-center overflow-hidden rounded-2xl bg-pink-50 text-4xl text-brand shadow-sm">
                                        <i class="fas fa-shirt" aria-hidden="true"></i>
                                        <div class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-black/75 to-transparent px-3 pb-3 pt-10 text-sm font-semibold text-white">{{ $item->name }}</div>
                                    </div>
                                @endif
                                <a href="https://wa.me/{{ $whatsapp }}?text={{ $message }}" target="_blank" rel="noopener" class="inline-flex w-full items-center justify-center rounded-xl bg-[#25D366] px-3 py-2 text-xs font-bold text-white no-underline transition hover:bg-[#1ebe5b]"><i class="fa-brands fa-whatsapp mr-1"></i> Pesan Sekarang</a>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach
        @else
            <div class="py-12 text-center" style="color:var(--muted);">
                Belum ada koleksi wardrobe.
            </div>
        @endif
    </div>
</section>
@endsection

@push('scripts')
<x-gallery-lightbox />
@endpush
