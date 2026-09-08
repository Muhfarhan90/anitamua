<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Faq;
use App\Models\Gallery;
use App\Models\SiteSetting;
use App\Models\Testimonial;
use App\Services\ActivityLogger;
use App\Services\ImageCompressor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ContentController extends Controller
{
    // ============ TESTIMONI ============

    public function testimonials()
    {
        $testimonials = Testimonial::with('booking')->orderByDesc('created_at')->paginate(15);

        return view('admin.content.testimonials', compact('testimonials'));
    }

    public function storeTestimonial(Request $request)
    {
        $data = $request->validate([
            'client_name' => ['required', 'string', 'max:255'],
            'rating' => ['required', 'integer', 'between:1,5'],
            'content' => ['required', 'string'],
            'status' => ['required', 'in:published,hidden'],
        ]);

        if ($request->hasFile('photo')) {
            $data['photo'] = $request->file('photo')->store('uploads/testimonials', 'public');
        }

        $testimonial = Testimonial::create($data);

        ActivityLogger::log('testimonial_created', 'Testimoni ditambahkan', 'Testimoni dari '.$testimonial->client_name.' ditambahkan oleh '.auth()->user()->name);

        return back()->with('success', 'Testimoni berhasil ditambahkan.');
    }

    public function destroyTestimonial(Testimonial $testimonial)
    {
        $testimonial->delete();

        ActivityLogger::log('testimonial_deleted', 'Testimoni dihapus', 'Testimoni dari '.$testimonial->client_name.' dihapus oleh '.auth()->user()->name);

        return back()->with('success', 'Testimoni dihapus.');
    }

    // ============ GALERI ============

    public function gallery()
    {
        $gallery = Gallery::with('booking')->orderByDesc('created_at')->paginate(15);

        return view('admin.content.gallery', compact('gallery'));
    }

    public function storeGallery(Request $request)
    {
        $data = $request->validate([
            'title' => ['nullable', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:100'],
            'photos' => ['required', 'array', 'min:3'],
            'photos.*' => ['required', 'image', 'max:8192'],
        ], [
            'photos.min' => 'Unggah minimal tiga foto untuk satu item galeri.',
        ]);

        $data['photos'] = collect($request->file('photos'))
            ->map(fn ($photo) => $photo->store('uploads/gallery', 'public'))
            ->all();
        $data['photo'] = $data['photos'][0];

        Gallery::create($data);

        ActivityLogger::log('gallery_created', 'Galeri ditambahkan', 'Galeri "'.($data['title'] ?? 'Tanpa judul').'" dengan '.count($data['photos']).' foto ditambahkan oleh '.auth()->user()->name);

        return back()->with('success', 'Galeri berhasil ditambahkan.');
    }

    public function destroyGallery(Gallery $gallery)
    {
        collect([$gallery->photo, ...($gallery->photos ?? [])])
            ->filter()
            ->unique()
            ->each(fn (string $photo) => Storage::disk('public')->delete($photo));

        $gallery->delete();

        ActivityLogger::log('gallery_deleted', 'Foto galeri dihapus', 'Foto galeri dihapus oleh '.auth()->user()->name);

        return back()->with('success', 'Foto galeri dihapus.');
    }

    // ============ FAQ ============

    public function faqs()
    {
        $faqs = Faq::orderBy('sort_order')->orderBy('id')->get();

        return view('admin.content.faqs', compact('faqs'));
    }

    public function storeFaq(Request $request)
    {
        $data = $request->validate([
            'question' => ['required', 'string', 'max:255'],
            'answer' => ['required', 'string'],
            'status' => ['required', 'in:published,hidden'],
        ]);

        $data['sort_order'] = Faq::max('sort_order') + 1;

        Faq::create($data);

        ActivityLogger::log('faq_created', 'FAQ ditambahkan', 'FAQ "'.str($data['question'])->limit(50).'" ditambahkan oleh '.auth()->user()->name);

        return back()->with('success', 'FAQ berhasil ditambahkan.');
    }

    public function reorderFaqs(Request $request)
    {
        $data = $request->validate([
            'order' => ['required', 'array'],
            'order.*' => ['integer'],
        ]);

        foreach ($data['order'] as $index => $id) {
            Faq::where('id', $id)->update(['sort_order' => $index + 1]);
        }

        ActivityLogger::log('faq_reordered', 'Urutan FAQ diubah', 'Urutan FAQ diubah oleh '.auth()->user()->name);

        return response()->json(['success' => true]);
    }

    public function updateFaq(Request $request, Faq $faq)
    {
        $data = $request->validate([
            'question' => ['required', 'string', 'max:255'],
            'answer' => ['required', 'string'],
            'status' => ['required', 'in:published,hidden'],
        ]);

        $faq->update($data);

        ActivityLogger::log('faq_updated', 'FAQ diperbarui', 'FAQ diperbarui oleh '.auth()->user()->name);

        return back()->with('success', 'FAQ berhasil diperbarui.');
    }

    public function destroyFaq(Faq $faq)
    {
        $faq->delete();

        ActivityLogger::log('faq_deleted', 'FAQ dihapus', 'FAQ dihapus oleh '.auth()->user()->name);

        return back()->with('success', 'FAQ dihapus.');
    }

    // ============ PENGATURAN SITUS ============

    public function settings()
    {
        $keys = ['company_name', 'tagline', 'about', 'address', 'phone', 'email', 'instagram', 'whatsapp', 'bank_name', 'bank_account_number', 'bank_account_name', 'invoice_greeting', 'logo', 'landing_hero_image', 'about_image'];

        $settings = SiteSetting::whereIn('key', $keys)->pluck('value', 'key');

        return view('admin.content.settings', compact('settings', 'keys'));
    }

    public function storeSettings(Request $request)
    {
        $data = $request->validate([
            'company_name' => ['nullable', 'string', 'max:100'],
            'tagline' => ['nullable', 'string', 'max:200'],
            'about' => ['nullable', 'string'],
            'address' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:100'],
            'instagram' => ['nullable', 'string', 'max:100'],
            'whatsapp' => ['nullable', 'string', 'max:30'],
            'bank_name' => ['nullable', 'string', 'max:50'],
            'bank_account_number' => ['nullable', 'string', 'max:50'],
            'bank_account_name' => ['nullable', 'string', 'max:100'],
            'invoice_greeting' => ['nullable', 'string', 'max:500'],
            'logo' => ['nullable', 'image', 'max:2048'],
            'landing_hero_image' => ['nullable', 'image', 'max:8192'],
            'about_image' => ['nullable', 'image', 'max:8192'],
        ]);

        foreach (['logo' => 'uploads/logo', 'landing_hero_image' => 'uploads/site', 'about_image' => 'uploads/site'] as $key => $directory) {
            if ($request->hasFile($key)) {
                $oldImage = SiteSetting::where('key', $key)->value('value');
                if ($oldImage && Storage::disk('public')->exists($oldImage)) {
                    Storage::disk('public')->delete($oldImage);
                }

                $data[$key] = ImageCompressor::compressAndStore($request->file($key), $directory);
            } else {
                unset($data[$key]);
            }
        }

        foreach ($data as $key => $value) {
            SiteSetting::set($key, $value);
        }

        // Hapus cache agar perubahan langsung tampil di landing & dashboard
        cache()->forget('site_settings');

        ActivityLogger::log('settings_updated', 'Pengaturan situs diperbarui', 'Pengaturan situs diperbarui oleh '.auth()->user()->name);

        return back()->with('success', 'Pengaturan situs berhasil disimpan.');
    }

}
