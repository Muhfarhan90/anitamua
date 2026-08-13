<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\InventoryCategory;
use App\Models\InventoryItem;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;

class InventoryController extends Controller
{
    public function index(Request $request)
    {
        $query = InventoryItem::with('category')
            ->when($request->category_id, fn ($q, $id) => $q->where('inventory_category_id', $id))
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->when($request->condition, fn ($q, $c) => $q->where('condition', $c))
            ->when($request->q, fn ($q, $s) => $q->where(function ($qq) use ($s) {
                $qq->where('name', 'like', "%{$s}%")->orWhere('code', 'like', "%{$s}%");
            }))
            ->orderBy('code');

        $items = $query->paginate(20)->withQueryString();
        $categories = InventoryCategory::all();
        $statusCounts = InventoryItem::select('status')->get()->countBy('status')->toArray();
        $conditionCounts = InventoryItem::select('condition')->get()->countBy('condition')->toArray();

        return view('admin.inventory.index', compact('items', 'categories', 'statusCounts', 'conditionCounts'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'inventory_category_id' => ['required', 'exists:inventory_categories,id'],
            'color' => ['nullable', 'string', 'max:50'],
            'size' => ['nullable', 'string', 'max:50'],
            'brand' => ['nullable', 'string', 'max:100'],
            'storage_location' => ['nullable', 'string', 'max:255'],
            'condition' => ['required', 'in:good,fair,damaged'],
            'status' => ['required', 'in:available,in_use,damaged,lost'],
            'notes' => ['nullable', 'string'],
            'photo' => ['nullable', 'image', 'max:5120'],
        ]);

        if ($request->hasFile('photo')) {
            $data['photo'] = $request->file('photo')->store('uploads/inventory', 'public');
        }

        $data['code'] = $this->generateCode($data['inventory_category_id']);

        $item = InventoryItem::create($data);

        ActivityLogger::log('inventory_created', 'Barang ditambahkan', 'Barang '.$item->name.' ('.$item->code.') ditambahkan oleh '.auth()->user()->name);

        return back()->with('success', 'Barang '.$item->code.' berhasil ditambahkan.');
    }

    public function update(Request $request, InventoryItem $item)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'inventory_category_id' => ['required', 'exists:inventory_categories,id'],
            'color' => ['nullable', 'string', 'max:50'],
            'size' => ['nullable', 'string', 'max:50'],
            'brand' => ['nullable', 'string', 'max:100'],
            'storage_location' => ['nullable', 'string', 'max:255'],
            'condition' => ['required', 'in:good,fair,damaged'],
            'status' => ['required', 'in:available,in_use,damaged,lost'],
            'notes' => ['nullable', 'string'],
            'photo' => ['nullable', 'image', 'max:5120'],
        ]);

        if ($request->hasFile('photo')) {
            $data['photo'] = $request->file('photo')->store('uploads/inventory', 'public');
        }

        $item->update($data);

        ActivityLogger::log('inventory_updated', 'Barang diperbarui', 'Barang '.$item->name.' diperbarui oleh '.auth()->user()->name);

        return back()->with('success', 'Barang berhasil diperbarui.');
    }

    public function destroy(InventoryItem $item)
    {
        $name = $item->name;
        $item->delete();

        ActivityLogger::log('inventory_deleted', 'Barang dihapus', 'Barang '.$name.' dihapus oleh '.auth()->user()->name);

        return back()->with('success', 'Barang dihapus.');
    }

    private function generateCode(int $categoryId): string
    {
        $category = InventoryCategory::findOrFail($categoryId);
        $prefix = strtoupper(substr(str_replace([' ', '&'], '', $category->name), 0, 3));

        $count = InventoryItem::where('inventory_category_id', $categoryId)->count() + 1;

        return $prefix.'-'.str_pad((string) $count, 3, '0', STR_PAD_LEFT);
    }
}
