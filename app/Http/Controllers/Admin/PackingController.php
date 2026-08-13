<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\InventoryItem;
use App\Models\PackingItem;
use App\Models\PackingList;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;

class PackingController extends Controller
{
    public function show(Booking $booking)
    {
        $booking->load(['packingLists.items.inventoryItem.category', 'package']);

        $before = $booking->packingLists()
            ->where('type', PackingList::TYPE_BEFORE)
            ->latest()
            ->first();

        $after = $booking->packingLists()
            ->where('type', PackingList::TYPE_AFTER)
            ->latest()
            ->first();

        $inventory = InventoryItem::with('category')->orderBy('code')->get();

        return view('admin.packing.show', compact('booking', 'before', 'after', 'inventory'));
    }

    public function createChecklist(Request $request)
    {
        $data = $request->validate([
            'booking_id' => ['required', 'exists:bookings,id'],
            'type' => ['required', 'in:before_fitting,after_fitting'],
            'item_ids' => ['sometimes', 'array'],
            'item_ids.*' => ['exists:inventory_items,id'],
        ]);

        // Checklist sesudah fitting: salin otomatis item dari checklist sebelum fitting
        if ($data['type'] === PackingList::TYPE_AFTER && empty($data['item_ids'])) {
            $before = PackingList::where('booking_id', $data['booking_id'])
                ->where('type', PackingList::TYPE_BEFORE)
                ->latest()
                ->first();

            $data['item_ids'] = $before ? $before->items->pluck('inventory_item_id')->all() : [];

            if (empty($data['item_ids'])) {
                return back()->withErrors(['item_ids' => 'Buat checklist sebelum fitting dulu, karena tidak ada barang untuk checklist sesudah fitting.']);
            }
        }

        if (empty($data['item_ids'])) {
            return back()->withErrors(['item_ids' => 'Pilih minimal 1 barang untuk checklist.']);
        }

        $existing = PackingList::where('booking_id', $data['booking_id'])
            ->where('type', $data['type'])
            ->where('status', 'draft')
            ->first();

        if (! $existing) {
            $existing = PackingList::create([
                'booking_id' => $data['booking_id'],
                'type' => $data['type'],
                'status' => 'draft',
                'created_by' => auth()->id(),
            ]);
        }

        foreach ($data['item_ids'] as $itemId) {
            $existing->items()->firstOrCreate([
                'inventory_item_id' => $itemId,
                'status' => PackingItem::STATUS_PACKED,
            ]);
        }

        if ($data['type'] === PackingList::TYPE_BEFORE) {
            InventoryItem::whereIn('id', $data['item_ids'])->update(['status' => 'in_use']);
        }

        ActivityLogger::log(
            'packing_created',
            'Packing checklist dibuat',
            'Checklist '.($data['type'] === 'before_fitting' ? 'sebelum' : 'sesudah').' fitting untuk '.Booking::find($data['booking_id'])->name,
            $data['booking_id'],
        );

        return back()->with('success', 'Checklist berhasil dibuat.');
    }

    public function toggleItem(PackingList $packingList, PackingItem $item, Request $request)
    {
        $status = $request->input('status') === 'missing'
            ? PackingItem::STATUS_MISSING
            : PackingItem::STATUS_PACKED;

        $item->update(['status' => $status]);

        if ($packingList->type === PackingList::TYPE_AFTER && $status === PackingItem::STATUS_MISSING) {
            $item->inventoryItem->update(['status' => 'lost']);

            ActivityLogger::log(
                'item_missing',
                'Barang belum kembali',
                $item->inventoryItem->name.' ('.$item->inventoryItem->code.') belum kembali setelah fitting.',
                $packingList->booking_id,
            );
        }

        return back()->with('success', 'Status barang diperbarui.');
    }

    public function close(PackingList $packingList)
    {
        $packingList->update([
            'status' => 'done',
            'closed_at' => now(),
        ]);

        $missing = $packingList->items()->where('status', 'missing')->count();

        // Checklist sesudah fitting ditutup: barang yang kembali dikembalikan ke status available
        if ($packingList->type === PackingList::TYPE_AFTER) {
            $packedIds = $packingList->items()
                ->where('status', PackingItem::STATUS_PACKED)
                ->pluck('inventory_item_id');

            InventoryItem::whereIn('id', $packedIds)->update(['status' => 'available']);
        }

        ActivityLogger::log(
            'packing_closed',
            'Packing checklist ditutup',
            'Checklist '.($packingList->type === 'before_fitting' ? 'sebelum' : 'sesudah').' fitting ditutup. Barang belum kembali: '.$missing,
            $packingList->booking_id,
        );

        $message = $missing > 0
            ? 'Checklist ditutup. '.$missing.' barang belum kembali!'
            : 'Checklist ditutup. Semua barang kembali.';

        return back()->with($missing > 0 ? 'warning' : 'success', $message);
    }
}
