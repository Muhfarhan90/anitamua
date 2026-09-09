@extends('layouts.app')

@section('title', 'Pengaturan Situs')

@section('content')
<x-page-header title="Konten Website — Pengaturan" subtitle="Informasi umum, kontak, dan sosial media yang tampil di landing">
    <x-slot:actions>
        <x-button href="{{ route('admin.content.testimonials') }}" color="ghost"><i class="fas fa-quote-right"></i> Testimoni</x-button>
        <x-button href="{{ route('admin.content.gallery') }}" color="ghost"><i class="fas fa-image"></i> Galeri</x-button>
        <x-button href="{{ route('admin.content.faqs') }}" color="ghost"><i class="fas fa-circle-question"></i> FAQ</x-button>
    </x-slot:actions>
</x-page-header>

<x-card class="max-w-4xl">
    <form action="{{ route('admin.content.settings.store') }}" method="POST" enctype="multipart/form-data" class="space-y-4">
        @csrf

        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider border-b border-gray-100 pb-2">Logo</p>
        <div>
            <label class="block text-sm font-medium text-gray-600 mb-1">Logo Website</label>
            <div class="w-32 h-20 rounded-lg border border-gray-100 bg-gray-50 flex items-center justify-center overflow-hidden mb-3 {{ empty($settings['logo']) ? 'hidden' : '' }}" id="logoBox">
                <img id="logoPreview" src="{{ !empty($settings['logo']) ? asset('storage/'.$settings['logo']) : '' }}" alt="Logo" class="max-h-full max-w-full object-contain p-1">
            </div>
            <input type="file" id="logoInput" name="logo" accept="image/*" onchange="previewLogo(this)" class="w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand-200">
            <p class="text-xs text-gray-400 mt-1">Format PNG/JPG, maks 2 MB — otomatis dikompres. Kosongkan untuk memakai logo lama.</p>
            @error('logo')
                <p class="text-xs text-red-600 mt-1"><i class="fas fa-circle-exclamation mr-1"></i>{{ $message }}</p>
            @enderror
        </div>

        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider border-b border-gray-100 pb-2">Gambar Landing</p>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            @foreach([
                ['field' => 'landing_hero_image', 'label' => 'Gambar utama & header halaman', 'description' => 'Tampil pada hero beranda dan header halaman landing.', 'url' => $landingImages['hero']],
                ['field' => 'about_image', 'label' => 'Gambar tentang kami', 'description' => 'Tampil pada bagian Tentang di beranda dan halaman Tentang.', 'url' => $landingImages['about']],
            ] as $image)
                <div class="rounded-xl border border-gray-100 p-3">
                    <label class="block text-sm font-medium text-gray-600 mb-2">{{ $image['label'] }}</label>
                    <img id="{{ $image['field'] }}Preview" src="{{ $image['url'] }}" alt="{{ $image['label'] }}" class="w-full h-36 rounded-lg object-cover border border-gray-100 mb-3">
                    <input type="file" id="{{ $image['field'] }}Input" name="{{ $image['field'] }}" accept="image/*" onchange="previewImage(this, '{{ $image['field'] }}Preview')" class="w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand-200">
                    <p class="text-xs text-gray-400 mt-1">{{ $image['description'] }} Maks. 8 MB.</p>
                    @error($image['field'])
                        <p class="text-xs text-red-600 mt-1"><i class="fas fa-circle-exclamation mr-1"></i>{{ $message }}</p>
                    @enderror
                </div>
            @endforeach
        </div>

        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider border-b border-gray-100 pb-2">Umum</p>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <x-input name="company_name" label="Nama Perusahaan" :value="$settings['company_name'] ?? ''" />
            <x-input name="tagline" label="Tagline" :value="$settings['tagline'] ?? ''" />
        </div>
        <x-textarea name="about" label="Tentang" rows="4">{{ $settings['about'] ?? '' }}</x-textarea>

        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider border-b border-gray-100 pb-2">Kontak</p>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <x-input name="address" label="Alamat" :value="$settings['address'] ?? ''" />
            <x-input name="phone" label="Telepon" :value="$settings['phone'] ?? ''" />
            <x-input name="email" label="Email" type="email" :value="$settings['email'] ?? ''" />
            <x-input name="whatsapp" label="WhatsApp (format 62xx)" :value="$settings['whatsapp'] ?? ''" />
            <x-input name="instagram" label="Instagram" :value="$settings['instagram'] ?? ''" />
        </div>

        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider border-b border-gray-100 pb-2">Rekening (untuk pembayaran DP)</p>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <x-input name="bank_name" label="Bank" :value="$settings['bank_name'] ?? ''" />
            <x-input name="bank_account_number" label="No. Rekening" :value="$settings['bank_account_number'] ?? ''" />
            <x-input name="bank_account_name" label="Atas Nama" :value="$settings['bank_account_name'] ?? ''" />
        </div>

        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider border-b border-gray-100 pb-2">Invoice</p>
        <x-textarea name="invoice_greeting" label="Teks ucapan invoice" rows="3">{{ $settings['invoice_greeting'] ?? '' }}</x-textarea>
        <p class="text-xs text-gray-400 -mt-2">Teks ini tampil pada bagian bawah invoice dan versi PDF. Maksimal 500 karakter.</p>

        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider border-b border-gray-100 pb-2">Sumber Booking</p>
        @php
            $referralSourceRows = old('booking_referral_sources', $bookingReferralSources);
            $referralSourceRows = is_array($referralSourceRows) ? $referralSourceRows : preg_split('/\R/', (string) $referralSourceRows);
        @endphp
        <div data-referral-source-editor class="space-y-3">
            <div data-referral-source-list class="space-y-2">
                @foreach($referralSourceRows as $index => $source)
                    <div data-referral-source-row class="flex items-center gap-2">
                        <input type="text" name="booking_referral_sources[]" value="{{ $source }}" required maxlength="100" placeholder="Contoh: Instagram" class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-800 focus:outline-none focus:ring-2 focus:ring-brand-200">
                        <button type="button" data-remove-referral-source class="inline-flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-lg border border-red-200 text-red-500 transition-colors hover:bg-red-50" aria-label="Hapus sumber booking"><i class="fas fa-trash"></i></button>
                    </div>
                @endforeach
            </div>
            <button type="button" data-add-referral-source class="inline-flex items-center gap-1.5 rounded-lg border border-brand px-3 py-2 text-xs font-semibold text-brand transition-colors hover:bg-brand-50"><i class="fas fa-plus"></i> Tambah Sumber</button>
            @error('booking_referral_sources')<p class="text-xs text-red-600"><i class="fas fa-circle-exclamation mr-1"></i>{{ $message }}</p>@enderror
            @foreach($errors->get('booking_referral_sources.*') as $message)<p class="text-xs text-red-600"><i class="fas fa-circle-exclamation mr-1"></i>{{ $message }}</p>@endforeach
            <p class="text-xs text-gray-400">Satu pilihan per baris. Baris kosong dan pilihan duplikat akan dibersihkan otomatis.</p>
            <template data-referral-source-template>
                <div data-referral-source-row class="flex items-center gap-2">
                    <input type="text" name="booking_referral_sources[]" required maxlength="100" placeholder="Contoh: Instagram" class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-800 focus:outline-none focus:ring-2 focus:ring-brand-200">
                    <button type="button" data-remove-referral-source class="inline-flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-lg border border-red-200 text-red-500 transition-colors hover:bg-red-50" aria-label="Hapus sumber booking"><i class="fas fa-trash"></i></button>
                </div>
            </template>
        </div>

        <div class="pt-2">
            <x-button color="primary" type="submit"><i class="fas fa-save"></i> Simpan Pengaturan</x-button>
        </div>
    </form>
</x-card>

@push('scripts')
<script>
    function previewLogo(input) {
        previewImage(input, 'logoPreview', 'logoBox');
    }

    function previewImage(input, imageId, boxId = null) {
        const file = input.files[0];
        if (!file) return;
        const reader = new FileReader();
        reader.onload = function (e) {
            const img = document.getElementById(imageId);
            img.src = e.target.result;
            if (boxId) document.getElementById(boxId).classList.remove('hidden');
        };
        reader.readAsDataURL(file);
    }

    document.addEventListener('DOMContentLoaded', function () {
        const editor = document.querySelector('[data-referral-source-editor]');
        if (!editor) return;

        const list = editor.querySelector('[data-referral-source-list]');
        const template = editor.querySelector('[data-referral-source-template]');
        const addButton = editor.querySelector('[data-add-referral-source]');

        addButton.addEventListener('click', function () {
            const row = template.content.firstElementChild.cloneNode(true);
            list.appendChild(row);
            row.querySelector('input').focus();
        });

        list.addEventListener('click', function (event) {
            const button = event.target.closest('[data-remove-referral-source]');
            if (!button) return;
            button.closest('[data-referral-source-row]').remove();
        });
    });
</script>
@endpush
@endsection
