<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\InventoryCategory;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;

class InventoryCategoryController extends Controller
{
    public function index()
    {
        $categories = InventoryCategory::withCount('items')->orderBy('name')->get();

        return view('admin.inventory-categories.index', compact('categories'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:inventory_categories,name'],
        ]);

        $category = InventoryCategory::create($data);

        ActivityLogger::log('inventory_category_created', 'Kategori inventory ditambahkan', 'Kategori inventory '.$category->name.' ditambahkan oleh '.auth()->user()->name);

        return back()->with('success', 'Kategori berhasil ditambahkan.');
    }

    public function update(Request $request, InventoryCategory $category)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:inventory_categories,name,'.$category->id],
        ]);

        $category->update($data);

        ActivityLogger::log('inventory_category_updated', 'Kategori inventory diperbarui', 'Kategori inventory '.$category->name.' diperbarui oleh '.auth()->user()->name);

        return back()->with('success', 'Kategori berhasil diperbarui.');
    }

    public function destroy(InventoryCategory $category)
    {
        if ($category->items()->count() > 0) {
            return back()->with('warning', 'Kategori tidak bisa dihapus karena masih dipakai '.$category->items()->count().' barang.');
        }

        $name = $category->name;
        $category->delete();

        ActivityLogger::log('inventory_category_deleted', 'Kategori inventory dihapus', 'Kategori inventory '.$name.' dihapus oleh '.auth()->user()->name);

        return back()->with('success', 'Kategori dihapus.');
    }
}
