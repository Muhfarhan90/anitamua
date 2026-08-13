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
    <form action="{{ route('admin.content.settings.store') }}" method="POST" class="space-y-4">
        @csrf

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

        <div class="pt-2">
            <x-button color="primary" type="submit"><i class="fas fa-save"></i> Simpan Pengaturan</x-button>
        </div>
    </form>
</x-card>
@endsection
