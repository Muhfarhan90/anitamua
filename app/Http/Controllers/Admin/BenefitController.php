<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Benefit;
use App\Models\BenefitCategory;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;

class BenefitController extends Controller
{
    public function index(Request $request)
    {
        $query = Benefit::with('category')
            ->when($request->q, fn ($q, $s) => $q->where('name', 'like', "%{$s}%"))
            ->when($request->category_id, fn ($q, $id) => $q->where('benefit_category_id', $id))
            ->orderBy('benefit_category_id')->orderBy('sort_order');

        $benefits = $query->paginate(30)->withQueryString();
        $categories = BenefitCategory::withCount('benefits')->orderBy('sort_order')->get();

        return view('admin.benefits.index', compact('benefits', 'categories'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:benefits,name'],
            'benefit_category_id' => ['required', 'exists:benefit_categories,id'],
            'status' => ['required', 'in:active,inactive'],
        ]);

        $data['sort_order'] = Benefit::where('benefit_category_id', $data['benefit_category_id'])->max('sort_order') + 1;

        $benefit = Benefit::create($data);

        ActivityLogger::log('benefit_created', 'Benefit ditambahkan', 'Benefit '.$benefit->name.' ('.$benefit->category->name.') ditambahkan oleh '.auth()->user()->name);

        return back()->with('success', 'Benefit berhasil ditambahkan.');
    }

    public function update(Request $request, Benefit $benefit)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:benefits,name,'.$benefit->id],
            'benefit_category_id' => ['required', 'exists:benefit_categories,id'],
            'status' => ['required', 'in:active,inactive'],
        ]);

        $benefit->update($data);

        ActivityLogger::log('benefit_updated', 'Benefit diperbarui', 'Benefit '.$benefit->name.' diperbarui oleh '.auth()->user()->name);

        return back()->with('success', 'Benefit berhasil diperbarui.');
    }

    public function destroy(Benefit $benefit)
    {
        if ($benefit->packages()->count() > 0) {
            return back()->with('warning', 'Benefit tidak bisa dihapus karena dipakai '.$benefit->packages()->count().' paket. Nonaktifkan saja.');
        }

        $name = $benefit->name;
        $benefit->delete();

        ActivityLogger::log('benefit_deleted', 'Benefit dihapus', 'Benefit '.$name.' dihapus oleh '.auth()->user()->name);

        return back()->with('success', 'Benefit dihapus.');
    }
}
