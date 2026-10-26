@extends('layouts.app')

@section('title', 'Syarat & Ketentuan')

@section('content')
<x-page-header title="Konten Website — Syarat & Ketentuan" subtitle="Isi halaman syarat dan ketentuan yang berlaku untuk semua paket." />

<x-card title="Isi Syarat & Ketentuan" title-icon="fa-file-lines" class="max-w-5xl">
    <form action="{{ route('admin.content.terms.store') }}" method="POST" class="space-y-5">
        @csrf
        <div class="terms-editor">
            <x-rich-text-editor name="package_terms_html" label="Konten halaman" :value="old('package_terms_html', $terms)" help="Gunakan judul, paragraf, dan daftar agar ketentuan mudah dibaca klien." />
        </div>
        <div class="flex justify-end border-t border-gray-100 pt-5">
            <x-button color="primary" type="submit"><i class="fas fa-save"></i> Simpan Syarat &amp; Ketentuan</x-button>
        </div>
    </form>
</x-card>
@vite('resources/js/rich-text-editor.js')
@endsection
