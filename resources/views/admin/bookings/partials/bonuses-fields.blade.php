@php $bonusRows = $bonusRows ?? []; @endphp

<div class="rounded-xl border border-brand-100 bg-brand-50/30 p-4" data-bonus-fields>
    <div class="mb-3 flex items-center justify-between gap-3">
        <div>
            <label class="block text-sm font-medium text-gray-700">Bonus</label>
            <p class="mt-0.5 text-xs text-gray-400">Opsional. Bonus dicatat sebagai informasi dan tidak mengubah total tagihan.</p>
        </div>
        <button type="button" data-add-bonus class="inline-flex items-center gap-1.5 rounded-lg bg-brand px-3 py-2 text-xs font-semibold text-white transition-colors hover:bg-brand-dark">
            <i class="fas fa-plus"></i> Tambah Bonus
        </button>
    </div>

    <div data-bonus-list class="space-y-3">
        @foreach ($bonusRows as $index => $bonus)
            <div data-bonus-row data-bonus-index="{{ $index }}" class="booking-bonus-row flex gap-3 items-end">
                <div class="bonus-note-field">
                    <label class="mb-1 block text-xs font-medium text-gray-600">Keterangan Bonus</label>
                    <input type="text" name="bonuses[{{ $index }}][note]" value="{{ $bonus['note'] ?? '' }}" required maxlength="255" placeholder="Contoh: Tambahan touch up" class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-800 focus:outline-none focus:ring-2 focus:ring-brand-200">
                </div>
                <div class="bonus-amount-field">
                    <label class="mb-1 block text-xs font-medium text-gray-600">Harga</label>
                    <input type="text" inputmode="numeric" name="bonuses[{{ $index }}][amount]" value="{{ ($bonus['amount'] ?? '') === '' ? '' : number_format((float) $bonus['amount'], 0, '.', '') }}" data-bonus-amount required pattern="[0-9.]*" placeholder="500.000" class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-800 focus:outline-none focus:ring-2 focus:ring-brand-200">
                </div>
                <button type="button" data-remove-bonus class="bonus-remove-button inline-flex h-10 flex-shrink-0 items-center justify-center rounded-lg border border-red-200 px-3 text-red-500 transition-colors hover:bg-red-50" aria-label="Hapus bonus">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        @endforeach
    </div>

    <p data-bonus-empty class="{{ count($bonusRows) ? 'hidden' : '' }} py-3 text-center text-xs text-gray-400">Belum ada bonus booking.</p>

    @foreach ($errors->get('bonuses.*.note') as $message)
        <p class="mt-2 text-xs text-red-600">{{ $message }}</p>
    @endforeach
    @foreach ($errors->get('bonuses.*.amount') as $message)
        <p class="mt-2 text-xs text-red-600">{{ $message }}</p>
    @endforeach
</div>

<template data-bonus-template>
    <div data-bonus-row class="booking-bonus-row flex gap-3 items-end">
        <div class="bonus-note-field">
            <label class="mb-1 block text-xs font-medium text-gray-600">Keterangan Bonus</label>
            <input type="text" required maxlength="255" placeholder="Contoh: Tambahan touch up" class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-800 focus:outline-none focus:ring-2 focus:ring-brand-200">
        </div>
        <div class="bonus-amount-field">
            <label class="mb-1 block text-xs font-medium text-gray-600">Harga</label>
            <input type="text" inputmode="numeric" data-bonus-amount required pattern="[0-9.]*" placeholder="500.000" class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-800 focus:outline-none focus:ring-2 focus:ring-brand-200">
        </div>
        <button type="button" data-remove-bonus class="bonus-remove-button inline-flex h-10 flex-shrink-0 items-center justify-center rounded-lg border border-red-200 px-3 text-red-500 transition-colors hover:bg-red-50" aria-label="Hapus bonus">
            <i class="fas fa-trash"></i>
        </button>
    </div>
</template>

<style>
    .booking-bonus-row { flex-wrap: nowrap; }
    .booking-bonus-row .bonus-note-field { flex: 1 1 auto; min-width: 0; }
    .booking-bonus-row .bonus-amount-field { flex: 0 0 180px; }
    .booking-bonus-row .bonus-remove-button { flex: 0 0 44px; width: 44px; }

    @media (max-width: 767px) {
        .booking-bonus-row { flex-direction: column; align-items: stretch; }
        .booking-bonus-row .bonus-note-field,
        .booking-bonus-row .bonus-amount-field,
        .booking-bonus-row .bonus-remove-button { flex: 1 1 auto; width: 100%; }
    }
</style>

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('[data-bonus-fields]').forEach(function (section) {
                const list = section.querySelector('[data-bonus-list]');
                const empty = section.querySelector('[data-bonus-empty]');
                const template = section.parentElement.querySelector('[data-bonus-template]');
                const addButton = section.querySelector('[data-add-bonus]');
                const indexes = Array.from(list.querySelectorAll('[data-bonus-index]')).map(row => Number(row.dataset.bonusIndex));
                let nextIndex = indexes.length ? Math.max(...indexes) + 1 : 0;

                function syncEmptyState() {
                    empty.classList.toggle('hidden', list.children.length > 0);
                }

                function formatMoney(input) {
                    const digits = input.value.replace(/\D/g, '');
                    input.value = digits.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
                }

                list.querySelectorAll('[data-bonus-amount]').forEach(formatMoney);
                list.addEventListener('input', function (event) {
                    if (event.target.matches('[data-bonus-amount]')) formatMoney(event.target);
                });
                list.closest('form').addEventListener('submit', function () {
                    list.querySelectorAll('[data-bonus-amount]').forEach(input => {
                        input.value = input.value.replaceAll('.', '');
                    });
                });

                addButton.addEventListener('click', function () {
                    const row = template.content.cloneNode(true).firstElementChild;
                    row.dataset.bonusIndex = nextIndex;
                    row.querySelector('.bonus-note-field input').name = `bonuses[${nextIndex}][note]`;
                    row.querySelector('[data-bonus-amount]').name = `bonuses[${nextIndex}][amount]`;
                    nextIndex += 1;
                    list.appendChild(row);
                    syncEmptyState();
                    row.querySelector('.bonus-note-field input').focus();
                });

                list.addEventListener('click', function (event) {
                    const removeButton = event.target.closest('[data-remove-bonus]');
                    if (!removeButton) return;
                    removeButton.closest('[data-bonus-row]').remove();
                    syncEmptyState();
                });
            });
        });
    </script>
@endpush
