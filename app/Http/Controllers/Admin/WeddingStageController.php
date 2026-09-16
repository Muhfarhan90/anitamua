<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\WeddingStage;
use App\Services\ActivityLogger;
use App\Services\ImageCompressor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class WeddingStageController extends Controller
{
    public function index()
    {
        $weddingStages = WeddingStage::orderBy('name')->get();

        return view('admin.wedding-stages.index', compact('weddingStages'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:wedding_stages,name'],
            'size' => ['nullable', 'string', 'max:100'],
            'photos' => ['required', 'array', 'min:1'],
            'photos.*' => ['image', 'max:5120'],
            'is_active' => ['required', 'boolean'],
        ]);
        $data['photos'] = collect($request->file('photos'))
            ->map(fn ($photo) => ImageCompressor::compressAndStore($photo, 'uploads/wedding-stages'))
            ->all();
        $data['photo_path'] = $data['photos'][0];

        $weddingStage = WeddingStage::create($data);

        ActivityLogger::log(
            'wedding_stage_created',
            'Pelaminan ditambahkan',
            'Pelaminan '.$weddingStage->name.' ditambahkan oleh '.auth()->user()->name,
        );

        return back()->with('success', 'Pelaminan berhasil ditambahkan.');
    }

    public function update(Request $request, WeddingStage $weddingStage)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:wedding_stages,name,'.$weddingStage->id],
            'size' => ['nullable', 'string', 'max:100'],
            'photos' => ['sometimes', 'array'],
            'photos.*' => ['image', 'max:5120'],
            'remove_photos' => ['sometimes', 'array'],
            'remove_photos.*' => ['string', 'max:2048'],
            'is_active' => ['required', 'boolean'],
        ]);
        $currentPhotos = array_values(array_filter($weddingStage->photos ?: [$weddingStage->photo_path]));
        $removedPhotos = array_values(array_intersect($data['remove_photos'] ?? [], $currentPhotos));
        $remainingPhotos = array_values(array_diff($currentPhotos, $removedPhotos));
        unset($data['remove_photos']);

        $newPhotos = [];
        if ($request->hasFile('photos')) {
            $newPhotos = collect($request->file('photos'))
                ->map(fn ($photo) => ImageCompressor::compressAndStore($photo, 'uploads/wedding-stages'))
                ->all();
        }
        if ($removedPhotos || $newPhotos) {
            $data['photos'] = array_values(array_merge($remainingPhotos, $newPhotos));
            $data['photo_path'] = $data['photos'][0] ?? null;
        }

        $weddingStage->update($data);
        foreach ($removedPhotos as $photo) {
            $folder = 'uploads/wedding-stages/';
            if (str_starts_with($photo, $folder) && basename($photo) === substr($photo, strlen($folder))) {
                Storage::disk('public')->delete($photo);
            }
        }

        ActivityLogger::log(
            'wedding_stage_updated',
            'Pelaminan diperbarui',
            'Pelaminan '.$weddingStage->name.' diperbarui oleh '.auth()->user()->name,
        );

        return back()->with('success', 'Pelaminan berhasil diperbarui.');
    }

    public function destroy(WeddingStage $weddingStage)
    {
        if ($weddingStage->surveys()->exists()) {
            return back()->with('warning', 'Pelaminan sudah dipakai pada survey. Nonaktifkan saja.');
        }

        $name = $weddingStage->name;
        $weddingStage->delete();

        ActivityLogger::log(
            'wedding_stage_deleted',
            'Pelaminan dihapus',
            'Pelaminan '.$name.' dihapus oleh '.auth()->user()->name,
        );

        return back()->with('success', 'Pelaminan berhasil dihapus.');
    }
}
