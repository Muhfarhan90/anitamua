<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EntranceGate;
use App\Services\ActivityLogger;
use App\Services\ImageCompressor;
use Illuminate\Http\Request;

class EntranceGateController extends Controller
{
    public function index()
    {
        return view('admin.master-photo-items.index', [
            'items' => EntranceGate::orderBy('name')->get(),
            'title' => 'Master Gapura',
            'singular' => 'Gapura',
            'routeName' => 'admin.entrance-gates',
            'icon' => 'fa-archway',
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:entrance_gates,name'],
            'photo' => ['required', 'image', 'max:5120'],
            'is_active' => ['required', 'boolean'],
        ]);
        $data['photo_path'] = ImageCompressor::compressAndStore($request->file('photo'), 'uploads/entrance-gates');
        unset($data['photo']);

        $gate = EntranceGate::create($data);
        ActivityLogger::log('entrance_gate_created', 'Gapura ditambahkan', 'Gapura '.$gate->name.' ditambahkan oleh '.auth()->user()->name);

        return back()->with('success', 'Gapura berhasil ditambahkan.');
    }

    public function update(Request $request, EntranceGate $entranceGate)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:entrance_gates,name,'.$entranceGate->id],
            'photo' => ['nullable', 'image', 'max:5120'],
            'is_active' => ['required', 'boolean'],
        ]);
        if ($request->hasFile('photo')) {
            $data['photo_path'] = ImageCompressor::compressAndStore($request->file('photo'), 'uploads/entrance-gates');
        }
        unset($data['photo']);
        $entranceGate->update($data);

        ActivityLogger::log('entrance_gate_updated', 'Gapura diperbarui', 'Gapura '.$entranceGate->name.' diperbarui oleh '.auth()->user()->name);

        return back()->with('success', 'Gapura berhasil diperbarui.');
    }

    public function destroy(EntranceGate $entranceGate)
    {
        if ($entranceGate->surveys()->exists()) {
            return back()->with('warning', 'Gapura sudah dipakai pada survey. Nonaktifkan saja.');
        }

        $name = $entranceGate->name;
        $entranceGate->delete();
        ActivityLogger::log('entrance_gate_deleted', 'Gapura dihapus', 'Gapura '.$name.' dihapus oleh '.auth()->user()->name);

        return back()->with('success', 'Gapura berhasil dihapus.');
    }
}
