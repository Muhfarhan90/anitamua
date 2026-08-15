<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PromoBanner;
use App\Services\ActivityLogger;
use App\Services\ImageCompressor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PromoBannerController extends Controller
{
    public function index()
    {
        $banners = PromoBanner::orderByDesc('id')->paginate(15);

        return view('admin.promo-banners.index', compact('banners'));
    }

    public function store(Request $request)
    {
        $data = $this->validateData($request);

        if ($request->hasFile('image')) {
            $data['image'] = ImageCompressor::compressAndStore($request->file('image'), 'uploads/banners');
        }

        $banner = PromoBanner::create($data);

        ActivityLogger::log('promo_banner_created', 'Promo banner ditambahkan', 'Promo banner "'.($banner->title ?? 'Tanpa judul').'" ditambahkan oleh '.auth()->user()->name);

        return back()->with('success', 'Promo banner berhasil ditambahkan.');
    }

    public function update(PromoBanner $banner, Request $request)
    {
        $data = $this->validateData($request);

        if ($request->hasFile('image')) {
            if ($banner->image && Storage::disk('public')->exists($banner->image)) {
                Storage::disk('public')->delete($banner->image);
            }

            $data['image'] = ImageCompressor::compressAndStore($request->file('image'), 'uploads/banners');
        }

        $banner->update($data);

        ActivityLogger::log('promo_banner_updated', 'Promo banner diperbarui', 'Promo banner "'.($banner->title ?? 'Tanpa judul').'" diperbarui oleh '.auth()->user()->name);

        return back()->with('success', 'Promo banner berhasil diperbarui.');
    }

    public function destroy(PromoBanner $banner)
    {
        if ($banner->image && Storage::disk('public')->exists($banner->image)) {
            Storage::disk('public')->delete($banner->image);
        }

        $banner->delete();

        ActivityLogger::log('promo_banner_deleted', 'Promo banner dihapus', 'Promo banner dihapus oleh '.auth()->user()->name);

        return back()->with('success', 'Promo banner dihapus.');
    }

    private function validateData(Request $request): array
    {
        return $request->validate([
            'title' => ['nullable', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:500'],
            'image' => ['nullable', 'image', 'max:2048'],
            'link' => ['nullable', 'url', 'max:500'],
            'is_active' => ['sometimes', 'boolean'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
        ]);
    }
}
