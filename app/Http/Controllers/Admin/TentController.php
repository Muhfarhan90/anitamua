<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tent;
use App\Services\ActivityLogger;
use App\Services\ImageCompressor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

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
            'photos' => ['required', 'array', 'min:1'],
            'photos.*' => ['image', 'max:5120'],
            'is_active' => ['required', 'boolean'],
        ]);
        $data['photos'] = collect($request->file('photos'))
            ->map(fn ($photo) => ImageCompressor::compressAndStore($photo, 'uploads/tents'))
            ->all();
        $data['photo_path'] = $data['photos'][0];

        $tent = Tent::create($data);
        ActivityLogger::log('tent_created', 'Tenda ditambahkan', 'Tenda '.$tent->name.' ditambahkan oleh '.auth()->user()->name);

        return back()->with('success', 'Tenda berhasil ditambahkan.');
    }

    public function update(Request $request, Tent $tent)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:tents,name,'.$tent->id],
            'photos' => ['sometimes', 'array'],
            'photos.*' => ['image', 'max:5120'],
            'remove_photos' => ['sometimes', 'array'],
            'remove_photos.*' => ['string', 'max:2048'],
            'is_active' => ['required', 'boolean'],
        ]);
        $currentPhotos = array_values(array_filter($tent->photos ?: [$tent->photo_path]));
        $removedPhotos = array_values(array_intersect($data['remove_photos'] ?? [], $currentPhotos));
        $remainingPhotos = array_values(array_diff($currentPhotos, $removedPhotos));
        unset($data['remove_photos']);

        $newPhotos = [];
        if ($request->hasFile('photos')) {
            $newPhotos = collect($request->file('photos'))
                ->map(fn ($photo) => ImageCompressor::compressAndStore($photo, 'uploads/tents'))
                ->all();
        }
        if ($removedPhotos || $newPhotos) {
            $data['photos'] = array_values(array_merge($remainingPhotos, $newPhotos));
            $data['photo_path'] = $data['photos'][0] ?? null;
        }
        $tent->update($data);
        foreach ($removedPhotos as $photo) {
            $folder = 'uploads/tents/';
            if (str_starts_with($photo, $folder) && basename($photo) === substr($photo, strlen($folder))) {
                Storage::disk('public')->delete($photo);
            }
        }

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
