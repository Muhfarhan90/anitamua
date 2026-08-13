<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BenefitCategory;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;

class BenefitCategoryController extends Controller
{
    public function index()
    {
        $categories = BenefitCategory::withCount('benefits')->orderBy('sort_order')->get();

        return view('admin.benefit-categories.index', compact('categories'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:benefit_categories,name'],
        ]);

        $category = BenefitCategory::create([
            'name' => $data['name'],
            'sort_order' => BenefitCategory::max('sort_order') + 1,
        ]);

        ActivityLogger::log('benefit_category_created', 'Kategori benefit ditambahkan', 'Kategori benefit '.$category->name.' ditambahkan oleh '.auth()->user()->name);

        return back()->with('success', 'Kategori berhasil ditambahkan.');
    }

    public function update(Request $request, BenefitCategory $category)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:benefit_categories,name,'.$category->id],
        ]);

        $category->update($data);

        ActivityLogger::log('benefit_category_updated', 'Kategori benefit diperbarui', 'Kategori benefit '.$category->name.' diperbarui oleh '.auth()->user()->name);

        return back()->with('success', 'Kategori berhasil diperbarui.');
    }

    public function destroy(BenefitCategory $category)
    {
        if ($category->benefits()->count() > 0) {
            return back()->with('warning', 'Kategori tidak bisa dihapus karena masih dipakai '.$category->benefits()->count().' benefit.');
        }

        $name = $category->name;
        $category->delete();

        ActivityLogger::log('benefit_category_deleted', 'Kategori benefit dihapus', 'Kategori benefit '.$name.' dihapus oleh '.auth()->user()->name);

        return back()->with('success', 'Kategori dihapus.');
    }
}
