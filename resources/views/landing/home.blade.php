@extends('layouts.landing')

@section('title', 'Home')

@section('content')
{{-- ══════════ HERO ══════════ --}}
<section class="relative overflow-hidden flex items-center" style="background: linear-gradient(135deg, #faf6f2 0%, #fdf2f6 50%, #fce8ef 100%); min-height: 92vh;">
    <div class="section-container" style="padding: 5rem 3rem 4rem; max-width: 1280px; margin: 0 auto; width:100%;">
        <div class="grid gap-5 lg:grid-cols-2 items-center">
            <div class="relative" style="z-index:2;">
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full mb-4" style="background:rgba(212,115,154,.1); border:1px solid rgba(212,115,154,.2);">
                    <i class="fas fa-gem text-rose" style="font-size:.85rem;"></i>
                    <span class="font-semibold" style="color:var(--primary); font-size:.72rem; letter-spacing:2.5px; text-transform:uppercase;">Make Up Artist & Wedding Stylist</span>
                </div>
                <h1 class="font-display font-bold mb-4" style="color:var(--text); font-size: clamp(2.2rem, 4.5vw, 3.3rem); line-height:1.15;">
                    Make Your Dream<br>Wedding <span class="text-rose">Come True</span>
                </h1>
                <p class="mb-4" style="color:var(--muted); max-width:460px; font-size:1rem; line-height:1.75;">
                    Riasan pengantin terbaik, kelola semua kebutuhan pernikahan Anda dalam satu pintu — mulai dari make up, vendor, hingga jadwal acara.
                </p>
                <div class="flex gap-3 flex-wrap mb-5">
                    <a href="{{ route('booking.create') }}" class="btn-pink px-5 py-3 text-base">
                        <i class="fas fa-calendar-check mr-2"></i>Booking Sekarang
                    </a>
                    <a href="#paket" class="btn-outline-pink px-5 py-3 text-base">Lihat Paket</a>
                </div>
                <div class="flex flex-wrap gap-2 mt-2">
                    @php
                        $badges = [
                            ['icon' => 'fa-solid fa-star', 'label' => 'Rating 5.0'],
                            ['icon' => 'fa-solid fa-award', 'label' => 'Berpengalaman'],
                            ['icon' => 'fa-solid fa-palette', 'label' => 'Make Up Profesional'],
                            ['icon' => 'fa-solid fa-shield-check', 'label' => 'Terpercaya'],
                        ];
                    @endphp
                    @foreach($badges as $badge)
                    <span class="inline-flex items-center gap-1.5 rounded-full px-3 py-2" style="background:rgba(212,115,154,.1); color:var(--primary-dark); font-weight:600; font-size:.78rem; border:1px solid rgba(212,115,154,.25);">
                        <i class="{{ $badge['icon'] }}" style="font-size:.7rem;"></i> {{ $badge['label'] }}
                    </span>
                    @endforeach
                </div>
            </div>
            <div class="hidden lg:block relative">
                <div style="border-radius: 32px; overflow:hidden; box-shadow: 0 30px 60px rgba(212,115,154,.2); border: 4px solid rgba(255,255,255,.9);">
                    <img src="{{ $landingImages['hero'] }}"
                         alt="Wedding" style="width:100%; height:520px; object-fit:cover; display:block;">
                </div>
                <div class="absolute" style="top:40px; right:-20px; width:80px; height:80px; background:var(--primary); border-radius:50%; opacity:.15;"></div>
                <div class="absolute" style="bottom:30px; left:-15px; width:60px; height:60px; background:var(--primary); border-radius:50%; opacity:.1;"></div>
            </div>
        </div>
    </div>
</section>

{{-- ══════════ TENTANG ══════════ --}}
<section class="py-16 landing-section" style="background:#fff;" id="tentang">
    <div class="container">
        <div class="grid gap-5 lg:grid-cols-2 items-center">
            <div class="">
                <img src="{{ $landingImages['about'] }}" class="w-full" alt="Anita MUA" style="height:420px; object-fit:cover; border-radius:24px; box-shadow:0 20px 40px rgba(0,0,0,.08);">
            </div>
            <div class="">
                <p class="section-eyebrow mb-2">Tentang Kami</p>
                <h2 class="font-display font-bold section-heading mb-3">Seni Rias yang Membuat Anda Bersinar</h2>
                <p style="color:var(--muted); line-height:1.75;">{{ $settings['about'] ?? 'Anita MUA adalah penyedia jasa rias pengantin profesional.' }}</p>
                <a href="{{ route('about') }}" class="btn btn-outline-pink mt-4 px-4">Selengkapnya</a>
            </div>
        </div>
    </div>
</section>

{{-- ══════════ PAKET + PRICELIST ══════════ --}}
<section class="py-16 landing-section" style="background:var(--bg);" id="paket">
    <div class="container">
        <div class="text-center mx-auto mb-8" style="max-width:560px;">
            <p class="section-eyebrow mb-2">Paket</p>
            <h2 class="font-display font-bold section-heading">Pilih Paket Sesuai Impian Anda</h2>
            <hr class="rose-rule mx-auto mt-3">
            <p class="mt-3 mb-0" style="color:var(--muted);">Setiap paket sudah termasuk benefit lengkap untuk hari spesial Anda.</p>
        </div>

        @php
            $makeupPkgs = $packages->where('type', 'makeup')->sortBy('price');
            $weddingPkgs = $packages->where('type', 'full')->sortBy('price');
        @endphp

        <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-4 items-stretch mb-8">
            @foreach($makeupPkgs->take(4) as $package)
                <div class="card-mua h-full text-center p-5 flex flex-col">
                    <div class="mx-auto mb-3 rounded-full flex items-center justify-center" style="width:60px; height:60px; background:{{ $package->color }}18;">
                        <i class="fas fa-wand-magic-sparkles" style="font-size:1.5rem; color:{{ $package->color }};"></i>
                    </div>
                    <h5 class="font-bold mb-1">{{ $package->name }}</h5>
                    <div class="font-display text-3xl font-bold my-3 text-rose">Rp {{ number_format($package->price, 0, ',', '.') }}</div>
                    <ul class="list-none text-left text-sm mt-2 mb-4" style="min-height:72px;">
                        @foreach($package->benefits->take(4) as $benefit)
                            <li class="mb-2 flex items-start gap-2">
                                <i class="fas fa-circle-check text-emerald-500 mt-1"></i>
                                <span>{{ $benefit->name }}</span>
                            </li>
                        @endforeach
                    </ul>
                    <a href="{{ route('packages') }}" class="btn-pink w-full mt-auto text-center">Lihat Detail Paket</a>
                </div>
            @endforeach
        </div>

        <div class="text-center">
            <a href="{{ route('packages') }}" class="btn-pink px-6 py-2.5 text-base">
                <i class="fa-regular fa-calendar-check mr-2"></i>Lihat Semua Paket (Makeup & Wedding)
            </a>
        </div>
    </div>
</section>

{{-- ══════════ CARA BOOKING ══════════ --}}
<section class="py-16 landing-section" style="background:#fff;">
    <div class="container">
        <div class="text-center mx-auto mb-5" style="max-width:560px;">
            <p class="section-eyebrow mb-2">Cara Booking</p>
            <h2 class="font-display font-bold section-heading">Mudah, Hanya 4 Langkah</h2>
            <hr class="rose-rule mx-auto mt-3">
        </div>
        @php
            $steps = [
                ['icon' => 'fa-solid fa-user-plus', 'title' => 'Pilih Paket', 'desc' => 'Tentukan paket yang sesuai dengan kebutuhan Anda'],
                ['icon' => 'fa-solid fa-file-lines', 'title' => 'Isi Form Booking', 'desc' => 'Lengkapi data acara, tanggal, dan lokasi'],
                ['icon' => 'fa-solid fa-credit-card', 'title' => 'Transfer DP1', 'desc' => 'Transfer DP untuk mengunci tanggal Anda'],
                ['icon' => 'fa-regular fa-calendar-check', 'title' => 'Booking Sah!', 'desc' => 'Admin verifikasi dan status berubah BOOKED'],
            ];
        @endphp
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            @foreach($steps as $i => $step)
                <div class="text-center">
                    <div class="relative inline-flex mb-3">
                        <div class="rounded-full flex items-center justify-center" style="width:72px; height:72px; background:var(--primary-light); color:var(--primary); font-size:1.5rem;">
                            <i class="{{ $step['icon'] }}"></i>
                        </div>
                        <span class="absolute top-0 left-full -translate-x-1/2 -translate-y-1/2 inline-flex items-center justify-center rounded-full w-6 h-6 text-white" style="background:var(--primary); font-size:.7rem;">{{ $i+1 }}</span>
                    </div>
                    <h6 class="font-bold">{{ $step['title'] }}</h6>
                    <small style="color:var(--muted);">{{ $step['desc'] }}</small>
                </div>
            @endforeach
        </div>
        <div class="rounded-2xl p-4 mt-4 mx-auto flex items-center gap-3" style="max-width:720px; background:var(--primary-light); color:var(--primary-dark);">
            <i class="fas fa-circle-info text-lg flex-shrink-0"></i>
            <div class="text-sm">
                <strong>Alur pembayaran:</strong> DP1 saat booking → DP 25% saat fitting → DP 75% pada H-7 → Pelunasan pada H-1/H-2.
                Jika booking dibatalkan sepihak oleh client, DP dinyatakan hangus.
            </div>
        </div>
    </div>
</section>

{{-- ══════════ DEKOR & TENDA ══════════ --}}
<section class="py-16 landing-section" style="background:#fff;">
    <div class="container">
        <div class="flex flex-wrap items-end justify-between gap-3 mb-5">
            <div>
                <p class="section-eyebrow mb-2">Dekor & Tenda</p>
                <h2 class="font-display font-bold section-heading mb-0">Pilihan untuk Hari Istimewa</h2>
            </div>
            <a href="{{ route('decor-tents') }}" class="btn-outline-pink">Lihat Semua <i class="fas fa-arrow-right ml-1"></i></a>
        </div>
        <div class="grid grid-cols-2 gap-4 md:grid-cols-4">
            @foreach([
                ['items' => $weddingStages->take(2), 'label' => 'Pelaminan', 'icon' => 'fa-panorama'],
                ['items' => $entranceGates->take(2), 'label' => 'Gapura', 'icon' => 'fa-door-open'],
                ['items' => $tents->take(2), 'label' => 'Tenda', 'icon' => 'fa-campground'],
            ] as $catalogSection)
                @foreach($catalogSection['items'] as $item)
                    <article class="group text-center">
                        <div class="overflow-hidden rounded-t-[999px] rounded-b-2xl border border-pink-100 bg-pink-50 shadow-sm transition duration-300 group-hover:-translate-y-1 group-hover:shadow-lg">
                            @if($item->photo_path)
                                <button type="button" class="block w-full cursor-zoom-in" aria-label="Buka foto {{ $item->name }}" data-gallery-lightbox data-gallery-photos="{{ base64_encode(json_encode([asset('storage/'.$item->photo_path)])) }}" data-gallery-title="{{ $item->name }}">
                                    <img src="{{ asset('storage/'.$item->photo_path) }}" alt="{{ $item->name }}" class="aspect-[4/5] w-full object-cover transition duration-500 group-hover:scale-105" loading="lazy">
                                </button>
                            @else
                                <div class="flex aspect-[4/5] items-center justify-center text-3xl text-brand"><i class="fas {{ $catalogSection['icon'] }}"></i></div>
                            @endif
                        </div>
                        <p class="mt-3 text-xs font-semibold uppercase tracking-wide text-gray-500">{{ $catalogSection['label'] }}</p>
                        <h3 class="font-display text-base font-semibold text-gray-800">{{ $item->name }}</h3>
                    </article>
                @endforeach
            @endforeach
        </div>
    </div>
</section>

{{-- ══════════ GALERI ══════════ --}}
<section class="py-16 landing-section" style="background:var(--bg);">
    <div class="container">
        <div class="flex flex-wrap justify-between items-end gap-3 mb-5">
            <div>
                <p class="section-eyebrow mb-2">Galeri</p>
                <h2 class="font-display font-bold section-heading mb-0">Hasil Karya Kami</h2>
            </div>
            <a href="{{ route('gallery') }}" class="btn-outline-pink">Lihat Semua <i class="fas fa-arrow-right ml-1"></i></a>
        </div>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
            @foreach($galleries as $gallery)
                <x-gallery-slider :gallery="$gallery" />
            @endforeach
        </div>
    </div>
</section>

{{-- ══════════ TESTIMONI ══════════ --}}
<section class="py-16 landing-section" style="background:#fff;">
    <div class="container">
        <div class="text-center mx-auto mb-5" style="max-width:560px;">
            <p class="section-eyebrow mb-2">Testimoni</p>
            <h2 class="font-display font-bold section-heading">Kata Mereka Tentang Kami</h2>
            <hr class="rose-rule mx-auto mt-3">
        </div>
        <div class="grid gap-4 md:grid-cols-3">
            @foreach($testimonials->take(3) as $t)
                <div class="card-mua h-full p-4 flex flex-col">
                        <div class="text-rose mb-3" style="font-size:1.05rem;">
                            @for($i = 0; $i < $t->rating; $i++)<i class="fas fa-star"></i>@endfor
                        </div>
                        <p class="mb-4 flex-1" style="color:var(--muted); line-height:1.75; font-style:italic;">"{{ $t->content }}"</p>
                        <div class="flex items-center gap-3 pt-3" style="border-top:1px solid rgba(45,37,33,.06);">
                            <div class="text-white rounded-full flex items-center justify-center font-bold" style="width:42px; height:42px; background:var(--primary);">{{ strtoupper(substr($t->client_name, 0, 1)) }}</div>
                            <div>
                                <div class="font-semibold text-sm">{{ $t->client_name }}</div>
                                <small style="color:var(--muted); font-size:.68rem; letter-spacing:1.5px; text-transform:uppercase;">Client ANITA</small>
                            </div>
                        </div>
                    </div>
            @endforeach
        </div>
        <div class="text-center mt-4">
            <a href="{{ route('testimonials') }}" class="btn-outline-pink">Lihat Semua Testimoni</a>
        </div>
    </div>
</section>

{{-- ══════════ FAQ ══════════ --}}
<section class="py-16 landing-section" style="background:var(--bg);">
    <div class="container" style="max-width:820px;">
        <div class="text-center mx-auto mb-5" style="max-width:560px;">
            <p class="section-eyebrow mb-2">FAQ</p>
            <h2 class="font-display font-bold section-heading">Pertanyaan yang Sering Diajukan</h2>
            <hr class="rose-rule mx-auto mt-3">
        </div>
        <div class="faq-custom" id="faqHome" style="border-radius:16px;">
            @foreach($faqs->take(4) as $i => $faq)
                <div class="faq-item{{ $i === 0 ? ' open' : '' }}" style="border:1px solid rgba(45,37,33,.06); border-radius:12px; margin-bottom:8px; background:#fff;">
                    <button class="faq-q" onclick="toggleFaq(this)" style="width:100%; display:flex; justify-content:space-between; align-items:center; padding:18px 24px; background:none; border:none; cursor:pointer; font-weight:600; font-size:.95rem; color:var(--text);">
                        {{ $faq->question }}
                        <span class="faq-chevron" style="transition:transform .2s; font-size:1.1rem; color:var(--primary);">&#9660;</span>
                    </button>
                    <div class="faq-a" style="max-height:0; overflow:hidden; transition:max-height .3s ease; padding:0 24px;">
                        <p style="color:var(--muted); padding-bottom:18px; line-height:1.7; font-size:.9rem;">{{ $faq->answer }}</p>
                    </div>
                </div>
            @endforeach
        </div>
        <div class="text-center mt-4">
            <a href="{{ route('faq') }}" class="btn-outline-pink">Lihat Semua FAQ</a>
        </div>
    </div>
</section>

{{-- ══════════ KONTAK ══════════ --}}
<section class="py-16 landing-section" style="background:#fff;">
    <div class="container">
        <div class="text-center mx-auto mb-5" style="max-width:560px;">
            <p class="section-eyebrow mb-2">Kontak</p>
            <h2 class="font-display font-bold section-heading">Hubungi Kami</h2>
            <hr class="rose-rule mx-auto mt-3">
        </div>
        @php
            $contacts = [
                ['icon' => 'fa-solid fa-location-dot', 'title' => 'Alamat', 'value' => $settings['address'] ?? '-'],
                ['icon' => 'fa-solid fa-phone', 'title' => 'Telepon / WhatsApp', 'value' => $settings['phone'] ?? '-'],
                ['icon' => 'fa-solid fa-envelope', 'title' => 'Email', 'value' => $settings['email'] ?? '-'],
            ];
        @endphp
        <div class="grid gap-4 md:grid-cols-3">
            @foreach($contacts as $contact)
            <div class="card-mua h-full text-center p-4">
                    <div class="text-3xl text-rose mb-2"><i class="{{ $contact['icon'] }}"></i></div>
                    <h6 class="font-bold">{{ $contact['title'] }}</h6>
                    <small style="color:var(--muted);">{{ $contact['value'] }}</small>
            </div>
            @endforeach
        </div>
    </div>
</section>

{{-- ══════════ CTA ══════════ --}}
<section class="py-16 landing-section" style="background:var(--bg); text-align:center;">
    <div class="container" style="text-align:center;">
        <h2 class="font-display font-bold mb-2" style="color:var(--text); font-size:2rem; text-align:center;">Wujudkan Hari Bahagia Anda</h2>
        <p style="color:var(--muted); max-width:420px; margin:1.5rem auto 2rem; text-align:center;">Jadwal terbatas setiap bulannya. Amankan tanggal pernikahan Anda sekarang.</p>
        <a href="{{ route('booking.create') }}" class="btn-pink px-5 py-3 text-base" style="display:inline-block;">
            <i class="fas fa-calendar-check mr-2"></i>Booking Sekarang
        </a>
    </div>
</section>
@endsection

@push('scripts')
<x-gallery-lightbox />

<script>
    function toggleFaq(btn) {
        const item = btn.parentElement;
        const answer = item.querySelector('.faq-a');
        const chevron = item.querySelector('.faq-chevron');
        const isOpen = item.classList.contains('open');

        document.querySelectorAll('#faqHome .faq-item').forEach(function (it) {
            it.classList.remove('open');
            it.querySelector('.faq-a').style.maxHeight = '0px';
            if (it.querySelector('.faq-chevron')) it.querySelector('.faq-chevron').style.transform = 'rotate(0deg)';
        });

        if (!isOpen) {
            item.classList.add('open');
            answer.style.maxHeight = answer.scrollHeight + 'px';
            chevron.style.transform = 'rotate(180deg)';
        }
    }
</script>
@endpush
