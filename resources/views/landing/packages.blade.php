@extends('layouts.landing')

@section('title', 'Paket')

@section('content')
<section class="page-header" style="background: linear-gradient(rgba(212,115,154,.8), rgba(184,92,133,.8)), url('{{ $landingImages['hero'] }}') center/cover no-repeat; padding: 64px 0; text-align:center; color:#fff;">
    <div class="section-container" style="max-width:820px; margin:0 auto; padding:0 3rem;">
        <p class="section-eyebrow mb-2" style="color:#fde8ef;">Paket</p>
        <h1 class="font-display font-bold mb-3" style="font-size:2.8rem;">Pilih Paket Sesuai Impian Anda</h1>
        <p style="opacity:.9;">Seluruh detail paket yang Anda pilih dapat dipantau melalui dashboard client.</p>
    </div>
</section>

@php
    $subTypeLabels = [
        'makeup' => 'Makeup Only', 'akad' => 'Akad Only', 'makeup_attire' => 'Makeup & Attire',
        'rumahan' => 'Wedding Rumahan', 'gedung' => 'Wedding Gedung',
    ];
    $packageTypes = $packages->pluck('packageType')->filter()->unique('id')->sortBy('name')->values();
@endphp

<section class="py-16 landing-section" style="background:var(--bg);" x-data="{ activeTab: '{{ $packageTypes->first()?->id }}' }">
    <div class="container">
        @if($packageTypes->isNotEmpty())
            <div class="flex justify-center mb-10">
                <div class="inline-flex max-w-full overflow-x-auto rounded-full border-2 border-gray-400 bg-white shadow-sm">
                    @foreach($packageTypes as $packageType)
                        <button @click="activeTab = '{{ $packageType->id }}'" class="cursor-pointer whitespace-nowrap border-0 px-6 py-3 text-sm font-semibold tracking-wide transition-all duration-200 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-inset" :class="activeTab === '{{ $packageType->id }}' ? 'bg-brand text-white shadow' : 'text-gray-800 hover:bg-gray-50'">{{ $packageType->name }}</button>
                        @if(! $loop->last)<span class="w-px self-stretch bg-gray-400"></span>@endif
                    @endforeach
                </div>
            </div>

            @foreach($packageTypes as $packageType)
                @php $subTypes = $packages->where('package_type_id', $packageType->id)->sortBy('price')->groupBy('sub_type'); @endphp
                <div x-show="activeTab === '{{ $packageType->id }}'" x-cloak x-transition.opacity.duration.200ms>
                    @foreach($subTypes as $subType => $items)
                        <div class="mb-10">
                            @if($subType)
                                <div class="flex items-center justify-center gap-4 mb-6">
                                    <span class="h-px flex-1" style="background:var(--primary-light);"></span>
                                    <h3 class="font-display font-bold text-lg px-4 whitespace-nowrap" style="color:var(--primary-dark);">{{ $subTypeLabels[$subType] ?? ucfirst(str_replace('_', ' ', $subType)) }}</h3>
                                    <span class="h-px flex-1" style="background:var(--primary-light);"></span>
                                </div>
                            @endif
                            <div class="flex flex-wrap justify-center gap-5">
                                @foreach($items as $package)
                                    <div class="card-mua text-center p-5 flex flex-col w-full sm:w-[calc(50%-0.625rem)] lg:w-[calc(25%-0.9375rem)]">
                                        <div class="mx-auto mb-3 rounded-full flex items-center justify-center" style="width:64px; height:64px; background:{{ $package->color }}18;"><i class="fa-solid fa-gem" style="font-size:1.5rem; color:{{ $package->color }};"></i></div>
                                        <h5 class="font-bold mb-1">{{ $package->name }}</h5>
                                        <div class="my-3 text-rose">
                                            @if($package->original_price && $package->original_price > $package->price)<div class="mb-1 text-base font-semibold text-gray-600 line-through decoration-1 decoration-gray-600">Rp {{ number_format($package->original_price, 0, ',', '.') }}</div>@endif
                                            <div class="font-display text-2xl font-bold">Rp {{ number_format($package->price, 0, ',', '.') }}</div>
                                        </div>
                                        <div class="text-left text-sm mb-4 border-t border-gray-100 pt-3">
                                            @foreach($package->benefits->groupBy(fn ($benefit) => $benefit->category->name ?? 'Umum') as $category => $benefits)
                                                <p class="font-bold mb-1.5 mt-3 first:mt-0" style="color:var(--primary-dark); font-size:.68rem; text-transform:uppercase; letter-spacing:1px;">{{ $category }}</p>
                                                <ul class="list-none mb-1">@foreach($benefits as $benefit)<li class="mb-1.5 flex items-start gap-2"><i class="fa-solid fa-circle-check text-green-600 mt-1"></i><span>{{ $benefit->name }}</span></li>@endforeach</ul>
                                            @endforeach
                                        </div>
                                        <a href="{{ route('booking.create') }}?package={{ $package->id }}" class="btn-pink w-full mt-auto">Booking Paket Ini</a>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            @endforeach
        @else
            <p class="text-center" style="color:var(--muted);">Belum ada paket aktif.</p>
        @endif

        <div class="text-center mt-6"><a href="{{ route('booking.create') }}" class="btn-pink px-6 py-2.5 text-base"><i class="fa-regular fa-calendar-check mr-2"></i>Booking Sekarang</a></div>
    </div>
</section>
@endsection
