@extends('layouts.landing')

@section('title', 'Syarat & Ketentuan')
@section('content')
<section class="page-header" style="background: linear-gradient(rgba(212,115,154,.8), rgba(184,92,133,.8)), url('{{ $landingImages['hero'] }}') center/cover no-repeat; padding: 64px 0; text-align:center; color:#fff;">
    <div class="section-container" style="max-width:820px; margin:0 auto; padding:0 3rem;">
        <p class="section-eyebrow mb-2" style="color:#fde8ef;">Informasi Paket</p>
        <h1 class="font-display font-bold mb-3" style="font-size:2.8rem;">Syarat &amp; Ketentuan</h1>
        <p style="opacity:.9;">Informasi yang perlu Anda ketahui sebelum memilih paket Anita MUA.</p>
    </div>
</section>

<section class="landing-section py-16" style="background:var(--bg);">
    <div class="container">
        <div class="mx-auto max-w-5xl rounded-2xl border border-brand-100 bg-white px-6 py-8 shadow-sm sm:px-10 sm:py-10">
            @if(trim(strip_tags($terms ?? '')) !== '')
                <div class="rich-text-content">{!! $terms !!}</div>
            @else
                <p class="text-center text-gray-600">Syarat dan ketentuan belum tersedia.</p>
            @endif
        </div>
        <div class="mt-6 text-center"><a href="{{ route('packages') }}" class="btn-outline-pink inline-flex items-center gap-2 px-5 py-2.5"><i class="fa-solid fa-arrow-left"></i> Kembali ke Paket</a></div>
    </div>
</section>
@endsection
