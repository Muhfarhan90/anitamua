@php $discountRows = $discountRows ?? []; @endphp

<div class="rounded-xl border border-brand-100 bg-brand-50/30 p-4" data-discount-fields>
    <div class="mb-3 flex items-center justify-between gap-3">
        <div>
            <label class="block text-sm font-medium text-gray-700">Diskon Booking</label>
            <p class="mt-0.5 text-xs text-gray-400">Opsional. Tambahkan setiap potongan harga secara terpisah.</p>
        </div>
        <button type="button" data-add-discount class="inline-flex items-center gap-1.5 rounded-lg bg-brand px-3 py-2 text-xs font-semibold text-white transition-colors hover:bg-brand-dark">
            <i class="fas fa-plus"></i> Tambah Diskon
        </button>
    </div>

    <div data-discount-list class="space-y-3">
        @foreach ($discountRows as $index => $discount)
            <div data-discount-row data-discount-index="{{ $index }}" class="booking-discount-row flex gap-3 items-end">
                <div class="discount-note-field">
                    <label class="mb-1 block text-xs font-medium text-gray-600">Keterangan Potongan</label>
                    <input type="text" name="discounts[{{ $index }}][note]" value="{{ $discount['note'] ?? '' }}" required maxlength="255" placeholder="Contoh: Promo pembukaan" class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-800 focus:outline-none focus:ring-2 focus:ring-brand-200">
                </div>
                <div class="discount-amount-field">
                    <label class="mb-1 block text-xs font-medium text-gray-600">Harga</label>
                    <input type="text" inputmode="numeric" name="discounts[{{ $index }}][amount]" value="{{ ($discount['amount'] ?? '') === '' ? '' : number_format((float) $discount['amount'], 0, '.', '') }}" data-discount-amount required pattern="[0-9.]*" placeholder="500.000" class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-800 focus:outline-none focus:ring-2 focus:ring-brand-200">
                </div>
                <button type="button" data-remove-discount class="discount-remove-button inline-flex h-10 flex-shrink-0 items-center justify-center rounded-lg border border-red-200 px-3 text-red-500 transition-colors hover:bg-red-50" aria-label="Hapus diskon">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        @endforeach
    </div>

    <p data-discount-empty class="{{ count($discountRows) ? 'hidden' : '' }} py-3 text-center text-xs text-gray-400">Belum ada diskon booking.</p>

    @error('discounts')
        <p class="mt-2 text-xs text-red-600">{{ $message }}</p>
    @enderror
    @foreach ($errors->get('discounts.*.note') as $message)
        <p class="mt-2 text-xs text-red-600">{{ $message }}</p>
    @endforeach
    @foreach ($errors->get('discounts.*.amount') as $message)
        <p class="mt-2 text-xs text-red-600">{{ $message }}</p>
    @endforeach
</div>

<template data-discount-template>
    <div data-discount-row class="booking-discount-row flex gap-3 items-end">
        <div class="discount-note-field">
            <label class="mb-1 block text-xs font-medium text-gray-600">Keterangan Potongan</label>
            <input type="text" required maxlength="255" placeholder="Contoh: Promo pembukaan" class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-800 focus:outline-none focus:ring-2 focus:ring-brand-200">
        </div>
        <div class="discount-amount-field">
            <label class="mb-1 block text-xs font-medium text-gray-600">Harga</label>
            <input type="text" inputmode="numeric" data-discount-amount required pattern="[0-9.]*" placeholder="500.000" class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-800 focus:outline-none focus:ring-2 focus:ring-brand-200">
        </div>
        <button type="button" data-remove-discount class="discount-remove-button inline-flex h-10 flex-shrink-0 items-center justify-center rounded-lg border border-red-200 px-3 text-red-500 transition-colors hover:bg-red-50" aria-label="Hapus diskon">
            <i class="fas fa-trash"></i>
        </button>
    </div>
</template>

<style>
    .booking-discount-row { flex-wrap: nowrap; }
    .booking-discount-row .discount-note-field { flex: 1 1 auto; min-width: 0; }
    .booking-discount-row .discount-amount-field { flex: 0 0 180px; }
    .booking-discount-row .discount-remove-button { flex: 0 0 44px; width: 44px; }

    @media (max-width: 767px) {
        .booking-discount-row { flex-direction: column; align-items: stretch; }
        .booking-discount-row .discount-note-field,
        .booking-discount-row .discount-amount-field,
        .booking-discount-row .discount-remove-button { flex: 1 1 auto; width: 100%; }
    }
</style>

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('[data-discount-fields]').forEach(function (section) {
                const list = section.querySelector('[data-discount-list]');
                const empty = section.querySelector('[data-discount-empty]');
                const template = section.parentElement.querySelector('[data-discount-template]');
                const addButton = section.querySelector('[data-add-discount]');
                const indexes = Array.from(list.querySelectorAll('[data-discount-index]')).map(row => Number(row.dataset.discountIndex));
                let nextIndex = indexes.length ? Math.max(...indexes) + 1 : 0;

                function syncEmptyState() {
                    empty.classList.toggle('hidden', list.children.length > 0);
                }

                function formatMoney(input) {
                    const digits = input.value.replace(/\D/g, '');
                    input.value = digits.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
                }

                list.querySelectorAll('[data-discount-amount]').forEach(formatMoney);
                list.addEventListener('input', function (event) {
                    if (event.target.matches('[data-discount-amount]')) formatMoney(event.target);
                });
                list.closest('form').addEventListener('submit', function () {
                    list.querySelectorAll('[data-discount-amount]').forEach(input => {
                        input.value = input.value.replaceAll('.', '');
                    });
                });

                addButton.addEventListener('click', function () {
                    const row = template.content.cloneNode(true).firstElementChild;
                    row.dataset.discountIndex = nextIndex;
                    row.querySelector('.discount-note-field input').name = `discounts[${nextIndex}][note]`;
                    row.querySelector('[data-discount-amount]').name = `discounts[${nextIndex}][amount]`;
                    nextIndex += 1;
                    list.appendChild(row);
                    syncEmptyState();
                    row.querySelector('.discount-note-field input').focus();
                });

                list.addEventListener('click', function (event) {
                    const removeButton = event.target.closest('[data-remove-discount]');
                    if (!removeButton) return;
                    removeButton.closest('[data-discount-row]').remove();
                    syncEmptyState();
                });
            });
        });
    </script>
@endpush
