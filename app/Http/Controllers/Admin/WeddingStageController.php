<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\WeddingStage;
use App\Services\ActivityLogger;
use App\Services\ImageCompressor;
use Illuminate\Http\Request;

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
            'photo' => ['required', 'image', 'max:5120'],
            'is_active' => ['required', 'boolean'],
        ]);
        $data['photo_path'] = ImageCompressor::compressAndStore($request->file('photo'), 'uploads/wedding-stages');
        unset($data['photo']);

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
            'photo' => ['nullable', 'image', 'max:5120'],
            'is_active' => ['required', 'boolean'],
        ]);
        if ($request->hasFile('photo')) {
            $data['photo_path'] = ImageCompressor::compressAndStore($request->file('photo'), 'uploads/wedding-stages');
        }
        unset($data['photo']);

        $weddingStage->update($data);

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
