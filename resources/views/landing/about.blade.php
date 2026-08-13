@extends('layouts.landing')

@section('title', 'Tentang Anita MUA')

@section('content')
<section class="page-header" style="background: linear-gradient(rgba(212,115,154,.8), rgba(184,92,133,.8)), url('https://images.unsplash.com/photo-1519741497674-611481863552?q=80&w=1920&auto=format&fit=crop') center/cover no-repeat; padding: 64px 0; text-align:center; color:#fff;">
    <div class="section-container" style="max-width:820px; margin:0 auto; padding:0 3rem;">
        <p class="section-eyebrow mb-2" style="color:#fde8ef;">Tentang Kami</p>
        <h1 class="font-display font-bold mb-3" style="font-size:2.8rem;">Kenalan dengan ANITA</h1>
        <p style="opacity:.9;">Make Up Artist profesional untuk hari spesial Anda.</p>
    </div>
</section>

<section class="py-16 landing-section" style="background:#fff;">
    <div class="container">
        <div class="grid gap-5 lg:grid-cols-2 items-center">
            <div>
                <img src="https://images.unsplash.com/photo-1583939003579-730e3918a45a?q=80&w=900&auto=format&fit=crop" class="w-full" alt="Anita MUA" style="height:420px; object-fit:cover; border-radius:24px; box-shadow:0 20px 40px rgba(0,0,0,.08);">
            </div>
            <div>
                <p class="section-eyebrow mb-2">Tentang Kami</p>
                <h2 class="font-display font-bold section-heading mb-3">Seni Rias yang Membuat Anda Bersinar</h2>
                <p style="color:var(--muted); line-height:1.75;">{{ $settings['about'] ?? 'Anita MUA adalah penyedia jasa rias pengantin profesional.' }}</p>
            </div>
        </div>
    </div>
</section>
@endsection
