@extends('layouts.landing')

@section('title', 'Testimoni')

@section('content')
<section class="page-header" style="background: linear-gradient(rgba(212,115,154,.8), rgba(184,92,133,.8)), url('{{ $landingImages['hero'] }}') center/cover no-repeat; padding: 64px 0; text-align:center; color:#fff;">
    <div class="section-container" style="max-width:820px; margin:0 auto; padding:0 3rem;">
        <p class="section-eyebrow mb-2" style="color:#fde8ef;">Testimoni</p>
        <h1 class="font-display font-bold mb-3" style="font-size:2.8rem;">Apa Kata Mereka?</h1>
        <p style="opacity:.9;">Kepercayaan klien adalah kebanggaan kami.</p>
    </div>
</section>

<section class="py-16 landing-section" style="background:var(--bg);">
    <div class="section-container">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            @forelse($testimonials as $t)
                <div class="card-mua h-full">
                    <div class="p-4 flex flex-col h-full">
                        <div class="text-rose mb-3" style="font-size:1.05rem;">
                            @for($i = 0; $i < $t->rating; $i++)<i class="fas fa-star"></i>@endfor
                        </div>
                        <p class="mb-4 flex-1" style="color:var(--muted); line-height:1.75; font-style:italic;">"{{ $t->content }}"</p>
                        <div class="flex items-center gap-3 pt-3" style="border-top:1px solid rgba(45,37,33,.06);">
                            <div class="text-white rounded-full flex items-center justify-center font-bold"
                                 style="width:42px; height:42px; background:var(--primary); flex-shrink:0;">
                                {{ strtoupper(substr($t->client_name, 0, 1)) }}
                            </div>
                            <div>
                                <div class="font-semibold text-sm">{{ $t->client_name }}</div>
                    <small style="color:var(--muted); font-size:.68rem; letter-spacing:1.5px; text-transform:uppercase;">Client ANITA</small>
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-span-full text-center py-12" style="color:var(--muted);">Belum ada testimoni.</div>
            @endforelse
        </div>
        <div class="mt-4">{{ $testimonials->links() }}</div>
    </div>
</section>
@endsection
