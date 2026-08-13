@extends('layouts.app')

@section('title', 'Booking Baru')

@section('content')
<x-page-header title="Buat Booking Baru" />

<x-card padding="p-6" class="max-w-3xl">
    <form action="{{ route('admin.bookings.store') }}" method="POST" class="space-y-5">
        @csrf

        <x-select name="client_id" label="Klien" required placeholder="Pilih Klien">
            @foreach($clients ?? [] as $client)
            <option value="{{ $client->id }}" @selected(old('client_id') == $client->id)>{{ $client->name }} - {{ $client->phone }}</option>
            @endforeach
        </x-select>

        <x-select name="package_id" label="Paket" required placeholder="Pilih Paket">
            @foreach($packages ?? [] as $package)
            <option value="{{ $package->id }}" @selected(old('package_id') == $package->id)>{{ $package->name }} - Rp {{ number_format($package->price, 0, ',', '.') }}</option>
            @endforeach
        </x-select>

        <x-input name="name" label="Nama Acara" required placeholder="Contoh: Wedding Bella & Andi" />

        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            <x-input name="phone" label="No. WhatsApp" placeholder="08123456789" />
            <x-input name="email" label="Email" type="email" placeholder="client@email.com" />
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
            <x-input name="event_date" label="Tanggal Acara" type="date" required />
            <x-input name="survey_date" label="Tanggal Survey" type="date" />
            <x-input name="fitting_date" label="Tanggal Fitting" type="date" />
        </div>

        <x-input name="location" label="Lokasi Acara" placeholder="Alamat lokasi acara" />

        <x-textarea name="notes" label="Catatan" placeholder="Catatan tambahan untuk booking ini..." />

        <div class="flex items-center gap-4 pt-5 border-t border-brand-100">
            <x-button href="{{ route('admin.bookings.index') }}" color="ghost" class="flex-1 justify-center">Batal</x-button>
            <x-button type="submit" class="flex-1 justify-center"><i class="fas fa-save"></i> Simpan Booking</x-button>
        </div>
    </form>
</x-card>
@endsection
