<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\VendorCategory;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class VendorCategoryController extends Controller
{
    public function index()
    {
        $categories = VendorCategory::withCount('vendors')->orderBy('name')->get();

        return view('admin.vendor-categories.index', compact('categories'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:vendor_categories,name'],
        ]);

        $category = VendorCategory::create([
            'name' => $data['name'],
            'slug' => Str::slug($data['name']),
        ]);

        ActivityLogger::log('vendor_category_created', 'Kategori vendor ditambahkan', 'Kategori vendor '.$category->name.' ditambahkan oleh '.auth()->user()->name);

        return back()->with('success', 'Kategori berhasil ditambahkan.');
    }

    public function update(Request $request, VendorCategory $category)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:vendor_categories,name,'.$category->id],
        ]);

        $category->update([
            'name' => $data['name'],
            'slug' => Str::slug($data['name']),
        ]);

        ActivityLogger::log('vendor_category_updated', 'Kategori vendor diperbarui', 'Kategori vendor '.$category->name.' diperbarui oleh '.auth()->user()->name);

        return back()->with('success', 'Kategori berhasil diperbarui.');
    }

    public function destroy(VendorCategory $category)
    {
        if ($category->vendors()->count() > 0) {
            return back()->with('warning', 'Kategori tidak bisa dihapus karena masih dipakai '.$category->vendors()->count().' vendor.');
        }

        $name = $category->name;
        $category->delete();

        ActivityLogger::log('vendor_category_deleted', 'Kategori vendor dihapus', 'Kategori vendor '.$name.' dihapus oleh '.auth()->user()->name);

        return back()->with('success', 'Kategori dihapus.');
    }
}
