@extends('layouts.landing')

@section('title', 'Galeri')

@section('content')
<section class="page-header" style="background: linear-gradient(rgba(212,115,154,.8), rgba(184,92,133,.8)), url('https://images.unsplash.com/photo-1519741497674-611481863552?q=80&w=1920&auto=format&fit=crop') center/cover no-repeat; padding: 64px 0; text-align:center; color:#fff;">
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
                <div class="relative overflow-hidden rounded-2xl shadow-sm" style="height:280px; cursor:pointer;" onclick="openLightbox(this)">
                    <img src="{{ $gallery->image_url }}"
                         class="w-full h-full"
                         style="object-fit:cover; transition:transform .4s ease;"
                         alt="{{ $gallery->title }}">
                    <div class="absolute bottom-0 left-0 w-full p-3 text-white" style="background:linear-gradient(transparent, rgba(0,0,0,.65));">
                        <small class="font-semibold">{{ $gallery->title }}</small><br>
                        <small style="font-size:.65rem; text-transform:uppercase; letter-spacing:1px; opacity:.8;">{{ $gallery->category }}</small>
                    </div>
                </div>
            @empty
                <div class="col-span-full text-center py-12" style="color:var(--muted);">Belum ada galeri.</div>
            @endforelse
        </div>
        <div class="mt-4">{{ $galleries->links() }}</div>
    </div>
</section>
@endsection

@push('scripts')
<div id="lightboxModal" onclick="closeLightbox()" style="display:none; position:fixed; inset:0; z-index:1100; background:rgba(0,0,0,.9); align-items:center; justify-content:center; padding:2rem;">
    <button onclick="event.stopPropagation();closeLightbox()" style="position:absolute; top:20px; right:30px; background:none; border:none; color:#fff; font-size:2.5rem; cursor:pointer; line-height:1;">&times;</button>
    <div onclick="event.stopPropagation()" style="max-width:900px; width:100%; text-align:center;">
        <img id="lightboxImg" src="" alt="" style="max-width:100%; max-height:78vh; border-radius:12px; display:block; margin:0 auto; object-fit:contain;">
        <p id="lightboxCaption" class="mt-3" style="color:#fff; font-size:1rem;"></p>
    </div>
</div>
<script>
    function openLightbox(el) {
        const img = el.querySelector('img');
        const caption = el.querySelector('small');
        document.getElementById('lightboxImg').src = img.src;
        document.getElementById('lightboxImg').alt = img.alt;
        document.getElementById('lightboxCaption').textContent = caption ? caption.textContent : '';
        const modal = document.getElementById('lightboxModal');
        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }
    function closeLightbox() {
        document.getElementById('lightboxModal').style.display = 'none';
        document.body.style.overflow = '';
    }
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') closeLightbox();
    });
</script>
@endpush
