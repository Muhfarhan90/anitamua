<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Benefit;
use App\Models\BenefitCategory;
use App\Models\Package;
use App\Models\PackageType;
use App\Models\Vendor;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;

class PackageController extends Controller
{
    public function index()
    {
        $packages = Package::with(['benefits', 'packageType'])->orderBy('price')->paginate(15);

        return view('admin.packages.index', compact('packages'));
    }

    public function create()
    {
        $benefits = Benefit::with('category')->where('status', Benefit::STATUS_ACTIVE)->orderBy('benefit_category_id')->orderBy('sort_order')->get();
        $benefitCategories = BenefitCategory::withCount('benefits')->orderBy('sort_order')->get();
        $vendors = Vendor::with('category')->orderBy('vendor_category_id')->orderBy('name')->get();

        $packageTypes = PackageType::orderBy('name')->get();

        return view('admin.packages.form', ['package' => null, 'benefits' => $benefits, 'benefitCategories' => $benefitCategories, 'vendors' => $vendors, 'packageTypes' => $packageTypes]);
    }

    public function store(Request $request)
    {
        $data = $this->validateData($request);

        $package = Package::create([
            'name' => $data['name'],
            'package_type_id' => $data['package_type_id'],
            'price' => $data['price'],
            'original_price' => $data['original_price'] ?? null,
            'description' => $data['description'],
            'color' => $data['color'],
            'status' => $data['status'],
        ]);

        $package->benefits()->sync($data['benefit_ids'] ?? []);
        $package->benefitCategories()->sync($data['benefit_category_ids'] ?? []);
        $this->syncVendors($package, $data['vendor_ids'] ?? []);

        ActivityLogger::log('package_created', 'Paket dibuat', 'Paket '.$package->name.' seharga '.$package->price.' dibuat oleh '.auth()->user()->name);

        return redirect()->route('admin.packages.index')->with('success', 'Paket berhasil dibuat.');
    }

    public function edit(Package $package)
    {
        $benefits = Benefit::with('category')->where('status', Benefit::STATUS_ACTIVE)->orderBy('benefit_category_id')->orderBy('sort_order')->get();
        $benefitCategories = BenefitCategory::withCount('benefits')->orderBy('sort_order')->get();
        $vendors = Vendor::with('category')->orderBy('vendor_category_id')->orderBy('name')->get();

        $packageTypes = PackageType::orderBy('name')->get();

        return view('admin.packages.form', compact('package', 'benefits', 'benefitCategories', 'vendors', 'packageTypes'));
    }

    public function update(Request $request, Package $package)
    {
        $data = $this->validateData($request);

        $package->update([
            'name' => $data['name'],
            'package_type_id' => $data['package_type_id'],
            'price' => $data['price'],
            'original_price' => $data['original_price'] ?? null,
            'description' => $data['description'],
            'color' => $data['color'],
            'status' => $data['status'],
        ]);

        $package->benefits()->sync($data['benefit_ids'] ?? []);
        $package->benefitCategories()->sync($data['benefit_category_ids'] ?? []);
        $this->syncVendors($package, $data['vendor_ids'] ?? []);

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

    public function types()
    {
        $packageTypes = PackageType::withCount('packages')->orderBy('name')->get();

        return view('admin.packages.types', compact('packageTypes'));
    }

    public function storeType(Request $request)
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:100', 'unique:package_types,name']]);
        $type = PackageType::create(['name' => trim($data['name'])]);

        ActivityLogger::log('package_type_created', 'Jenis paket dibuat', 'Jenis paket '.$type->name.' dibuat oleh '.auth()->user()->name);

        return back()->with('success', 'Jenis paket berhasil ditambahkan.');
    }

    public function updateType(Request $request, PackageType $packageType)
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:100', 'unique:package_types,name,'.$packageType->id]]);
        $packageType->update(['name' => trim($data['name'])]);

        ActivityLogger::log('package_type_updated', 'Jenis paket diperbarui', 'Jenis paket diperbarui oleh '.auth()->user()->name);

        return back()->with('success', 'Jenis paket berhasil diperbarui.');
    }

    public function destroyType(PackageType $packageType)
    {
        if ($packageType->packages()->exists()) {
            return back()->with('error', 'Jenis paket tidak dapat dihapus karena masih dipakai paket.');
        }

        $name = $packageType->name;
        $packageType->delete();
        ActivityLogger::log('package_type_deleted', 'Jenis paket dihapus', 'Jenis paket '.$name.' dihapus oleh '.auth()->user()->name);

        return back()->with('success', 'Jenis paket berhasil dihapus.');
    }

    private function validateData(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'package_type_id' => ['required', 'exists:package_types,id'],
            'price' => ['required', 'numeric', 'min:0'],
            'original_price' => ['nullable', 'numeric', 'min:0', 'gte:price'],
            'description' => ['nullable', 'string'],
            'color' => ['nullable', 'string', 'max:20'],
            'status' => ['required', 'in:active,inactive'],
            'benefit_ids' => ['nullable', 'array'],
            'benefit_ids.*' => ['exists:benefits,id'],
            'benefit_category_ids' => ['nullable', 'array'],
            'benefit_category_ids.*' => ['exists:benefit_categories,id'],
            'vendor_ids' => ['nullable', 'array'],
            'vendor_ids.*' => ['nullable', 'exists:vendors,id'],
        ]);

        $data['vendor_ids'] = array_values(array_filter($data['vendor_ids'] ?? []));

        return $data;
    }

    private function syncVendors(Package $package, array $vendorIds): void
    {
        $existingPrices = $package->vendors()->pluck('package_vendor.price', 'vendors.id');
        $vendors = Vendor::whereIn('id', $vendorIds)->pluck('price', 'id');

        $package->vendors()->sync(collect($vendorIds)->mapWithKeys(fn ($id) => [
            $id => ['price' => $existingPrices[$id] ?? $vendors[$id]],
        ])->all());
    }
}
