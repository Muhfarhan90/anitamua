@php($addonRows = $addonRows ?? [])

<div class="rounded-xl border border-brand-100 bg-brand-50/30 p-4">
    <div class="flex items-center justify-between gap-3 mb-3">
        <div>
            <label class="block text-sm font-medium text-gray-700">Paket Tambahan</label>
            <p class="text-xs text-gray-400 mt-0.5">Opsional. Tambahkan nama dan harga item tambahan untuk booking ini.</p>
        </div>
        <button type="button" id="add-addon" class="inline-flex items-center gap-1.5 rounded-lg bg-brand px-3 py-2 text-xs font-semibold text-white hover:bg-brand-dark transition-colors">
            <i class="fas fa-plus"></i> Tambah
        </button>
    </div>

    <div id="addon-list" class="space-y-3">
        @foreach ($addonRows as $index => $addon)
            <div data-addon-row data-addon-index="{{ $index }}" class="booking-addon-row flex gap-3 items-end">
                <div class="addon-name-field">
                    <label class="block text-xs font-medium text-gray-600 mb-1">Nama Tambahan</label>
                    <input type="text" name="addons[{{ $index }}][name]" value="{{ $addon['name'] ?? '' }}" required maxlength="255" placeholder="Contoh: Extra Touch Up"
                        class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-800 focus:outline-none focus:ring-2 focus:ring-brand-200">
                </div>
                <div class="addon-price-field">
                    <label class="block text-xs font-medium text-gray-600 mb-1">Harga</label>
                    <input type="text" inputmode="numeric" data-money-input name="addons[{{ $index }}][price]" value="{{ ($addon['price'] ?? '') === '' ? '' : number_format((float) $addon['price'], 0, '.', '') }}" required pattern="[0-9.]*" placeholder="250.000"
                        class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-800 focus:outline-none focus:ring-2 focus:ring-brand-200">
                </div>
                <button type="button" data-remove-addon class="addon-remove-button inline-flex h-10 flex-shrink-0 items-center justify-center rounded-lg border border-red-200 px-3 text-red-500 hover:bg-red-50 transition-colors" aria-label="Hapus paket tambahan">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        @endforeach
    </div>

    <p id="addon-empty" class="{{ count($addonRows) ? 'hidden' : '' }} text-xs text-gray-400 text-center py-3">Belum ada paket tambahan.</p>

    @foreach ($errors->get('addons.*.name') as $message)
        <p class="text-xs text-red-600 mt-2">{{ $message }}</p>
    @endforeach
    @foreach ($errors->get('addons.*.price') as $message)
        <p class="text-xs text-red-600 mt-2">{{ $message }}</p>
    @endforeach
</div>

<template id="addon-row-template">
    <div data-addon-row class="booking-addon-row flex gap-3 items-end">
        <div class="addon-name-field">
            <label class="block text-xs font-medium text-gray-600 mb-1">Nama Tambahan</label>
            <input type="text" required maxlength="255" placeholder="Contoh: Extra Touch Up"
                class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-800 focus:outline-none focus:ring-2 focus:ring-brand-200">
        </div>
        <div class="addon-price-field">
            <label class="block text-xs font-medium text-gray-600 mb-1">Harga</label>
                <input type="text" inputmode="numeric" data-money-input required pattern="[0-9.]*" placeholder="250.000"
                class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-800 focus:outline-none focus:ring-2 focus:ring-brand-200">
        </div>
        <button type="button" data-remove-addon class="addon-remove-button inline-flex h-10 flex-shrink-0 items-center justify-center rounded-lg border border-red-200 px-3 text-red-500 hover:bg-red-50 transition-colors" aria-label="Hapus paket tambahan">
            <i class="fas fa-trash"></i>
        </button>
    </div>
</template>

<style>
    .booking-addon-row {
        flex-wrap: nowrap;
    }

    .booking-addon-row .addon-name-field {
        flex: 1 1 auto;
        min-width: 0;
    }

    .booking-addon-row .addon-price-field {
        flex: 0 0 180px;
    }

    .booking-addon-row .addon-remove-button {
        flex: 0 0 44px;
        width: 44px;
    }

    @media (max-width: 767px) {
        .booking-addon-row {
            flex-direction: column;
            align-items: stretch;
        }

        .booking-addon-row .addon-name-field,
        .booking-addon-row .addon-price-field,
        .booking-addon-row .addon-remove-button {
            flex: 1 1 auto;
            width: 100%;
        }
    }
</style>

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const list = document.getElementById('addon-list');
            const empty = document.getElementById('addon-empty');
            const template = document.getElementById('addon-row-template');
            const addButton = document.getElementById('add-addon');
            const indexes = Array.from(list.querySelectorAll('[data-addon-index]')).map(row => Number(row.dataset.addonIndex));
            let nextIndex = indexes.length ? Math.max(...indexes) + 1 : 0;

            function syncEmptyState() {
                empty.classList.toggle('hidden', list.children.length > 0);
            }

            function formatMoney(input) {
                const digits = input.value.replace(/[,.]\d{1,2}$/, '').replace(/\D/g, '');
                input.value = digits.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
            }

            list.querySelectorAll('[data-money-input]').forEach(formatMoney);
            list.addEventListener('input', function (event) {
                if (event.target.matches('[data-money-input]')) formatMoney(event.target);
            });

            list.closest('form').addEventListener('submit', function () {
                list.querySelectorAll('[data-money-input]').forEach(input => {
                    input.value = input.value.replaceAll('.', '');
                });
            });

            addButton.addEventListener('click', function () {
                const row = template.content.cloneNode(true).firstElementChild;
                row.dataset.addonIndex = nextIndex;
                row.querySelectorAll('input').forEach((input, fieldIndex) => {
                    input.name = `addons[${nextIndex}][${fieldIndex === 0 ? 'name' : 'price'}]`;
                });
                nextIndex += 1;
                list.appendChild(row);
                syncEmptyState();
                row.querySelector('input').focus();
            });

            list.addEventListener('click', function (event) {
                const removeButton = event.target.closest('[data-remove-addon]');
                if (!removeButton) return;
                removeButton.closest('[data-addon-row]').remove();
                syncEmptyState();
            });
        });
    </script>
@endpush
