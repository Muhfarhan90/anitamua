<?php

namespace App\Http\Controllers;

use App\Models\Faq;
use App\Models\Gallery;
use App\Models\Package;
use App\Models\Testimonial;

class LandingController extends Controller
{
    public function index()
    {
        $packages = Package::with(['benefits'])->where('status', 'active')->get();
        $testimonials = Testimonial::where('status', 'published')->get();
        $galleries = Gallery::orderBy('id', 'desc')->limit(8)->get();
        $faqs = Faq::where('status', 'published')->orderBy('sort_order')->get();

        return view('landing.home', compact('packages', 'testimonials', 'galleries', 'faqs'));
    }

    public function about()
    {
        return view('landing.about');
    }

    public function packages()
    {
        $packages = Package::with(['benefits'])->where('status', 'active')->get();

        return view('landing.packages', compact('packages'));
    }

    public function gallery()
    {
        $galleries = Gallery::orderBy('id', 'desc')->paginate(12);

        return view('landing.gallery', compact('galleries'));
    }

    public function testimonials()
    {
        $testimonials = Testimonial::where('status', 'published')->orderBy('id', 'desc')->paginate(9);

        return view('landing.testimonials', compact('testimonials'));
    }

    public function faq()
    {
        $faqs = Faq::where('status', 'published')->orderBy('sort_order')->get();

        return view('landing.faq', compact('faqs'));
    }

    public function contact()
    {
        return view('landing.contact');
    }
}
