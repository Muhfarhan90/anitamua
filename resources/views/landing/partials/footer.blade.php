<footer class="site-footer pt-12 pb-4 mt-0">
    <div class="container">
        <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-4 items-start">
            <div class="">
                <a href="{{ route('home') }}" class="block mb-3">
                    @if(!empty($settings['logo']))
                        <img src="{{ asset('storage/'.$settings['logo']) }}" alt="{{ $settings['company_name'] ?? 'ANITA MUA' }}" style="height:48px; width:auto; display:block;">
                    @else
                        <span class="font-display font-bold text-xl leading-none" style="color:var(--primary); letter-spacing:1.5px;">ANITA</span>
                        <span class="block" style="font-size:.58rem; letter-spacing:4px; text-transform:uppercase; color:var(--muted); font-weight:600; margin-top:4px; line-height:1;">Make Up Artist</span>
                    @endif
                </a>
                <p style="color:var(--muted); font-size:.88rem; line-height:1.7;">
                    {{ $settings['tagline'] ?? 'Makeup profesional untuk hari spesial Anda. Elegan, flawless, dan unforgettable.' }}
                </p>
            </div>

            <div class="">
                <h6 class="footer-heading mb-3">Menu</h6>
                <div class="grid grid-cols-2 gap-2">
                    <div class="flex flex-col gap-2">
                        <a href="{{ route('home') }}">Home</a>
                        <a href="{{ route('about') }}">Tentang</a>
                        <a href="{{ route('packages') }}">Paket</a>
                        <a href="{{ route('gallery') }}">Galeri</a>
                    </div>
                    <div class="flex flex-col gap-2">
                        <a href="{{ route('testimonials') }}">Testimoni</a>
                        <a href="{{ route('faq') }}">FAQ</a>
                        <a href="{{ route('contact') }}">Kontak</a>
                        <a href="{{ route('booking.create') }}">Booking</a>
                    </div>
                </div>
            </div>

            <div class="">
                <h6 class="footer-heading mb-3">Kontak</h6>
                <div class="flex flex-col gap-2" style="color:var(--muted); font-size:.78rem; line-height:1.5;">
                    @if($settings['phone'] ?? null)
                        <span class="flex items-start"><i class="fa-solid fa-phone mr-2 text-rose"></i>{{ $settings['phone'] }}</span>
                    @endif
                    @if($settings['email'] ?? null)
                        <span class="flex items-start"><i class="fa-solid fa-envelope mr-2 text-rose"></i><span style="word-break:break-word;">{{ $settings['email'] }}</span></span>
                    @endif
                    @if($settings['address'] ?? null)
                        <span class="flex items-start"><i class="fa-solid fa-location-dot mr-2 text-rose"></i><span style="word-break:break-word;">{{ $settings['address'] }}</span></span>
                    @endif
                </div>
            </div>

            <div class="">
                <h6 class="footer-heading mb-3">Ikuti Kami</h6>
                <div class="flex gap-2">
                    <a href="https://wa.me/{{ $settings['whatsapp'] ?? '' }}" target="_blank" rel="noopener"
                       class="flex items-center justify-center rounded-full"
                       style="width:40px; height:40px; background:var(--primary-light); color:var(--primary); transition:all .25s;"
                       onmouseover="this.style.background='var(--primary)'; this.style.color='#fff';"
                       onmouseout="this.style.background='var(--primary-light)'; this.style.color='var(--primary)';">
                        <i class="fa-brands fa-whatsapp"></i>
                    </a>
                    <a href="https://instagram.com/{{ $settings['instagram'] ?? '' }}" target="_blank" rel="noopener"
                       class="flex items-center justify-center rounded-full"
                       style="width:40px; height:40px; background:var(--primary-light); color:var(--primary); transition:all .25s;"
                       onmouseover="this.style.background='var(--primary)'; this.style.color='#fff';"
                       onmouseout="this.style.background='var(--primary-light)'; this.style.color='var(--primary)';">
                        <i class="fa-brands fa-instagram"></i>
                    </a>
                    <a href="mailto:{{ $settings['email'] ?? '' }}"
                       class="flex items-center justify-center rounded-full"
                       style="width:40px; height:40px; background:var(--primary-light); color:var(--primary); transition:all .25s;"
                       onmouseover="this.style.background='var(--primary)'; this.style.color='#fff';"
                       onmouseout="this.style.background='var(--primary-light)'; this.style.color='var(--primary)';">
                        <i class="fa-solid fa-envelope"></i>
                    </a>
                </div>
            </div>
        </div>
        <hr style="border-color:rgba(45,37,33,.08); margin:1.5rem 0;">
        <div class="flex flex-col md:flex-row justify-between items-center gap-2">
            <p class="mb-0 text-sm" style="color:var(--muted);">&copy; {{ date('Y') }} {{ $settings['company_name'] ?? 'Anita MUA' }}. Semua hak dilindungi.</p>
            <p class="mb-0 text-sm" style="color:var(--muted);">Designed with <span class="text-rose">&#10084;</span> for your special moments</p>
        </div>
    </div>
</footer>
