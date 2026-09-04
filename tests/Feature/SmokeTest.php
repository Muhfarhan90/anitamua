<?php

use App\Models\ActivityLog;
use App\Models\Booking;
use App\Models\Package;
use App\Models\Payment;
use App\Models\Reminder;
use App\Models\User;
use App\Models\WeddingStage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed();
});

it('renders landing pages', function () {
    $this->get('/')->assertOk();
    $this->get('/tentang')->assertOk();
    $this->get('/paket')->assertOk();
    $this->get('/galeri')->assertOk();
    $this->get('/testimoni')->assertOk();
    $this->get('/faq')->assertOk();
    $this->get('/kontak')->assertOk();
    $this->get('/booking')->assertOk();
    $this->get('/booking/sukses/AMU-'.date('Y').'-0001')->assertOk();
});

it('client can login with email or whatsapp number', function () {
    $client = User::where('email', 'client@anitamua.com')->first();

    $this->post('/login', ['login' => $client->email, 'password' => 'password'])
        ->assertRedirect('/dashboard');
    $this->assertAuthenticatedAs($client);

    Auth::logout();

    $this->post('/login', ['login' => $client->phone, 'password' => 'password'])
        ->assertRedirect('/dashboard');
    $this->assertAuthenticatedAs($client);
});

it('client can change password from profile', function () {
    $client = User::where('email', 'client@anitamua.com')->first();

    $this->actingAs($client)->get('/profile')->assertOk();

    $this->actingAs($client)->post('/profile/password', [
        'current_password' => 'password',
        'password' => 'rahasia123',
        'password_confirmation' => 'rahasia123',
    ])->assertRedirect();

    $client->refresh();
    expect(Auth::attempt(['email' => 'client@anitamua.com', 'password' => 'rahasia123']))->toBeTrue();
    expect(Auth::attempt(['email' => 'client@anitamua.com', 'password' => 'password']))->toBeFalse();
});

it('rejects password change with wrong current password', function () {
    $client = User::where('email', 'client@anitamua.com')->first();

    $this->actingAs($client)->post('/profile/password', [
        'current_password' => 'salah',
        'password' => 'rahasia123',
        'password_confirmation' => 'rahasia123',
    ])->assertSessionHasErrors('current_password');

    $client->refresh();
    expect(Auth::attempt(['email' => 'client@anitamua.com', 'password' => 'password']))->toBeTrue();
});

it('team can save survey and fitting data', function () {
    Storage::fake('public');
    $team = User::where('email', 'team@anitamua.com')->first();
    $booking = Booking::first();
    $weddingStage = WeddingStage::create(['name' => 'Garden Modern', 'is_active' => true]);

    $this->actingAs($team)->post('/admin/fieldwork/survey', [
        'booking_id' => $booking->id,
        'wedding_stage_id' => $weddingStage->id,
        'flower_color' => 'Putih dan sage',
        'stage_size' => '6m',
        'tent_sizes' => ['4X6', '5X5'],
        'tent_size_quantities' => ['4X6' => 2, '5X5' => 1],
        'tent_additions' => ['4X4'],
        'tent_addition_quantities' => ['4X4' => 1],
        'gallery_booth' => 'Ya',
        'location' => 'The Glass House',
        'maps_url' => 'https://maps.app.goo.gl/abc',
        'pic' => 'Pak Budi',
        'notes' => 'Panggung di tengah',
        'photos' => [UploadedFile::fake()->image('survey.jpg')],
    ])->assertRedirect();

    $survey = $booking->survey()->first();
    expect($survey)->not->toBeNull();
    expect($survey->location)->toBe('The Glass House');
    expect($survey->wedding_stage_id)->toBe($weddingStage->id);
    expect($survey->tent_sizes)->toBe(['4X6', '5X5']);
    expect($survey->tent_size_quantities)->toBe(['4X6' => 2, '5X5' => 1]);
    expect($survey->tent_addition_quantities)->toBe(['4X4' => 1]);
    // foto baru tersimpan & dikompres
    $photos = $survey->photos;
    expect(count($photos))->toBeGreaterThanOrEqual(1);
    expect($photos[0])->toStartWith('uploads/photos/');
    Storage::disk('public')->assertExists(end($photos));

    $this->actingAs($team)->post('/admin/fieldwork/fitting', [
        'booking_id' => $booking->id,
        'date' => now()->addDays(5)->toDateString(),
        'time' => '13:00',
        'pic' => 'Dewi P.',
        'notes' => 'Sesuaikan gaun',
        'status' => 'scheduled',
    ])->assertRedirect();

    expect($booking->fittings()->count())->toBeGreaterThan(0);
    $booking->refresh();
    expect($booking->fitting_date->toDateString())->toBe(now()->addDays(5)->toDateString());
    expect($booking->fittings()->first()->cpw_busana_akad_notes)->toBeNull();
});

it('admin can manage wedding stages', function () {
    $admin = User::where('email', 'admin@anitamua.com')->first();

    $this->actingAs($admin)->post('/admin/wedding-stages', [
        'name' => 'Rustic White',
        'is_active' => 1,
        'photo' => UploadedFile::fake()->image('rustic-white.jpg'),
    ])->assertRedirect();

    $weddingStage = WeddingStage::where('name', 'Rustic White')->firstOrFail();
    expect($weddingStage->is_active)->toBeTrue();
    expect($weddingStage->photo_path)->toStartWith('uploads/wedding-stages/');
    Storage::disk('public')->assertExists($weddingStage->photo_path);

    $this->actingAs($admin)->put('/admin/wedding-stages/'.$weddingStage->id, [
        'name' => 'Rustic White Updated',
        'is_active' => 0,
        'photo' => UploadedFile::fake()->image('rustic-white-updated.jpg'),
    ])->assertRedirect();

    expect($weddingStage->refresh()->name)->toBe('Rustic White Updated');
    expect($weddingStage->is_active)->toBeFalse();
    expect($weddingStage->photo_path)->toStartWith('uploads/wedding-stages/');

    $this->actingAs($admin)->delete('/admin/wedding-stages/'.$weddingStage->id)->assertRedirect();
    expect(WeddingStage::find($weddingStage->id))->toBeNull();
});

it('saves fitting checklist notes and keeps an item photo on edit', function () {
    Storage::fake('public');
    $team = User::where('email', 'team@anitamua.com')->first();
    $booking = Booking::first();
    $photo = UploadedFile::fake()->image('cpp-akad.jpg');

    $this->actingAs($team)->post('/admin/fieldwork/fitting', [
        'booking_id' => $booking->id,
        'date' => now()->addDays(8)->toDateString(),
        'status' => 'on_going',
        'items' => [
            'cpp_busana_akad' => ['notes' => 'Jas hitam', 'photo' => $photo],
            'ukuran_bb_tb_ld' => ['notes' => '70 kg / 175 cm / 92 cm'],
        ],
    ])->assertRedirect();

    $fitting = $booking->fittings()->firstOrFail();
    $photoPath = $fitting->cpp_busana_akad_photo_path;
    expect($fitting->cpp_busana_akad_notes)->toBe('Jas hitam');
    expect($photoPath)->toStartWith('uploads/photos/');
    Storage::disk('public')->assertExists($photoPath);

    $this->actingAs($team)->post('/admin/fieldwork/fitting', [
        'booking_id' => $booking->id,
        'date' => now()->addDays(8)->toDateString(),
        'status' => 'finished',
        'items' => [
            'cpp_busana_akad' => ['notes' => 'Jas hitam sudah pas'],
        ],
    ])->assertRedirect();

    $fitting->refresh();
    expect($fitting->cpp_busana_akad_notes)->toBe('Jas hitam sudah pas');
    expect($fitting->cpp_busana_akad_photo_path)->toBe($photoPath);
});

it('rejects selecting an inactive decoration for a new survey', function () {
    $team = User::where('email', 'team@anitamua.com')->first();
    $booking = Booking::first();
    $weddingStage = WeddingStage::create(['name' => 'Legacy Decor', 'is_active' => false]);

    $this->actingAs($team)->post('/admin/fieldwork/survey', [
        'booking_id' => $booking->id,
        'wedding_stage_id' => $weddingStage->id,
    ])->assertSessionHasErrors('wedding_stage_id');
});

it('admin creating booking auto-verifies dp, books it, and adds hari h schedule', function () {
    Storage::fake('public');
    $admin = User::where('email', 'admin@anitamua.com')->first();
    $client = User::where('email', 'client@anitamua.com')->first();
    $package = Package::first();

    $eventDate = now()->addMonths(2)->toDateString();

    $this->actingAs($admin)->post('/admin/bookings', [
        'client_mode' => 'existing',
        'client_id' => $client->id,
        'package_id' => $package->id,
        'event_date' => $eventDate,
        'event_time' => '18:30',
        'location' => 'Ballroom Hotel X',
        'dp1_amount' => 500000,
        'proof' => UploadedFile::fake()->image('bukti-admin.jpg'),
    ])->assertRedirect();

    $booking = Booking::where('email', $client->email)->orderByDesc('id')->first();
    expect($booking)->not->toBeNull();
    expect($booking->status)->toBe('booked');
    expect($booking->client_id)->toBe($client->id);
    expect($booking->name)->toBe($client->name);
    expect($booking->phone)->toBe($client->phone);
    expect($booking->email)->toBe($client->email);

    $payment = $booking->payments()->whereIn('type', ['dp1', 'dp10'])->first();
    expect($payment)->not->toBeNull();
    expect($payment->status)->toBe('verified');
    expect($payment->proof)->not->toBeNull();
    expect($payment->proof)->toStartWith('uploads/proofs/');
    Storage::disk('public')->assertExists($payment->proof);

    // Jadwal Hari H otomatis masuk kalender
    $hariH = $booking->schedules()->where('type', 'hari_h')->first();
    expect($hariH)->not->toBeNull();
    expect($hariH->date->toDateString())->toBe($eventDate);
    expect($hariH->time->format('H:i'))->toBe('18:30');
    expect($hariH->status)->toBe('scheduled');
});

it('admin can create and edit manual booking add-ons without changing payments', function () {
    Storage::fake('public');
    $admin = User::where('email', 'admin@anitamua.com')->first();
    $client = User::where('email', 'client@anitamua.com')->first();
    $package = Package::first();

    $this->actingAs($admin)->post('/admin/bookings', [
        'client_mode' => 'existing',
        'client_id' => $client->id,
        'package_id' => $package->id,
        'event_date' => now()->addMonths(2)->toDateString(),
        'dp1_amount' => 1000000,
        'addons' => [
            ['name' => 'Extra Touch Up', 'price' => 250000],
            ['name' => 'Tambahan Hijab', 'price' => 150000],
        ],
    ])->assertRedirect();

    $booking = Booking::where('client_id', $client->id)->latest('id')->firstOrFail();
    $paidBefore = (float) $booking->payments()->where('status', 'verified')->sum('amount');

    expect($booking->addons()->count())->toBe(2);
    expect($booking->total_price)->toBe((float) $package->price + 400000.0);

    $this->actingAs($admin)->patch('/admin/bookings/'.$booking->id, [
        'client_id' => $client->id,
        'package_id' => $package->id,
        'name' => $booking->name,
        'phone' => $booking->phone,
        'email' => $booking->email,
        'event_date' => $booking->event_date->toDateString(),
        'location' => $booking->location,
        'notes' => $booking->notes,
        'status' => $booking->status,
        'addons' => [
            ['name' => 'Extra Touch Up Premium', 'price' => 300000],
        ],
    ])->assertRedirect();

    $booking->refresh();
    expect($booking->addons()->count())->toBe(1);
    expect($booking->addons()->first()->name)->toBe('Extra Touch Up Premium');
    expect($booking->total_price)->toBe((float) $package->price + 300000.0);
    expect((float) $booking->payments()->where('status', 'verified')->sum('amount'))->toBe($paidBefore);

    $this->actingAs($client)
        ->get('/client/booking/'.$booking->id)
        ->assertOk()
        ->assertSee('Extra Touch Up Premium')
        ->assertSee('Rp 300.000');
});

it('admin booking form saves survey and fitting details', function () {
    Storage::fake('public');
    $admin = User::where('email', 'admin@anitamua.com')->first();
    $client = User::where('email', 'client@anitamua.com')->first();
    $package = Package::first();
    $stage = WeddingStage::create(['name' => 'Classic White', 'is_active' => true]);

    $this->actingAs($admin)->post('/admin/bookings', [
        'client_mode' => 'existing',
        'client_id' => $client->id,
        'package_id' => $package->id,
        'event_date' => now()->addMonths(3)->toDateString(),
        'dp1_amount' => 1000000,
        'survey_wedding_stage_id' => $stage->id,
        'survey_location' => 'Gedung Serbaguna',
        'survey_flower_color' => 'Putih',
        'survey_tent_sizes' => ['4X6', '5X5'],
        'survey_tent_size_quantities' => ['4X6' => 2, '5X5' => 1],
        'survey_tent_additions' => ['4X4'],
        'survey_tent_addition_quantities' => ['4X4' => 1],
        'fitting_date' => now()->addDays(10)->toDateString(),
        'fitting_status' => 'scheduled',
        'items' => [
            'cpp_busana_akad' => [
                'notes' => 'Jas hitam',
                'photo' => UploadedFile::fake()->image('cpp-akad.jpg'),
            ],
        ],
    ])->assertRedirect();

    $booking = Booking::where('client_id', $client->id)->latest('id')->firstOrFail();
    expect($booking->survey->wedding_stage_id)->toBe($stage->id);
    expect($booking->survey->tent_sizes)->toBe(['4X6', '5X5']);
    expect($booking->survey->tent_size_quantities)->toBe(['4X6' => 2, '5X5' => 1]);
    expect($booking->fittings->first()->cpp_busana_akad_notes)->toBe('Jas hitam');
    expect($booking->fittings->first()->cpp_busana_akad_photo_path)->not->toBeNull();
    Storage::disk('public')->assertExists($booking->fittings->first()->cpp_busana_akad_photo_path);

    $this->actingAs($admin)->get('/admin/bookings/'.$booking->id.'/edit')
        ->assertOk()
        ->assertSee('Data Survey')
        ->assertSee('Data Fitting')
        ->assertSee('Classic White')
        ->assertSee('CPW', false)
        ->assertSee('CPP', false)
        ->assertSee('Nama stylist / hijab')
        ->assertSee('Foto busana')
        ->assertSee('TULIS / ISI BB / TB / LD', false);
});

it('rejects invalid manual booking add-ons', function () {
    $admin = User::where('email', 'admin@anitamua.com')->first();
    $client = User::where('email', 'client@anitamua.com')->first();
    $package = Package::first();

    $this->actingAs($admin)->post('/admin/bookings', [
        'client_mode' => 'existing',
        'client_id' => $client->id,
        'package_id' => $package->id,
        'event_date' => now()->addMonths(2)->toDateString(),
        'dp1_amount' => 1000000,
        'addons' => [
            ['name' => '', 'price' => -1],
        ],
    ])->assertSessionHasErrors(['addons.0.name', 'addons.0.price']);
});

it('guest can create booking without an account', function () {
    $package = Package::first();

    $this->get('/booking')->assertOk();

    $this->post('/booking', [
        'package_id' => $package->id,
        'name' => 'Reno & Dewi',
        'phone' => '0812999888777',
        'email' => 'renodewi@test.com',
        'event_date' => now()->addMonths(2)->toDateString(),
        'location' => 'Ballroom Hotel X',
        'proof' => UploadedFile::fake()->image('bukti.jpg'),
    ])->assertRedirect();

    $booking = Booking::where('email', 'renodewi@test.com')->first();
    expect($booking)->not->toBeNull();
    expect($booking->status)->toBe('pending');
    expect($booking->client_id)->toBeNull();
});

it('guest can book with compressed dp10 proof upload', function () {
    Storage::fake('public');
    $package = Package::first();

    $this->post('/booking', [
        'package_id' => $package->id,
        'name' => 'Reno & Dewi',
        'phone' => '0812999888777',
        'email' => 'renodewi@test.com',
        'event_date' => now()->addMonths(2)->toDateString(),
        'location' => 'Ballroom Hotel X',
        'proof' => UploadedFile::fake()->image('bukti.jpg', 3000, 2000),
    ])->assertRedirect();

    $booking = Booking::where('email', 'renodewi@test.com')->firstOrFail();

    $payment = $booking->payments()->where('type', 'dp10')->first();
    expect($payment)->not->toBeNull();
    expect((float) $payment->amount)->toBe((float) round($package->price * 0.1));
    expect($payment->proof)->not->toBeNull();
    expect($payment->proof)->toStartWith('uploads/proofs/');
    Storage::disk('public')->assertExists($payment->proof);
});

it('admin verifying dp10 creates client account automatically', function () {
    Mail::fake();
    $admin = User::where('email', 'admin@anitamua.com')->first();
    $package = Package::first();

    $this->post('/booking', [
        'package_id' => $package->id,
        'name' => 'Reno & Dewi',
        'phone' => '0812999888777',
        'email' => 'renodewi@test.com',
        'event_date' => now()->addMonths(2)->toDateString(),
        'location' => 'Ballroom Hotel X',
        'proof' => UploadedFile::fake()->image('bukti.jpg'),
    ]);

    $booking = Booking::where('email', 'renodewi@test.com')->firstOrFail();

    $this->actingAs($admin)
        ->post("/admin/bookings/{$booking->id}/verify-dp", [
            'amount' => 1000000,
            'method' => 'transfer',
        ])
        ->assertRedirect();

    $booking->refresh();
    expect($booking->status)->toBe('booked');

    // Jadwal Hari H otomatis masuk kalender saat DP diverifikasi
    $hariH = $booking->schedules()->where('type', 'hari_h')->first();
    expect($hariH)->not->toBeNull();
    expect($hariH->status)->toBe('scheduled');

    $user = User::where('email', 'renodewi@test.com')->first();
    expect($user)->not->toBeNull();
    expect($user->role)->toBe('client');
    expect($booking->client_id)->toBe($user->id);

    expect(Auth::attempt(['email' => 'renodewi@test.com', 'password' => 'Reno123']))->toBeTrue();
    expect(ActivityLog::where('action', 'client_account_created')->exists())->toBeTrue();

    // Email kredensial akun terkirim ke client
    Mail::assertSent(\App\Mail\ClientAccountCredentials::class, function ($mail) use ($user, $booking) {
        return $mail->hasTo($user->email)
            && $mail->password === 'Reno123'
            && $mail->bookingCode === $booking->code;
    });
});

it('booking with existing client email attaches to existing account', function () {
    $client = User::where('email', 'client@anitamua.com')->first();
    $package = Package::first();

    $this->post('/booking', [
        'package_id' => $package->id,
        'name' => 'Dewi Anggraini',
        'phone' => $client->phone,
        'email' => $client->email,
        'event_date' => now()->addMonths(2)->toDateString(),
        'location' => 'The Glass House',
        'proof' => UploadedFile::fake()->image('bukti.jpg'),
    ]);

    $booking = Booking::where('email', $client->email)
        ->where('client_id', $client->id)
        ->first();

    expect($booking)->not->toBeNull();
    expect(User::where('email', $client->email)->count())->toBe(1);
});

it('shows owner dashboard with booking pipeline widgets', function () {
    $owner = User::where('email', 'owner@anitamua.com')->first();

    $this->actingAs($owner)
        ->get('/dashboard')
        ->assertOk()
        ->assertSee('Booking Baru')
        ->assertSee('Menunggu Verifikasi')
        ->assertDontSee('Pendapatan');
});

it('shows admin dashboard', function () {
    $admin = User::where('email', 'admin@anitamua.com')->first();

    $this->actingAs($admin)
        ->get('/dashboard')
        ->assertOk()
        ->assertSee('Booking Menunggu');
});

it('shows team dashboard', function () {
    $team = User::where('email', 'team@anitamua.com')->first();

    $this->actingAs($team)
        ->get('/dashboard')
        ->assertOk()
        ->assertSee('Jadwal Tugas');
});

it('shows client dashboard with booking summary', function () {
    $client = User::where('email', 'client@anitamua.com')->first();

    $this->actingAs($client)
        ->get('/dashboard')
        ->assertOk()
        ->assertSee('Dewi & Andi')
        ->assertSee('Booked');
});

it('allows team to access booking pages but blocks admin-only pages', function () {
    $team = User::where('email', 'team@anitamua.com')->first();
    $booking = Booking::first();

    $this->actingAs($team)->get('/admin/bookings')->assertOk();
    $this->actingAs($team)->get('/admin/bookings/'.$booking->id)->assertForbidden();
    $this->actingAs($team)->get('/admin/calendar')->assertOk();
    $this->actingAs($team)->get('/admin/payments')->assertForbidden();
    $this->actingAs($team)->get('/admin/packages')->assertForbidden();
});

it('blocks client from back office pages', function () {
    $client = User::where('email', 'client@anitamua.com')->first();

    $this->actingAs($client)->get('/admin/bookings')->assertForbidden();
});

it('lets client view their booking detail', function () {
    $client = User::where('email', 'client@anitamua.com')->first();
    $booking = $client->bookings()->first();

    $this->actingAs($client)->get("/client/booking/{$booking->id}")->assertOk();
});

it('client can add a new payment stage with custom label and nominal', function () {
    Storage::fake('public');
    $client = User::where('email', 'client@anitamua.com')->first();
    $booking = $client->bookings()->first();
    $before = $booking->payments()->count();

    $this->actingAs($client)->post("/client/booking/{$booking->id}/proof", [
        'type' => 'Pelunasan',
        'amount' => 2500000,
        'method' => 'transfer',
        'proof' => UploadedFile::fake()->image('pelunasan.jpg'),
    ])->assertRedirect()->assertSessionHas('success');

    $payment = $booking->payments()->latest('id')->first();
    expect($payment)->not->toBeNull();
    expect($booking->payments()->count())->toBe($before + 1);
    expect($payment->type)->toBe('Pelunasan');
    expect((float) $payment->amount)->toBe(2500000.0);
    expect($payment->status)->toBe('pending');
    expect($payment->proof)->toStartWith('uploads/proofs/');
    Storage::disk('public')->assertExists($payment->proof);
});

it('admin can verify pending booking DP', function () {
    $admin = User::where('email', 'admin@anitamua.com')->first();
    $booking = $admin->bookings->first() ?? Booking::first();

    $this->actingAs($admin)
        ->post("/admin/bookings/{$booking->id}/verify-dp", [
            'amount' => 1800000,
            'method' => 'transfer',
        ])
        ->assertRedirect();

    $booking->refresh();
    expect($booking->status)->toBe('booked');
    expect(ActivityLog::where('booking_id', $booking->id)->exists())->toBeTrue();
});

it('admin can cancel booking and dp is forfeited', function () {
    $admin = User::where('email', 'admin@anitamua.com')->first();
    $booking = Booking::first();

    $this->actingAs($admin)
        ->post("/admin/bookings/{$booking->id}/cancel", ['reason' => 'Client mengundur diri'])
        ->assertRedirect();

    $booking->refresh();
    expect($booking->status)->toBe('cancelled');
    expect($booking->cancelled_reason)->toBe('Client mengundur diri');
});

it('renders MVP back office pages and hides deferred features', function () {
    $owner = User::where('email', 'owner@anitamua.com')->first();

    $this->actingAs($owner)
        ->get('/admin/bookings')->assertOk();
    $this->actingAs($owner)
        ->get('/admin/payments')->assertOk();
    $this->actingAs($owner)
        ->get('/admin/packages')->assertOk();
    $this->actingAs($owner)
        ->get('/admin/calendar')->assertOk();

    $booking = Booking::first();
    $this->actingAs($owner)
        ->get("/admin/bookings/{$booking->id}/packing")->assertOk();

    // Modul fase 2 sudah aktif
    foreach (['/admin/inventory', '/admin/inventory-categories', '/admin/benefits', '/admin/benefit-categories',
              '/admin/users/staff', '/admin/users/clients',
              '/admin/content/testimonials', '/admin/content/gallery', '/admin/content/faqs', '/admin/content/settings'] as $url) {
        $this->actingAs($owner)->get($url)->assertOk();
    }

    // Vendor, Keuangan, Timeline, Reminder dinonaktifkan sementara
    foreach (['/admin/vendors', '/admin/vendor-categories',
              '/admin/finances', '/admin/timeline', '/admin/reminders'] as $url) {
        $this->actingAs($owner)->get($url)->assertNotFound();
    }
});

it('renders booking detail page for admin', function () {
    $admin = User::where('email', 'admin@anitamua.com')->first();
    $booking = Booking::first();

    $this->actingAs($admin)
        ->get("/admin/bookings/{$booking->id}")
        ->assertOk()
        ->assertSee('Tambah Pembayaran')
        ->assertDontSee('Data survey bersifat read-only')
        ->assertDontSee('Jadwal hanya dapat diubah melalui Edit Booking.');
});

it('admin can add a pending payment stage from booking detail', function () {
    Storage::fake('public');
    $admin = User::where('email', 'admin@anitamua.com')->first();
    $booking = Booking::first();

    $this->actingAs($admin)
        ->post("/admin/bookings/{$booking->id}/payment", [
            'type' => 'Pelunasan Admin',
            'amount' => 2500000,
            'method' => 'transfer',
            'proof' => UploadedFile::fake()->image('pelunasan-admin.jpg'),
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    $payment = $booking->payments()->latest('id')->first();
    expect($payment->type)->toBe('Pelunasan Admin');
    expect((float) $payment->amount)->toBe(2500000.0);
    expect($payment->status)->toBe(Payment::STATUS_PENDING);
    expect($payment->proof)->toStartWith('uploads/proofs/');
    Storage::disk('public')->assertExists($payment->proof);
});

it('generates reminders via command', function () {
    $this->artisan('reminders:generate')->assertSuccessful();

    expect(Reminder::where('type', 'h30')->exists())->toBeTrue();
    expect(Reminder::where('type', 'h7')->exists())->toBeTrue();
});
