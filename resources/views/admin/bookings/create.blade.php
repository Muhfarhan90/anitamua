@extends('layouts.app')

@section('title', 'Booking Baru')

@section('content')
    <x-page-header title="Buat Booking Baru" />

    <form action="{{ route('admin.bookings.store') }}" method="POST" enctype="multipart/form-data" class="max-w-6xl space-y-5">
        <x-card title="Data Booking" title-icon="fa-calendar-check" padding="p-6">
            <div class="space-y-5">
            @csrf

            <div>
                <label class="block text-sm font-medium text-gray-600 mb-1">Klien <span class="text-red-500">*</span></label>
                <div class="inline-flex overflow-hidden rounded-lg border border-gray-200 text-sm" role="radiogroup" aria-label="Sumber klien">
                    <label class="cursor-pointer">
                        <input type="radio" name="client_mode" value="existing" class="sr-only peer" @checked(old('client_mode', 'existing') === 'existing')>
                        <span class="block px-4 py-2 peer-checked:bg-brand peer-checked:text-white">Pilih Klien</span>
                    </label>
                    <label class="cursor-pointer border-l border-gray-200">
                        <input type="radio" name="client_mode" value="new" class="sr-only peer" @checked(old('client_mode') === 'new')>
                        <span class="block px-4 py-2 peer-checked:bg-brand peer-checked:text-white">Klien Baru</span>
                    </label>
                </div>
                @error('client_mode')
                    <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div id="existingClientFields" class="space-y-3 {{ old('client_mode') === 'new' ? 'hidden' : '' }}">
                <x-select name="client_id" id="client_id" label="Pilih Klien" required placeholder="Pilih Klien">
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
            </div>

            <div id="newClientFields" class="hidden grid grid-cols-1 md:grid-cols-2 gap-5 rounded-lg border border-brand-100 bg-brand-50/40 p-4">
                <x-input name="new_client_name" label="Nama Klien" placeholder="Nama lengkap" />
                <x-input name="new_client_phone" label="Nomor WhatsApp" placeholder="08xxxxxxxxxx" />
                <x-input name="new_client_email" label="Email" type="email" placeholder="email@client.com" />
                <x-input name="new_client_instagram" label="Username Instagram" placeholder="@username" />
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

            @include('admin.bookings.partials.addons-fields', ['addonRows' => old('addons', [])])

            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <x-input name="event_date" id="event_date" label="Tanggal Acara" type="date" required />
                <x-input name="event_time" id="event_time" label="Jam Acara" type="time" />
            </div>

            <x-input name="location" label="Lokasi Acara" placeholder="Alamat lokasi acara" />

            <x-select name="referral_source" label="Tahu Anita dari mana?" required>
                <option value="">— Pilih sumber informasi —</option>
                @foreach($referralSources as $source)<option value="{{ $source }}" @selected(old('referral_source') === $source)>{{ $source }}</option>@endforeach
            </x-select>

            <x-textarea name="notes" label="Catatan" placeholder="Catatan tambahan untuk booking ini..." />

            <div class="border-t border-gray-100 pt-5 mt-5 space-y-4">
                <div>
                    <h3 class="text-base font-semibold text-gray-800"><i class="fas fa-credit-card text-brand mr-2"></i>Pembayaran Booking</h3>
                    <p class="mt-1 text-sm text-gray-500">Isi pembayaran awal booking.</p>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <x-input name="dp1_amount" label="Nominal DP1" currency required placeholder="500.000" />
                    <div>
                        <label class="block text-sm font-medium text-gray-600 mb-1">Bukti Transfer DP1 <span class="text-gray-400 font-normal">(opsional)</span></label>
                        <input type="file" name="proof" accept="image/jpeg,image/png,image/webp" class="w-full rounded-lg border bg-gray-50 px-3 py-2 text-sm text-gray-800 focus:outline-none focus:ring-2 focus:ring-brand-200 border-gray-200">
                        <p class="text-xs text-gray-400 mt-1">JPG/PNG/WebP, maks 3 MB.</p>
                        @error('proof')
                            <p class="text-xs text-red-600 mt-1"><i class="fas fa-circle-exclamation mr-1"></i>{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            </div>
        </x-card>

            @include('admin.bookings.partials.survey-card', [
                'embedded' => true,
                'booking' => null,
                'weddingStages' => $weddingStages,
                'tents' => $tents,
                'entranceGates' => $entranceGates,
                'teamMembers' => $teamMembers,
            ])

            @include('admin.bookings.partials.fitting-card', [
                'embedded' => true,
                'booking' => null,
                'teamMembers' => $teamMembers,
            ])

            <div class="flex items-center gap-4 pt-5 border-t border-brand-100">
                <x-button href="{{ route('admin.bookings.index') }}" color="ghost"
                    class="flex-1 justify-center">Batal</x-button>
                <x-button type="submit" class="flex-1 justify-center"><i class="fas fa-save"></i> Simpan Booking</x-button>
            </div>
    </form>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const eventDate = document.getElementById('event_date');
                const surveyDate = document.getElementById('survey_date');
                const fittingDate = document.getElementById('fitting_date');
                const selectJenis = document.getElementById('package_type');
                const selectPaket = document.getElementById('package_id');
                const paketOptions = Array.from(selectPaket.options).slice(1);
                const clientModeInputs = document.querySelectorAll('input[name="client_mode"]');
                const existingClientFields = document.getElementById('existingClientFields');
                const newClientFields = document.getElementById('newClientFields');
                const selectClient = document.getElementById('client_id');
                const preview = document.getElementById('client-preview');
                const newClientInputs = newClientFields.querySelectorAll('input');

                function oneMonthBefore(value) {
                    const [year, month, day] = value.split('-').map(Number);
                    const targetMonth = month - 2;
                    const targetYear = year + Math.floor(targetMonth / 12);
                    const normalizedMonth = ((targetMonth % 12) + 12) % 12;
                    const lastDay = new Date(targetYear, normalizedMonth + 1, 0).getDate();
                    return `${targetYear}-${String(normalizedMonth + 1).padStart(2, '0')}-${String(Math.min(day, lastDay)).padStart(2, '0')}`;
                }

                function setRelatedDates() {
                    if (!eventDate.value) return;
                    const date = oneMonthBefore(eventDate.value);
                    if (!surveyDate.value) surveyDate.value = date;
                    if (fittingDate && !fittingDate.value) fittingDate.value = date;
                }

                eventDate.addEventListener('change', function() {
                    if (!eventDate.value) return;
                    const date = oneMonthBefore(eventDate.value);
                    surveyDate.value = date;
                    if (fittingDate) fittingDate.value = date;
                });
                setRelatedDates();

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

                function updateClientMode() {
                    const isNewClient = document.querySelector('input[name="client_mode"]:checked').value === 'new';
                    existingClientFields.classList.toggle('hidden', isNewClient);
                    newClientFields.classList.toggle('hidden', !isNewClient);
                    selectClient.disabled = isNewClient;
                    selectClient.required = !isNewClient;
                    newClientInputs.forEach(input => {
                        input.disabled = !isNewClient;
                        input.required = isNewClient;
                    });
                    if (isNewClient) preview.classList.add('hidden');
                    else updateClientPreview();
                }

                clientModeInputs.forEach(input => input.addEventListener('change', updateClientMode));
                selectClient.addEventListener('change', updateClientPreview);
                updateClientMode();
            });
        </script>
    @endpush
@endsection
