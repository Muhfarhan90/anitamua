<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Anita MUA') — {{ $settings['company_name'] ?? 'Anita MUA' }}</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    @vite('resources/css/app.css')
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    @stack('styles')
</head>
<body>

<nav class="nav-landing">
    <div class="container nav-inner">
        <a class="brand-logo" href="{{ route('home') }}">
            ANITA<small>Make Up Artist</small>
        </a>

        <button class="nav-hamburger" type="button" x-data @click="$el.closest('.nav-inner').classList.toggle('nav-open')" aria-label="Toggle menu">
            <i class="fas fa-bars"></i>
        </button>

        <div class="nav-menu">
            <a class="nav-menu-link {{ request()->routeIs('home')?'active':'' }}" href="{{ route('home') }}">Home</a>
            <a class="nav-menu-link {{ request()->routeIs('about')?'active':'' }}" href="{{ route('about') }}">Tentang Anita MUA</a>
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

<a href="{{ route('booking.create') }}" class="btn-pink btn-float" id="floatBookingBtn"><i class="fas fa-calendar-check mr-1"></i> Booking</a>
<script>if(window.innerWidth>=992)document.getElementById('floatBookingBtn').style.display='none';</script>

<a href="https://wa.me/{{ $settings['whatsapp'] ?? '' }}?text=Halo%20{{ $settings['company_name'] ?? 'ANITA MUA' }}%2C%20saya%20ingin%20bertanya%20tentang%20paket%20dan%20booking."
   class="wa-float" target="_blank" rel="noopener" aria-label="Chat WhatsApp">
    <i class="fa-brands fa-whatsapp"></i>
</a>
@stack('scripts')
</body>
</html>
