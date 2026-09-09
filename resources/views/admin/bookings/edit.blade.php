@extends('layouts.app')

@section('title', 'Edit Booking')

@section('content')
    <x-page-header :title="'Edit Booking - '.$booking->code" />

    @foreach($booking->bookingVendors as $bookingVendor)
        <form id="vendor-update-{{ $bookingVendor->id }}" method="POST" action="{{ route('admin.bookings.vendors.update', $booking) }}">
            @csrf
            @method('PATCH')
            <input type="hidden" name="booking_vendor_id" value="{{ $bookingVendor->id }}">
        </form>
    @endforeach
    <form id="additional-vendor-form" method="POST" action="{{ route('admin.bookings.vendors.store', $booking) }}">
        @csrf
    </form>

    <form action="{{ route('admin.bookings.update', $booking) }}" method="POST" enctype="multipart/form-data" class="max-w-6xl space-y-5">
        <x-card title="Data Booking" title-icon="fa-calendar-check" padding="p-6">
            <div class="space-y-5">
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

            <x-card title="Daftar Vendor" title-icon="fa-store" padding="p-5">
                @forelse($booking->bookingVendors as $bookingVendor)
                    @php $vendor = $bookingVendor->vendor; @endphp
                    <div class="border-b border-gray-100 py-4 first:pt-0 last:border-0 last:pb-0">
                        <div class="flex items-center gap-3">
                            @if($vendor?->logo)
                                <img src="{{ asset('storage/'.$vendor->logo) }}" alt="Logo {{ $vendor->name }}" class="h-10 w-10 shrink-0 rounded-full border border-brand-100 bg-white object-cover">
                            @else
                                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-brand-50 text-brand"><i class="fas fa-store"></i></div>
                            @endif
                            <div class="min-w-0">
                                <p class="font-semibold text-gray-800">{{ $vendor?->name ?? 'Vendor' }}</p>
                                <p class="text-xs text-brand">{{ $bookingVendor->role ?: $vendor?->category?->name ?: 'Vendor acara' }}</p>
                            </div>
                            <span class="ml-auto whitespace-nowrap text-sm font-semibold text-gray-700">Rp {{ number_format($bookingVendor->price, 0, ',', '.') }}</span>
                        </div>

                        @if($vendor?->vendor_category_id)
                            <div class="mt-3 flex flex-wrap items-center gap-2">
                                <select name="vendor_id" form="vendor-update-{{ $bookingVendor->id }}" class="min-w-[220px] flex-1 rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm text-gray-800 focus:outline-none focus:ring-2 focus:ring-brand-200">
                                    @foreach($vendors->where('vendor_category_id', $vendor->vendor_category_id) as $vendorOption)
                                        <option value="{{ $vendorOption->id }}" @selected($vendorOption->id === $vendor->id)>{{ $vendorOption->name }}</option>
                                    @endforeach
                                </select>
                                <x-button type="submit" form="vendor-update-{{ $bookingVendor->id }}" color="outline" class="shrink-0 justify-center"><i class="fas fa-save"></i> Simpan</x-button>
                            </div>
                        @endif
                    </div>
                @empty
                    <x-empty-state icon="fa-store" title="Belum ada vendor booking" text="Jalankan backfill vendor untuk menyalin vendor dari paket." />
                @endforelse

                @php
                    $additionalVendorGroups = $vendors
                        ->where('status', 'active')
                        ->filter(fn ($vendor) => $vendor->vendor_category_id)
                        ->groupBy('vendor_category_id');
                @endphp
                @if($additionalVendorGroups->isNotEmpty())
                    <div class="mt-5 border-t border-gray-100 pt-5">
                        <p class="mb-3 text-sm font-semibold text-gray-800">Tambah Vendor</p>
                        <div class="flex flex-wrap items-end gap-3">
                            <div class="min-w-[180px] flex-1">
                                <label for="additional_vendor_category" class="mb-1 block text-xs font-medium text-gray-600">Kategori Vendor</label>
                                <select id="additional_vendor_category" class="w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm text-gray-800 focus:outline-none focus:ring-2 focus:ring-brand-200">
                                    <option value="">Pilih kategori</option>
                                    @foreach($additionalVendorGroups as $categoryId => $categoryVendors)
                                        <option value="{{ $categoryId }}">{{ $categoryVendors->first()->category?->name ?? 'Tanpa kategori' }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="min-w-[220px] flex-1">
                                <label for="additional_vendor_id" class="mb-1 block text-xs font-medium text-gray-600">Vendor</label>
                                <select id="additional_vendor_id" name="vendor_id" form="additional-vendor-form" required disabled class="w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm text-gray-800 focus:outline-none focus:ring-2 focus:ring-brand-200">
                                    <option value="">Pilih kategori dahulu</option>
                                    @foreach($vendors->where('status', 'active')->filter(fn ($vendor) => $vendor->vendor_category_id) as $vendorOption)
                                        <option value="{{ $vendorOption->id }}" data-category="{{ $vendorOption->vendor_category_id }}">{{ $vendorOption->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <x-button type="submit" form="additional-vendor-form" class="shrink-0 justify-center"><i class="fas fa-plus"></i> Tambah</x-button>
                        </div>
                    </div>
                @endif
            </x-card>

            @php
                $addonRows = old('addons', $booking->addons->map(fn ($addon) => [
                    'name' => $addon->name,
                    'price' => $addon->price,
                ])->values()->all());
            @endphp
            @include('admin.bookings.partials.addons-fields', ['addonRows' => $addonRows])

            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <x-input name="event_date" id="event_date" label="Tanggal Acara" type="date" required :value="$booking->event_date?->format('Y-m-d')" />
                <x-input name="event_time" id="event_time" label="Jam Acara" type="time" :value="$booking->event_time ? substr((string) $booking->event_time, 0, 5) : ''" />
            </div>

            <x-input name="location" label="Lokasi Acara" placeholder="Alamat lokasi acara" :value="$booking->location" />

            <x-textarea name="notes" label="Catatan" placeholder="Catatan tambahan untuk booking ini..." :value="$booking->notes" />

            </div>
        </x-card>

            @include('admin.bookings.partials.survey-card', [
                'embedded' => true,
                'booking' => $booking,
                'weddingStages' => $weddingStages,
                'teamMembers' => $teamMembers,
            ])

            @include('admin.bookings.partials.fitting-card', [
                'embedded' => true,
                'booking' => $booking,
                'teamMembers' => $teamMembers,
            ])

            <div class="flex items-center gap-4 pt-5 border-t border-brand-100">
                <x-button href="{{ route('admin.bookings.show', $booking) }}" color="ghost" class="flex-1 justify-center">Batal</x-button>
                <x-button type="submit" class="flex-1 justify-center"><i class="fas fa-save"></i> Simpan Perubahan</x-button>
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
                const additionalVendorCategory = document.getElementById('additional_vendor_category');
                const additionalVendor = document.getElementById('additional_vendor_id');
                const paketOptions = Array.from(selectPaket.options).slice(1);

                function oneMonthBefore(value) {
                    const [year, month, day] = value.split('-').map(Number);
                    const targetMonth = month - 2;
                    const targetYear = year + Math.floor(targetMonth / 12);
                    const normalizedMonth = ((targetMonth % 12) + 12) % 12;
                    const lastDay = new Date(targetYear, normalizedMonth + 1, 0).getDate();
                    return `${targetYear}-${String(normalizedMonth + 1).padStart(2, '0')}-${String(Math.min(day, lastDay)).padStart(2, '0')}`;
                }

                eventDate.addEventListener('change', function() {
                    if (!eventDate.value) return;
                    const date = oneMonthBefore(eventDate.value);
                    surveyDate.value = date;
                    if (fittingDate) fittingDate.value = date;
                });

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

                if (additionalVendorCategory && additionalVendor) {
                    const vendorOptions = Array.from(additionalVendor.options).slice(1);

                    additionalVendorCategory.addEventListener('change', function() {
                        const categoryId = additionalVendorCategory.value;
                        additionalVendor.value = '';
                        additionalVendor.disabled = !categoryId;
                        vendorOptions.forEach(option => {
                            option.hidden = !categoryId || option.dataset.category !== categoryId;
                        });
                    });
                }
            });
        </script>
    @endpush
@endsection
