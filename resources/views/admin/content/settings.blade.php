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

<x-card class="max-w-2xl">
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

        <div class="pt-2">
            <x-button color="primary" type="submit"><i class="fas fa-save"></i> Simpan Pengaturan</x-button>
        </div>
    </form>
</x-card>

@push('scripts')
<script>
    function previewLogo(input) {
        const file = input.files[0];
        if (!file) return;
        const reader = new FileReader();
        reader.onload = function (e) {
            const img = document.getElementById('logoPreview');
            const box = document.getElementById('logoBox');
            img.src = e.target.result;
            box.classList.remove('hidden');
        };
        reader.readAsDataURL(file);
    }
</script>
@endpush
@endsection
