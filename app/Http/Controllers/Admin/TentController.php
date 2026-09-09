<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tent;
use App\Services\ActivityLogger;
use App\Services\ImageCompressor;
use Illuminate\Http\Request;

class TentController extends Controller
{
    public function index()
    {
        return view('admin.master-photo-items.index', [
            'items' => Tent::orderBy('name')->get(),
            'title' => 'Master Tenda',
            'singular' => 'Tenda',
            'routeName' => 'admin.tents',
            'icon' => 'fa-campground',
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:tents,name'],
            'photo' => ['required', 'image', 'max:5120'],
            'is_active' => ['required', 'boolean'],
        ]);
        $data['photo_path'] = ImageCompressor::compressAndStore($request->file('photo'), 'uploads/tents');
        unset($data['photo']);

        $tent = Tent::create($data);
        ActivityLogger::log('tent_created', 'Tenda ditambahkan', 'Tenda '.$tent->name.' ditambahkan oleh '.auth()->user()->name);

        return back()->with('success', 'Tenda berhasil ditambahkan.');
    }

    public function update(Request $request, Tent $tent)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:tents,name,'.$tent->id],
            'photo' => ['nullable', 'image', 'max:5120'],
            'is_active' => ['required', 'boolean'],
        ]);
        if ($request->hasFile('photo')) {
            $data['photo_path'] = ImageCompressor::compressAndStore($request->file('photo'), 'uploads/tents');
        }
        unset($data['photo']);
        $tent->update($data);

        ActivityLogger::log('tent_updated', 'Tenda diperbarui', 'Tenda '.$tent->name.' diperbarui oleh '.auth()->user()->name);

        return back()->with('success', 'Tenda berhasil diperbarui.');
    }

    public function destroy(Tent $tent)
    {
        if ($tent->surveys()->exists()) {
            return back()->with('warning', 'Tenda sudah dipakai pada survey. Nonaktifkan saja.');
        }

        $name = $tent->name;
        $tent->delete();
        ActivityLogger::log('tent_deleted', 'Tenda dihapus', 'Tenda '.$name.' dihapus oleh '.auth()->user()->name);

        return back()->with('success', 'Tenda berhasil dihapus.');
    }
}
