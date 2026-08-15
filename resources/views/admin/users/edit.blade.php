@extends('layouts.app')

@section('title', 'Edit User')

@section('content')
@php
    $isStaff = in_array($user->role, ['owner', 'admin', 'team']);
    $backRoute = $isStaff ? route('admin.users.staff') : route('admin.users.clients');
@endphp

<x-page-header title="Edit {{ $isStaff ? 'Staff' : 'Klien' }}" subtitle="Perbarui data akun {{ $user->name }}">
    <x-slot:actions>
        <x-button href="{{ $backRoute }}" color="ghost"><i class="fas fa-arrow-left"></i> Kembali</x-button>
    </x-slot:actions>
</x-page-header>

<div class="max-w-lg">
    <x-card>
        <form method="POST" action="{{ route('admin.users.update', $user->id) }}" class="space-y-4">
            @csrf
            @method('PATCH')

            <x-input name="name" label="Nama Lengkap" :value="old('name', $user->name)" required />
            <x-input name="email" label="Email" type="email" :value="old('email', $user->email)" required />
            <x-input name="phone" label="No HP" :value="old('phone', $user->phone)" placeholder="08xxxxxxxxxx" />
            <x-input name="password" label="Password Baru (kosongkan jika tidak diganti)" type="password" minlength="6" />

            @if($isStaff)
            <div>
                <label for="role" class="block text-sm font-medium text-gray-600 mb-1">Role <span class="text-red-500">*</span></label>
                <select name="role" id="role" required
                        class="w-full rounded-lg border bg-gray-50 px-3 py-2 text-sm text-gray-800 focus:outline-none focus:ring-2 focus:ring-brand-200 border-gray-200">
                    <option value="admin" @selected(old('role', $user->role) === 'admin')>Admin</option>
                    <option value="team" @selected(old('role', $user->role) === 'team')>Tim Lapangan</option>
                    <option value="owner" @selected(old('role', $user->role) === 'owner')>Owner</option>
                </select>
            </div>
            @endif

            <label class="flex items-center justify-between bg-gray-50 rounded-lg px-3 py-2.5 cursor-pointer">
                <span class="text-sm font-medium text-gray-600">Akun Aktif</span>
                <input type="checkbox" name="is_active" class="w-4 h-4 accent-[#d4739a]" @checked($user->is_active)>
            </label>

            <div class="flex gap-3 pt-2">
                <x-button color="primary" type="submit"><i class="fas fa-save"></i> Simpan Perubahan</x-button>
                <x-button href="{{ $backRoute }}" color="ghost">Batal</x-button>
            </div>
        </form>
    </x-card>
</div>
@endsection
