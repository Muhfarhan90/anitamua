@extends('layouts.app')

@section('title', 'Edit Booking')

@section('content')
    <x-page-header :title="'Edit Booking - '.$booking->code">
        <x-slot:actions>
            <a href="{{ route('admin.bookings.show', $booking) }}" class="text-sm font-semibold text-brand hover:text-brand-dark no-underline inline-flex items-center gap-1.5">
                <i class="fas fa-arrow-left text-xs"></i> Kembali
            </a>
        </x-slot:actions>
    </x-page-header>

    <div class="space-y-5">
    <form action="{{ route('admin.bookings.update', $booking) }}" method="POST" enctype="multipart/form-data" class="space-y-5">
        <x-card title="Data Booking" title-icon="fa-calendar-check" padding="p-5" data-fieldwork-card>
            <x-slot:actions>
                <button type="button" data-fieldwork-reset class="inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1.5 text-xs font-semibold text-gray-500 hover:bg-gray-100 hover:text-gray-700"><i class="fas fa-rotate-left"></i> Reset</button>
                <button type="button" data-fieldwork-toggle aria-expanded="true" class="inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1.5 text-xs font-semibold text-brand hover:bg-brand-50"><i class="fas fa-eye-slash" data-fieldwork-toggle-icon></i> <span data-fieldwork-toggle-label>Hide</span></button>
            </x-slot:actions>
            <div class="space-y-5" data-fieldwork-content data-fieldwork-reset-scope>
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
                    @foreach($packageTypes as $packageType)
                        <option value="{{ $packageType->id }}">{{ $packageType->name }}</option>
                    @endforeach
                </select>
            </div>

            <x-select name="package_id" id="package_id" label="Pilih Paket" required placeholder="- Pilih Jenis Paket dahulu -" data-initial-package-id="{{ $booking->package_id }}">
                @foreach ($packages ?? [] as $package)
                    <option value="{{ $package->id }}" data-type="{{ $package->package_type_id }}" data-price="{{ $package->price }}" data-survey="{{ (int) ($package->packageType?->is_data_survey ?? true) }}" data-fitting="{{ (int) ($package->packageType?->is_data_fitting ?? true) }}" @selected(old('package_id', $booking->package_id) == $package->id)>
                        {{ $package->name }}@if ($package->sub_type)
                            ({{ $subTypeLabels[$package->sub_type] ?? $package->sub_type }})
                        @endif - Rp {{ number_format($package->price, 0, ',', '.') }}
                    </option>
                @endforeach
            </x-select>
            <p class="hidden rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-700" data-package-vendor-warning>
                Paket diganti. Saat disimpan, daftar vendor akan mengikuti vendor bawaan paket baru.
            </p>

            <x-card title="Daftar Vendor" title-icon="fa-store" padding="p-5">
                @php
                    $bookingVendorGroups = $booking->bookingVendors->groupBy(fn ($bookingVendor) => $bookingVendor->vendor?->vendor_category_id ?? 'uncategorized');
                @endphp
                <div class="space-y-4" data-vendor-category-list-root>
                    @forelse($bookingVendorGroups as $categoryId => $bookingVendors)
                        @php
                            $categoryVendor = $bookingVendors->first()?->vendor;
                            $categoryName = $categoryVendor?->category?->name ?? $bookingVendors->first()?->role ?? 'Vendor lainnya';
                        @endphp
                        <section class="overflow-hidden rounded-xl border border-brand-100 bg-white" data-vendor-category-card data-category-id="{{ $categoryId }}">
                            <div class="flex flex-wrap items-center gap-3 bg-brand-50/70 px-4 py-3">
                                <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-white text-brand shadow-sm"><i class="fas fa-store"></i></span>
                                <div class="min-w-0 flex-1">
                                    <p class="font-semibold text-gray-800" data-vendor-category-name>{{ $categoryName }}</p>
                                    <p class="text-xs text-gray-500"><span data-vendor-category-count>{{ $bookingVendors->count() }}</span> vendor dipilih</p>
                                </div>
                                @if($categoryId !== 'uncategorized')
                                    <button type="button" data-remove-vendor-category class="inline-flex items-center gap-1 rounded-lg border border-red-200 bg-white px-3 py-2 text-xs font-semibold text-red-500 hover:bg-red-50"><i class="fas fa-trash"></i> Hapus Kategori</button>
                                @endif
                            </div>
                            <div class="space-y-3 p-3" data-vendor-category-list>
                                @foreach($bookingVendors as $bookingVendor)
                                    @php
                                        $vendor = $bookingVendor->vendor;
                                        $oldVendorAdditions = old('vendor_additions', []);
                                        $customRows = is_array($oldVendorAdditions) && array_key_exists($bookingVendor->id, $oldVendorAdditions)
                                            ? ($oldVendorAdditions[$bookingVendor->id] ?? [])
                                            : ($bookingVendor->custom_additions ?? []);
                                    @endphp
                                    <div class="rounded-lg border border-gray-100 bg-white p-3 shadow-sm" data-vendor-card data-vendor-id="{{ $vendor?->id ?? '' }}" data-booking-vendor-id="{{ $bookingVendor->id }}" data-vendor-category-id="{{ $vendor?->vendor_category_id ?? '' }}" data-vendor-base-price="{{ $bookingVendor->price }}">
                                        <div class="grid gap-3 lg:grid-cols-[minmax(0,1fr)_auto] lg:items-center">
                                            <div class="flex min-w-0 items-center gap-3">
                                                @if($vendor?->logo)
                                                    <a href="{{ asset('storage/'.$vendor->logo) }}" onclick="openProof(event, this.href)" aria-label="Perbesar logo {{ $vendor->name }}" class="shrink-0 cursor-zoom-in">
                                                        <img src="{{ asset('storage/'.$vendor->logo) }}" alt="Logo {{ $vendor->name }}" class="h-9 w-9 rounded-full border border-brand-100 bg-white object-cover">
                                                    </a>
                                                @else
                                                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-brand-50 text-brand"><i class="fas fa-store"></i></span>
                                                @endif
                                                <div class="min-w-0"><p class="truncate font-semibold text-gray-800" data-vendor-name>{{ $vendor?->name ?? 'Vendor' }}</p><p class="sr-only" data-vendor-role>{{ $bookingVendor->role ?: $categoryName }}</p><p class="text-xs text-gray-400" data-vendor-base>Dasar Rp {{ number_format($bookingVendor->price, 0, ',', '.') }}</p></div>
                                            </div>
                                            <div class="flex items-center justify-between gap-3 lg:justify-end"><span class="whitespace-nowrap text-sm font-semibold text-gray-800" data-vendor-total>Rp {{ number_format($bookingVendor->total_price, 0, ',', '.') }}</span><button type="button" data-remove-vendor class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-red-200 text-red-500 transition-colors hover:bg-red-50" aria-label="Hapus vendor {{ $vendor?->name ?? '' }}"><i class="fas fa-trash"></i></button></div>
                                        </div>
                                        <input type="hidden" name="vendor_additions_present[{{ $bookingVendor->id }}]" value="1">
                                        @include('admin.bookings.partials.vendor-additions-fields', ['inputPrefix' => 'vendor_additions', 'vendorId' => $bookingVendor->id, 'rows' => $customRows])
                                        @foreach($errors->get("vendor_additions.{$bookingVendor->id}.*.name") as $message)<p class="mt-2 text-xs text-red-600">{{ $message }}</p>@endforeach
                                        @foreach($errors->get("vendor_additions.{$bookingVendor->id}.*.price") as $message)<p class="mt-2 text-xs text-red-600">{{ $message }}</p>@endforeach
                                    </div>
                                @endforeach
                            </div>
                        </section>
                    @empty
                        <div data-vendor-empty><x-empty-state icon="fa-store" title="Belum ada vendor booking" text="Tambahkan vendor dari pilihan di bawah." /></div>
                    @endforelse
                </div>

                @php
                    $oldAdditionalVendorIds = array_values(array_filter((array) old('additional_vendor_ids', [])));
                    $oldAdditionalVendorAdditions = old('additional_vendor_additions', []);
                    $additionalVendorGroups = $vendors
                        ->where('status', 'active')
                        ->filter(fn ($vendor) => $vendor->vendor_category_id)
                        ->groupBy('vendor_category_id');
                @endphp
                <div class="hidden space-y-2" data-pending-vendor-list>
                    @foreach($oldAdditionalVendorIds as $pendingVendorId)
                        @php $pendingVendor = $vendors->firstWhere('id', (int) $pendingVendorId); @endphp
                        @if($pendingVendor)
                            @php $pendingAdditions = $oldAdditionalVendorAdditions[$pendingVendor->id] ?? []; @endphp
                            <div class="rounded-xl border border-dashed border-brand-200 bg-brand-50/40 p-3" data-pending-vendor-row data-vendor-card data-vendor-id="{{ $pendingVendor->id }}" data-vendor-category-id="{{ $pendingVendor->vendor_category_id }}" data-category-id="{{ $pendingVendor->vendor_category_id }}" data-vendor-base-price="{{ $pendingVendor->price }}">
                                <input type="hidden" name="additional_vendor_ids[]" value="{{ $pendingVendor->id }}">
                                <div class="flex items-center gap-3"><div class="min-w-0 flex-1"><p class="truncate text-sm font-semibold text-gray-700" data-pending-vendor-name>{{ $pendingVendor->name }}</p><p class="text-xs text-brand" data-pending-vendor-role>{{ $pendingVendor->category?->name ?? 'Vendor acara' }} · Menunggu disimpan</p></div><span class="whitespace-nowrap text-sm font-semibold text-gray-700" data-pending-vendor-price data-vendor-total>Rp {{ number_format($pendingVendor->price, 0, ',', '.') }}</span><button type="button" data-remove-pending-vendor class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-red-200 text-red-500 hover:bg-red-50" aria-label="Hapus vendor"><i class="fas fa-trash"></i></button></div>
                                <p class="hidden" data-vendor-base></p>
                                @include('admin.bookings.partials.vendor-additions-fields', ['inputPrefix' => 'additional_vendor_additions', 'vendorId' => $pendingVendor->id, 'rows' => $pendingAdditions])
                            </div>
                        @endif
                    @endforeach
                </div>
                @if($additionalVendorGroups->isNotEmpty())
                    <div class="mt-5 border-t border-gray-100 pt-5" data-additional-vendor-section>
                        <p class="mb-3 text-sm font-semibold text-gray-800">Tambah Vendor</p>
                        <div class="flex flex-wrap items-end gap-3">
                            <div class="min-w-[180px] flex-1">
                                <label for="additional_vendor_category" class="mb-1 block text-xs font-medium text-gray-600">Kategori Vendor</label>
                                <select id="additional_vendor_category" class="w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm text-gray-800 focus:outline-none focus:ring-2 focus:ring-brand-200">
                                    <option value="">Pilih kategori</option>
                                    @foreach($additionalVendorGroups as $categoryId => $categoryVendors)
                                        <option value="{{ $categoryId }}" data-category-name="{{ $categoryVendors->first()->category?->name ?? 'Tanpa kategori' }}">{{ $categoryVendors->first()->category?->name ?? 'Tanpa kategori' }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="min-w-[220px] flex-1">
                                <label for="additional_vendor_id" class="mb-1 block text-xs font-medium text-gray-600">Vendor</label>
                                <div class="relative" data-vendor-picker>
                                    <button type="button" id="additional_vendor_picker_button" disabled aria-haspopup="listbox" aria-expanded="false" class="flex w-full items-center justify-between rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-left text-sm text-gray-800 focus:outline-none focus:ring-2 focus:ring-brand-200 disabled:cursor-not-allowed disabled:text-gray-400">
                                        <span data-vendor-picker-label>Pilih kategori dahulu</span>
                                        <i class="fas fa-chevron-down text-xs text-gray-400"></i>
                                    </button>
                                    <div id="additional_vendor_picker_menu" data-vendor-picker-menu role="listbox" class="absolute z-30 mt-1 hidden max-h-64 w-full overflow-y-auto rounded-xl border border-gray-200 bg-white p-1 shadow-lg">
                                        @foreach($vendors->where('status', 'active')->filter(fn ($vendor) => $vendor->vendor_category_id) as $vendorOption)
                                            <button type="button" role="option" data-vendor-picker-option data-vendor-id="{{ $vendorOption->id }}" data-category="{{ $vendorOption->vendor_category_id }}" class="flex w-full items-center gap-2 rounded-lg px-2 py-2 text-left hover:bg-brand-50 disabled:cursor-not-allowed disabled:opacity-40">
                                                @if($vendorOption->logo)
                                                    <img src="{{ asset('storage/'.$vendorOption->logo) }}" alt="" class="h-8 w-8 rounded-lg object-cover">
                                                @else
                                                    <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-brand-50 text-brand"><i class="fas fa-store text-xs"></i></span>
                                                @endif
                                                <span class="min-w-0"><span class="block truncate text-sm font-semibold text-gray-700">{{ $vendorOption->name }}</span><span class="block truncate text-xs text-gray-400">{{ $vendorOption->category?->name ?? 'Vendor acara' }}</span></span>
                                            </button>
                                        @endforeach
                                    </div>
                                </div>
                                <select id="additional_vendor_id" required disabled class="sr-only">
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
                    <div class="rounded-xl border border-dashed border-brand-200 bg-brand-50/40 p-3" data-pending-vendor-row data-vendor-card>
                        <input type="hidden" name="additional_vendor_ids[]">
                        <div class="flex items-center gap-3"><div class="min-w-0 flex-1"><p class="truncate text-sm font-semibold text-gray-700" data-pending-vendor-name></p><p class="text-xs text-brand" data-pending-vendor-role></p></div><span class="whitespace-nowrap text-sm font-semibold text-gray-700" data-pending-vendor-price data-vendor-total></span><button type="button" data-remove-pending-vendor class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-red-200 text-red-500 hover:bg-red-50" aria-label="Hapus vendor"><i class="fas fa-trash"></i></button></div>
                        <p class="hidden" data-vendor-base></p>
                        @include('admin.bookings.partials.vendor-additions-fields', ['inputPrefix' => 'additional_vendor_additions', 'vendorId' => '', 'rows' => []])
                    </div>
                </template>
                <div data-removed-vendor-inputs></div>
                <template id="vendor-category-template">
                    <section class="rounded-xl border border-gray-200 bg-gray-50/40 p-3" data-vendor-category-card>
                        <div class="mb-3 flex items-center gap-3 border-b border-gray-100 pb-3">
                            <div class="min-w-0 flex-1"><p class="font-semibold text-gray-800" data-vendor-category-name></p><p class="text-xs text-gray-400"><span data-vendor-category-count>0</span> vendor dalam kategori ini</p></div>
                            <button type="button" data-remove-vendor-category class="inline-flex items-center gap-1 rounded-lg border border-red-200 px-3 py-2 text-xs font-semibold text-red-500 hover:bg-red-50"><i class="fas fa-trash"></i> Hapus Kategori</button>
                        </div>
                        <div class="space-y-3" data-vendor-category-list></div>
                    </section>
                </template>
            </x-card>

            @php
                $addonRows = old('addons', $booking->addons->map(fn ($addon) => [
                    'name' => $addon->name,
                    'price' => $addon->price,
                ])->values()->all());
            @endphp
            @include('admin.bookings.partials.addons-fields', ['addonRows' => $addonRows])

            @include('admin.bookings.partials.discounts-fields', ['discountRows' => old('discounts', $booking->discount_lines)])

            @include('admin.bookings.partials.bonuses-fields', ['bonusRows' => old('bonuses', $booking->bonuses ?? [])])

            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <x-input name="event_date" id="event_date" label="Tanggal Acara" type="date" required :value="$booking->event_date?->format('Y-m-d')" />
                <x-input name="event_time" id="event_time" label="Jam Acara" type="time" :value="$booking->event_time ? substr((string) $booking->event_time, 0, 5) : ''" />
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <x-input name="location" label="Lokasi Acara" placeholder="Alamat lokasi acara" :value="$booking->location" />
                <x-input name="maps_url" label="Link Maps" placeholder="https://maps.app.goo.gl/..." :value="$booking->maps_url" />
            </div>

            <x-select name="referral_source" label="Tahu Anita dari mana?" required>
                <option value="">— Pilih sumber informasi —</option>
                @foreach($referralSources as $source)<option value="{{ $source }}" @selected(old('referral_source', $booking->referral_source) === $source)>{{ $source }}</option>@endforeach
            </x-select>

            <x-textarea name="notes" label="Catatan" placeholder="Catatan tambahan untuk booking ini..." :value="$booking->notes" />

            </div>
            <div class="border-t border-gray-100 pt-5 mt-5">
                <x-button type="submit"><i class="fas fa-save"></i> Perbarui Booking</x-button>
            </div>
        </x-card>
    </form>

            <div data-package-section="survey" data-initial-enabled="{{ (int) $booking->allowsSurvey() }}" @if(!$booking->allowsSurvey()) class="hidden" @endif>
            @include('admin.bookings.partials.survey-card', [
                'booking' => $booking,
                'showScheduleDate' => true,
                'weddingStages' => $weddingStages,
                'tents' => $tents,
                'entranceGates' => $entranceGates,
                'teamMembers' => $teamMembers,
            ])
            </div>

            <div data-package-section="fitting" data-initial-enabled="{{ (int) $booking->allowsFitting() }}" @if(!$booking->allowsFitting()) class="hidden" @endif>
            @include('admin.bookings.partials.fitting-card', [
                'booking' => $booking,
                'teamMembers' => $teamMembers,
            ])
            </div>
    </div>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const selectJenis = document.getElementById('package_type');
                const selectPaket = document.getElementById('package_id');
                const paketOptions = Array.from(selectPaket.options).slice(1);
                function updateFieldworkSections() {
                    const selected = selectPaket.selectedOptions[0];
                    document.querySelectorAll('[data-package-section]').forEach(section => {
                        const enabled = selected?.value
                            ? selected.dataset[section.dataset.packageSection] === '1'
                            : !selectJenis.value && section.dataset.initialEnabled === '1';
                        section.classList.toggle('hidden', !enabled);
                    });
                }
                selectPaket.addEventListener('change', updateFieldworkSections);
                const packageVendorWarning = document.querySelector('[data-package-vendor-warning]');
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
                selectJenis.addEventListener('change', updateFieldworkSections);
                selectPaket.addEventListener('change', function () {
                    packageVendorWarning?.classList.toggle('hidden', String(selectPaket.value) === String(selectPaket.dataset.initialPackageId));
                });

                const preselected = selectPaket.selectedOptions[0];
                if (preselected && preselected.value) {
                    selectJenis.value = preselected.dataset.type || '';
                    applyFilter();
                }
                updateFieldworkSections();

                function formatRupiah(value) {
                    return 'Rp ' + Number(value || 0).toLocaleString('id-ID');
                }

                function confirmDeferredRemoval(message) {
                    return window.confirm(`${message}\n\nPerubahan baru diterapkan setelah Anda menekan Simpan Perubahan.`);
                }

                const vendorCategoryRoot = document.querySelector('[data-vendor-category-list-root]');
                const vendorCategoryTemplate = document.getElementById('vendor-category-template');
                const removedVendorInputs = document.querySelector('[data-removed-vendor-inputs]');

                function appendRemovedVendorInput(name, value) {
                    if (!removedVendorInputs || !value || Array.from(removedVendorInputs.querySelectorAll(`input[name="${name}"]`)).some(input => input.value === String(value))) return;
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = name;
                    input.value = value;
                    removedVendorInputs.appendChild(input);
                }

                function categoryIdFor(row) {
                    return row.dataset.vendorCategoryId || row.dataset.categoryId || 'uncategorized';
                }

                function categoryNameFor(row) {
                    return (row.querySelector('[data-vendor-role], [data-pending-vendor-role]')?.textContent || 'Vendor acara').replace(/\s*·\s*Menunggu disimpan$/, '').trim();
                }

                function findCategoryCard(categoryId) {
                    return Array.from(vendorCategoryRoot?.querySelectorAll('[data-vendor-category-card]') || [])
                        .find(card => card.dataset.categoryId === String(categoryId));
                }

                function createCategoryCard(categoryId, categoryName) {
                    if (!vendorCategoryRoot || !vendorCategoryTemplate || !categoryId) return null;
                    const existing = findCategoryCard(categoryId);
                    if (existing) return existing;
                    const card = vendorCategoryTemplate.content.firstElementChild.cloneNode(true);
                    card.dataset.categoryId = categoryId;
                    card.querySelector('[data-vendor-category-name]').textContent = categoryName || 'Vendor acara';
                    vendorCategoryRoot.appendChild(card);
                    return card;
                }

                function syncCategoryCounts() {
                    vendorCategoryRoot?.querySelectorAll('[data-vendor-category-card]').forEach(card => {
                        const count = card.querySelectorAll('[data-vendor-card]').length;
                        card.querySelector('[data-vendor-category-count]').textContent = count;
                    });
                    document.querySelector('[data-vendor-empty]')?.classList.toggle('hidden', !!vendorCategoryRoot?.querySelector('[data-vendor-card]'));
                }

                function groupVendorCards() {
                    if (!vendorCategoryRoot) return;
                    const rows = Array.from(document.querySelectorAll('[data-vendor-card]'))
                        .filter(row => !row.closest('[data-vendor-category-card]'));
                    rows.forEach(row => {
                        const categoryId = categoryIdFor(row);
                        const card = createCategoryCard(categoryId, categoryNameFor(row));
                        card?.querySelector('[data-vendor-category-list]')?.appendChild(row);
                    });
                    syncCategoryCounts();
                }

                groupVendorCards();

                const additionSections = new Set();
                function setupVendorAdditions(section) {
                    if (additionSections.has(section)) return;
                    additionSections.add(section);
                    const list = section.querySelector('[data-vendor-addition-list]');
                    const template = section.querySelector('[data-vendor-addition-template]');
                    const empty = section.querySelector('[data-vendor-addition-empty]');
                    const form = section.closest('form');
                    if (!list || !template || !empty || !form) return;
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
                        if (!card) return;
                        const basePrice = Number(card.dataset.vendorBasePrice ?? 0);
                        const customTotal = Array.from(list.querySelectorAll('[data-vendor-money-input]'))
                            .reduce((total, input) => total + Number(input.value.replace(/\D/g, '') || 0), 0);
                        card.querySelector('[data-vendor-total]')?.replaceChildren(document.createTextNode(formatRupiah(basePrice + customTotal)));
                        card.querySelector('[data-vendor-base]')?.replaceChildren(document.createTextNode(`Dasar ${formatRupiah(basePrice)}`));
                    }

                    list.querySelectorAll('[data-vendor-money-input]').forEach(formatMoney);
                    list.addEventListener('input', function(event) {
                        if (event.target.matches('[data-vendor-money-input]')) {
                            formatMoney(event.target);
                            updateVendorSummary();
                        }
                    });
                    form.addEventListener('submit', function() {
                        list.querySelectorAll('[data-vendor-money-input]').forEach(input => input.value = input.value.replaceAll('.', ''));
                    });

                    section.querySelector('[data-add-vendor-addition]')?.addEventListener('click', function() {
                        const row = template.content.firstElementChild.cloneNode(true);
                        const inputs = row.querySelectorAll('input');
                        const vendorId = section.dataset.vendorId;
                        const prefix = section.dataset.additionInputPrefix || 'vendor_additions';
                        inputs[0].name = `${prefix}[${vendorId}][${nextIndex}][name]`;
                        inputs[1].name = `${prefix}[${vendorId}][${nextIndex}][price]`;
                        row.dataset.index = nextIndex++;
                        list.appendChild(row);
                        syncEmpty();
                        inputs[0].focus();
                    });
                    list.addEventListener('click', function(event) {
                        const button = event.target.closest('[data-remove-vendor-addition]');
                        if (!button) return;
                        if (!confirmDeferredRemoval('Hapus tambahan custom ini?')) return;
                        button.closest('[data-vendor-addition-row]')?.remove();
                        syncEmpty();
                        updateVendorSummary();
                    });
                    updateVendorSummary();
                }

                document.querySelectorAll('[data-vendor-additions]').forEach(setupVendorAdditions);

                const additionalVendorCategory = document.getElementById('additional_vendor_category');
                const additionalVendor = document.getElementById('additional_vendor_id');
                const addPendingVendor = document.getElementById('add-pending-vendor');
                const pendingVendorTemplate = document.getElementById('pending-vendor-template');
                const additionalVendorFeedback = document.querySelector('[data-additional-vendor-feedback]');
                const additionalVendorSection = document.querySelector('[data-additional-vendor-section]');

                if (additionalVendorCategory && additionalVendor && addPendingVendor && pendingVendorTemplate) {
                    const vendorOptions = Array.from(additionalVendor.options).slice(1);
                    const categoryOptions = Array.from(additionalVendorCategory.options).slice(1);
                    const additionalVendorPicker = document.querySelector('[data-vendor-picker]');
                    const additionalVendorPickerButton = document.getElementById('additional_vendor_picker_button');
                    const additionalVendorPickerMenu = document.getElementById('additional_vendor_picker_menu');
                    const additionalVendorPickerLabel = document.querySelector('[data-vendor-picker-label]');
                    const additionalVendorPickerOptions = Array.from(document.querySelectorAll('[data-vendor-picker-option]'));

                    function closeVendorPicker() {
                        additionalVendorPickerMenu?.classList.add('hidden');
                        additionalVendorPickerButton?.setAttribute('aria-expanded', 'false');
                    }

                    function syncVendorPicker() {
                        const selected = additionalVendor.selectedOptions[0];
                        if (additionalVendorPickerLabel) {
                            additionalVendorPickerLabel.textContent = selected?.value ? selected.dataset.name : additionalVendor.options[0].textContent;
                        }
                        if (additionalVendorPickerButton) additionalVendorPickerButton.disabled = additionalVendor.disabled;
                        additionalVendorPickerOptions.forEach(pickerOption => {
                            const option = vendorOptions.find(item => item.value === pickerOption.dataset.vendorId);
                            const visible = !!option && !option.hidden;
                            pickerOption.classList.toggle('hidden', !visible);
                            pickerOption.disabled = !visible || option.disabled;
                            pickerOption.setAttribute('aria-selected', String(option?.value === additionalVendor.value));
                        });
                        closeVendorPicker();
                    }

                    additionalVendorPickerButton?.addEventListener('click', () => {
                        if (additionalVendorPickerButton.disabled) return;
                        const isClosed = additionalVendorPickerMenu.classList.toggle('hidden');
                        additionalVendorPickerButton.setAttribute('aria-expanded', String(!isClosed));
                    });
                    additionalVendorPickerMenu?.addEventListener('click', event => {
                        const pickerOption = event.target.closest('[data-vendor-picker-option]');
                        if (!pickerOption || pickerOption.disabled) return;
                        additionalVendor.value = pickerOption.dataset.vendorId;
                        additionalVendor.dispatchEvent(new Event('change'));
                    });
                    document.addEventListener('click', event => {
                        if (additionalVendorPicker && !additionalVendorPicker.contains(event.target)) closeVendorPicker();
                    });

                    function usedVendorIds() {
                        return new Set(Array.from(document.querySelectorAll('[data-vendor-card][data-vendor-id]'))
                            .map(row => row.dataset.vendorId)
                            .filter(Boolean));
                    }

                    function syncAdditionalVendorOptions() {
                        const usedVendors = usedVendorIds();
                        categoryOptions.forEach(option => {
                            option.hidden = false;
                            option.disabled = false;
                        });
                        const categoryId = additionalVendorCategory.value;
                        const availableOptions = [];
                        vendorOptions.forEach(option => {
                            const matchesCategory = !!categoryId && option.dataset.category === categoryId;
                            const available = matchesCategory && !usedVendors.has(option.value);
                            option.hidden = !matchesCategory;
                            option.disabled = !available;
                            if (available) availableOptions.push(option);
                        });
                        if (!availableOptions.some(option => option.value === additionalVendor.value)) additionalVendor.value = '';
                        additionalVendor.disabled = !categoryId || !availableOptions.length;
                        additionalVendor.options[0].textContent = !categoryId
                            ? 'Pilih kategori dahulu'
                            : availableOptions.length ? 'Pilih vendor' : 'Semua vendor kategori ini sudah dipilih';
                        addPendingVendor.disabled = !additionalVendor.value;
                        additionalVendorSection?.classList.remove('hidden');
                        additionalVendorFeedback?.classList.add('hidden');
                        syncVendorPicker();
                    }

                    additionalVendorCategory.addEventListener('change', syncAdditionalVendorOptions);
                    additionalVendor.addEventListener('change', () => {
                        addPendingVendor.disabled = !additionalVendor.value;
                        syncVendorPicker();
                    });

                    addPendingVendor.addEventListener('click', function() {
                        const option = additionalVendor.selectedOptions[0];
                        const categoryId = additionalVendorCategory.value;
                        if (!option?.value || !categoryId) return;
                        if (usedVendorIds().has(option.value)) {
                            additionalVendorFeedback.textContent = 'Vendor tersebut sudah ada di booking.';
                            additionalVendorFeedback.classList.remove('hidden');
                            syncAdditionalVendorOptions();
                            return;
                        }
                        const categoryCard = createCategoryCard(categoryId, additionalVendorCategory.selectedOptions[0]?.dataset.categoryName || option.dataset.role);
                        const row = pendingVendorTemplate.content.firstElementChild.cloneNode(true);
                        row.dataset.vendorId = option.value;
                        row.dataset.vendorCategoryId = categoryId;
                        row.dataset.categoryId = categoryId;
                        row.dataset.vendorBasePrice = option.dataset.price;
                        row.querySelector('input').value = option.value;
                        row.querySelector('[data-pending-vendor-name]').textContent = option.dataset.name;
                        row.querySelector('[data-pending-vendor-role]').textContent = `${option.dataset.role} · Menunggu disimpan`;
                        row.querySelector('[data-pending-vendor-price]').textContent = formatRupiah(option.dataset.price);
                        categoryCard?.querySelector('[data-vendor-category-list]')?.appendChild(row);
                        const additions = row.querySelector('[data-vendor-additions]');
                        if (additions) additions.dataset.vendorId = option.value;
                        setupVendorAdditions(additions);
                        syncCategoryCounts();
                        syncAdditionalVendorOptions();
                    });

                    vendorCategoryRoot?.addEventListener('click', function(event) {
                        const pendingButton = event.target.closest('[data-remove-pending-vendor]');
                        if (pendingButton) {
                            if (!confirmDeferredRemoval('Hapus vendor ini dari booking?')) return;
                            pendingButton.closest('[data-pending-vendor-row]')?.remove();
                            syncCategoryCounts();
                            syncAdditionalVendorOptions();
                            return;
                        }
                        const categoryButton = event.target.closest('[data-remove-vendor-category]');
                        if (categoryButton) {
                            const card = categoryButton.closest('[data-vendor-category-card]');
                            const categoryName = card?.querySelector('[data-vendor-category-name]')?.textContent?.trim() || 'ini';
                            if (!confirmDeferredRemoval(`Hapus kategori ${categoryName} beserta seluruh vendor di dalamnya dari booking?`)) return;
                            const categoryId = card?.dataset.categoryId;
                            card?.querySelectorAll('[data-booking-vendor-id]').forEach(row => appendRemovedVendorInput('removed_booking_vendor_ids[]', row.dataset.bookingVendorId));
                            if (categoryId && categoryId !== 'uncategorized') appendRemovedVendorInput('removed_vendor_category_ids[]', categoryId);
                            card?.remove();
                            syncCategoryCounts();
                            syncAdditionalVendorOptions();
                            return;
                        }
                        const vendorButton = event.target.closest('[data-remove-vendor]');
                        if (vendorButton) {
                            const row = vendorButton.closest('[data-vendor-card]');
                            const vendorName = row?.querySelector('[data-vendor-name]')?.textContent?.trim() || 'ini';
                            if (!confirmDeferredRemoval(`Hapus vendor ${vendorName} dari booking?`)) return;
                            if (row?.dataset.bookingVendorId) appendRemovedVendorInput('removed_booking_vendor_ids[]', row.dataset.bookingVendorId);
                            row?.remove();
                            syncCategoryCounts();
                            syncAdditionalVendorOptions();
                        }
                    });

                    syncAdditionalVendorOptions();
                }

            });
        </script>
    @endpush
@endsection
