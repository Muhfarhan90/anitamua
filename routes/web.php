<?php

use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\BenefitCategoryController;
use App\Http\Controllers\Admin\BenefitController;
use App\Http\Controllers\Admin\BookingManagementController;
use App\Http\Controllers\Admin\ContentController;
use App\Http\Controllers\Admin\FieldWorkController;
use App\Http\Controllers\Admin\FinanceController;
use App\Http\Controllers\Admin\InventoryCategoryController;
use App\Http\Controllers\Admin\InventoryController;
use App\Http\Controllers\Admin\PackageController;
use App\Http\Controllers\Admin\PackingController;
use App\Http\Controllers\Admin\PaymentController;
use App\Http\Controllers\Admin\PromoBannerController;
use App\Http\Controllers\Admin\ScheduleController;
use App\Http\Controllers\Admin\VendorCategoryController;
use App\Http\Controllers\Admin\VendorController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LandingController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

// ============ LANDING (PUBLIC) ============
Route::get('/', [LandingController::class, 'index'])->name('home');
Route::get('/tentang', [LandingController::class, 'about'])->name('about');
Route::get('/paket', [LandingController::class, 'packages'])->name('packages');
Route::get('/galeri', [LandingController::class, 'gallery'])->name('gallery');
Route::get('/testimoni', [LandingController::class, 'testimonials'])->name('testimonials');
Route::get('/faq', [LandingController::class, 'faq'])->name('faq');
Route::get('/kontak', [LandingController::class, 'contact'])->name('contact');

Route::get('/booking', [BookingController::class, 'create'])->name('booking.create');
Route::post('/booking', [BookingController::class, 'store'])->name('booking.store');
Route::get('/booking/sukses/{code}', [BookingController::class, 'success'])->name('booking.success');

// ============ AUTH ============
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.post');
});

Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

// ============ DASHBOARD (per role) ============
Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/client/booking/{booking}', [ClientController::class, 'booking'])->name('client.booking');
    Route::post('/client/booking/{booking}/proof', [ClientController::class, 'uploadProof'])->name('client.booking.proof');

    // Profil & ganti password
    Route::get('/profile', [ProfileController::class, 'show'])->name('profile');
    Route::post('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::post('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');

    // ============ BACK OFFICE MVP (booking flow only) ============
    // Fitur fase 2+ (packing, survey, fitting, finance, vendor, inventory,
    // reminder, timeline, users, ubah paket) belum diaktifkan.

    Route::middleware('role:owner,admin,team')->group(function () {
        Route::get('/admin/calendar', [ScheduleController::class, 'index'])->name('admin.calendar');

        // Harus terdaftar SEBELUM /admin/bookings/{booking} agar tidak tertangkap sebagai {booking}
        Route::get('/admin/bookings/create', [BookingManagementController::class, 'create'])
            ->name('admin.bookings.create')
            ->middleware('role:owner,admin');

        Route::get('/admin/bookings', [BookingManagementController::class, 'index'])->name('admin.bookings.index');
        Route::post('/admin/schedules/{schedule}/status', [ScheduleController::class, 'updateStatus'])->name('admin.schedules.status');
        Route::post('/admin/schedules/{schedule}/pic', [ScheduleController::class, 'updatePic'])->name('admin.schedules.pic');

        // Survey & Fitting (data lapangan)
        Route::get('/admin/fieldwork', [FieldWorkController::class, 'index'])->name('admin.fieldwork.index');
        Route::get('/admin/fieldwork/{booking}', [FieldWorkController::class, 'fieldwork'])->name('admin.fieldwork.booking');
        Route::post('/admin/survey', [FieldWorkController::class, 'surveyStore'])->name('admin.survey.store');
        Route::post('/admin/fitting', [FieldWorkController::class, 'fittingStore'])->name('admin.fitting.store');

        // Packing Checklist (Owner, Admin, Tim Lapangan)
        Route::get('/admin/bookings/{booking}/packing', [PackingController::class, 'show'])->name('admin.bookings.packing');
        Route::post('/admin/packing', [PackingController::class, 'createChecklist'])->name('admin.packing.create');
        Route::post('/admin/packing/{packingList}/items/{item}/toggle', [PackingController::class, 'toggleItem'])->name('admin.packing.toggle');
        Route::post('/admin/packing/{packingList}/close', [PackingController::class, 'close'])->name('admin.packing.close');

        // Survey & Fitting (Tim Lapangan)
        Route::post('/admin/fieldwork/survey', [FieldWorkController::class, 'surveyStore'])->name('admin.fieldwork.survey');
        Route::post('/admin/fieldwork/fitting', [FieldWorkController::class, 'fittingStore'])->name('admin.fieldwork.fitting');

        Route::prefix('admin')->name('admin.')->middleware('role:owner,admin')->group(function () {
            // Booking management (operasional admin)
            Route::post('/bookings', [BookingManagementController::class, 'store'])->name('bookings.store');
            Route::get('/bookings/{booking}/edit', [BookingManagementController::class, 'edit'])->name('bookings.edit');
            Route::patch('/bookings/{booking}', [BookingManagementController::class, 'update'])->name('bookings.update');
            Route::get('/bookings/{booking}', [BookingManagementController::class, 'show'])->name('bookings.show');
            Route::post('/bookings/{booking}/verify-dp', [BookingManagementController::class, 'verifyDp'])->name('bookings.verify-dp');
            Route::post('/bookings/{booking}/cancel', [BookingManagementController::class, 'cancel'])->name('bookings.cancel');
            Route::post('/bookings/{booking}/complete', [BookingManagementController::class, 'complete'])->name('bookings.complete');

            // Payments (tahap dibuat oleh client; admin hanya verifikasi)
            Route::get('/payments', [PaymentController::class, 'index'])->name('payments.index');
            Route::post('/payments/{payment}/verify', [PaymentController::class, 'verify'])->name('payments.verify');

            // Packages (dibutuhkan form booking & pricelist)
            Route::resource('/packages', PackageController::class)->names('packages')->except('show');

            // Master Benefit (Owner & Admin) — benefit dipakai ulang banyak paket
            Route::get('/benefits', [BenefitController::class, 'index'])->name('benefits.index');
            Route::post('/benefits', [BenefitController::class, 'store'])->name('benefits.store');
            Route::put('/benefits/{benefit}', [BenefitController::class, 'update'])->name('benefits.update');
            Route::delete('/benefits/{benefit}', [BenefitController::class, 'destroy'])->name('benefits.destroy');

            // Kategori Benefit (Owner & Admin)
            Route::get('/benefit-categories', [BenefitCategoryController::class, 'index'])->name('benefit-categories.index');
            Route::post('/benefit-categories', [BenefitCategoryController::class, 'store'])->name('benefit-categories.store');
            Route::put('/benefit-categories/{category}', [BenefitCategoryController::class, 'update'])->name('benefit-categories.update');
            Route::delete('/benefit-categories/{category}', [BenefitCategoryController::class, 'destroy'])->name('benefit-categories.destroy');

            // // Master Vendor (Owner & Admin) — dinonaktifkan (modul vendor tidak dipakai)
            // Route::resource('/vendors', VendorController::class)->names('vendors')->except('show');

            // // Kategori Vendor (Owner & Admin) — dinonaktifkan
            // Route::get('/vendor-categories', [VendorCategoryController::class, 'index'])->name('vendor-categories.index');
            // Route::post('/vendor-categories', [VendorCategoryController::class, 'store'])->name('vendor-categories.store');
            // Route::put('/vendor-categories/{category}', [VendorCategoryController::class, 'update'])->name('vendor-categories.update');
            // Route::delete('/vendor-categories/{category}', [VendorCategoryController::class, 'destroy'])->name('vendor-categories.destroy');

            // Inventory Wardrobe (Owner & Admin)
            Route::get('/inventory', [InventoryController::class, 'index'])->name('inventory.index');
            Route::post('/inventory', [InventoryController::class, 'store'])->name('inventory.store');
            Route::put('/inventory/{item}', [InventoryController::class, 'update'])->name('inventory.update');
            Route::delete('/inventory/{item}', [InventoryController::class, 'destroy'])->name('inventory.destroy');

            // Kategori Inventory (Owner & Admin)
            Route::get('/inventory-categories', [InventoryCategoryController::class, 'index'])->name('inventory-categories.index');
            Route::post('/inventory-categories', [InventoryCategoryController::class, 'store'])->name('inventory-categories.store');
            Route::put('/inventory-categories/{category}', [InventoryCategoryController::class, 'update'])->name('inventory-categories.update');
            Route::delete('/inventory-categories/{category}', [InventoryCategoryController::class, 'destroy'])->name('inventory-categories.destroy');

            // // Reminder manual & Timeline (Owner & Admin) — dinonaktifkan sementara
            // Route::get('/reminders', [AdminController::class, 'reminders'])->name('reminders.index');
            // Route::post('/reminders', [AdminController::class, 'storeReminder'])->name('reminders.store');
            // Route::delete('/reminders/{reminder}', [AdminController::class, 'destroyReminder'])->name('reminders.destroy');
            // Route::get('/timeline', [AdminController::class, 'timeline'])->name('timeline.index');

            // Schedules (admin)
            Route::post('/schedules', [ScheduleController::class, 'store'])->name('schedules.store');
            Route::delete('/schedules/{schedule}', [ScheduleController::class, 'destroy'])->name('schedules.destroy');

            // Promo Banner popup (Owner & Admin)
            Route::get('/promo-banners', [PromoBannerController::class, 'index'])->name('promo-banners.index');
            Route::post('/promo-banners', [PromoBannerController::class, 'store'])->name('promo-banners.store');
            Route::put('/promo-banners/{banner}', [PromoBannerController::class, 'update'])->name('promo-banners.update');
            Route::delete('/promo-banners/{banner}', [PromoBannerController::class, 'destroy'])->name('promo-banners.destroy');

                // Manajemen User (Owner only) — Staff & Klien
                Route::middleware('role:owner')->group(function () {
                    Route::get('/users/staff', [AdminController::class, 'staffs'])->name('users.staff');
                    Route::post('/users/staff', [AdminController::class, 'storeStaff'])->name('users.staff.store');
                    Route::get('/users/clients', [AdminController::class, 'clients'])->name('users.clients');
                    Route::post('/users/clients', [AdminController::class, 'storeClient'])->name('users.clients.store');
                    Route::patch('/users/{user}/toggle', [AdminController::class, 'toggleUser'])->name('users.toggle');
                    Route::get('/users/{user}/edit', [AdminController::class, 'edit'])->name('users.edit');
                    Route::patch('/users/{user}', [AdminController::class, 'update'])->name('users.update');
                    Route::delete('/users/{user}', [AdminController::class, 'destroy'])->name('users.destroy');
                });

                // Konten Website — Owner only
                Route::get('/content/testimonials', [ContentController::class, 'testimonials'])->name('content.testimonials');
                Route::post('/content/testimonials', [ContentController::class, 'storeTestimonial'])->name('content.testimonials.store');
                Route::delete('/content/testimonials/{testimonial}', [ContentController::class, 'destroyTestimonial'])->name('content.testimonials.destroy');

                Route::get('/content/gallery', [ContentController::class, 'gallery'])->name('content.gallery');
                Route::post('/content/gallery', [ContentController::class, 'storeGallery'])->name('content.gallery.store');
                Route::delete('/content/gallery/{gallery}', [ContentController::class, 'destroyGallery'])->name('content.gallery.destroy');

                Route::get('/content/faqs', [ContentController::class, 'faqs'])->name('content.faqs');
                Route::post('/content/faqs', [ContentController::class, 'storeFaq'])->name('content.faqs.store');
                Route::post('/content/faqs/reorder', [ContentController::class, 'reorderFaqs'])->name('content.faqs.reorder');
                Route::put('/content/faqs/{faq}', [ContentController::class, 'updateFaq'])->name('content.faqs.update');
                Route::delete('/content/faqs/{faq}', [ContentController::class, 'destroyFaq'])->name('content.faqs.destroy');

                Route::get('/content/settings', [ContentController::class, 'settings'])->name('content.settings');
                Route::post('/content/settings', [ContentController::class, 'storeSettings'])->name('content.settings.store');
        });
    });
});
