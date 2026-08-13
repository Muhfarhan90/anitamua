@extends('layouts.landing')

@section('title', 'Paket')

@section('content')
<section class="page-header" style="background: linear-gradient(rgba(212,115,154,.8), rgba(184,92,133,.8)), url('https://images.unsplash.com/photo-1519741497674-611481863552?q=80&w=1920&auto=format&fit=crop') center/cover no-repeat; padding: 64px 0; text-align:center; color:#fff;">
    <div class="section-container" style="max-width:820px; margin:0 auto; padding:0 3rem;">
        <p class="section-eyebrow mb-2" style="color:#fde8ef;">Paket</p>
        <h1 class="font-display font-bold mb-3" style="font-size:2.8rem;">Pilih Paket Sesuai Impian Anda</h1>
        <p style="opacity:.9;">Seluruh detail paket yang Anda pilih dapat dipantau melalui dashboard client.</p>
    </div>
</section>

<section class="py-16 landing-section" style="background:var(--bg);" x-data="{ activeTab: 'makeup' }">
    <div class="container">

        {{-- TOGGLE --}}
        <div class="flex justify-center mb-10">
            <div class="inline-flex rounded-full overflow-hidden shadow-sm border border-gray-200 bg-white">
                <button @click="activeTab = 'makeup'"
                        class="px-9 py-3 text-sm font-semibold tracking-wide transition-all duration-200 cursor-pointer whitespace-nowrap"
                        :class="activeTab === 'makeup' ? 'bg-brand text-white shadow' : 'text-gray-800 hover:bg-gray-50'">
                    Makeup & Attire
                </button>
                <span class="w-px self-stretch bg-gray-200"></span>
                <button @click="activeTab = 'wedding'"
                        class="px-9 py-3 text-sm font-semibold tracking-wide transition-all duration-200 cursor-pointer whitespace-nowrap"
                        :class="activeTab === 'wedding' ? 'bg-brand text-white shadow' : 'text-gray-800 hover:bg-gray-50'">
                    Wedding
                </button>
            </div>
        </div>

        @php
            $subTypeLabels = [
                'makeup' => 'Makeup Only',
                'akad' => 'Akad Only',
                'makeup_attire' => 'Makeup & Attire',
                'rumahan' => 'Wedding Rumahan',
                'gedung' => 'Wedding Gedung',
            ];
            $makeupSubTypes = $packages->where('type', 'makeup')->sortBy('price')->groupBy('sub_type');
            $weddingSubTypes = $packages->where('type', 'full')->sortBy('price')->groupBy('sub_type');
        @endphp

        {{-- MAKEUP TAB --}}
        <div x-show="activeTab === 'makeup'" x-cloak x-transition.opacity.duration.200ms>
            @forelse($makeupSubTypes as $subType => $items)
                <div class="mb-10">
                    <div class="flex items-center justify-center gap-4 mb-6">
                        <span class="h-px flex-1" style="background:var(--primary-light);"></span>
                        <h3 class="font-display font-bold text-lg px-4 whitespace-nowrap" style="color:var(--primary-dark);">
                            {{ $subTypeLabels[$subType] ?? ucfirst(str_replace('_', ' ', $subType)) }}
                        </h3>
                        <span class="h-px flex-1" style="background:var(--primary-light);"></span>
                    </div>
                    <div class="flex flex-wrap justify-center gap-5">
                        @foreach($items as $package)
                            <div class="card-mua text-center p-5 flex flex-col w-full sm:w-[calc(50%-0.625rem)] lg:w-[calc(25%-0.9375rem)]">
                                <div class="mx-auto mb-3 rounded-full flex items-center justify-center" style="width:64px; height:64px; background:{{ $package->color }}18;">
                                    <i class="fa-solid fa-wand-magic-sparkles" style="font-size:1.5rem; color:{{ $package->color }};"></i>
                                </div>
                                <h5 class="font-bold mb-1">{{ $package->name }}</h5>
                                <div class="font-display text-2xl font-bold my-3 text-rose">Rp {{ number_format($package->price, 0, ',', '.') }}</div>
                                <div class="text-left text-sm mb-4 border-t border-gray-100 pt-3">
                                    @foreach($package->benefits->groupBy(fn ($b) => $b->category->name ?? 'Umum') as $category => $benefits)
                                        <p class="font-bold mb-1.5 mt-3 first:mt-0" style="color:var(--primary-dark); font-size:.68rem; text-transform:uppercase; letter-spacing:1px;">{{ $category }}</p>
                                        <ul class="list-none mb-1">
                                            @foreach($benefits as $benefit)
                                                <li class="mb-1.5 flex items-start gap-2">
                                                    <i class="fa-solid fa-circle-check text-green-600 mt-1"></i>
                                                    <span>{{ $benefit->name }}</span>
                                                </li>
                                            @endforeach
                                        </ul>
                                    @endforeach
                                </div>
                                <a href="{{ route('booking.create') }}?package={{ $package->id }}" class="btn-pink w-full mt-auto">Booking Paket Ini</a>
                            </div>
                        @endforeach
                    </div>
                </div>
            @empty
                <p class="text-center" style="color:var(--muted);">Belum ada paket makeup.</p>
            @endforelse
        </div>

        {{-- WEDDING TAB --}}
        <div x-show="activeTab === 'wedding'" x-cloak x-transition.opacity.duration.200ms>
            @forelse($weddingSubTypes as $subType => $items)
                <div class="mb-10">
                    <div class="flex items-center justify-center gap-4 mb-6">
                        <span class="h-px flex-1" style="background:var(--primary-light);"></span>
                        <h3 class="font-display font-bold text-lg px-4 whitespace-nowrap" style="color:var(--primary-dark);">
                            {{ $subTypeLabels[$subType] ?? ucfirst(str_replace('_', ' ', $subType)) }}
                        </h3>
                        <span class="h-px flex-1" style="background:var(--primary-light);"></span>
                    </div>
                    <div class="flex flex-wrap justify-center gap-5">
                        @foreach($items as $package)
                            <div class="card-mua text-center p-5 flex flex-col w-full sm:w-[calc(50%-0.625rem)] lg:w-[calc(25%-0.9375rem)]">
                                <div class="mx-auto mb-3 rounded-full flex items-center justify-center" style="width:64px; height:64px; background:{{ $package->color }}18;">
                                    <i class="fa-solid fa-gem" style="font-size:1.5rem; color:{{ $package->color }};"></i>
                                </div>
                                <h5 class="font-bold mb-1">{{ $package->name }}</h5>
                                <div class="font-display text-2xl font-bold my-3 text-rose">Rp {{ number_format($package->price, 0, ',', '.') }}</div>
                                <div class="text-left text-sm mb-4 border-t border-gray-100 pt-3">
                                    @foreach($package->benefits->groupBy(fn ($b) => $b->category->name ?? 'Umum') as $category => $benefits)
                                        <p class="font-bold mb-1.5 mt-3 first:mt-0" style="color:var(--primary-dark); font-size:.68rem; text-transform:uppercase; letter-spacing:1px;">{{ $category }}</p>
                                        <ul class="list-none mb-1">
                                            @foreach($benefits as $benefit)
                                                <li class="mb-1.5 flex items-start gap-2">
                                                    <i class="fa-solid fa-circle-check text-green-600 mt-1"></i>
                                                    <span>{{ $benefit->name }}</span>
                                                </li>
                                            @endforeach
                                        </ul>
                                    @endforeach
                                </div>
                                <a href="{{ route('booking.create') }}?package={{ $package->id }}" class="btn-pink w-full mt-auto">Booking Paket Ini</a>
                            </div>
                        @endforeach
                    </div>
                </div>
            @empty
                <p class="text-center" style="color:var(--muted);">Belum ada paket wedding.</p>
            @endforelse
        </div>

        <div class="text-center mt-6">
            <a href="{{ route('booking.create') }}" class="btn-pink px-6 py-2.5 text-base"><i class="fa-regular fa-calendar-check mr-2"></i>Booking Sekarang</a>
        </div>
    </div>
</section>
@endsection
