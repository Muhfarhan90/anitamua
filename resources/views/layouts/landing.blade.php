<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Anita MUA') — {{ $settings['company_name'] ?? 'Anita MUA' }}</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    @vite('resources/css/app.css')
    @vite('resources/js/app.js')
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    @stack('styles')
</head>
<body>

<nav class="nav-landing">
    <div class="container nav-inner">
        <a class="brand-logo" href="{{ route('home') }}">
            @if(!empty($settings['logo']))
                <img src="{{ asset('storage/'.$settings['logo']) }}" alt="{{ $settings['company_name'] ?? 'ANITA MUA' }}" style="height:48px; width:auto; display:block;">
            @else
                ANITA<small>Make Up Artist</small>
            @endif
        </a>

        <button class="nav-hamburger" type="button" x-data @click="$el.closest('.nav-inner').classList.toggle('nav-open')" aria-label="Toggle menu">
            <i class="fas fa-bars"></i>
        </button>

        <div class="nav-menu">
            <a class="nav-menu-link {{ request()->routeIs('home')?'active':'' }}" href="{{ route('home') }}">Home</a>
            <a class="nav-menu-link {{ request()->routeIs('about')?'active':'' }}" href="{{ route('about') }}">Tentang</a>
            <a class="nav-menu-link {{ request()->routeIs('packages')?'active':'' }}" href="{{ route('packages') }}">Paket</a>
            <a class="nav-menu-link {{ request()->routeIs('gallery')?'active':'' }}" href="{{ route('gallery') }}">Galeri</a>
            <a class="nav-menu-link {{ request()->routeIs('testimonials')?'active':'' }}" href="{{ route('testimonials') }}">Testimoni</a>
            <a class="nav-menu-link {{ request()->routeIs('faq')?'active':'' }}" href="{{ route('faq') }}">FAQ</a>
            <a class="nav-menu-link {{ request()->routeIs('contact')?'active':'' }}" href="{{ route('contact') }}">Kontak</a>
        </div>

        <div class="nav-actions">
            @auth
                <a href="{{ route('dashboard') }}" class="btn-outline-pink"><i class="fas fa-user-circle mr-1"></i> Dashboard</a>
            @else
                <a href="{{ route('login') }}" class="btn-outline-pink"><i class="fas fa-user mr-1"></i> Login</a>
            @endauth
            <a href="{{ route('booking.create') }}" class="btn-pink" style="display:none" id="navBookingBtn"><i class="fas fa-calendar-check mr-1"></i> Booking Sekarang</a>
            <script>if(window.innerWidth>=992)document.getElementById('navBookingBtn').style.display='';</script>
        </div>
    </div>
</nav>

@yield('content')
@include('landing.partials.footer')

{{-- ══ PROMO BANNER POPUP (full gambar + tombol silang) ══ --}}
@php $promo = \App\Models\PromoBanner::active()->whereNotNull('image')->latest()->first(); @endphp
@if($promo)
<div id="promoModal" class="hidden fixed inset-0 z-[1200] bg-black/70 flex items-center justify-center p-4">
    <div class="relative w-full max-w-3xl">
        <button type="button" onclick="closePromo()" aria-label="Tutup"
                class="absolute -top-3 -right-3 z-10 w-10 h-10 rounded-full bg-white shadow-lg flex items-center justify-center text-gray-600 hover:text-gray-900 hover:bg-gray-100 transition-colors cursor-pointer">
            <i class="fas fa-xmark text-lg"></i>
        </button>
        <img src="{{ asset('storage/'.$promo->image) }}" alt="Promo" class="w-full h-auto rounded-2xl shadow-2xl">
    </div>
</div>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const promoKey = 'promo_dismissed_{{ $promo->id }}';
        const modal = document.getElementById('promoModal');

        window.closePromo = function () {
            modal.classList.add('hidden');
            document.body.style.overflow = '';
            try { sessionStorage.setItem(promoKey, '1'); } catch (e) {}
        };

        if (!sessionStorage.getItem(promoKey)) {
            modal.classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        }

        modal.addEventListener('click', function (e) {
            if (e.target === this) closePromo();
        });
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') closePromo();
        });
    });
</script>
@endif

<a href="{{ route('booking.create') }}" class="btn-pink btn-float" id="floatBookingBtn"><i class="fas fa-calendar-check mr-1"></i> Booking</a>
<script>if(window.innerWidth>=992)document.getElementById('floatBookingBtn').style.display='none';</script>

<a href="https://wa.me/{{ $settings['whatsapp'] ?? '' }}?text=Halo%20{{ $settings['company_name'] ?? 'ANITA MUA' }}%2C%20saya%20ingin%20bertanya%20tentang%20paket%20dan%20booking."
   class="wa-float" target="_blank" rel="noopener" aria-label="Chat WhatsApp">
    <i class="fa-brands fa-whatsapp"></i>
</a>
<script>
    const formatMoney = (input) => {
        const digits = input.value.replace(/[,.]\d{1,2}$/, '').replace(/\D/g, '');
        input.value = digits.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    };

    document.querySelectorAll('[data-money-input]').forEach(formatMoney);
    document.addEventListener('input', (event) => {
        if (event.target.matches('[data-money-input]')) formatMoney(event.target);
    });
    document.addEventListener('submit', (event) => {
        event.target.querySelectorAll?.('[data-money-input]').forEach(input => {
            input.value = input.value.replaceAll('.', '');
        });
    });
</script>
@stack('scripts')
</body>
</html>
