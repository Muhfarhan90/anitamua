@extends('layouts.app')

@section('title', 'Profil')

@section('content')
<x-page-header title="Profil" subtitle="Kelola data diri dan keamanan akun Anda" />

<div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
    {{-- PROFIL --}}
    <x-card title="Data Profil" title-icon="fa-user">
        <form action="{{ route('profile.update') }}" method="POST" class="space-y-4">
            @csrf
            <x-input name="name" label="Nama" required :value="auth()->user()->name" icon="fa-user" />
            <x-input name="phone" label="No. WhatsApp" :value="auth()->user()->phone" icon="fa-phone" />
            <div>
                <label class="block text-sm font-medium text-gray-600 mb-1">Email</label>
                <div class="relative">
                    <i class="fas fa-envelope absolute left-3 top-1/2 -translate-y-1/2 text-sm text-brand pointer-events-none"></i>
                    <input type="email" value="{{ auth()->user()->email }}" disabled
                           class="w-full rounded-lg border border-gray-200 bg-gray-100 pl-9 pr-3 py-2 text-sm text-gray-500 cursor-not-allowed">
                </div>
                <p class="text-xs text-gray-400 mt-1">Email tidak bisa diubah.</p>
            </div>
            <x-button type="submit"><i class="fas fa-save"></i> Simpan Profil</x-button>
        </form>
    </x-card>

    {{-- GANTI PASSWORD --}}
    <x-card title="Ganti Password" title-icon="fa-lock">
        <form action="{{ route('profile.password') }}" method="POST" class="space-y-4">
            @csrf
            <x-input name="current_password" label="Password Saat Ini" type="password" required icon="fa-lock" />
            <x-input name="password" label="Password Baru" type="password" required icon="fa-key" />
            <x-input name="password_confirmation" label="Konfirmasi Password Baru" type="password" required icon="fa-key" />
            <div class="rounded-lg bg-yellow-50 border border-yellow-100 text-yellow-700 text-xs px-3 py-2 flex items-start gap-2">
                <i class="fas fa-circle-info mt-0.5"></i>
                <span>Akun dibuat otomatis dengan password default. Segera ganti dengan password pribadi Anda (minimal 6 karakter).</span>
            </div>
            <x-button type="submit"><i class="fas fa-key"></i> Ganti Password</x-button>
        </form>
    </x-card>
</div>
@endsection
