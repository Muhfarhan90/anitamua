@extends('layouts.app')

@section('title', 'Edit Booking')

@section('content')
    <x-page-header :title="'Edit Booking - '.$booking->code" />

    <form action="{{ route('admin.bookings.update', $booking) }}" method="POST" enctype="multipart/form-data" class="max-w-6xl space-y-5">
        <x-card title="Data Booking" title-icon="fa-calendar-check" padding="p-6">
            <div class="space-y-5">
            @csrf
            @method('PATCH')

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
                    <div class="border-b border-gray-100 py-4 first:pt-0 last:border-0 last:pb-0" data-vendor-card data-vendor-category-id="{{ $vendor?->vendor_category_id ?? '' }}" data-vendor-base-price="{{ $bookingVendor->price }}">
                        <div class="flex items-center gap-3">
                            @if($vendor?->logo)
                                <img src="{{ asset('storage/'.$vendor->logo) }}" alt="Logo {{ $vendor->name }}" class="h-10 w-10 shrink-0 rounded-full border border-brand-100 bg-white object-cover">
                            @else
                                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-brand-50 text-brand"><i class="fas fa-store"></i></div>
                            @endif
                            <div class="min-w-0">
                                <p class="font-semibold text-gray-800" data-vendor-name>{{ $vendor?->name ?? 'Vendor' }}</p>
                                <p class="text-xs text-brand" data-vendor-role>{{ $bookingVendor->role ?: $vendor?->category?->name ?: 'Vendor acara' }}</p>
                            </div>
                            <div class="ml-auto text-right"><span class="whitespace-nowrap text-sm font-semibold text-gray-700" data-vendor-total>Rp {{ number_format($bookingVendor->total_price, 0, ',', '.') }}</span><p class="text-[11px] text-gray-400" data-vendor-base>Dasar Rp {{ number_format($bookingVendor->price, 0, ',', '.') }}</p></div>
                        </div>

                        @if($vendor?->vendor_category_id)
                            <div class="mt-3 flex flex-wrap items-center gap-2">
                                <select name="vendor_changes[{{ $bookingVendor->id }}][vendor_id]" data-vendor-change class="min-w-[220px] flex-1 rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm text-gray-800 focus:outline-none focus:ring-2 focus:ring-brand-200">
                                    @foreach($vendors->where('vendor_category_id', $vendor->vendor_category_id) as $vendorOption)
                                        <option value="{{ $vendorOption->id }}" data-name="{{ $vendorOption->name }}" data-role="{{ $vendorOption->category?->name ?? 'Vendor acara' }}" data-price="{{ $vendorOption->price }}" @selected((string) $vendorOption->id === (string) old("vendor_changes.{$bookingVendor->id}.vendor_id", $vendor->id))>{{ $vendorOption->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @endif
                        @php
                            $oldVendorAdditions = old('vendor_additions', []);
                            $customRows = is_array($oldVendorAdditions) && array_key_exists($bookingVendor->id, $oldVendorAdditions)
                                ? ($oldVendorAdditions[$bookingVendor->id] ?? [])
                                : ($bookingVendor->custom_additions ?? []);
                        @endphp
                        <div class="mt-4 rounded-xl border border-gray-100 bg-gray-50/60 p-3" data-vendor-additions data-vendor-id="{{ $bookingVendor->id }}">
                            <input type="hidden" name="vendor_additions_present[{{ $bookingVendor->id }}]" value="1">
                            <div class="mb-3 flex items-center justify-between gap-3"><div><p class="text-sm font-semibold text-gray-700">Tambahan Custom</p><p class="text-xs text-gray-400">Biaya tambahan khusus vendor ini.</p></div><button type="button" data-add-vendor-addition class="inline-flex items-center gap-1 rounded-lg bg-brand px-3 py-2 text-xs font-semibold text-white"><i class="fas fa-plus"></i> Tambah</button></div>
                            <div class="space-y-2" data-vendor-addition-list>
                                @foreach($customRows as $index => $addition)
                                    <div class="booking-addon-row flex items-end gap-3" data-vendor-addition-row data-index="{{ $index }}">
                                        <div class="addon-name-field">
                                            <label class="mb-1 block text-xs font-medium text-gray-600">Nama Tambahan</label>
                                            <input type="text" name="vendor_additions[{{ $bookingVendor->id }}][{{ $index }}][name]" value="{{ $addition['name'] ?? '' }}" required maxlength="255" placeholder="Contoh: Extra Touch Up" class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-800 focus:outline-none focus:ring-2 focus:ring-brand-200">
                                        </div>
                                        <div class="addon-price-field">
                                            <label class="mb-1 block text-xs font-medium text-gray-600">Harga</label>
                                            <input type="text" inputmode="numeric" data-vendor-money-input name="vendor_additions[{{ $bookingVendor->id }}][{{ $index }}][price]" value="{{ ($addition['price'] ?? '') === '' ? '' : number_format((float) $addition['price'], 0, '.', '') }}" required pattern="[0-9.]*" placeholder="250.000" class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-800 focus:outline-none focus:ring-2 focus:ring-brand-200">
                                        </div>
                                        <button type="button" data-remove-vendor-addition class="addon-remove-button inline-flex h-10 flex-shrink-0 items-center justify-center rounded-lg border border-red-200 px-3 text-red-500 transition-colors hover:bg-red-50" aria-label="Hapus tambahan"><i class="fas fa-trash"></i></button>
                                    </div>
                                @endforeach
                            </div>
                            <p data-vendor-addition-empty class="{{ count($customRows) ? 'hidden' : '' }} py-2 text-center text-xs text-gray-400">Belum ada tambahan custom.</p>
                            @foreach($errors->get("vendor_additions.{$bookingVendor->id}.*.name") as $message)<p class="mt-2 text-xs text-red-600">{{ $message }}</p>@endforeach
                            @foreach($errors->get("vendor_additions.{$bookingVendor->id}.*.price") as $message)<p class="mt-2 text-xs text-red-600">{{ $message }}</p>@endforeach
                            <template data-vendor-addition-template>
                                <div class="booking-addon-row flex items-end gap-3" data-vendor-addition-row>
                                    <div class="addon-name-field">
                                        <label class="mb-1 block text-xs font-medium text-gray-600">Nama Tambahan</label>
                                        <input type="text" required maxlength="255" placeholder="Contoh: Extra Touch Up" class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-800 focus:outline-none focus:ring-2 focus:ring-brand-200">
                                    </div>
                                    <div class="addon-price-field">
                                        <label class="mb-1 block text-xs font-medium text-gray-600">Harga</label>
                                        <input type="text" inputmode="numeric" data-vendor-money-input required pattern="[0-9.]*" placeholder="250.000" class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-800 focus:outline-none focus:ring-2 focus:ring-brand-200">
                                    </div>
                                    <button type="button" data-remove-vendor-addition class="addon-remove-button inline-flex h-10 flex-shrink-0 items-center justify-center rounded-lg border border-red-200 px-3 text-red-500 transition-colors hover:bg-red-50" aria-label="Hapus tambahan"><i class="fas fa-trash"></i></button>
                                </div>
                            </template>
                        </div>
                    </div>
                @empty
                    <x-empty-state icon="fa-store" title="Belum ada vendor booking" text="Jalankan backfill vendor untuk menyalin vendor dari paket." />
                @endforelse

                @php
                    $existingVendorCategoryIds = $booking->bookingVendors
                        ->map(fn ($bookingVendor) => $bookingVendor->vendor?->vendor_category_id)
                        ->filter()
                        ->unique();
                    $oldAdditionalVendorIds = array_values(array_filter((array) old('additional_vendor_ids', [])));
                    $additionalVendorGroups = $vendors
                        ->where('status', 'active')
                        ->filter(fn ($vendor) => $vendor->vendor_category_id)
                        ->reject(fn ($vendor) => $existingVendorCategoryIds->contains($vendor->vendor_category_id))
                        ->groupBy('vendor_category_id');
                @endphp
                <div class="space-y-2" data-pending-vendor-list>
                    @foreach($oldAdditionalVendorIds as $pendingVendorId)
                        @php $pendingVendor = $vendors->firstWhere('id', (int) $pendingVendorId); @endphp
                        @if($pendingVendor)
                            <div class="flex items-center gap-3 rounded-lg border border-dashed border-brand-200 bg-brand-50/40 px-3 py-2" data-pending-vendor-row data-vendor-id="{{ $pendingVendor->id }}" data-category-id="{{ $pendingVendor->vendor_category_id }}">
                                <input type="hidden" name="additional_vendor_ids[]" value="{{ $pendingVendor->id }}">
                                <div class="min-w-0 flex-1"><p class="truncate text-sm font-semibold text-gray-700">{{ $pendingVendor->name }}</p><p class="text-xs text-brand">{{ $pendingVendor->category?->name ?? 'Vendor acara' }} · Menunggu disimpan</p></div>
                                <span class="whitespace-nowrap text-xs font-semibold text-gray-600">Rp {{ number_format($pendingVendor->price, 0, ',', '.') }}</span>
                                <button type="button" data-remove-pending-vendor class="inline-flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-lg border border-red-200 text-red-500 hover:bg-red-50" aria-label="Hapus vendor"><i class="fas fa-trash"></i></button>
                            </div>
                        @endif
                    @endforeach
                </div>
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
                                <select id="additional_vendor_id" required disabled class="w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm text-gray-800 focus:outline-none focus:ring-2 focus:ring-brand-200">
                                    <option value="">Pilih kategori dahulu</option>
                                    @foreach($vendors->where('status', 'active')->filter(fn ($vendor) => $vendor->vendor_category_id) as $vendorOption)
                                        <option value="{{ $vendorOption->id }}" data-category="{{ $vendorOption->vendor_category_id }}" data-name="{{ $vendorOption->name }}" data-role="{{ $vendorOption->category?->name ?? 'Vendor acara' }}" data-price="{{ $vendorOption->price }}">{{ $vendorOption->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <x-button type="button" id="add-pending-vendor" class="shrink-0 justify-center" disabled><i class="fas fa-plus"></i> Tambah</x-button>
                        </div>
                        <p data-additional-vendor-feedback class="mt-2 hidden text-xs text-red-600"></p>
                        @error('additional_vendor_ids')<p class="mt-2 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>
                @endif
                <template id="pending-vendor-template">
                    <div class="flex items-center gap-3 rounded-lg border border-dashed border-brand-200 bg-brand-50/40 px-3 py-2" data-pending-vendor-row>
                        <input type="hidden" name="additional_vendor_ids[]">
                        <div class="min-w-0 flex-1"><p class="truncate text-sm font-semibold text-gray-700" data-pending-vendor-name></p><p class="text-xs text-brand" data-pending-vendor-role></p></div>
                        <span class="whitespace-nowrap text-xs font-semibold text-gray-600" data-pending-vendor-price></span>
                        <button type="button" data-remove-pending-vendor class="inline-flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-lg border border-red-200 text-red-500 hover:bg-red-50" aria-label="Hapus vendor"><i class="fas fa-trash"></i></button>
                    </div>
                </template>
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

            <x-select name="referral_source" label="Tahu Anita dari mana?" required>
                <option value="">— Pilih sumber informasi —</option>
                @foreach($referralSources as $source)<option value="{{ $source }}" @selected(old('referral_source', $booking->referral_source) === $source)>{{ $source }}</option>@endforeach
            </x-select>

            <x-textarea name="notes" label="Catatan" placeholder="Catatan tambahan untuk booking ini..." :value="$booking->notes" />

            </div>
        </x-card>

            @include('admin.bookings.partials.survey-card', [
                'embedded' => true,
                'booking' => $booking,
                'weddingStages' => $weddingStages,
                'tents' => $tents,
                'entranceGates' => $entranceGates,
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
                const selectJenis = document.getElementById('package_type');
                const selectPaket = document.getElementById('package_id');
                const additionalVendorCategory = document.getElementById('additional_vendor_category');
                const additionalVendor = document.getElementById('additional_vendor_id');
                const addPendingVendor = document.getElementById('add-pending-vendor');
                const pendingVendorList = document.querySelector('[data-pending-vendor-list]');
                const pendingVendorTemplate = document.getElementById('pending-vendor-template');
                const additionalVendorFeedback = document.querySelector('[data-additional-vendor-feedback]');
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

                function formatRupiah(value) {
                    return 'Rp ' + Number(value || 0).toLocaleString('id-ID');
                }

                if (additionalVendorCategory && additionalVendor && addPendingVendor && pendingVendorList && pendingVendorTemplate) {
                    const vendorOptions = Array.from(additionalVendor.options).slice(1);
                    const categoryOptions = Array.from(additionalVendorCategory.options).slice(1);

                    function usedVendorCategories() {
                        return new Set([
                            ...Array.from(document.querySelectorAll('[data-vendor-card][data-vendor-category-id]'))
                                .map(card => card.dataset.vendorCategoryId)
                                .filter(Boolean),
                            ...Array.from(pendingVendorList.querySelectorAll('[data-pending-vendor-row]'))
                                .map(row => row.dataset.categoryId)
                                .filter(Boolean),
                        ]);
                    }

                    function syncAdditionalVendorOptions() {
                        const usedCategories = usedVendorCategories();
                        categoryOptions.forEach(option => {
                            option.hidden = usedCategories.has(option.value);
                            option.disabled = option.hidden;
                        });

                        if (usedCategories.has(additionalVendorCategory.value)) {
                            additionalVendorCategory.value = '';
                        }

                        const categoryId = additionalVendorCategory.value;
                        additionalVendor.value = '';
                        additionalVendor.disabled = !categoryId;
                        vendorOptions.forEach(option => {
                            option.hidden = !categoryId || option.dataset.category !== categoryId;
                        });
                        addPendingVendor.disabled = !categoryId;
                        additionalVendorFeedback?.classList.add('hidden');
                    }

                    function syncSelectedVendor() {
                        const categoryId = additionalVendorCategory.value;
                        const selectedVendor = additionalVendor.value;
                        addPendingVendor.disabled = !categoryId || !selectedVendor;
                    }

                    additionalVendorCategory.addEventListener('change', function() {
                        syncAdditionalVendorOptions();
                    });
                    additionalVendor.addEventListener('change', syncSelectedVendor);

                    addPendingVendor.addEventListener('click', function() {
                        const option = additionalVendor.selectedOptions[0];
                        const categoryId = additionalVendorCategory.value;
                        if (!option?.value || !categoryId) return;

                        if (usedVendorCategories().has(categoryId)) {
                            additionalVendorFeedback.textContent = 'Kategori vendor ini sudah ada di booking.';
                            additionalVendorFeedback.classList.remove('hidden');
                            syncAdditionalVendorOptions();
                            return;
                        }

                        const row = pendingVendorTemplate.content.firstElementChild.cloneNode(true);
                        row.dataset.vendorId = option.value;
                        row.dataset.categoryId = categoryId;
                        row.querySelector('input').value = option.value;
                        row.querySelector('[data-pending-vendor-name]').textContent = option.dataset.name;
                        row.querySelector('[data-pending-vendor-role]').textContent = `${option.dataset.role} · Menunggu disimpan`;
                        row.querySelector('[data-pending-vendor-price]').textContent = formatRupiah(option.dataset.price);
                        pendingVendorList.appendChild(row);
                        syncAdditionalVendorOptions();
                    });

                    pendingVendorList.addEventListener('click', function(event) {
                        const button = event.target.closest('[data-remove-pending-vendor]');
                        if (!button) return;
                        button.closest('[data-pending-vendor-row]').remove();
                        syncAdditionalVendorOptions();
                    });

                    syncAdditionalVendorOptions();
                }

                document.querySelectorAll('[data-vendor-additions]').forEach(function(section) {
                    const list = section.querySelector('[data-vendor-addition-list]');
                    const template = section.querySelector('[data-vendor-addition-template]');
                    const empty = section.querySelector('[data-vendor-addition-empty]');
                    const form = section.closest('form');
                    const indexes = Array.from(list.querySelectorAll('[data-index]')).map(row => Number(row.dataset.index));
                    let nextIndex = indexes.length ? Math.max(...indexes) + 1 : 0;

                    function syncEmpty() {
                        empty.classList.toggle('hidden', list.children.length > 0);
                    }

                    function formatMoney(input) {
                        const digits = input.value.replace(/[,.]\d{1,2}$/, '').replace(/\D/g, '');
                        input.value = digits.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
                    }

                    function updateVendorSummary() {
                        const card = section.closest('[data-vendor-card]');
                        const vendorSelect = card.querySelector('[data-vendor-change]');
                        const selectedVendor = vendorSelect?.selectedOptions[0];
                        const basePrice = Number(selectedVendor?.dataset.price ?? card.dataset.vendorBasePrice ?? 0);
                        const customTotal = Array.from(list.querySelectorAll('[data-vendor-money-input]'))
                            .reduce((total, input) => total + Number(input.value.replace(/\D/g, '') || 0), 0);

                        card.querySelector('[data-vendor-total]').textContent = formatRupiah(basePrice + customTotal);
                        card.querySelector('[data-vendor-base]').textContent = `Dasar ${formatRupiah(basePrice)}`;
                        if (selectedVendor) {
                            card.querySelector('[data-vendor-name]').textContent = selectedVendor.dataset.name;
                            card.querySelector('[data-vendor-role]').textContent = selectedVendor.dataset.role;
                        }
                    }

                    list.querySelectorAll('[data-vendor-money-input]').forEach(formatMoney);
                    list.addEventListener('input', function(event) {
                        if (event.target.matches('[data-vendor-money-input]')) {
                            formatMoney(event.target);
                            updateVendorSummary();
                        }
                    });
                    form.addEventListener('submit', function() {
                        list.querySelectorAll('[data-vendor-money-input]').forEach(input => {
                            input.value = input.value.replaceAll('.', '');
                        });
                    });

                    section.closest('[data-vendor-card]').querySelector('[data-vendor-change]')?.addEventListener('change', updateVendorSummary);
                    updateVendorSummary();

                    section.querySelector('[data-add-vendor-addition]').addEventListener('click', function() {
                        const row = template.content.firstElementChild.cloneNode(true);
                        const inputs = row.querySelectorAll('input');
                        const vendorId = section.dataset.vendorId;
                        inputs[0].name = `vendor_additions[${vendorId}][${nextIndex}][name]`;
                        inputs[1].name = `vendor_additions[${vendorId}][${nextIndex}][price]`;
                        nextIndex++;
                        list.appendChild(row);
                        syncEmpty();
                        inputs[0].focus();
                    });

                    list.addEventListener('click', function(event) {
                        const button = event.target.closest('[data-remove-vendor-addition]');
                        if (!button) return;
                        button.closest('[data-vendor-addition-row]').remove();
                        syncEmpty();
                    });
                });
            });
        </script>
    @endpush
@endsection
