@extends('layouts.landing')

@section('title', 'FAQ')

@section('content')
<section class="page-header" style="background: linear-gradient(rgba(212,115,154,.8), rgba(184,92,133,.8)), url('https://images.unsplash.com/photo-1519741497674-611481863552?q=80&w=1920&auto=format&fit=crop') center/cover no-repeat; padding: 64px 0; text-align:center; color:#fff;">
    <div class="section-container" style="max-width:820px; margin:0 auto; padding:0 3rem;">
        <p class="section-eyebrow mb-2" style="color:#fde8ef;">FAQ</p>
        <h1 class="font-display font-bold mb-3" style="font-size:2.8rem;">Pertanyaan Umum</h1>
        <p style="opacity:.9;">Temukan jawaban untuk pertanyaan yang sering diajukan</p>
    </div>
</section>

<section class="py-16 landing-section" style="background:var(--bg);">
    <div class="section-container" style="max-width:820px;">
        <div class="faq-custom" id="faqList" style="border-radius:16px;">
            @forelse($faqs as $i => $faq)
                <div class="faq-item{{ $i === 0 ? ' open' : '' }}" style="border:1px solid rgba(45,37,33,.06); border-radius:12px; margin-bottom:8px; background:#fff;">
                    <button class="faq-q" onclick="toggleFaq(this)" style="width:100%; display:flex; justify-content:space-between; align-items:center; padding:18px 24px; background:none; border:none; cursor:pointer; font-weight:600; font-size:.95rem; color:var(--text);">
                        {{ $faq->question }}
                        <span class="faq-chevron" style="transition:transform .2s; font-size:1.1rem; color:var(--primary);">&#9660;</span>
                    </button>
                    <div class="faq-a" style="max-height:0; overflow:hidden; transition:max-height .3s ease; padding:0 24px;">
                        <p style="color:var(--muted); padding-bottom:18px; line-height:1.7; font-size:.9rem;">{{ $faq->answer }}</p>
                    </div>
                </div>
            @empty
                <div class="text-center py-12" style="color:var(--muted);">Belum ada FAQ.</div>
            @endforelse
        </div>

        <div class="text-center mt-12">
            <p style="color:var(--muted);">Masih ada pertanyaan? Hubungi kami</p>
            <a href="{{ route('contact') }}" class="btn-pink px-4">Hubungi Kami</a>
        </div>
    </div>
</section>
@endsection

@push('scripts')
<script>
    function toggleFaq(btn) {
        const item = btn.parentElement;
        const answer = item.querySelector('.faq-a');
        const chevron = item.querySelector('.faq-chevron');
        const isOpen = item.classList.contains('open');

        document.querySelectorAll('#faqList .faq-item').forEach(function (it) {
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
