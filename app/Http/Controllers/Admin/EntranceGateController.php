<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EntranceGate;
use App\Services\ActivityLogger;
use App\Services\ImageCompressor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

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
            'photos' => ['required', 'array', 'min:1'],
            'photos.*' => ['image', 'max:5120'],
            'is_active' => ['required', 'boolean'],
        ]);
        $data['photos'] = collect($request->file('photos'))
            ->map(fn ($photo) => ImageCompressor::compressAndStore($photo, 'uploads/entrance-gates'))
            ->all();
        $data['photo_path'] = $data['photos'][0];

        $gate = EntranceGate::create($data);
        ActivityLogger::log('entrance_gate_created', 'Gapura ditambahkan', 'Gapura '.$gate->name.' ditambahkan oleh '.auth()->user()->name);

        return back()->with('success', 'Gapura berhasil ditambahkan.');
    }

    public function update(Request $request, EntranceGate $entranceGate)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:entrance_gates,name,'.$entranceGate->id],
            'photos' => ['sometimes', 'array'],
            'photos.*' => ['image', 'max:5120'],
            'remove_photos' => ['sometimes', 'array'],
            'remove_photos.*' => ['string', 'max:2048'],
            'is_active' => ['required', 'boolean'],
        ]);
        $currentPhotos = array_values(array_filter($entranceGate->photos ?: [$entranceGate->photo_path]));
        $removedPhotos = array_values(array_intersect($data['remove_photos'] ?? [], $currentPhotos));
        $remainingPhotos = array_values(array_diff($currentPhotos, $removedPhotos));
        unset($data['remove_photos']);

        $newPhotos = [];
        if ($request->hasFile('photos')) {
            $newPhotos = collect($request->file('photos'))
                ->map(fn ($photo) => ImageCompressor::compressAndStore($photo, 'uploads/entrance-gates'))
                ->all();
        }
        if ($removedPhotos || $newPhotos) {
            $data['photos'] = array_values(array_merge($remainingPhotos, $newPhotos));
            $data['photo_path'] = $data['photos'][0] ?? null;
        }
        $entranceGate->update($data);
        foreach ($removedPhotos as $photo) {
            $folder = 'uploads/entrance-gates/';
            if (str_starts_with($photo, $folder) && basename($photo) === substr($photo, strlen($folder))) {
                Storage::disk('public')->delete($photo);
            }
        }

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
