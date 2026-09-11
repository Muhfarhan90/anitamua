@php
    $inputPrefix = $inputPrefix ?? 'vendor_additions';
    $vendorId = $vendorId ?? '';
    $rows = $rows ?? [];
@endphp

<details class="mt-3 overflow-hidden rounded-lg border border-gray-100 bg-gray-50/70" data-vendor-additions data-vendor-id="{{ $vendorId }}" data-addition-input-prefix="{{ $inputPrefix }}" @if(count($rows)) open @endif>
    <summary class="flex cursor-pointer list-none items-center justify-between gap-3 px-3 py-2.5 text-sm text-gray-700 marker:hidden">
        <span><i class="fas fa-plus-circle mr-1.5 text-brand"></i> Tambahan Custom</span>
        <span class="text-xs text-gray-400">{{ count($rows) ? count($rows).' tambahan' : 'Opsional' }} <i class="fas fa-chevron-down ml-1 transition-transform"></i></span>
    </summary>
    <div class="border-t border-gray-100 p-3">
        <div class="mb-3 flex items-center justify-between gap-3">
            <p class="text-xs text-gray-400">Biaya tambahan khusus vendor ini.</p>
            <button type="button" data-add-vendor-addition class="inline-flex items-center gap-1 rounded-lg bg-brand px-3 py-2 text-xs font-semibold text-white">
                <i class="fas fa-plus"></i> Tambah
            </button>
        </div>
        <div class="space-y-2" data-vendor-addition-list>
            @foreach($rows as $index => $addition)
                <div class="booking-addon-row flex items-end gap-3" data-vendor-addition-row data-index="{{ $index }}">
                    <div class="addon-name-field">
                        <label class="mb-1 block text-xs font-medium text-gray-600">Nama Tambahan</label>
                        <input type="text" name="{{ $inputPrefix }}[{{ $vendorId }}][{{ $index }}][name]" value="{{ $addition['name'] ?? '' }}" required maxlength="255" placeholder="Contoh: Extra Touch Up" class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-800 focus:outline-none focus:ring-2 focus:ring-brand-200">
                    </div>
                    <div class="addon-price-field">
                        <label class="mb-1 block text-xs font-medium text-gray-600">Harga</label>
                        <input type="text" inputmode="numeric" data-vendor-money-input name="{{ $inputPrefix }}[{{ $vendorId }}][{{ $index }}][price]" value="{{ ($addition['price'] ?? '') === '' ? '' : number_format((float) $addition['price'], 0, '.', '') }}" required pattern="[0-9.]*" placeholder="250.000" class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-800 focus:outline-none focus:ring-2 focus:ring-brand-200">
                    </div>
                    <button type="button" data-remove-vendor-addition class="addon-remove-button inline-flex h-10 shrink-0 items-center justify-center rounded-lg border border-red-200 px-3 text-red-500 hover:bg-red-50" aria-label="Hapus tambahan">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            @endforeach
        </div>
        <p data-vendor-addition-empty class="{{ count($rows) ? 'hidden' : '' }} py-2 text-center text-xs text-gray-400">Belum ada tambahan custom.</p>
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
                <button type="button" data-remove-vendor-addition class="addon-remove-button inline-flex h-10 shrink-0 items-center justify-center rounded-lg border border-red-200 px-3 text-red-500 hover:bg-red-50" aria-label="Hapus tambahan">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        </template>
    </div>
</details>
