<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Benefit;
use App\Models\BenefitCategory;
use App\Models\Package;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;

class PackageController extends Controller
{
    public function index()
    {
        $packages = Package::with('benefits')->orderBy('price')->paginate(15);

        return view('admin.packages.index', compact('packages'));
    }

    public function create()
    {
        $benefits = Benefit::with('category')->where('status', Benefit::STATUS_ACTIVE)->orderBy('benefit_category_id')->orderBy('sort_order')->get();
        $benefitCategories = BenefitCategory::withCount('benefits')->orderBy('sort_order')->get();

        return view('admin.packages.form', ['package' => null, 'benefits' => $benefits, 'benefitCategories' => $benefitCategories]);
    }

    public function store(Request $request)
    {
        $data = $this->validateData($request);

        $package = Package::create([
            'name' => $data['name'],
            'type' => $data['type'],
            'price' => $data['price'],
            'description' => $data['description'],
            'color' => $data['color'],
            'status' => $data['status'],
        ]);

        $package->benefits()->sync($data['benefit_ids'] ?? []);
        $package->benefitCategories()->sync($data['benefit_category_ids'] ?? []);

        ActivityLogger::log('package_created', 'Paket dibuat', 'Paket '.$package->name.' seharga '.$package->price.' dibuat oleh '.auth()->user()->name);

        return redirect()->route('admin.packages.index')->with('success', 'Paket berhasil dibuat.');
    }

    public function edit(Package $package)
    {
        $benefits = Benefit::with('category')->where('status', Benefit::STATUS_ACTIVE)->orderBy('benefit_category_id')->orderBy('sort_order')->get();
        $benefitCategories = BenefitCategory::withCount('benefits')->orderBy('sort_order')->get();

        return view('admin.packages.form', compact('package', 'benefits', 'benefitCategories'));
    }

    public function update(Request $request, Package $package)
    {
        $data = $this->validateData($request);

        $package->update([
            'name' => $data['name'],
            'type' => $data['type'],
            'price' => $data['price'],
            'description' => $data['description'],
            'color' => $data['color'],
            'status' => $data['status'],
        ]);

        $package->benefits()->sync($data['benefit_ids'] ?? []);
        $package->benefitCategories()->sync($data['benefit_category_ids'] ?? []);

        ActivityLogger::log('package_updated', 'Paket diperbarui', 'Paket '.$package->name.' diperbarui oleh '.auth()->user()->name);

        return redirect()->route('admin.packages.index')->with('success', 'Paket berhasil diperbarui.');
    }

    public function destroy(Package $package)
    {
        $name = $package->name;
        $package->delete();

        ActivityLogger::log('package_deleted', 'Paket dihapus', 'Paket '.$name.' dihapus oleh '.auth()->user()->name);

        return redirect()->route('admin.packages.index')->with('success', 'Paket dihapus.');
    }

    private function validateData(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:makeup,full'],
            'price' => ['required', 'numeric', 'min:0'],
            'description' => ['nullable', 'string'],
            'color' => ['nullable', 'string', 'max:20'],
            'status' => ['required', 'in:active,inactive'],
            'benefit_ids' => ['nullable', 'array'],
            'benefit_ids.*' => ['exists:benefits,id'],
            'benefit_category_ids' => ['nullable', 'array'],
            'benefit_category_ids.*' => ['exists:benefit_categories,id'],
        ]);
    }
}
