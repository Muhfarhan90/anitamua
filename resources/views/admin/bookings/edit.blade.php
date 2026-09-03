@extends('layouts.app')

@section('title', 'Edit Booking')

@section('content')
    <x-page-header :title="'Edit Booking - '.$booking->code" />

    <x-card padding="p-6" class="max-w-3xl">
        <form action="{{ route('admin.bookings.update', $booking) }}" method="POST" class="space-y-5">
            @csrf
            @method('PATCH')

            <x-select name="client_id" label="Klien" placeholder="Tanpa akun klien">
                @foreach ($clients ?? [] as $client)
                    <option value="{{ $client->id }}" data-phone="{{ $client->phone }}" data-email="{{ $client->email }}"
                        @selected(old('client_id', $booking->client_id) == $client->id)>{{ $client->name }} - {{ $client->phone }}</option>
                @endforeach
            </x-select>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                <x-input name="name" label="Nama Pengantin" required :value="$booking->name" />
                <x-input name="phone" label="Nomor WhatsApp" required :value="$booking->phone" />
                <x-input name="email" label="Email" type="email" required :value="$booking->email" />
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-600 mb-1">Jenis Paket <span class="text-red-500">*</span></label>
                <select id="package_type"
                    class="w-full rounded-lg border bg-gray-50 px-3 py-2 text-sm text-gray-800 focus:outline-none focus:ring-2 focus:ring-brand-200 border-gray-200">
                    <option value="">- Pilih Jenis -</option>
                    <option value="makeup">Make Up & Attire</option>
                    <option value="full">Full WO Package</option>
                </select>
            </div>

            <x-select name="package_id" id="package_id" label="Pilih Paket" required placeholder="- Pilih Jenis Paket dahulu -">
                @foreach ($packages ?? [] as $package)
                    <option value="{{ $package->id }}" data-type="{{ $package->type }}" @selected(old('package_id', $booking->package_id) == $package->id)>
                        {{ $package->name }}@if ($package->sub_type)
                            ({{ $subTypeLabels[$package->sub_type] ?? $package->sub_type }})
                        @endif - Rp {{ number_format($package->price, 0, ',', '.') }}
                    </option>
                @endforeach
            </x-select>

            @php
                $addonRows = old('addons', $booking->addons->map(fn ($addon) => [
                    'name' => $addon->name,
                    'price' => $addon->price,
                ])->values()->all());
            @endphp
            @include('admin.bookings.partials.addons-fields', ['addonRows' => $addonRows])

            <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                <x-input name="event_date" label="Tanggal Acara" type="date" required :value="$booking->event_date?->format('Y-m-d')" />
                <x-input name="survey_date" label="Tanggal Survey" type="date" :value="$booking->survey_date?->format('Y-m-d')" />
                <x-input name="fitting_date" label="Tanggal Fitting" type="date" :value="$booking->fitting_date?->format('Y-m-d')" />
            </div>

            <x-input name="location" label="Lokasi Acara" placeholder="Alamat lokasi acara" :value="$booking->location" />

            <x-select name="status" label="Status" required>
                <option value="pending" @selected(old('status', $booking->status) === 'pending')>Pending</option>
                <option value="booked" @selected(old('status', $booking->status) === 'booked')>Booked</option>
                <option value="completed" @selected(old('status', $booking->status) === 'completed')>Selesai</option>
                <option value="cancelled" @selected(old('status', $booking->status) === 'cancelled')>Dibatalkan</option>
            </x-select>

            <x-textarea name="notes" label="Catatan" placeholder="Catatan tambahan untuk booking ini..." :value="$booking->notes" />

            <div class="flex items-center gap-4 pt-5 border-t border-brand-100">
                <x-button href="{{ route('admin.bookings.show', $booking) }}" color="ghost" class="flex-1 justify-center">Batal</x-button>
                <x-button type="submit" class="flex-1 justify-center"><i class="fas fa-save"></i> Simpan Perubahan</x-button>
            </div>
        </form>
    </x-card>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const selectJenis = document.getElementById('package_type');
                const selectPaket = document.getElementById('package_id');
                const paketOptions = Array.from(selectPaket.options).slice(1);

                function applyFilter() {
                    const jenis = selectJenis.value;
                    paketOptions.forEach(opt => {
                        opt.hidden = jenis !== '' && opt.dataset.type !== jenis;
                    });
                    if (selectPaket.value && selectPaket.selectedOptions[0]?.hidden) {
                        selectPaket.value = '';
                    }
                }

                selectJenis.addEventListener('change', applyFilter);

                const preselected = selectPaket.selectedOptions[0];
                if (preselected && preselected.value) {
                    selectJenis.value = preselected.dataset.type || '';
                    applyFilter();
                }
            });
        </script>
    @endpush
@endsection
