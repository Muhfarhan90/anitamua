<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ReferenceType;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;

class ReferenceTypeController extends Controller
{
    public function index()
    {
        $referenceTypes = ReferenceType::withCount('references')->orderBy('name')->get();

        return view('admin.reference-types.index', compact('referenceTypes'));
    }

    public function store(Request $request)
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:100', 'unique:reference_types,name']]);
        $type = ReferenceType::create(['name' => trim($data['name'])]);

        ActivityLogger::log('reference_type_created', 'Jenis referensi dibuat', 'Jenis referensi '.$type->name.' dibuat oleh '.auth()->user()->name.'.');

        return back()->with('success', 'Jenis referensi berhasil ditambahkan.');
    }

    public function update(Request $request, ReferenceType $referenceType)
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:100', 'unique:reference_types,name,'.$referenceType->id]]);
        $referenceType->update(['name' => trim($data['name'])]);

        ActivityLogger::log('reference_type_updated', 'Jenis referensi diperbarui', 'Jenis referensi diperbarui oleh '.auth()->user()->name.'.');

        return back()->with('success', 'Jenis referensi berhasil diperbarui.');
    }

    public function destroy(ReferenceType $referenceType)
    {
        if ($referenceType->references()->exists()) {
            return back()->with('error', 'Jenis referensi tidak dapat dihapus karena masih digunakan.');
        }

        $name = $referenceType->name;
        $referenceType->delete();
        ActivityLogger::log('reference_type_deleted', 'Jenis referensi dihapus', 'Jenis referensi '.$name.' dihapus oleh '.auth()->user()->name.'.');

        return back()->with('success', 'Jenis referensi berhasil dihapus.');
    }
}
