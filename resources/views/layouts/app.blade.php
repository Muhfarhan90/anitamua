<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', config('app.name', 'Anita MUA'))</title>

    {{-- Fonts --}}
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    {{-- Font Awesome (single icon library) --}}
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">

    {{-- Compiled Tailwind CSS (lokal, tanpa CDN) --}}
    @vite('resources/css/app.css')
    @vite('resources/js/app.js')

    {{-- Alpine.js (replaces Bootstrap JS for interactivity) --}}
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <style>
        [x-cloak] { display: none !important; }

        /* Hapus border default & focus outline pada tombol
           (kecuali tombol yang punya class bg-*/border-* dari Tailwind —
           gaya global tanpa @layer mengalahkan utilities ber-layer) */
        button { outline: none; }
        button:focus { outline: none; box-shadow: none; }
        button:focus-visible { outline: 2px solid #d4739a; outline-offset: 2px; }
        button:not([class*="bg-"]) { background: none; }
        button:not([class*="border-"]) { border: none; }
    </style>

    @stack('styles')
</head>
<body>
    @php
        $user  = auth()->user();
        $role  = $user->role ?? 'client';
        $firstBooking = $role === 'client' ? $user->bookings()->latest()->first() : null;

        /* Icon map: Bootstrap Icon class → FA class */
        $iconMap = [
            'bi-grid-1x2-fill'   => 'fa-solid fa-grip',
            'bi-calendar-check'  => 'fa-solid fa-calendar-check',
            'bi-credit-card'     => 'fa-solid fa-credit-card',
            'bi-calendar2-week'  => 'fa-solid fa-calendar-week',
            'bi-box-seam'        => 'fa-solid fa-box',
        ];

        $sidebarMenus = [
            'owner' => [
                ['title' => 'Menu Utama', 'items' => [
                    ['label' => 'Dashboard',  'icon' => 'fa-solid fa-grip',           'url' => route('dashboard'),              'active' => 'dashboard'],
                    ['label' => 'Booking',    'icon' => 'fa-solid fa-calendar-check', 'url' => route('admin.bookings.index'),   'active' => 'admin.bookings.*'],
                    ['label' => 'Pembayaran', 'icon' => 'fa-solid fa-credit-card',     'url' => route('admin.payments.index'),   'active' => 'admin.payments.*'],
                    ['label' => 'Invoice',    'icon' => 'fa-solid fa-file-invoice',   'url' => route('admin.invoices.index'),   'active' => 'admin.invoices.*'],
                    ['label' => 'Jadwal',     'icon' => 'fa-solid fa-calendar-week',   'url' => route('admin.calendar'),         'active' => 'admin.calendar'],
                ]],
                ['title' => 'Laporan', 'items' => [
                    ['label' => 'Keuangan',   'icon' => 'fa-solid fa-wallet',          'url' => route('admin.finances.index'),   'active' => 'admin.finances.*'],
                ]],
                ['title' => 'Paket', 'items' => [
                    ['label' => 'Paket',      'icon' => 'fa-solid fa-box',             'url' => route('admin.packages.index'),   'active' => 'admin.packages.*'],
                    ['label' => 'Pelaminan',  'icon' => 'fa-solid fa-panorama',        'url' => route('admin.wedding-stages.index'), 'active' => 'admin.wedding-stages.*'],
                    ['label' => 'Tenda',      'icon' => 'fa-solid fa-campground',       'url' => route('admin.tents.index'), 'active' => 'admin.tents.*'],
                    ['label' => 'Gapura',     'icon' => 'fa-solid fa-archway',          'url' => route('admin.entrance-gates.index'), 'active' => 'admin.entrance-gates.*'],
                    ['label' => 'Benefit',    'icon' => 'fa-solid fa-circle-check',    'url' => route('admin.benefits.index'),   'active' => 'admin.benefits.*'],
                    ['label' => 'Kat. Benefit', 'icon' => 'fa-solid fa-tags',          'url' => route('admin.benefit-categories.index'), 'active' => 'admin.benefit-categories.*'],
                ]],
                ['title' => 'Wardrobe', 'items' => [
                    ['label' => 'Inventory',  'icon' => 'fa-solid fa-shirt',           'url' => route('admin.inventory.index'),  'active' => 'admin.inventory.*'],
                    ['label' => 'Kat. Inventory', 'icon' => 'fa-solid fa-tags',        'url' => route('admin.inventory-categories.index'), 'active' => 'admin.inventory-categories.*'],
                ]],
                ['title' => 'Vendor', 'items' => [
                    ['label' => 'Master Vendor', 'icon' => 'fa-solid fa-store',         'url' => route('admin.vendors.index'), 'active' => 'admin.vendors.*'],
                    ['label' => 'Kat. Vendor',   'icon' => 'fa-solid fa-tags',          'url' => route('admin.vendor-categories.index'), 'active' => 'admin.vendor-categories.*'],
                ]],
                ['title' => 'Users', 'items' => [
                    ['label' => 'Manajemen Staff', 'icon' => 'fa-solid fa-user-tie',     'url' => route('admin.users.staff'),      'active' => 'admin.users.staff*'],
                    ['label' => 'Manajemen Klien', 'icon' => 'fa-solid fa-users',        'url' => route('admin.users.clients'),    'active' => 'admin.users.clients*'],
                ]],
                ['title' => 'Settings', 'items' => [
                    ['label' => 'Testimoni',  'icon' => 'fa-solid fa-quote-right',     'url' => route('admin.content.testimonials'), 'active' => 'admin.content.testimonials*'],
                    ['label' => 'Galeri',     'icon' => 'fa-solid fa-image',           'url' => route('admin.content.gallery'), 'active' => 'admin.content.gallery*'],
                    ['label' => 'FAQ',        'icon' => 'fa-solid fa-circle-question', 'url' => route('admin.content.faqs'),    'active' => 'admin.content.faqs*'],
                    ['label' => 'Pengaturan', 'icon' => 'fa-solid fa-gear',            'url' => route('admin.content.settings'), 'active' => 'admin.content.settings*'],
                ]],
                ['title' => 'Marketing', 'items' => [
                    ['label' => 'Promo Banner', 'icon' => 'fa-solid fa-bullhorn',      'url' => route('admin.promo-banners.index'), 'active' => 'admin.promo-banners.*'],
                ]],
            ],
            'admin' => [
                ['title' => 'Menu Utama', 'items' => [
                    ['label' => 'Dashboard',  'icon' => 'fa-solid fa-grip',           'url' => route('dashboard'),              'active' => 'dashboard'],
                    ['label' => 'Booking',    'icon' => 'fa-solid fa-calendar-check', 'url' => route('admin.bookings.index'),   'active' => 'admin.bookings.*'],
                    ['label' => 'Pembayaran', 'icon' => 'fa-solid fa-credit-card',     'url' => route('admin.payments.index'),   'active' => 'admin.payments.*'],
                    ['label' => 'Invoice',    'icon' => 'fa-solid fa-file-invoice',   'url' => route('admin.invoices.index'),   'active' => 'admin.invoices.*'],
                    ['label' => 'Jadwal',     'icon' => 'fa-solid fa-calendar-week',   'url' => route('admin.calendar'),         'active' => 'admin.calendar'],
                ]],
                ['title' => 'Laporan', 'items' => [
                    ['label' => 'Keuangan',   'icon' => 'fa-solid fa-wallet',          'url' => route('admin.finances.index'),   'active' => 'admin.finances.*'],
                ]],
                ['title' => 'Paket', 'items' => [
                    ['label' => 'Paket',      'icon' => 'fa-solid fa-box',             'url' => route('admin.packages.index'),   'active' => 'admin.packages.*'],
                    ['label' => 'Pelaminan',  'icon' => 'fa-solid fa-panorama',        'url' => route('admin.wedding-stages.index'), 'active' => 'admin.wedding-stages.*'],
                    ['label' => 'Tenda',      'icon' => 'fa-solid fa-campground',       'url' => route('admin.tents.index'), 'active' => 'admin.tents.*'],
                    ['label' => 'Gapura',     'icon' => 'fa-solid fa-archway',          'url' => route('admin.entrance-gates.index'), 'active' => 'admin.entrance-gates.*'],
                    ['label' => 'Benefit',    'icon' => 'fa-solid fa-circle-check',    'url' => route('admin.benefits.index'),   'active' => 'admin.benefits.*'],
                    ['label' => 'Kat. Benefit', 'icon' => 'fa-solid fa-tags',          'url' => route('admin.benefit-categories.index'), 'active' => 'admin.benefit-categories.*'],
                ]],
                ['title' => 'Wardrobe', 'items' => [
                    ['label' => 'Inventory',  'icon' => 'fa-solid fa-shirt',           'url' => route('admin.inventory.index'),  'active' => 'admin.inventory.*'],
                    ['label' => 'Kat. Inventory', 'icon' => 'fa-solid fa-tags',        'url' => route('admin.inventory-categories.index'), 'active' => 'admin.inventory-categories.*'],
                ]],
                ['title' => 'Vendor', 'items' => [
                    ['label' => 'Master Vendor', 'icon' => 'fa-solid fa-store',         'url' => route('admin.vendors.index'), 'active' => 'admin.vendors.*'],
                    ['label' => 'Kat. Vendor',   'icon' => 'fa-solid fa-tags',          'url' => route('admin.vendor-categories.index'), 'active' => 'admin.vendor-categories.*'],
                ]],
                ['title' => 'Users', 'items' => [
                    ['label' => 'Manajemen Klien', 'icon' => 'fa-solid fa-users',       'url' => route('admin.users.clients'), 'active' => 'admin.users.clients*'],
                ]],
                ['title' => 'Settings', 'items' => [
                    ['label' => 'Testimoni',  'icon' => 'fa-solid fa-quote-right',     'url' => route('admin.content.testimonials'), 'active' => 'admin.content.testimonials*'],
                    ['label' => 'Galeri',     'icon' => 'fa-solid fa-image',           'url' => route('admin.content.gallery'), 'active' => 'admin.content.gallery*'],
                    ['label' => 'FAQ',        'icon' => 'fa-solid fa-circle-question', 'url' => route('admin.content.faqs'),    'active' => 'admin.content.faqs*'],
                    ['label' => 'Pengaturan', 'icon' => 'fa-solid fa-gear',            'url' => route('admin.content.settings'), 'active' => 'admin.content.settings*'],
                ]],
                ['title' => 'Marketing', 'items' => [
                    ['label' => 'Promo Banner', 'icon' => 'fa-solid fa-bullhorn',      'url' => route('admin.promo-banners.index'), 'active' => 'admin.promo-banners.*'],
                ]],
            ],
            'team' => [
                ['title' => 'Menu', 'items' => [
                    ['label' => 'Dashboard', 'icon' => 'fa-solid fa-table-cells-large', 'url' => route('dashboard'), 'active' => 'dashboard'],
                    ['label' => 'Jadwal Saya', 'icon' => 'fa-regular fa-calendar', 'url' => route('admin.calendar'), 'active' => 'admin.calendar'],
                    ['label' => 'Tugas Lapangan', 'icon' => 'fa-solid fa-clipboard-list', 'url' => route('admin.fieldwork.index'), 'active' => 'admin.fieldwork.*'],
                ]],
            ],
            'client' => [
                ['title' => 'Menu', 'items' => [
                    ['label' => 'Dashboard',    'icon' => 'fa-solid fa-grip',           'url' => route('dashboard'),              'active' => 'dashboard'],
                    ['label' => 'Booking Saya', 'icon' => 'fa-solid fa-calendar-check', 'url' => $firstBooking ? route('client.booking', $firstBooking) : route('booking.create'), 'active' => ['client.booking*', 'booking.create']],
                ]],
            ],
        ];
        $menus = $sidebarMenus[$role] ?? $sidebarMenus['client'];
    @endphp

    {{-- ════════════════════════════════════════
         SIDEBAR OVERLAY (mobile)
    ════════════════════════════════════════ --}}
    <div class="fixed inset-0 z-[1045] bg-black/50 hidden" id="sidebarOverlay"></div>

    {{-- ════════════════════════════════════════
         SIDEBAR
    ════════════════════════════════════════ --}}
    <aside id="sidebar"
           class="fixed top-0 left-0 z-[1050] flex h-screen w-[260px] flex-col overflow-y-auto bg-white border-r border-gray-100 shadow-[4px_0_24px_rgba(0,0,0,0.05)]">

        {{-- Logo --}}
        <div class="px-5 h-16 flex items-center gap-3 border-b border-gray-100">
            @if(!empty($settings['logo']))
                <img src="{{ asset('storage/'.$settings['logo']) }}" alt="{{ $settings['company_name'] ?? 'ANITA MUA' }}" class="h-8 w-auto object-contain flex-shrink-0">
                <a href="{{ route('home') }}" class="font-display text-lg font-bold tracking-wide text-gray-800 no-underline leading-tight">
                    {{ $settings['company_name'] ?? 'ANITA MUA' }}
                </a>
            @else
                <div class="w-8 h-8 rounded-xl bg-brand flex items-center justify-center flex-shrink-0">
                    <i class="fas fa-wand-sparkles text-white text-sm"></i>
                </div>
                <a href="{{ route('home') }}" class="font-display text-lg font-bold tracking-wide text-gray-800 no-underline leading-tight">
                    {{ $settings['company_name'] ?? 'ANITA MUA' }}
                </a>
            @endif
        </div>

        {{-- Navigation (grup menu + scrollable) --}}
        <nav class="flex-1 px-3 pt-5 pb-3 overflow-y-auto">
            @foreach($menus as $section)
            <p class="px-3 {{ $loop->first ? 'mt-2' : 'mt-5' }} mb-1 pb-1 text-[10px] font-bold uppercase tracking-widest text-gray-400 border-b border-gray-100">{{ $section['title'] }}</p>
            @foreach($section['items'] as $item)
            @php $isActive = request()->routeIs($item['active']); @endphp
            <a href="{{ $item['url'] }}"
               class="flex items-center gap-3 rounded-xl px-3 py-2 text-sm font-medium no-underline mb-0.5 transition-all duration-150
                      {{ $isActive
                           ? 'nav-item-active shadow-sm'
                           : 'text-gray-500 hover:bg-brand-50 hover:text-brand' }}">
                <span class="w-5 flex items-center justify-center flex-shrink-0">
                    <i class="{{ $item['icon'] }} text-base {{ $isActive ? 'text-white' : '' }}"></i>
                </span>
                {{ $item['label'] }}
            </a>
            @endforeach
            @endforeach
        </nav>

        {{-- User info at bottom --}}
        <div class="px-4 py-4 border-t border-gray-100">
            <div class="flex items-center gap-3 px-2">
                <div class="w-8 h-8 rounded-full bg-brand text-white flex items-center justify-center font-bold text-xs flex-shrink-0">
                    {{ strtoupper(substr($user->name ?? 'U', 0, 1)) }}
                </div>
                <div class="min-w-0">
                    <p class="text-xs font-semibold text-gray-800 truncate">{{ $user->name ?? 'User' }}</p>
                    <p class="text-[11px] text-gray-400">{{ ucfirst($role) }}</p>
                </div>
            </div>
        </div>
    </aside>

    {{-- ════════════════════════════════════════
         MAIN CONTENT
    ════════════════════════════════════════ --}}
    <div class="min-h-screen lg:ml-[260px]">

        {{-- ── TOPBAR ── --}}
        <header class="sticky top-0 z-[1040] flex items-center justify-between bg-white border-b border-gray-50 px-5 h-16 shadow-sm">

            {{-- Mobile menu toggle --}}
            <button id="sidebarToggle"
                    class="w-7 h-7 flex items-center justify-center rounded-lg text-gray-500 hover:bg-gray-100 hover:text-brand transition-colors lg:hidden">
                <i class="fas fa-bars text-sm"></i>
            </button>

            {{-- Page breadcrumb (desktop only) --}}
            <div class="hidden lg:flex items-center gap-2 text-sm text-gray-400">
                <i class="fas fa-home text-xs"></i>
                <span>/</span>
                <span class="text-gray-600 font-medium">@yield('title', 'Dashboard')</span>
            </div>

            {{-- Right side: User dropdown (Alpine.js) --}}
            <div class="ml-auto" x-data="{ open: false }">
                <div class="relative">
                    {{-- Trigger --}}
                    <button @click="open = !open" @click.outside="open = false"
                            class="flex items-center gap-2 px-2 py-1 transition-colors cursor-pointer">
                        <div class="w-8 h-8 rounded-full bg-gradient-to-br from-brand to-brand-dark text-white flex items-center justify-center font-bold text-[11px] flex-shrink-0">
                            {{ strtoupper(substr($user->name ?? 'U', 0, 1)) }}
                        </div>
                        <div class="hidden lg:block text-left">
                            <p class="text-xs font-semibold text-gray-800 leading-none">{{ $user->name ?? 'User' }}</p>
                            <p class="text-[10px] text-gray-400 leading-tight mt-px">{{ ucfirst($role) }}</p>
                        </div>
                        <i class="fas fa-chevron-down text-[10px] text-gray-400 transition-transform duration-200"
                           :class="open ? 'rotate-180' : ''"></i>
                    </button>

                    {{-- Dropdown panel --}}
                    <div x-show="open" x-cloak
                         x-transition:enter="transition ease-out duration-200"
                         x-transition:enter-start="opacity-0 scale-95 translate-y-[-4px]"
                         x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                         x-transition:leave="transition ease-in duration-100"
                         x-transition:leave-start="opacity-100 scale-100"
                         x-transition:leave-end="opacity-0 scale-95"
                         class="absolute right-0 top-full mt-2 w-48 bg-white rounded-2xl shadow-xl border border-gray-100 p-1 z-50">

                        {{-- User info header --}}
                        <div class="px-3 py-2.5 mb-1 border-b border-gray-50">
                            <p class="text-xs font-semibold text-gray-800">{{ $user->name ?? 'User' }}</p>
                            <p class="text-[11px] text-gray-400">{{ $user->email ?? '' }}</p>
                        </div>

                        <a href="{{ route('profile') }}"
                           class="flex items-center gap-2.5 px-3 py-2 rounded-xl text-sm text-gray-600 hover:bg-gray-50 hover:text-brand transition-colors no-underline">
                            <i class="fas fa-user text-xs w-4 text-center"></i> Profil
                        </a>

                        <a href="{{ route('home') }}"
                           class="flex items-center gap-2.5 px-3 py-2 rounded-xl text-sm text-gray-600 hover:bg-gray-50 hover:text-brand transition-colors no-underline">
                            <i class="fas fa-globe text-xs w-4 text-center"></i> View Website
                        </a>

                        <div class="my-1 border-t border-gray-50"></div>

                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit"
                                    class="w-full flex items-center gap-2.5 px-3 py-2 rounded-xl text-sm text-red-500 hover:bg-red-50 transition-colors text-left">
                                <i class="fas fa-arrow-right-from-bracket text-xs w-4 text-center"></i> Logout
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </header>

        {{-- ── FLASH MESSAGES ── --}}
        <div class="fixed top-4 right-4 z-[1090] flex flex-col gap-2 max-w-sm w-full pointer-events-none"
             x-data x-init="
                document.querySelectorAll('.flash-msg').forEach(el => {
                    setTimeout(() => {
                        el.style.transition = 'opacity .3s, transform .3s';
                        el.style.opacity = '0';
                        el.style.transform = 'translateY(-8px)';
                        setTimeout(() => el.remove(), 300);
                    }, 5000);
                });
             ">

            @if(session('success'))
                <div class="flash-msg pointer-events-auto flex items-start gap-3 rounded-2xl bg-white border border-emerald-200 text-emerald-800 px-4 py-3 text-sm shadow-xl"
                     style="animation: flashIn .3s ease;">
                    <span class="w-6 h-6 rounded-full bg-emerald-100 flex items-center justify-center flex-shrink-0">
                        <i class="fas fa-check text-emerald-600 text-[10px]"></i>
                    </span>
                    <span class="flex-1 pt-0.5">{{ session('success') }}</span>
                    <button class="text-gray-400 hover:text-gray-600 mt-0.5" onclick="this.closest('.flash-msg').remove()">
                        <i class="fas fa-xmark text-xs"></i>
                    </button>
                </div>
            @endif

            @if(session('warning'))
                <div class="flash-msg pointer-events-auto flex items-start gap-3 rounded-2xl bg-white border border-amber-200 text-amber-800 px-4 py-3 text-sm shadow-xl"
                     style="animation: flashIn .3s ease;">
                    <span class="w-6 h-6 rounded-full bg-amber-100 flex items-center justify-center flex-shrink-0">
                        <i class="fas fa-triangle-exclamation text-amber-600 text-[10px]"></i>
                    </span>
                    <span class="flex-1 pt-0.5">{{ session('warning') }}</span>
                    <button class="text-gray-400 hover:text-gray-600 mt-0.5" onclick="this.closest('.flash-msg').remove()">
                        <i class="fas fa-xmark text-xs"></i>
                    </button>
                </div>
            @endif

            @if($errors->any())
                <div class="flash-msg pointer-events-auto flex items-start gap-3 rounded-2xl bg-white border border-red-200 text-red-800 px-4 py-3 text-sm shadow-xl"
                     style="animation: flashIn .3s ease;">
                    <span class="w-6 h-6 rounded-full bg-red-100 flex items-center justify-center flex-shrink-0 mt-0.5">
                        <i class="fas fa-xmark text-red-600 text-[10px]"></i>
                    </span>
                    <div class="flex-1 pt-0.5">
                        @foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach
                    </div>
                    <button class="text-gray-400 hover:text-gray-600 mt-0.5" onclick="this.closest('.flash-msg').remove()">
                        <i class="fas fa-xmark text-xs"></i>
                    </button>
                </div>
            @endif
        </div>

        {{-- ── PAGE CONTENT ── --}}
        <main class="p-4 lg:p-5">
            @yield('content')
        </main>
    </div>

    {{-- ── PROOF LIGHTBOX ── --}}
    <div id="proofLightbox" class="hidden fixed inset-0 z-[1200] bg-black/90 flex items-center justify-center p-4" onclick="closeProof()">
        <button type="button" onclick="event.stopPropagation(); closeProof()"
                class="absolute top-4 right-4 w-10 h-10 rounded-full bg-white/10 text-white hover:bg-white/20 transition-colors flex items-center justify-center cursor-pointer">
            <i class="fas fa-xmark text-xl"></i>
        </button>
        <img id="proofLightboxImg" src="" alt="Bukti Transfer"
             class="max-h-[85vh] max-w-full rounded-xl shadow-2xl object-contain"
             onclick="event.stopPropagation()">
    </div>

    {{-- ── SIDEBAR TOGGLE SCRIPT (vanilla JS, no Bootstrap needed) ── --}}
    <script>
        const sidebarToggle = document.getElementById('sidebarToggle');
        const sidebar       = document.getElementById('sidebar');
        const overlay       = document.getElementById('sidebarOverlay');

        sidebarToggle?.addEventListener('click', () => {
            sidebar.classList.toggle('open');
            overlay.classList.toggle('hidden');
        });
        overlay?.addEventListener('click', () => {
            sidebar.classList.remove('open');
            overlay.classList.add('hidden');
        });

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

        // Proof lightbox
        function openProof(event, src) {
            event.preventDefault();
            document.getElementById('proofLightboxImg').src = src;
            document.getElementById('proofLightbox').classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        }
        function closeProof() {
            document.getElementById('proofLightbox').classList.add('hidden');
            document.body.style.overflow = '';
        }
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') closeProof();
        });
    </script>

    @stack('scripts')
</body>
</html>
