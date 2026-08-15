@extends('layouts.app')

@section('title', 'Booking Baru')

@section('content')
    <x-page-header title="Buat Booking Baru" />

    <x-card padding="p-6" class="max-w-3xl">
        <form action="{{ route('admin.bookings.store') }}" method="POST" enctype="multipart/form-data" class="space-y-5">
            @csrf

            <x-select name="client_id" label="Klien" required placeholder="Pilih Klien">
                @foreach ($clients ?? [] as $client)
                    <option value="{{ $client->id }}" data-phone="{{ $client->phone }}" data-email="{{ $client->email }}"
                        @selected(old('client_id') == $client->id)>{{ $client->name }} - {{ $client->phone }}</option>
                @endforeach
            </x-select>
            <div id="client-preview" class="hidden rounded-lg bg-brand-50/60 border border-brand-100 px-4 py-3 text-sm">
                <p class="font-medium text-gray-700" id="client-preview-name"></p>
                <p class="text-gray-500" id="client-preview-phone"></p>
                <p class="text-gray-500" id="client-preview-email"></p>
            </div>

            {{-- Jenis Paket (sama dengan form landing) --}}
            <div>
                <label class="block text-sm font-medium text-gray-600 mb-1">Jenis Paket <span
                        class="text-red-500">*</span></label>
                <select id="package_type"
                    class="w-full rounded-lg border bg-gray-50 px-3 py-2 text-sm text-gray-800 focus:outline-none focus:ring-2 focus:ring-brand-200 border-gray-200">
                    <option value="">— Pilih Jenis —</option>
                    <option value="makeup">Make Up & Attire</option>
                    <option value="full">Full WO Package</option>
                </select>
            </div>

            <x-select name="package_id" id="package_id" label="Pilih Paket" required
                placeholder="— Pilih Jenis Paket dahulu —">
                @foreach ($packages ?? [] as $package)
                    <option value="{{ $package->id }}" data-type="{{ $package->type }}" @selected(old('package_id') == $package->id)>
                        {{ $package->name }}@if ($package->sub_type)
                            ({{ $subTypeLabels[$package->sub_type] ?? $package->sub_type }})
                        @endif - Rp {{ number_format($package->price, 0, ',', '.') }}
                    </option>
                @endforeach
            </x-select>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                <x-input name="event_date" label="Tanggal Acara" type="date" required />
                <x-input name="survey_date" label="Tanggal Survey (Opsional)" type="date" />
                <x-input name="fitting_date" label="Tanggal Fitting (Opsional)" type="date" />
            </div>

            <x-input name="location" label="Lokasi Acara" placeholder="Alamat lokasi acara" />

            <x-textarea name="notes" label="Catatan" placeholder="Catatan tambahan untuk booking ini..." />

            <div>
                <label class="block text-sm font-medium text-gray-600 mb-1">Bukti Transfer DP 10% <span
                        class="text-gray-400 font-normal">(opsional)</span></label>
                <input type="file" name="proof" accept="image/jpeg,image/png,image/webp"
                    class="w-full rounded-lg border bg-gray-50 px-3 py-2 text-sm text-gray-800 focus:outline-none focus:ring-2 focus:ring-brand-200 border-gray-200">
                <p class="text-xs text-gray-400 mt-1">JPG/PNG/WebP, maks 3 MB.</p>
                @error('proof')
                    <p class="text-xs text-red-600 mt-1"><i class="fas fa-circle-exclamation mr-1"></i>{{ $message }}</p>
                @enderror
            </div>

            <div class="flex items-center gap-4 pt-5 border-t border-brand-100">
                <x-button href="{{ route('admin.bookings.index') }}" color="ghost"
                    class="flex-1 justify-center">Batal</x-button>
                <x-button type="submit" class="flex-1 justify-center"><i class="fas fa-save"></i> Simpan Booking</x-button>
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

                // Preview data klien terpilih
                const selectClient = document.getElementById('client_id');
                const preview = document.getElementById('client-preview');
                function updateClientPreview() {
                    const opt = selectClient.selectedOptions[0];
                    if (!opt || !opt.value) {
                        preview.classList.add('hidden');
                        return;
                    }
                    preview.classList.remove('hidden');
                    document.getElementById('client-preview-name').textContent = opt.textContent.trim();
                    document.getElementById('client-preview-phone').textContent = 'WA: ' + (opt.dataset.phone || '—');
                    document.getElementById('client-preview-email').textContent = 'Email: ' + (opt.dataset.email || '—');
                }
                selectClient.addEventListener('change', updateClientPreview);
                updateClientPreview();
            });
        </script>
    @endpush
@endsection
