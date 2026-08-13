<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Vendor;
use App\Models\VendorCategory;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;

class VendorController extends Controller
{
    public function index(Request $request)
    {
        $query = Vendor::with('category')
            ->when($request->category_id, fn ($q, $id) => $q->where('vendor_category_id', $id))
            ->when($request->q, fn ($q, $s) => $q->where('name', 'like', "%{$s}%"))
            ->orderBy('name');

        $vendors = $query->paginate(15)->withQueryString();
        $categories = VendorCategory::all();

        return view('admin.vendors.index', compact('vendors', 'categories'));
    }

    public function create()
    {
        $categories = VendorCategory::all();

        return view('admin.vendors.form', ['vendor' => null, 'categories' => $categories]);
    }

    public function store(Request $request)
    {
        $data = $this->validateData($request);

        $vendor = Vendor::create($data);

        ActivityLogger::log('vendor_created', 'Vendor ditambahkan', 'Vendor '.$vendor->name.' ditambahkan oleh '.auth()->user()->name);

        return redirect()->route('admin.vendors.index')->with('success', 'Vendor berhasil ditambahkan.');
    }

    public function edit(Vendor $vendor)
    {
        $categories = VendorCategory::all();

        return view('admin.vendors.form', compact('vendor', 'categories'));
    }

    public function update(Request $request, Vendor $vendor)
    {
        $data = $this->validateData($request);

        $vendor->update($data);

        ActivityLogger::log('vendor_updated', 'Vendor diperbarui', 'Vendor '.$vendor->name.' diperbarui oleh '.auth()->user()->name);

        return redirect()->route('admin.vendors.index')->with('success', 'Vendor berhasil diperbarui.');
    }

    public function destroy(Vendor $vendor)
    {
        $name = $vendor->name;
        $vendor->delete();

        ActivityLogger::log('vendor_deleted', 'Vendor dihapus', 'Vendor '.$name.' dihapus oleh '.auth()->user()->name);

        return redirect()->route('admin.vendors.index')->with('success', 'Vendor dihapus.');
    }

    private function validateData(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'vendor_category_id' => ['required', 'exists:vendor_categories,id'],
            'phone' => ['nullable', 'string', 'max:30'],
            'instagram' => ['nullable', 'string', 'max:100'],
            'address' => ['nullable', 'string', 'max:255'],
            'price' => ['required', 'numeric', 'min:0'],
            'status' => ['required', 'in:active,inactive'],
            'notes' => ['nullable', 'string'],
        ]);
    }
}
