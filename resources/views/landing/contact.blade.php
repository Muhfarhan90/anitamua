@extends('layouts.landing')

@section('title', 'Kontak')

@section('content')
<section class="page-header" style="background: linear-gradient(rgba(212,115,154,.8), rgba(184,92,133,.8)), url('https://images.unsplash.com/photo-1519741497674-611481863552?q=80&w=1920&auto=format&fit=crop') center/cover no-repeat; padding: 64px 0; text-align:center; color:#fff;">
    <div class="section-container" style="max-width:820px; margin:0 auto; padding:0 3rem;">
        <p class="section-eyebrow mb-2" style="color:#fde8ef;">Kontak</p>
        <h1 class="font-display font-bold mb-3" style="font-size:2.8rem;">Hubungi Kami</h1>
        <p style="opacity:.9;">Kami siap membantu mewujudkan hari bahagia Anda.</p>
    </div>
</section>

<section class="py-16 landing-section" style="background:var(--bg);">
    <div class="section-container">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 justify-items-center">
            <div class="card-mua h-full w-full text-center">
                <div class="p-4">
                    <div class="text-3xl text-rose mb-2"><i class="fas fa-location-dot"></i></div>
                    <h6 class="font-bold">Alamat</h6>
                    <small style="color:var(--muted);">{{ $settings['address'] ?? '-' }}</small>
                </div>
            </div>
            <div class="card-mua h-full w-full text-center">
                <div class="p-4">
                    <div class="text-3xl text-rose mb-2"><i class="fas fa-phone"></i></div>
                    <h6 class="font-bold">Telepon / WhatsApp</h6>
                    <small style="color:var(--muted);">{{ $settings['phone'] ?? '-' }}</small>
                </div>
            </div>
            <div class="card-mua h-full w-full text-center">
                <div class="p-4">
                    <div class="text-3xl text-rose mb-2"><i class="fas fa-envelope"></i></div>
                    <h6 class="font-bold">Email</h6>
                    <small style="color:var(--muted);">{{ $settings['email'] ?? '-' }}</small>
                </div>
            </div>
        </div>

        <div class="text-center mt-12">
            <a href="{{ route('booking.create') }}" class="btn-pink py-3 px-8 text-base">
                <i class="fas fa-calendar-check mr-2"></i>Booking Sekarang
            </a>
        </div>
    </div>
</section>
@endsection
