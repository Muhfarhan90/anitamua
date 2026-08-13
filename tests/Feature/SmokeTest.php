<?php

use App\Models\ActivityLog;
use App\Models\Booking;
use App\Models\Package;
use App\Models\Reminder;
use App\Models\User;
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

    $this->actingAs($team)->post('/admin/fieldwork/survey', [
        'booking_id' => $booking->id,
        'location' => 'The Glass House',
        'maps_url' => 'https://maps.app.goo.gl/abc',
        'pic' => 'Pak Budi',
        'notes' => 'Panggung di tengah',
        'photos' => [UploadedFile::fake()->image('survey.jpg')],
    ])->assertRedirect();

    $survey = $booking->survey()->first();
    expect($survey)->not->toBeNull();
    expect($survey->location)->toBe('The Glass House');
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
        ->assertSee('DP Menunggu')
        ->assertSee('Pelunasan Menunggu')
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
    foreach (['/admin/benefits', '/admin/benefit-categories',
              '/admin/content/testimonials', '/admin/content/gallery', '/admin/content/faqs', '/admin/content/settings'] as $url) {
        $this->actingAs($owner)->get($url)->assertOk();
    }

    // Vendor, Inventory, Keuangan, Users, Timeline, Reminder dinonaktifkan sementara
    foreach (['/admin/vendors', '/admin/vendor-categories', '/admin/inventory', '/admin/inventory-categories',
              '/admin/finances', '/admin/users', '/admin/timeline', '/admin/reminders'] as $url) {
        $this->actingAs($owner)->get($url)->assertNotFound();
    }
});

it('renders booking detail page for admin', function () {
    $admin = User::where('email', 'admin@anitamua.com')->first();
    $booking = Booking::first();

    $this->actingAs($admin)
        ->get("/admin/bookings/{$booking->id}")->assertOk();
});

it('generates reminders via command', function () {
    $this->artisan('reminders:generate')->assertSuccessful();

    expect(Reminder::where('type', 'h30')->exists())->toBeTrue();
    expect(Reminder::where('type', 'h7')->exists())->toBeTrue();
});
