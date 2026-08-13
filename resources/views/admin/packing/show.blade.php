@extends('layouts.app')

@section('title', 'Packing Checklist')

@section('content')
{{-- HEADER --}}
<div class="flex items-center justify-between mb-6 flex-wrap gap-3">
    <div class="flex items-center gap-3 flex-wrap">
        <span class="px-2.5 py-1 text-xs font-bold text-white rounded-lg bg-brand">{{ $booking->code }}</span>
        <span class="font-semibold text-gray-800">{{ $booking->name }}</span>
        <span class="text-xs text-gray-500">{{ $booking->event_date->format('d M Y') }}</span>
    </div>
    <a href="{{ in_array(auth()->user()->role, ['owner', 'admin']) ? route('admin.bookings.show', $booking) : route('dashboard') }}"
       class="inline-flex items-center gap-1.5 text-sm font-medium text-brand hover:text-brand-dark no-underline transition-colors">
        <i class="fas fa-arrow-left text-xs"></i> {{ in_array(auth()->user()->role, ['owner', 'admin']) ? 'Kembali ke Detail' : 'Kembali ke Dashboard' }}
    </a>
</div>

@if(!$before || $before->status !== 'done')
    {{-- STEP 1: Checklist Sebelum Fitting --}}
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm border-l-4 border-l-amber-400 p-6 mb-6">
        <h6 class="font-bold text-gray-800 mb-4 flex items-center gap-2">
            <i class="fas fa-box text-amber-500"></i> Step 1 — Checklist Sebelum Fitting
        </h6>

        @if($before)
            <div class="flex justify-end mb-4">
                <form method="POST" action="{{ route('admin.packing.close', $before) }}"
                      onsubmit="return confirm('Tutup checklist sebelum fitting?')">
                    @csrf
                    <button class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-emerald-500 text-white text-sm font-semibold hover:bg-emerald-600 transition-colors">
                        <i class="fas fa-lock text-xs"></i> Kunci Checklist
                    </button>
                </form>
            </div>
        @else
            <form method="POST" action="{{ route('admin.packing.create') }}">
                @csrf
                <input type="hidden" name="booking_id" value="{{ $booking->id }}">
                <input type="hidden" name="type" value="before_fitting">
                <label class="block text-xs font-semibold text-gray-600 uppercase tracking-wider mb-3">
                    Pilih barang yang akan dibawa ke fitting:
                </label>
                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-2 mb-4">
                    @foreach($inventory as $item)
                        <label class="flex items-start gap-3 border border-gray-200 rounded-xl px-4 py-3 cursor-pointer hover:bg-brand-50/30 transition-colors has-[:checked]:border-brand has-[:checked]:bg-brand-50/40">
                            <input type="checkbox" name="item_ids[]" value="{{ $item->id }}"
                                   class="mt-0.5 w-4 h-4 accent-[#d4739a] flex-shrink-0">
                            <div>
                                <p class="text-sm font-semibold text-gray-800">{{ $item->name }}</p>
                                <p class="text-xs text-gray-400">{{ $item->code }} · {{ $item->category->name }}</p>
                            </div>
                        </label>
                    @endforeach
                </div>
                <button type="submit"
                        class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-brand text-white text-sm font-semibold hover:bg-brand-dark transition-colors shadow-sm">
                    <i class="fas fa-circle-check"></i> Buat Checklist
                </button>
            </form>
        @endif

        @if($before && $before->items->count() > 0)
            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-2 mt-4">
                @foreach($before->items as $item)
                    <div class="flex items-center gap-3 border border-gray-100 rounded-xl px-4 py-3 bg-gray-50">
                        <i class="fas fa-circle-check text-emerald-500 text-sm flex-shrink-0"></i>
                        <div>
                            <p class="text-sm font-semibold text-gray-800">{{ $item->inventoryItem->name }}</p>
                            <p class="text-xs text-gray-400">{{ $item->inventoryItem->code }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
@endif

@if($before && $before->status === 'done')
    {{-- CHECKLIST SEBELUM FITTING — SELESAI --}}
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm border-l-4 border-l-emerald-400 p-6 mb-6">
        <h6 class="font-bold text-gray-800 mb-4 flex items-center gap-2">
            <i class="fas fa-circle-check text-emerald-500"></i> Checklist Sebelum Fitting (Selesai)
        </h6>
        <div class="flex flex-wrap gap-2">
            @foreach($before->items as $item)
                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-sm font-medium bg-emerald-100 text-emerald-700">
                    <i class="fas fa-check text-xs"></i> {{ $item->inventoryItem->name }}
                </span>
            @endforeach
        </div>
    </div>

    {{-- STEP 2: Checklist Sesudah Fitting --}}
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm border-l-4 border-l-amber-400 p-6 mb-6">
        <h6 class="font-bold text-gray-800 mb-4 flex items-center gap-2">
            <i class="fas fa-rotate-left text-amber-500"></i> Step 2 — Checklist Sesudah Fitting (Barang Kembali)
        </h6>

        @if(!$after || $after->status !== 'done')
            <p class="text-sm text-gray-500 mb-4">Tandai barang yang <strong>tidak kembali</strong> setelah fitting.</p>

            @if(!$after)
                <form method="POST" action="{{ route('admin.packing.create') }}" class="mb-4">
                    @csrf
                    <input type="hidden" name="booking_id" value="{{ $booking->id }}">
                    <input type="hidden" name="type" value="after_fitting">
                    <button class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-brand text-white text-sm font-semibold hover:bg-brand-dark transition-colors shadow-sm">
                        <i class="fas fa-circle-plus"></i> Buat Checklist Sesudah Fitting
                    </button>
                </form>
            @endif

            @if($after)
                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-2 mb-5">
                    @foreach($after->items as $item)
                        <div class="flex items-center justify-between gap-3 border rounded-xl px-4 py-3 {{ $item->status === 'missing' ? 'border-red-200 bg-red-50' : 'border-gray-100 bg-gray-50' }}">
                            <div class="flex items-center gap-2">
                                <i class="fas {{ $item->status === 'packed' ? 'fa-circle-check text-emerald-500' : 'fa-circle-xmark text-red-500' }} text-sm flex-shrink-0"></i>
                                <div>
                                    <p class="text-sm font-semibold {{ $item->status === 'missing' ? 'text-red-700' : 'text-gray-800' }}">
                                        {{ $item->inventoryItem->name }}
                                    </p>
                                </div>
                            </div>
                            <form method="POST" action="{{ route('admin.packing.toggle', [$after, $item]) }}">
                                @csrf
                                <input type="hidden" name="status" value="{{ $item->status === 'packed' ? 'missing' : 'packed' }}">
                                <button class="text-xs px-2.5 py-1 rounded-lg border transition-colors font-medium
                                    {{ $item->status === 'packed'
                                        ? 'border-red-300 text-red-600 hover:bg-red-50'
                                        : 'border-emerald-300 text-emerald-600 hover:bg-emerald-50' }}">
                                    {{ $item->status === 'packed' ? 'Belum Kembali' : 'Kembali' }}
                                </button>
                            </form>
                        </div>
                    @endforeach
                </div>

                @php $missing = $after->items->where('status', 'missing'); @endphp
                <div class="flex flex-wrap items-center justify-between gap-4 pt-4 border-t border-gray-100">
                    <div>
                        @if($missing->count() > 0)
                            <div class="flex items-start gap-2 bg-red-50 border border-red-200 text-red-700 rounded-xl px-4 py-3 text-sm">
                                <i class="fas fa-triangle-exclamation mt-0.5 flex-shrink-0"></i>
                                <span><strong>{{ $missing->count() }} barang belum kembali:</strong>
                                    {{ $missing->pluck('inventoryItem.name')->implode(', ') }}
                                </span>
                            </div>
                        @else
                            <span class="flex items-center gap-1.5 text-emerald-600 text-sm font-semibold">
                                <i class="fas fa-circle-check"></i> Semua barang sudah kembali
                            </span>
                        @endif
                    </div>
                    <form method="POST" action="{{ route('admin.packing.close', $after) }}"
                          onsubmit="return confirm('Tutup checklist?')">
                        @csrf
                        <button class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-brand text-white text-sm font-semibold hover:bg-brand-dark transition-colors shadow-sm">
                            <i class="fas fa-lock text-xs"></i> Kunci Checklist
                        </button>
                    </form>
                </div>
            @endif
        @else
            {{-- After checklist done --}}
            <div class="flex flex-wrap gap-2">
                @foreach($after->items as $item)
                    @if($item->status === 'missing')
                        <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-sm font-medium bg-red-100 text-red-700">
                            <i class="fas fa-xmark text-xs"></i> {{ $item->inventoryItem->name }} — BELUM KEMBALI
                        </span>
                    @else
                        <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-sm font-medium bg-emerald-100 text-emerald-700">
                            <i class="fas fa-check text-xs"></i> {{ $item->inventoryItem->name }}
                        </span>
                    @endif
                @endforeach
            </div>
        @endif
    </div>
@endif
@endsection
