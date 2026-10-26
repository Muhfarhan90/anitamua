@extends('layouts.landing')

@section('title', 'Paket')

@section('content')
    <section class="page-header"
        style="background: linear-gradient(rgba(212,115,154,.8), rgba(184,92,133,.8)), url('{{ $landingImages['hero'] }}') center/cover no-repeat; padding: 64px 0; text-align:center; color:#fff;">
        <div class="section-container" style="max-width:820px; margin:0 auto; padding:0 3rem;">
            <p class="section-eyebrow mb-2" style="color:#fde8ef;">Paket</p>
            <h1 class="font-display font-bold mb-3" style="font-size:2.8rem;">Pilih Paket Sesuai Impian Anda</h1>
            <p style="opacity:.9;">Seluruh detail paket yang Anda pilih dapat dipantau melalui dashboard client.</p>
        </div>
    </section>

    @php
        $subTypeLabels = [
            'makeup' => 'Makeup Only',
            'akad' => 'Akad Only',
            'makeup_attire' => 'Makeup & Attire',
            'rumahan' => 'Wedding Rumahan',
            'gedung' => 'Wedding Gedung',
        ];
        $packageGroups = [
            'regular' => $packages->filter(fn($package) => $package->packageType?->display_group === 'regular'),
            'wedding' => $packages->filter(fn($package) => $package->packageType?->display_group !== 'regular'),
        ];
    @endphp

    <section class="py-16 landing-section" style="background:var(--bg);" x-data="{ activeTab: 'wedding' }">
        <div class="container">
            <div class="mb-10 flex flex-col items-center gap-4">
                <div class="inline-flex w-full max-w-sm rounded-full border border-brand-200 bg-white p-1 shadow-sm"
                    role="tablist" aria-label="Kelompok paket">
                    <button type="button" id="tab-regular" role="tab" aria-controls="panel-regular"
                        :aria-selected="activeTab === 'regular'" @click="activeTab = 'regular'"
                        class="min-w-0 flex-1 cursor-pointer rounded-full border-0 px-4 py-2.5 text-sm font-semibold transition-colors focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand"
                        :class="activeTab === 'regular' ? 'bg-brand text-white shadow-sm' :
                            'bg-transparent text-gray-700 hover:bg-brand-50'">Reguler</button>
                    <button type="button" id="tab-wedding" role="tab" aria-controls="panel-wedding"
                        :aria-selected="activeTab === 'wedding'" @click="activeTab = 'wedding'"
                        class="min-w-0 flex-1 cursor-pointer rounded-full border-0 px-4 py-2.5 text-sm font-semibold transition-colors focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand"
                        :class="activeTab === 'wedding' ? 'bg-brand text-white shadow-sm' :
                            'bg-transparent text-gray-700 hover:bg-brand-50'">Wedding</button>
                </div>
                <a href="{{ route('terms') }}"
                    class="text-sm font-semibold text-brand hover:text-brand-dark focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand"><i
                        class="fa-regular fa-file-lines mr-1"></i> Baca Syarat &amp; Ketentuan</a>
            </div>

            @foreach ($packageGroups as $group => $groupPackages)
                <div id="panel-{{ $group }}" role="tabpanel" aria-labelledby="tab-{{ $group }}"
                    x-show="activeTab === '{{ $group }}'" x-cloak>
                    @forelse($groupPackages->groupBy(fn ($package) => $package->packageType?->name ?? 'Lainnya')->sortKeys() as $typeName => $typePackages)
                        <div class="mb-12">
                            <div class="mb-6 flex items-center justify-center gap-4">
                                <span class="h-px flex-1 bg-brand-200"></span>
                                <h2 class="font-display px-3 text-center text-xl font-bold text-brand-dark">
                                    {{ $typeName }}</h2>
                                <span class="h-px flex-1 bg-brand-200"></span>
                            </div>
                            @foreach ($typePackages->sortBy('price')->groupBy('sub_type') as $subType => $items)
                                @if ($subType)
                                    <h3 class="mb-4 text-center text-sm font-semibold tracking-wide text-gray-600">
                                        {{ $subTypeLabels[$subType] ?? ucfirst(str_replace('_', ' ', $subType)) }}</h3>
                                @endif
                                <div class="mb-8 flex flex-wrap justify-center gap-5">
                                    @foreach ($items as $package)
                                        <div
                                            class="card-mua text-center p-5 flex flex-col w-full sm:w-[calc(50%-0.625rem)] lg:w-[calc(25%-0.9375rem)]">
                                            <div class="mx-auto mb-3 rounded-full flex items-center justify-center"
                                                style="width:64px; height:64px; background:{{ $package->color }}18;"><i
                                                    class="fa-solid fa-gem"
                                                    style="font-size:1.5rem; color:{{ $package->color }};"></i></div>
                                            <h5 class="font-bold mb-1">{{ $package->name }}</h5>
                                            <div class="my-3 text-rose">
                                                @if ($package->original_price && $package->original_price > $package->price)
                                                    <div
                                                        class="mb-1 text-base font-semibold text-gray-600 line-through decoration-1 decoration-gray-600">
                                                        Rp {{ number_format($package->original_price, 0, ',', '.') }}</div>
                                                @endif
                                                <div class="font-display text-2xl font-bold">Rp
                                                    {{ number_format($package->price, 0, ',', '.') }}</div>
                                            </div>
                                            <div class="text-left text-sm mb-4 border-t border-gray-100 pt-3 max-h-48 overflow-y-auto pe-2"
                                                tabindex="0" role="region"
                                                aria-label="Benefit paket {{ $package->name }}">
                                                @foreach ($package->benefits->groupBy(fn($benefit) => $benefit->category->name ?? 'Umum') as $category => $benefits)
                                                    <p class="font-bold mb-1.5 mt-3 first:mt-0"
                                                        style="color:var(--primary-dark); font-size:.68rem; text-transform:uppercase; letter-spacing:1px;">
                                                        {{ $category }}</p>
                                                    <ul class="list-none mb-1">
                                                        @foreach ($benefits as $benefit)
                                                            <li class="mb-1.5 flex items-start gap-2"><i
                                                                    class="fa-solid fa-circle-check text-green-600 mt-1"></i><span>{{ $benefit->name }}</span>
                                                            </li>
                                                        @endforeach
                                                    </ul>
                                                @endforeach
                                            </div>
                                            <a href="{{ route('booking.create') }}?package={{ $package->id }}"
                                                class="btn-pink w-full mt-auto">Booking Paket Ini</a>
                                        </div>
                                    @endforeach
                                </div>
                            @endforeach
                        </div>
                    @empty
                        <div
                            class="mx-auto max-w-xl rounded-2xl border border-brand-100 bg-white px-6 py-12 text-center shadow-sm">
                            <i class="fa-regular fa-calendar-check mb-3 text-3xl text-brand"></i>
                            <h2 class="font-display mb-2 text-xl font-bold">Belum ada paket
                                {{ $group === 'regular' ? 'reguler' : 'wedding' }} aktif</h2>
                            <p class="text-sm text-gray-600">Silakan hubungi kami untuk menanyakan pilihan yang sesuai
                                dengan acara Anda.</p>
                        </div>
                    @endforelse
                </div>
            @endforeach

            <div class="text-center mt-6"><a href="{{ route('booking.create') }}" class="btn-pink px-6 py-2.5 text-base"><i
                        class="fa-regular fa-calendar-check mr-2"></i>Booking Sekarang</a></div>
        </div>
    </section>
@endsection
