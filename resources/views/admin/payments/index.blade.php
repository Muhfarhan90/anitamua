@extends('layouts.app')

@section('title', 'Pembayaran')

@section('content')
<x-page-header title="Pembayaran" subtitle="Kelola pembayaran & verifikasi bukti transfer">
    <x-slot:actions>
        <div class="bg-white rounded-xl shadow-sm px-5 py-2.5 border border-brand-100 text-right">
            <span class="block text-xs text-gray-500">Total Menunggu Verifikasi</span>
            <span class="font-display text-xl font-bold text-brand leading-tight">Rp {{ number_format($totalOutstanding ?? 0, 0, ',', '.') }}</span>
        </div>
    </x-slot:actions>
</x-page-header>

<x-card class="mb-5">
    <form method="GET" class="flex flex-wrap items-end gap-4">
        <div class="flex-1 min-w-[160px]">
            <label class="block text-sm font-medium text-gray-600 mb-1">Status</label>
            <select name="status" class="w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand-200">
                <option value="">Semua Status</option>
                <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                <option value="verified" {{ request('status') == 'verified' ? 'selected' : '' }}>Verified</option>
                <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>Batal</option>
            </select>
        </div>
        <div class="flex-1 min-w-[160px]">
            <label class="block text-sm font-medium text-gray-600 mb-1">Tahap</label>
            <select name="type" class="w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand-200">
                <option value="">Semua Tahap</option>
                <option value="dp10" {{ request('type') == 'dp10' ? 'selected' : '' }}>DP 10%</option>
                <option value="dp25" {{ request('type') == 'dp25' ? 'selected' : '' }}>DP 25% (Fitting)</option>
                <option value="dp75" {{ request('type') == 'dp75' ? 'selected' : '' }}>DP 75% (H-7)</option>
                <option value="pelunasan" {{ request('type') == 'pelunasan' ? 'selected' : '' }}>Pelunasan</option>
            </select>
        </div>
        <x-button type="submit"><i class="fas fa-filter"></i> Filter</x-button>
        <a href="{{ route('admin.payments.index') }}" class="text-gray-400 hover:text-gray-600 px-2 py-2 text-sm">Reset</a>
    </form>
</x-card>

<x-card padding="p-0">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="text-left text-gray-500 border-b border-gray-100 bg-cream/60">
                    <th class="px-5 py-2.5 font-medium">Booking</th>
                    <th class="px-5 py-2.5 font-medium">Tahap</th>
                    <th class="px-5 py-2.5 font-medium">Nominal</th>
                    <th class="px-5 py-2.5 font-medium">Jatuh Tempo</th>
                    <th class="px-5 py-2.5 font-medium">Bukti</th>
                    <th class="px-5 py-2.5 font-medium">Status</th>
                    <th class="px-5 py-2.5 font-medium text-center">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($payments as $payment)
                <tr class="border-b border-gray-50 hover:bg-brand-50/30 transition-colors">
                    <td class="px-5 py-3">
                        <div class="font-semibold text-gray-800">{{ $payment->booking->code }}</div>
                        <div class="text-gray-500 text-xs mt-0.5">{{ $payment->booking->name }}</div>
                    </td>
                    <td class="px-5 py-3"><x-badge>{{ \App\Models\Payment::typeLabel($payment->type) }}</x-badge></td>
                    <td class="px-5 py-3 font-semibold text-gray-800">Rp {{ number_format($payment->amount, 0, ',', '.') }}</td>
                    <td class="px-5 py-3 text-gray-600">{{ $payment->due_date ? $payment->due_date->format('d M Y') : '-' }}</td>
                    <td class="px-5 py-3">
                        @if($payment->proof)
                            <a href="{{ asset('storage/' . $payment->proof) }}" onclick="openProof(event, this.href)" class="text-brand underline text-sm cursor-pointer">Lihat</a>
                        @else
                            <span class="text-gray-400">-</span>
                        @endif
                    </td>
                    <td class="px-5 py-3">
                        <x-badge :color="match($payment->status) {
                            'pending' => 'warning',
                            'verified' => 'success',
                            'cancelled' => 'danger',
                            default => 'gray',
                        }">{{ ucfirst($payment->status) }}</x-badge>
                    </td>
                    <td class="px-5 py-3 text-center">
                        @if($payment->status === 'pending')
                        <form action="{{ route('admin.payments.verify', $payment->id) }}" method="POST" class="inline">
                            @csrf
                            <x-button size="sm" color="success" type="submit">Verifikasi</x-button>
                        </form>
                        @else
                        <span class="text-gray-400 text-xs">-</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7"><x-empty-state icon="fa-receipt" title="Tidak ada pembayaran" text="Tidak ada data yang cocok dengan filter" /></td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if(method_exists($payments, 'links'))
    <div class="px-5 py-4 border-t border-gray-100">
        {{ $payments->links() }}
    </div>
    @endif
</x-card>
@endsection
