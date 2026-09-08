@extends('layouts.landing')

@section('title', 'Galeri')

@section('content')
<section class="page-header" style="background: linear-gradient(rgba(212,115,154,.8), rgba(184,92,133,.8)), url('{{ $landingImages['hero'] }}') center/cover no-repeat; padding: 64px 0; text-align:center; color:#fff;">
    <div class="section-container" style="max-width:820px; margin:0 auto; padding:0 3rem;">
        <p class="section-eyebrow mb-2" style="color:#fde8ef;">Galeri</p>
        <h1 class="font-display font-bold mb-3" style="font-size:2.8rem;">Hasil Karya Kami</h1>
        <p style="opacity:.9;">Momen-momen indah dari klien yang telah kami rias.</p>
    </div>
</section>

<section class="py-16 landing-section" style="background:var(--bg);">
    <div class="section-container">
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3">
            @forelse($galleries as $gallery)
                <x-gallery-slider :gallery="$gallery" :show-category="true" />
            @empty
                <div class="col-span-full text-center py-12" style="color:var(--muted);">Belum ada galeri.</div>
            @endforelse
        </div>
        <div class="mt-4">{{ $galleries->links() }}</div>
    </div>
</section>
@endsection

@push('scripts')
<x-gallery-lightbox />
@endpush
