@extends('layouts.app')

@section('title', 'Invoice')

@section('content')
<x-page-header title="Invoice" subtitle="Kelola dan unduh invoice booking Anita MUA" />

<x-card class="mb-5">
    <form method="GET" class="flex flex-wrap items-end gap-4">
        <div class="flex-1 min-w-[220px]">
            <label class="block text-sm font-medium text-gray-600 mb-1">Cari</label>
            <input name="q" value="{{ request('q') }}" placeholder="Nomor invoice, nama, kode booking"
                   class="w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand-200">
        </div>
        <div class="min-w-[160px]">
            <label class="block text-sm font-medium text-gray-600 mb-1">Status</label>
            <select name="status" class="w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand-200">
                <option value="">Semua Status</option>
                <option value="unpaid" @selected(request('status') === 'unpaid')>Unpaid</option>
                <option value="partial" @selected(request('status') === 'partial')>Partial</option>
                <option value="paid" @selected(request('status') === 'paid')>Paid</option>
            </select>
        </div>
        <x-button type="submit"><i class="fas fa-filter"></i> Filter</x-button>
        <x-button color="outline" href="{{ route('admin.invoices.index') }}"><i class="fas fa-rotate-left"></i> Reset</x-button>
    </form>
</x-card>

<x-card padding="p-0">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="text-left text-gray-500 border-b border-gray-100 bg-cream/60">
                    <th class="px-5 py-2.5 font-medium">Invoice</th>
                    <th class="px-5 py-2.5 font-medium">Booking / Client</th>
                    <th class="px-5 py-2.5 font-medium">Total</th>
                    <th class="px-5 py-2.5 font-medium">Terbayar</th>
                    <th class="px-5 py-2.5 font-medium">Sisa</th>
                    <th class="px-5 py-2.5 font-medium">Status</th>
                    <th class="px-5 py-2.5 font-medium text-center">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($invoices as $invoice)
                <tr class="border-b border-gray-50 hover:bg-brand-50/30">
                    <td class="px-5 py-3">
                        <div class="font-semibold text-gray-800">{{ $invoice->invoice_number }}</div>
                        <div class="text-xs text-gray-400">{{ $invoice->issue_date?->format('d M Y') }}</div>
                    </td>
                    <td class="px-5 py-3">
                        <div class="font-semibold text-gray-800">{{ $invoice->booking->code }}</div>
                        <div class="text-xs text-gray-500">{{ $invoice->booking->name }}</div>
                    </td>
                    <td class="px-5 py-3 font-semibold">Rp {{ number_format($invoice->total_amount, 0, ',', '.') }}</td>
                    <td class="px-5 py-3 text-emerald-600 font-semibold">Rp {{ number_format($invoice->paid_amount, 0, ',', '.') }}</td>
                    <td class="px-5 py-3 text-brand font-semibold">Rp {{ number_format($invoice->remaining_amount, 0, ',', '.') }}</td>
                    <td class="px-5 py-3"><x-badge :color="match($invoice->status) { 'paid' => 'success', 'partial' => 'warning', default => 'gray' }">{{ $invoice->statusLabel() }}</x-badge></td>
                    <td class="px-5 py-3 text-center whitespace-nowrap">
                        <a href="{{ route('admin.invoices.show', $invoice) }}" class="text-brand hover:underline mr-3">Lihat</a>
                    </td>
                </tr>
                @empty
                <tr><td colspan="7"><x-empty-state icon="fa-file-invoice" title="Belum ada invoice" text="Invoice dibuat otomatis setelah DP1 terverifikasi." /></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="px-5 py-4 border-t border-gray-100">{{ $invoices->links() }}</div>
</x-card>
@endsection
