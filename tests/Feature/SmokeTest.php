<?php

use App\Helpers\BookingProgress;
use App\Mail\ClientAccountCredentials;
use App\Models\ActivityLog;
use App\Models\Booking;
use App\Models\ClientReference;
use App\Models\EntranceGate;
use App\Models\Finance;
use App\Models\Fitting;
use App\Models\Gallery;
use App\Models\InventoryItem;
use App\Models\InventoryCategory;
use App\Models\Invoice;
use App\Models\Package;
use App\Models\PackageType;
use App\Models\Payment;
use App\Models\Reminder;
use App\Models\ReferenceType;
use App\Models\Schedule;
use App\Models\SiteSetting;
use App\Models\Tent;
use App\Models\Testimonial;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorCategory;
use App\Models\WeddingStage;
use App\Services\InvoiceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed();
});

it('renders landing pages', function () {
    Gallery::create([
        'title' => 'Galeri multi foto',
        'photo' => 'gallery/cover.jpg',
        'photos' => ['gallery/cover.jpg', 'gallery/detail.jpg'],
    ]);

    $this->get('/')->assertOk()->assertDontSee('2 foto</div>', false);
    $this->get('/tentang')->assertOk();
    $this->get('/paket')->assertOk();
    $this->get('/galeri')->assertOk()->assertDontSee('Menampilkan', false)->assertDontSee('2 foto</div>', false);
    $this->get('/dekor-tenda')->assertOk();
    $this->get('/wardrobe')->assertOk()->assertSee('Gaun Pengantin Amina');
    $this->get('/testimoni')->assertOk()->assertDontSee('Menampilkan', false);
    $this->get('/faq')->assertOk();
    $this->get('/kontak')->assertOk();
    $this->get('/booking')->assertOk();
    $this->get('/booking/sukses/'.Booking::where('name', 'Dewi & Andi')->value('code'))->assertOk();
});

it('uses the active site logo as the favicon', function () {
    SiteSetting::set('logo', 'uploads/logo/logo-tab.png');
    cache()->forget('site_settings');
    $owner = User::where('email', 'owner@anitamua.com')->firstOrFail();

    $this->get('/')->assertOk()
        ->assertSee('rel="icon"', false)
        ->assertSee('storage/uploads/logo/logo-tab.png', false);
    $this->get('/login')->assertOk()
        ->assertSee('rel="icon"', false)
        ->assertSee('storage/uploads/logo/logo-tab.png', false);
    $this->actingAs($owner)->get('/dashboard')->assertOk()
        ->assertSee('rel="icon"', false)
        ->assertSee('storage/uploads/logo/logo-tab.png', false);
});

it('shows only active decorations, gates, and tents in the public catalog', function () {
    WeddingStage::create([
        'name' => 'Dekor Publik',
        'photo_path' => 'uploads/decor/dekor-publik.jpg',
        'photos' => ['uploads/decor/dekor-publik.jpg', 'uploads/decor/dekor-publik-detail.jpg'],
        'is_active' => true,
    ]);
    WeddingStage::create(['name' => 'Dekor Disembunyikan', 'is_active' => false]);
    EntranceGate::create(['name' => 'Gapura Publik', 'is_active' => true]);
    EntranceGate::create(['name' => 'Gapura Disembunyikan', 'is_active' => false]);
    Tent::create(['name' => 'Tenda Publik', 'is_active' => true]);
    Tent::create(['name' => 'Tenda Disembunyikan', 'is_active' => false]);

    $this->get('/dekor-tenda')
        ->assertOk()
        ->assertSee('Dekor Publik')
        ->assertDontSee('2 foto')
        ->assertSee('Gapura Publik')
        ->assertSee('Tenda Publik')
        ->assertDontSee('Dekor Disembunyikan')
        ->assertDontSee('Gapura Disembunyikan')
        ->assertDontSee('Tenda Disembunyikan');

    $this->get('/admin/tents')->assertRedirect('/login');
    $client = User::where('email', 'client@anitamua.com')->firstOrFail();
    $this->actingAs($client)->get('/admin/tents')->assertForbidden();
});

it('hides cancelled and completed booking schedules from the calendar without deleting them', function () {
    $team = User::where('email', 'team@anitamua.com')->firstOrFail();
    $source = Booking::firstOrFail();
    $bookings = collect([
        [Booking::STATUS_BOOKED, 'Jadwal Aktif'],
        [Booking::STATUS_CANCELLED, 'Jadwal Batal'],
        [Booking::STATUS_COMPLETED, 'Jadwal Selesai'],
    ])->map(function ($item) use ($source, $team) {
        [$status, $title] = $item;
        $booking = $source->replicate();
        $booking->code = Booking::generateCode();
        $booking->name = $title;
        $booking->status = $status;
        $booking->save();
        $booking->schedules()->create([
            'type' => Schedule::TYPE_HARI_H,
            'title' => $title,
            'date' => now()->toDateString(),
            'pic_user_id' => $team->id,
            'status' => Schedule::STATUS_SCHEDULED,
        ]);

        return $booking;
    });

    $this->actingAs($team)
        ->get('/admin/calendar?month='.now()->month.'&year='.now()->year)
        ->assertOk()
        ->assertSee('Jadwal Aktif')
        ->assertDontSee('Jadwal Batal')
        ->assertDontSee('Jadwal Selesai');

    expect(Schedule::whereIn('booking_id', $bookings->pluck('id'))->count())->toBe(3);
});

it('filters the calendar to one date for owner, admin, and field teams', function () {
    $owner = User::where('role', User::ROLE_OWNER)->firstOrFail();
    $admin = User::where('role', User::ROLE_ADMIN)->firstOrFail();
    $team = User::where('email', 'team@anitamua.com')->firstOrFail();
    $client = User::where('email', 'client@anitamua.com')->firstOrFail();
    $source = Booking::firstOrFail();
    $matchingDate = now()->setDate(2031, 4, 12);
    $outsideDate = $matchingDate->copy()->addDays(5);

    $makeSchedule = function (string $name, string $date) use ($source, $client, $team) {
        $booking = $source->replicate();
        $booking->forceFill([
            'code' => Booking::generateCode(),
            'client_id' => $client->id,
            'name' => $name,
            'status' => Booking::STATUS_BOOKED,
        ])->save();
        $booking->schedules()->create([
            'type' => Schedule::TYPE_HARI_H,
            'title' => $name,
            'date' => $date,
            'pic_user_id' => $team->id,
            'status' => Schedule::STATUS_SCHEDULED,
        ]);

        return $booking;
    };

    $matching = $makeSchedule('Jadwal Dalam Rentang', $matchingDate->toDateString());
    $outside = $makeSchedule('Jadwal di Luar Rentang', $outsideDate->toDateString());
    $query = http_build_query([
        'month' => $matchingDate->month,
        'year' => $matchingDate->year,
        'filter_date' => $matchingDate->toDateString(),
    ]);

    foreach ([$owner, $admin, $team] as $user) {
        $this->actingAs($user)->get('/admin/calendar?'.$query)
            ->assertOk()
            ->assertSee('"title":"'.$matching->name.'"', false)
            ->assertDontSee('"title":"'.$outside->name.'"', false)
            ->assertSee('name="month" value="4"', false)
            ->assertSee('name="year" value="2031"', false)
            ->assertSee("const [year, month] = selectedDate.split('-');", false)
            ->assertSee('this.elements.month.value = Number(month);', false)
            ->assertSee('filter_date')
            ->assertDontSee('date_from')
            ->assertDontSee('date_to');
    }

    $this->actingAs($owner)->get('/admin/calendar')
        ->assertOk()
        ->assertSeeInOrder(['id="calendarDateFilter"', 'Tambah Jadwal'], false);
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

it('allows owner to activate a client from the edit form', function () {
    $owner = User::where('role', User::ROLE_OWNER)->firstOrFail();
    $client = User::where('email', 'client@anitamua.com')->firstOrFail();
    $client->update(['is_active' => false]);

    $this->actingAs($owner)
        ->get(route('admin.users.edit', $client))
        ->assertOk()
        ->assertSee('name="is_active" value="1"', false);

    $this->actingAs($owner)
        ->patch(route('admin.users.update', $client), [
            'name' => $client->name,
            'email' => $client->email,
            'phone' => $client->phone,
            'is_active' => '1',
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    expect($client->fresh()->is_active)->toBeTrue();
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
    $weddingStage = WeddingStage::create(['name' => 'Garden Modern', 'photos' => ['uploads/stages/garden-1.jpg', 'uploads/stages/garden-2.jpg'], 'is_active' => true]);
    $tent = Tent::create(['name' => 'Tenda Garden', 'photos' => ['uploads/tents/garden-1.jpg'], 'is_active' => true]);
    $entranceGate = EntranceGate::create(['name' => 'Gapura Garden', 'photos' => ['uploads/gates/garden-1.jpg'], 'is_active' => true]);

    $this->actingAs($team)->post('/admin/fieldwork/survey', [
        'booking_id' => $booking->id,
        'wedding_stage_id' => $weddingStage->id,
        'wedding_stage_photo_path' => 'uploads/stages/garden-2.jpg',
        'tent_id' => $tent->id,
        'tent_photo_path' => 'uploads/tents/garden-1.jpg',
        'entrance_gate_id' => $entranceGate->id,
        'entrance_gate_photo_path' => 'uploads/gates/garden-1.jpg',
        'flower_color' => 'Putih dan sage',
        'stage_size' => '6m',
        'tent_sizes' => ['4X6', '5X5'],
        'tent_size_quantities' => ['4X6' => 2, '5X5' => 1],
        'tent_additions' => ['4X4'],
        'tent_addition_quantities' => ['4X4' => 1],
        'gallery_booth' => 'Ya',
        'pic' => 'Pak Budi',
        'notes' => 'Panggung di tengah',
        'photos' => [UploadedFile::fake()->image('survey.jpg')],
    ])->assertRedirect();

    $survey = $booking->survey()->first();
    expect($survey)->not->toBeNull();
    expect($survey->wedding_stage_id)->toBe($weddingStage->id);
    expect($survey->wedding_stage_photo_path)->toBe('uploads/stages/garden-2.jpg');
    expect($survey->tent_id)->toBe($tent->id);
    expect($survey->tent_photo_path)->toBe('uploads/tents/garden-1.jpg');
    expect($survey->entrance_gate_id)->toBe($entranceGate->id);
    expect($survey->entrance_gate_photo_path)->toBe('uploads/gates/garden-1.jpg');
    expect($survey->tent_sizes)->toBe(['4X6', '5X5']);
    expect($survey->tent_size_quantities)->toBe(['4X6' => 2, '5X5' => 1]);
    expect($survey->tent_addition_quantities)->toBe(['4X4' => 1]);
    // foto baru tersimpan & dikompres
    $photos = $survey->photos;
    expect(count($photos))->toBeGreaterThanOrEqual(1);
    expect($photos[0])->toStartWith('uploads/photos/');
    Storage::disk('public')->assertExists(end($photos));

    $this->actingAs($team)->get(route('admin.fieldwork.booking', $booking))
        ->assertOk()
        ->assertDontSee('Lokasi Acara')
        ->assertDontSee('Link Maps')
        ->assertDontSee('<span class="text-gray-500">PIC</span>', false)
        ->assertDontSee('<span class="text-gray-500">Pelaminan</span>', false);

    $admin = User::where('email', 'admin@anitamua.com')->first();
    $surveyDate = now()->addDays(3)->toDateString();
    $this->actingAs($admin)->post('/admin/fieldwork/survey', [
        'booking_id' => $booking->id,
        'survey_date' => $surveyDate,
    ])->assertRedirect();
    $booking->refresh();
    expect($booking->survey_date->toDateString())->toBe($surveyDate);

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

    $this->actingAs($team)->get(route('admin.fieldwork.booking', $booking))
        ->assertOk()
        ->assertSee('Catatan Fitting')
        ->assertDontSee('Catatan Umum')
        ->assertDontSee('PIC: Dewi P. · Sesuaikan gaun');
});

it('admin edit page separates booking, survey, and fitting saves', function () {
    $admin = User::where('email', 'admin@anitamua.com')->firstOrFail();
    $booking = Booking::firstOrFail();

    $this->actingAs($admin)->get(route('admin.bookings.edit', $booking))
        ->assertOk()
        ->assertSee('Perbarui Booking')
        ->assertSee('Perbarui Survey')
        ->assertSee('Perbarui Fitting')
        ->assertSee('data-fieldwork-reset', false)
        ->assertSee('data-fieldwork-toggle', false)
        ->assertSee('action="'.route('admin.fieldwork.survey').'"', false)
        ->assertSee('action="'.route('admin.fieldwork.fitting').'"', false);
});

it('shows payment proof in its own column on the client page', function () {
    $client = User::where('email', 'client@anitamua.com')->firstOrFail();
    $booking = Booking::where('client_id', $client->id)->firstOrFail();
    $booking->payments()->where('status', Payment::STATUS_PENDING)->firstOrFail()
        ->update(['proof' => 'uploads/proofs/contoh.jpg']);

    $this->actingAs($client)->get(route('client.booking', $booking))
        ->assertOk()
        ->assertDontSee('Bayar Sekarang')
        ->assertDontSee('<th class="px-5 py-2.5 font-medium text-center">Aksi</th>', false)
        ->assertSee('<th class="px-5 py-2.5 font-medium text-center">Bukti</th>', false)
        ->assertSee('Lihat bukti')
        ->assertSee('Bayar / Tambah Tahap Pembayaran');
});

it('uses package type settings to control survey and fitting per booking', function () {
    $owner = User::where('email', 'owner@anitamua.com')->firstOrFail();
    $team = User::where('email', 'team@anitamua.com')->firstOrFail();
    $client = User::where('email', 'client@anitamua.com')->firstOrFail();
    $booking = Booking::where('client_id', $client->id)->firstOrFail();
    $type = $booking->package->packageType;

    $this->actingAs($owner)->post(route('admin.packages.types.store'), [
        'name' => 'Paket Tanpa Survey dan Fitting',
        'is_data_survey' => 0,
        'is_data_fitting' => 0,
    ])->assertRedirect();
    $newType = PackageType::where('name', 'Paket Tanpa Survey dan Fitting')->firstOrFail();
    expect($newType->is_data_survey)->toBeFalse()
        ->and($newType->is_data_fitting)->toBeFalse();

    $this->actingAs($owner)->put(route('admin.packages.types.update', $type), [
        'name' => $type->name,
        'is_data_survey' => 1,
        'is_data_fitting' => 0,
    ])->assertRedirect();

    expect($type->refresh()->is_data_survey)->toBeTrue()
        ->and($type->is_data_fitting)->toBeFalse();

    $this->actingAs($team)->get(route('admin.fieldwork.booking', $booking))
        ->assertOk()->assertSee('Data Survey')->assertDontSee('Data Fitting');
    $this->actingAs($client)->get(route('client.booking', $booking))
        ->assertOk()->assertDontSee('Data Fitting');
    $this->actingAs($team)->post(route('admin.fieldwork.fitting'), [
        'booking_id' => $booking->id,
        'date' => now()->toDateString(),
        'status' => 'scheduled',
    ])->assertForbidden();
    $this->actingAs($team)->get(route('admin.bookings.packing', $booking))->assertNotFound();

    $this->actingAs($owner)->put(route('admin.packages.types.update', $type), [
        'name' => $type->name,
        'is_data_survey' => 0,
        'is_data_fitting' => 1,
    ])->assertRedirect();

    $this->actingAs($team)->get(route('admin.fieldwork.booking', $booking))
        ->assertOk()->assertDontSee('Data Survey')->assertSee('Data Fitting');
    $this->actingAs($team)->post(route('admin.fieldwork.survey'), [
        'booking_id' => $booking->id,
    ])->assertForbidden();

    $this->actingAs($owner)->put(route('admin.packages.types.update', $type), [
        'name' => $type->name,
        'is_data_survey' => 1,
        'is_data_fitting' => 1,
    ])->assertRedirect();
    $this->actingAs($team)->get(route('admin.fieldwork.booking', $booking))
        ->assertOk()->assertSee('Data Survey')->assertSee('Data Fitting');
});

it('admin can manage wedding stages', function () {
    Storage::fake('public');
    $admin = User::where('email', 'admin@anitamua.com')->first();

    $this->actingAs($admin)->post('/admin/wedding-stages', [
        'name' => 'Rustic White',
        'size' => '6 x 3 m',
        'is_active' => 1,
        'photos' => [
            UploadedFile::fake()->image('rustic-white.jpg'),
            UploadedFile::fake()->image('rustic-white-detail.jpg'),
        ],
    ])->assertRedirect();

    $weddingStage = WeddingStage::where('name', 'Rustic White')->firstOrFail();
    expect($weddingStage->is_active)->toBeTrue();
    expect($weddingStage->size)->toBe('6 x 3 m');
    expect($weddingStage->photo_path)->toStartWith('uploads/wedding-stages/');
    expect($weddingStage->photos)->toHaveCount(2);
    Storage::disk('public')->assertExists($weddingStage->photo_path);

    $this->actingAs($admin)->put('/admin/wedding-stages/'.$weddingStage->id, [
        'name' => 'Rustic White Updated',
        'size' => '8 x 4 m',
        'is_active' => 0,
        'photos' => [
            UploadedFile::fake()->image('rustic-white-updated.jpg'),
            UploadedFile::fake()->image('rustic-white-detail-updated.jpg'),
        ],
    ])->assertRedirect();

    expect($weddingStage->refresh()->name)->toBe('Rustic White Updated');
    expect($weddingStage->size)->toBe('8 x 4 m');
    expect($weddingStage->is_active)->toBeFalse();
    expect($weddingStage->photo_path)->toStartWith('uploads/wedding-stages/');
    expect($weddingStage->photos)->toHaveCount(4);

    $photoToDelete = $weddingStage->photos[1];
    $this->actingAs($admin)->put('/admin/wedding-stages/'.$weddingStage->id, [
        'name' => $weddingStage->name,
        'size' => $weddingStage->size,
        'is_active' => 0,
        'remove_photos' => [$photoToDelete],
    ])->assertRedirect();
    expect($weddingStage->refresh()->photos)->toHaveCount(3)
        ->and($weddingStage->photos)->not->toContain($photoToDelete);
    Storage::disk('public')->assertMissing($photoToDelete);

    $this->actingAs($admin)->get('/admin/wedding-stages')
        ->assertOk()
        ->assertSee('Ukuran Pelaminan')
        ->assertSee('8 x 4 m');

    $this->actingAs($admin)->delete('/admin/wedding-stages/'.$weddingStage->id)->assertRedirect();
    expect(WeddingStage::find($weddingStage->id))->toBeNull();
});

it('admin can manage tent and entrance gate masters and cannot delete used items', function () {
    Storage::fake('public');
    $admin = User::where('email', 'admin@anitamua.com')->firstOrFail();

    $this->actingAs($admin)->post('/admin/tents', [
        'name' => 'Tenda Transparan', 'is_active' => 1,
        'photos' => [UploadedFile::fake()->image('tent.jpg'), UploadedFile::fake()->image('tent-detail.jpg')],
    ])->assertRedirect();
    $this->actingAs($admin)->post('/admin/entrance-gates', [
        'name' => 'Gapura Bunga', 'is_active' => 1,
        'photos' => [UploadedFile::fake()->image('gate.jpg'), UploadedFile::fake()->image('gate-detail.jpg')],
    ])->assertRedirect();

    $tent = Tent::where('name', 'Tenda Transparan')->firstOrFail();
    $gate = EntranceGate::where('name', 'Gapura Bunga')->firstOrFail();
    Storage::disk('public')->assertExists($tent->photo_path);
    Storage::disk('public')->assertExists($gate->photo_path);
    expect($tent->photos)->toHaveCount(2)->and($gate->photos)->toHaveCount(2);

    $this->actingAs($admin)->put('/admin/tents/'.$tent->id, [
        'name' => $tent->name, 'is_active' => 0,
        'photos' => [UploadedFile::fake()->image('tent-extra.jpg')],
    ])->assertRedirect();
    $this->actingAs($admin)->put('/admin/entrance-gates/'.$gate->id, [
        'name' => $gate->name, 'is_active' => 1,
        'photos' => [UploadedFile::fake()->image('gate-extra.jpg')],
    ])->assertRedirect();
    expect($tent->refresh()->is_active)->toBeFalse();
    expect($tent->photos)->toHaveCount(3)->and($gate->refresh()->photos)->toHaveCount(3);

    $tentPhotoToDelete = $tent->photos[1];
    $gatePhotoToDelete = $gate->photos[1];
    $this->actingAs($admin)->put('/admin/tents/'.$tent->id, [
        'name' => $tent->name, 'is_active' => 0, 'remove_photos' => [$tentPhotoToDelete],
    ])->assertRedirect();
    $this->actingAs($admin)->put('/admin/entrance-gates/'.$gate->id, [
        'name' => $gate->name, 'is_active' => 1, 'remove_photos' => [$gatePhotoToDelete],
    ])->assertRedirect();
    expect($tent->refresh()->photos)->toHaveCount(2)
        ->and($gate->refresh()->photos)->toHaveCount(2);
    Storage::disk('public')->assertMissing($tentPhotoToDelete);
    Storage::disk('public')->assertMissing($gatePhotoToDelete);

    Booking::firstOrFail()->survey()->updateOrCreate([], ['tent_id' => $tent->id, 'entrance_gate_id' => $gate->id]);
    $this->actingAs($admin)->delete('/admin/tents/'.$tent->id)->assertSessionHas('warning');
    $this->actingAs($admin)->delete('/admin/entrance-gates/'.$gate->id)->assertSessionHas('warning');
    expect($tent->fresh())->not->toBeNull()->and($gate->fresh())->not->toBeNull();
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
            'cpp_busana_akad' => ['notes' => 'Jas hitam', 'size' => 'L', 'photo' => $photo],
            'ukuran_bb_tb_ld' => ['notes' => '70 kg / 175 cm / 92 cm'],
        ],
    ])->assertRedirect();

    $fitting = $booking->fittings()->firstOrFail();
    $photoPath = $fitting->cpp_busana_akad_photo_path;
    expect($fitting->cpp_busana_akad_notes)->toBe('Jas hitam');
    expect($fitting->item_sizes['cpp_busana_akad'])->toBe('L');
    expect($photoPath)->toStartWith('uploads/photos/');
    Storage::disk('public')->assertExists($photoPath);

    $this->actingAs($team)->post('/admin/fieldwork/fitting', [
        'booking_id' => $booking->id,
        'date' => now()->addDays(8)->toDateString(),
        'status' => 'finished',
        'items' => [
            'cpp_busana_akad' => ['notes' => 'Jas hitam sudah pas', 'size' => 'LD 92 / PB 140'],
        ],
    ])->assertRedirect();

    $fitting->refresh();
    expect($fitting->cpp_busana_akad_notes)->toBe('Jas hitam sudah pas');
    expect($fitting->cpp_busana_akad_photo_path)->toBe($photoPath);
    expect($fitting->item_sizes['cpp_busana_akad'])->toBe('LD 92 / PB 140');
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

it('rejects a survey photo that does not belong to the selected master', function () {
    $team = User::where('email', 'team@anitamua.com')->firstOrFail();
    $booking = Booking::firstOrFail();
    $weddingStage = WeddingStage::create([
        'name' => 'Dekor Pilihan',
        'photos' => ['uploads/wedding-stages/dekor-pilihan.jpg'],
        'is_active' => true,
    ]);

    $this->actingAs($team)->post('/admin/fieldwork/survey', [
        'booking_id' => $booking->id,
        'wedding_stage_id' => $weddingStage->id,
        'wedding_stage_photo_path' => 'uploads/wedding-stages/foto-lain.jpg',
    ])->assertSessionHasErrors('wedding_stage_photo_path');
});

it('rejects inactive tent and entrance gate for a new survey', function () {
    $team = User::where('email', 'team@anitamua.com')->firstOrFail();
    $booking = Booking::firstOrFail();
    $tent = Tent::create(['name' => 'Tenda Nonaktif', 'is_active' => false]);
    $gate = EntranceGate::create(['name' => 'Gapura Nonaktif', 'is_active' => false]);

    $this->actingAs($team)->post('/admin/fieldwork/survey', [
        'booking_id' => $booking->id,
        'tent_id' => $tent->id,
    ])->assertSessionHasErrors('tent_id');

    $this->actingAs($team)->post('/admin/fieldwork/survey', [
        'booking_id' => $booking->id,
        'entrance_gate_id' => $gate->id,
    ])->assertSessionHasErrors('entrance_gate_id');
});

it('admin creating booking auto-verifies dp, books it, and adds hari h schedule', function () {
    Storage::fake('public');
    $admin = User::where('email', 'admin@anitamua.com')->first();
    $client = User::where('email', 'client@anitamua.com')->first();
    $package = Package::first();
    $vendorCategory = VendorCategory::firstOrCreate(['name' => 'Live Music'], ['slug' => 'live-music']);
    $vendor = Vendor::create([
        'vendor_category_id' => $vendorCategory->id,
        'name' => 'Harmoni Musik',
        'phone' => '081255555555',
        'price' => 3000000,
        'status' => 'active',
    ]);
    $package->vendors()->attach($vendor->id, ['price' => 2750000]);

    $eventDate = now()->addMonths(2)->toDateString();

    $this->actingAs($admin)->post('/admin/bookings', [
        'client_mode' => 'existing',
        'client_id' => $client->id,
        'referral_source' => 'Instagram',
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
    expect($booking->bookingVendors()->value('vendor_id'))->toBe($vendor->id);
    expect((float) $booking->bookingVendors()->value('price'))->toBe(2750000.0);

    $payment = $booking->payments()->where('type', 'DP1')->first();
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

    $this->actingAs($client)->get("/client/booking/{$booking->id}")
        ->assertOk()
        ->assertSee('Vendor yang Digunakan')
        ->assertSee('Harmoni Musik');
});

it('admin can create and edit manual booking add-ons without changing payments', function () {
    Storage::fake('public');
    $admin = User::where('email', 'admin@anitamua.com')->first();
    $client = User::where('email', 'client@anitamua.com')->first();
    $package = Package::first();

    $this->actingAs($admin)->post('/admin/bookings', [
        'client_mode' => 'existing',
        'client_id' => $client->id,
        'referral_source' => 'Instagram',
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
        'referral_source' => $booking->referral_source,
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

it('updates the vendor snapshot when an admin changes a booking package', function () {
    $admin = User::where('email', 'admin@anitamua.com')->firstOrFail();
    $client = User::where('email', 'client@anitamua.com')->firstOrFail();
    $sourcePackage = Package::firstOrFail();
    $targetPackage = Package::where('id', '!=', $sourcePackage->id)->firstOrFail();
    $vendors = Vendor::limit(2)->get();
    $sourcePackage->vendors()->sync([$vendors[0]->id => ['price' => 1000000]]);
    $targetPackage->vendors()->sync([$vendors[1]->id => ['price' => 2000000]]);
    $booking = Booking::create([
        'code' => Booking::generateCode(),
        'client_id' => $client->id,
        'package_id' => $sourcePackage->id,
        'package_price' => $sourcePackage->price,
        'name' => 'Maya & Arga',
        'phone' => $client->phone,
        'email' => $client->email,
        'event_date' => now()->addMonths(2)->toDateString(),
        'status' => Booking::STATUS_BOOKED,
    ]);
    $booking->syncVendorsFromPackage();

    $this->actingAs($admin)->patch("/admin/bookings/{$booking->id}", [
        'client_id' => $client->id,
        'package_id' => $targetPackage->id,
        'name' => $booking->name,
        'phone' => $booking->phone,
        'email' => $booking->email,
        'referral_source' => 'Instagram',
        'event_date' => $booking->event_date->toDateString(),
        'location' => $booking->location,
        'notes' => $booking->notes,
        'status' => Booking::STATUS_BOOKED,
    ])->assertRedirect();

    $booking->refresh();
    expect($booking->bookingVendors()->value('vendor_id'))->toBe($vendors[1]->id);
    expect((float) $booking->package_price)->toBe((float) $targetPackage->price);
});

it('admin can replace a booking vendor within the same category', function () {
    $admin = User::where('email', 'admin@anitamua.com')->firstOrFail();
    $booking = Booking::firstOrFail();
    $category = VendorCategory::firstOrFail();
    $oldVendor = Vendor::create([
        'vendor_category_id' => $category->id,
        'name' => 'Vendor Lama',
        'price' => 1000000,
        'status' => 'active',
    ]);
    $replacement = Vendor::create([
        'vendor_category_id' => $category->id,
        'name' => 'Vendor Pengganti',
        'price' => 1250000,
        'status' => 'active',
    ]);
    $bookingVendor = $booking->bookingVendors()->create([
        'vendor_id' => $oldVendor->id,
        'role' => $category->name,
        'price' => $oldVendor->price,
        'status' => 'confirmed',
    ]);

    $this->actingAs($admin)
        ->patch("/admin/bookings/{$booking->id}/vendors", [
            'booking_vendor_id' => $bookingVendor->id,
            'vendor_id' => $replacement->id,
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    $bookingVendor->refresh();
    expect($bookingVendor->vendor_id)->toBe($replacement->id)
        ->and((float) $bookingVendor->price)->toBe(1250000.0)
        ->and($bookingVendor->status)->toBe('changed');
});

it('admin can add another vendor from an existing category but cannot duplicate a vendor', function () {
    $admin = User::where('email', 'admin@anitamua.com')->firstOrFail();
    $booking = Booking::firstOrFail();
    $existingBookingVendor = $booking->bookingVendors()->with('vendor')->firstOrFail();
    $additionalVendor = Vendor::create([
        'vendor_category_id' => $existingBookingVendor->vendor->vendor_category_id,
        'name' => 'Vendor Kedua Satu Kategori',
        'price' => 950000,
        'status' => 'active',
    ]);
    $payload = [
        'package_id' => $booking->package_id,
        'name' => $booking->name,
        'phone' => $booking->phone,
        'email' => $booking->email,
        'referral_source' => $booking->referral_source ?: 'Instagram',
        'event_date' => $booking->event_date->toDateString(),
        'event_time' => $booking->event_time ? substr((string) $booking->event_time, 0, 5) : null,
        'additional_vendor_ids' => [$additionalVendor->id],
        'additional_vendor_additions' => [
            $additionalVendor->id => [
                ['name' => 'Transport vendor kedua', 'price' => 125000],
            ],
        ],
    ];

    $this->actingAs($admin)
        ->patch(route('admin.bookings.update', $booking), $payload)
        ->assertRedirect()
        ->assertSessionHas('success');

    expect($booking->bookingVendors()
        ->whereHas('vendor', fn ($query) => $query->where('vendor_category_id', $additionalVendor->vendor_category_id))
        ->count())->toBeGreaterThanOrEqual(2);

    $this->actingAs($admin)
        ->patch(route('admin.bookings.update', $booking), $payload)
        ->assertSessionHasErrors('additional_vendor_ids');

    expect($booking->bookingVendors()->where('vendor_id', $additionalVendor->id)->count())->toBe(1);
    expect($booking->bookingVendors()->where('vendor_id', $additionalVendor->id)->first()->custom_additions)->toBe([
        ['name' => 'Transport vendor kedua', 'price' => 125000],
    ]);
});

it('renders booking vendors in server-rendered category cards with compact custom additions', function () {
    $admin = User::where('email', 'admin@anitamua.com')->firstOrFail();
    $booking = Booking::has('bookingVendors')->firstOrFail();

    $this->actingAs($admin)
        ->get(route('admin.bookings.edit', $booking))
        ->assertOk()
        ->assertSee('data-vendor-category-card', false)
        ->assertSee('Hapus Kategori')
        ->assertSee('<details', false)
        ->assertSee('data-vendor-additions', false)
        ->assertSee('confirmDeferredRemoval', false)
        ->assertSee('Perubahan baru diterapkan setelah Anda menekan Simpan Perubahan.', false)
        ->assertSee('data-category-name', false)
        ->assertSee('option.hidden = false', false)
        ->assertDontSee('data-vendor-change', false);
});

it('admin can remove one vendor or an entire vendor category when saving booking edits', function () {
    $admin = User::where('email', 'admin@anitamua.com')->firstOrFail();
    $booking = Booking::firstOrFail();
    $singleCategory = VendorCategory::create(['name' => 'Vendor Untuk Dihapus', 'slug' => 'vendor-untuk-dihapus']);
    $singleVendor = Vendor::create([
        'vendor_category_id' => $singleCategory->id,
        'name' => 'Vendor Satu Dihapus',
        'price' => 400000,
        'status' => 'active',
    ]);
    $category = VendorCategory::create(['name' => 'Kategori Untuk Dihapus', 'slug' => 'kategori-untuk-dihapus']);
    $categoryVendorOne = Vendor::create([
        'vendor_category_id' => $category->id,
        'name' => 'Vendor Kategori Satu',
        'price' => 500000,
        'status' => 'active',
    ]);
    $categoryVendorTwo = Vendor::create([
        'vendor_category_id' => $category->id,
        'name' => 'Vendor Kategori Dua',
        'price' => 600000,
        'status' => 'active',
    ]);
    $booking->bookingVendors()->createMany([
        ['vendor_id' => $singleVendor->id, 'role' => $singleCategory->name, 'price' => $singleVendor->price, 'status' => 'confirmed'],
        ['vendor_id' => $categoryVendorOne->id, 'role' => $category->name, 'price' => $categoryVendorOne->price, 'custom_additions' => [['name' => 'Tambahan lama', 'price' => 100000]], 'status' => 'confirmed'],
        ['vendor_id' => $categoryVendorTwo->id, 'role' => $category->name, 'price' => $categoryVendorTwo->price, 'status' => 'confirmed'],
    ]);
    $singleBookingVendor = $booking->bookingVendors()->where('vendor_id', $singleVendor->id)->firstOrFail();

    $this->actingAs($admin)->patch(route('admin.bookings.update', $booking), [
        'package_id' => $booking->package_id,
        'name' => $booking->name,
        'phone' => $booking->phone,
        'email' => $booking->email,
        'referral_source' => $booking->referral_source ?: 'Instagram',
        'event_date' => $booking->event_date->toDateString(),
        'removed_booking_vendor_ids' => [$singleBookingVendor->id],
        'removed_vendor_category_ids' => [$category->id],
    ])->assertRedirect()->assertSessionHas('success');

    expect($booking->bookingVendors()->whereKey($singleBookingVendor->id)->exists())->toBeFalse()
        ->and($booking->bookingVendors()->whereHas('vendor', fn ($query) => $query->where('vendor_category_id', $category->id))->exists())->toBeFalse()
        ->and(Vendor::whereKey($singleVendor->id)->exists())->toBeTrue()
        ->and(VendorCategory::whereKey($category->id)->exists())->toBeTrue();
});

it('rejects replacing a booking vendor with another vendor already on the booking', function () {
    $admin = User::where('email', 'admin@anitamua.com')->firstOrFail();
    $booking = Booking::firstOrFail();
    $firstBookingVendor = $booking->bookingVendors()->with('vendor')->firstOrFail();
    $duplicateTarget = Vendor::create([
        'vendor_category_id' => $firstBookingVendor->vendor->vendor_category_id,
        'name' => 'Target Vendor Terpakai',
        'price' => 900000,
        'status' => 'active',
    ]);
    $booking->bookingVendors()->create([
        'vendor_id' => $duplicateTarget->id,
        'role' => $firstBookingVendor->role,
        'price' => $duplicateTarget->price,
        'status' => 'confirmed',
    ]);

    $this->actingAs($admin)
        ->patch("/admin/bookings/{$booking->id}/vendors", [
            'booking_vendor_id' => $firstBookingVendor->id,
            'vendor_id' => $duplicateTarget->id,
        ])
        ->assertRedirect()
        ->assertSessionHas('warning');

    expect($firstBookingVendor->fresh()->vendor_id)->not->toBe($duplicateTarget->id);
});

it('admin saves custom additions with the booking changes and finance uses the total', function () {
    $admin = User::where('email', 'admin@anitamua.com')->firstOrFail();
    $owner = User::where('email', 'owner@anitamua.com')->firstOrFail();
    $booking = Booking::where('status', '!=', Booking::STATUS_CANCELLED)->firstOrFail();
    $bookingVendor = $booking->bookingVendors()
        ->whereHas('vendor', fn ($query) => $query->whereNotNull('vendor_category_id'))
        ->firstOrFail();
    $currentVendor = $bookingVendor->vendor;
    $replacementVendor = Vendor::create([
        'vendor_category_id' => $currentVendor->vendor_category_id,
        'name' => 'Vendor Pengganti Utama',
        'price' => $currentVendor->price,
        'status' => 'active',
    ]);
    $year = $bookingVendor->created_at->year;
    $expensesBefore = (float) $this->actingAs($owner)->get('/admin/finances?year='.$year)->viewData('expenses');

    $this->actingAs($admin)->patch(route('admin.bookings.update', $booking), [
        'client_id' => $booking->client_id,
        'package_id' => $booking->package_id,
        'name' => $booking->name,
        'phone' => $booking->phone,
        'email' => $booking->email,
        'referral_source' => $booking->referral_source ?: 'Instagram',
        'event_date' => $booking->event_date->toDateString(),
        'event_time' => $booking->event_time ? substr((string) $booking->event_time, 0, 5) : null,
        'vendor_changes' => [$bookingVendor->id => ['vendor_id' => $replacementVendor->id]],
        'vendor_additions_present' => [$bookingVendor->id => 1],
        'vendor_additions' => [
            $bookingVendor->id => [
                ['name' => 'Transport luar kota', 'price' => 250000],
                ['name' => 'Crew tambahan', 'price' => 150000],
            ],
        ],
    ])->assertRedirect()->assertSessionHas('success');

    $bookingVendor->refresh();
    expect($bookingVendor->vendor_id)->toBe($replacementVendor->id)
        ->and($bookingVendor->custom_additions)->toBe([
            ['name' => 'Transport luar kota', 'price' => 250000],
            ['name' => 'Crew tambahan', 'price' => 150000],
        ])->and($bookingVendor->custom_additions_total)->toBe(400000.0)
        ->and($bookingVendor->total_price)->toBe((float) $bookingVendor->price + 400000.0);

    $expensesAfter = (float) $this->actingAs($owner)->get('/admin/finances?year='.$year)->viewData('expenses');
    expect($expensesAfter - $expensesBefore)->toBe(400000.0);

    $this->actingAs($admin)->get('/admin/bookings/'.$booking->id)
        ->assertOk()->assertSee('Transport luar kota')->assertSee('Crew tambahan');
});

it('renders fixed booking discounts without trailing decimal zeroes', function () {
    $admin = User::where('email', 'admin@anitamua.com')->firstOrFail();
    $booking = Booking::firstOrFail();
    $booking->update(['discount_type' => 'fixed', 'discount_value' => 100000]);

    $this->actingAs($admin)
        ->get(route('admin.bookings.edit', $booking))
        ->assertOk()
        ->assertSee('value="100000"', false)
        ->assertDontSee('value="100000.00"', false);
});

it('admin booking form saves survey and fitting details', function () {
    Storage::fake('public');
    $admin = User::where('email', 'admin@anitamua.com')->first();
    $client = User::where('email', 'client@anitamua.com')->first();
    $package = Package::first();
    $stage = WeddingStage::create(['name' => 'Classic White', 'photos' => ['uploads/stages/classic-1.jpg'], 'is_active' => true]);
    $tent = Tent::create(['name' => 'Sisir Premium', 'photos' => ['uploads/tents/sisir-1.jpg'], 'is_active' => true]);
    $gate = EntranceGate::create(['name' => 'Lorong Bunga', 'photos' => ['uploads/gates/lorong-1.jpg'], 'is_active' => true]);

    $this->actingAs($admin)->post('/admin/bookings', [
        'client_mode' => 'existing',
        'client_id' => $client->id,
        'referral_source' => 'Instagram',
        'package_id' => $package->id,
        'event_date' => now()->addMonths(3)->toDateString(),
        'dp1_amount' => 1000000,
        'survey_wedding_stage_id' => $stage->id,
        'survey_wedding_stage_photo_path' => 'uploads/stages/classic-1.jpg',
        'survey_tent_id' => $tent->id,
        'survey_tent_photo_path' => 'uploads/tents/sisir-1.jpg',
        'survey_entrance_gate_id' => $gate->id,
        'survey_entrance_gate_photo_path' => 'uploads/gates/lorong-1.jpg',
        'location' => 'Gedung Serbaguna',
        'maps_url' => 'https://maps.app.goo.gl/gedung-serbaguna',
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
                'size' => 'L',
                'photo' => UploadedFile::fake()->image('cpp-akad.jpg'),
            ],
        ],
    ])->assertRedirect();

    $booking = Booking::where('client_id', $client->id)->latest('id')->firstOrFail();
    expect($booking->location)->toBe('Gedung Serbaguna');
    expect($booking->maps_url)->toBe('https://maps.app.goo.gl/gedung-serbaguna');
    expect($booking->survey->wedding_stage_id)->toBe($stage->id);
    expect($booking->survey->wedding_stage_photo_path)->toBe('uploads/stages/classic-1.jpg');
    expect($booking->survey->tent_id)->toBe($tent->id);
    expect($booking->survey->tent_photo_path)->toBe('uploads/tents/sisir-1.jpg');
    expect($booking->survey->entrance_gate_id)->toBe($gate->id);
    expect($booking->survey->entrance_gate_photo_path)->toBe('uploads/gates/lorong-1.jpg');
    expect($booking->survey->tent_sizes)->toBe(['4X6', '5X5']);
    expect($booking->survey->tent_size_quantities)->toBe(['4X6' => 2, '5X5' => 1]);
    expect($booking->fittings->first()->cpp_busana_akad_notes)->toBe('Jas hitam');
    expect($booking->fittings->first()->item_sizes['cpp_busana_akad'])->toBe('L');
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
        'referral_source' => 'Instagram',
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
        'referral_source' => 'Instagram',
        'event_date' => now()->addMonths(2)->toDateString(),
        'location' => 'Ballroom Hotel X',
        'amount' => '1.800.000',
        'proof' => UploadedFile::fake()->image('bukti.jpg'),
    ])->assertRedirect();

    $booking = Booking::where('email', 'renodewi@test.com')->first();
    expect($booking)->not->toBeNull();
    expect($booking->status)->toBe('pending');
    expect($booking->client_id)->toBeNull();
    expect((float) $booking->package_price)->toBe((float) $package->price);
    expect((float) $booking->payments()->where('type', 'DP1')->value('amount'))->toBe(1800000.0);
});

it('guest can book with compressed initial-payment proof upload', function () {
    Storage::fake('public');
    $package = Package::first();

    $this->post('/booking', [
        'package_id' => $package->id,
        'name' => 'Reno & Dewi',
        'phone' => '0812999888777',
        'email' => 'renodewi@test.com',
        'referral_source' => 'Instagram',
        'event_date' => now()->addMonths(2)->toDateString(),
        'location' => 'Ballroom Hotel X',
        'amount' => 1800000,
        'proof' => UploadedFile::fake()->image('bukti.jpg', 3000, 2000),
    ])->assertRedirect();

    $booking = Booking::where('email', 'renodewi@test.com')->firstOrFail();

    $payment = $booking->payments()->where('type', 'DP1')->first();
    expect($payment)->not->toBeNull();
    expect((float) $payment->amount)->toBe(1800000.0);
    expect($payment->proof)->not->toBeNull();
    expect($payment->proof)->toStartWith('uploads/proofs/');
    Storage::disk('public')->assertExists($payment->proof);
});

it('admin verifying the initial payment creates client account automatically', function () {
    Mail::fake();
    $admin = User::where('email', 'admin@anitamua.com')->first();
    $package = Package::first();

    $this->post('/booking', [
        'package_id' => $package->id,
        'name' => 'Reno & Dewi',
        'phone' => '0812999888777',
        'email' => 'renodewi@test.com',
        'referral_source' => 'Instagram',
        'event_date' => now()->addMonths(2)->toDateString(),
        'location' => 'Ballroom Hotel X',
        'amount' => 1800000,
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
    expect((float) $booking->payments()->where('type', 'DP1')->value('amount'))->toBe(1000000.0);

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
    Mail::assertSent(ClientAccountCredentials::class, function ($mail) use ($user, $booking) {
        return $mail->hasTo($user->email)
            && $mail->password === 'Reno123'
            && $mail->bookingCode === $booking->code;
    });
});

it('guest booking with existing client email waits for verification before attaching', function () {
    $client = User::where('email', 'client@anitamua.com')->first();
    $admin = User::where('email', 'admin@anitamua.com')->first();
    $package = Package::first();

    $this->post('/booking', [
        'package_id' => $package->id,
        'name' => 'Dewi Anggraini',
        'phone' => $client->phone,
        'email' => $client->email,
        'referral_source' => 'Rekomendasi',
        'event_date' => now()->addMonths(2)->toDateString(),
        'location' => 'The Glass House',
        'amount' => 1800000,
        'proof' => UploadedFile::fake()->image('bukti.jpg'),
    ]);

    $booking = Booking::where('email', $client->email)->latest('id')->first();

    expect($booking)->not->toBeNull();
    expect($booking->client_id)->toBeNull();

    $this->actingAs($admin)->post(route('admin.bookings.verify-dp', $booking), [
        'amount' => 1800000,
        'method' => 'transfer',
    ])->assertRedirect();

    expect($booking->refresh()->client_id)->toBe($client->id);
    expect($booking->name)->toBe($client->name);
    expect($booking->phone)->toBe($client->phone);
    expect($booking->email)->toBe($client->email);
    expect($booking->instagram)->toBe($client->instagram);
    expect(User::where('email', $client->email)->count())->toBe(1);
    expect(ActivityLog::where('booking_id', $booking->id)->where('action', 'client_account_linked')->exists())->toBeTrue();
});

it('keeps public booking identity synchronized with the logged in client', function () {
    $client = User::where('email', 'client@anitamua.com')->firstOrFail();
    $package = Package::firstOrFail();

    $this->actingAs($client)->post('/booking', [
        'package_id' => $package->id,
        'name' => 'Nama Profil Terbaru',
        'phone' => '081277788899',
        'email' => 'email-palsu@example.com',
        'instagram' => '@profilbaru',
        'referral_source' => 'Website',
        'event_date' => now()->addMonths(2)->toDateString(),
        'location' => 'Gedung Uji',
        'amount' => 1000000,
        'proof' => UploadedFile::fake()->image('bukti.jpg'),
    ])->assertRedirect();

    $client->refresh();
    $booking = Booking::where('client_id', $client->id)->latest('id')->firstOrFail();

    expect($client->name)->toBe('Nama Profil Terbaru');
    expect($client->phone)->toBe('081277788899');
    expect($client->instagram)->toBe('@profilbaru');
    expect($booking->name)->toBe($client->name);
    expect($booking->phone)->toBe($client->phone);
    expect($booking->email)->toBe($client->email);
    expect($booking->instagram)->toBe($client->instagram);

    $this->actingAs($client)->post(route('profile.update'), [
        'name' => 'Tidak Boleh Tanpa Telepon',
        'phone' => '',
    ])->assertSessionHasErrors('phone');
});

it('synchronizes linked bookings when a client profile changes', function () {
    $client = User::where('email', 'client@anitamua.com')->firstOrFail();
    $package = Package::firstOrFail();
    $booking = Booking::create([
        'code' => Booking::generateCode(),
        'client_id' => $client->id,
        'package_id' => $package->id,
        'package_price' => $package->price,
        'name' => $client->name,
        'phone' => $client->phone,
        'email' => $client->email,
        'instagram' => $client->instagram,
        'event_date' => now()->addMonths(2)->toDateString(),
        'status' => Booking::STATUS_BOOKED,
    ]);

    $this->actingAs($client)->post(route('profile.update'), [
        'name' => 'Profil Client Baru',
        'phone' => '081211122233',
    ])->assertRedirect();

    $client->refresh();
    $booking->refresh();
    expect($booking->name)->toBe($client->name);
    expect($booking->phone)->toBe($client->phone);
    expect($booking->email)->toBe($client->email);
    expect($booking->instagram)->toBe($client->instagram);
});

it('rejects linking an existing client email when the phone does not match', function () {
    $client = User::where('email', 'client@anitamua.com')->firstOrFail();
    $admin = User::where('email', 'admin@anitamua.com')->firstOrFail();
    $package = Package::firstOrFail();

    $this->post('/booking', [
        'package_id' => $package->id,
        'name' => 'Identitas Tidak Cocok',
        'phone' => '081200000099',
        'email' => $client->email,
        'referral_source' => 'Instagram',
        'event_date' => now()->addMonths(2)->toDateString(),
        'location' => 'Gedung Uji',
        'amount' => 1000000,
        'proof' => UploadedFile::fake()->image('bukti.jpg'),
    ]);

    $booking = Booking::where('name', 'Identitas Tidak Cocok')->firstOrFail();
    $payment = $booking->payments()->firstOrFail();

    $this->actingAs($admin)->post(route('admin.bookings.verify-dp', $booking), [
        'amount' => 1000000,
        'method' => 'transfer',
    ])->assertSessionHasErrors('phone');

    expect($booking->refresh()->status)->toBe(Booking::STATUS_PENDING);
    expect($booking->client_id)->toBeNull();
    expect($payment->refresh()->status)->toBe(Payment::STATUS_PENDING);
});

it('never assigns an authenticated owner as a public booking client', function () {
    $owner = User::where('email', 'owner@anitamua.com')->firstOrFail();
    $package = Package::firstOrFail();

    $this->actingAs($owner)->get('/booking')
        ->assertOk()
        ->assertDontSee('value="'.$owner->email.'"', false);

    $this->actingAs($owner)->post('/booking', [
        'package_id' => $package->id,
        'name' => 'Client Baru',
        'phone' => '081299900011',
        'email' => 'client-baru@example.com',
        'referral_source' => 'TikTok',
        'event_date' => now()->addMonths(2)->toDateString(),
        'location' => 'Gedung Baru',
        'amount' => 1000000,
        'proof' => UploadedFile::fake()->image('bukti.jpg'),
    ])->assertRedirect();

    $booking = Booking::where('email', 'client-baru@example.com')->firstOrFail();
    expect($booking->client_id)->toBeNull();
    expect($booking->created_by)->toBe($owner->id);
});

it('links a client created from the admin booking form', function () {
    $admin = User::where('email', 'admin@anitamua.com')->firstOrFail();
    $package = Package::firstOrFail();

    $this->actingAs($admin)->post('/admin/bookings', [
        'client_mode' => 'new',
        'new_client_name' => 'Ayu & Bima',
        'new_client_email' => 'ayu-bima@example.com',
        'new_client_phone' => '081288877766',
        'referral_source' => 'Instagram',
        'package_id' => $package->id,
        'event_date' => now()->addMonths(2)->toDateString(),
        'dp1_amount' => 1000000,
    ])->assertRedirect();

    $client = User::where('email', 'ayu-bima@example.com')->firstOrFail();
    $booking = Booking::where('email', 'ayu-bima@example.com')->firstOrFail();

    expect($client->role)->toBe(User::ROLE_CLIENT);
    expect($client->position)->toBe('Bride');
    expect($booking->client_id)->toBe($client->id);
    expect($booking->status)->toBe(Booking::STATUS_BOOKED);
});

it('rolls back dp verification when the booking email belongs to staff', function () {
    $admin = User::where('email', 'admin@anitamua.com')->firstOrFail();
    $owner = User::where('email', 'owner@anitamua.com')->firstOrFail();
    $package = Package::firstOrFail();

    $this->post('/booking', [
        'package_id' => $package->id,
        'name' => 'Data Salah',
        'phone' => '081200000001',
        'email' => $owner->email,
        'referral_source' => 'Instagram',
        'event_date' => now()->addMonths(2)->toDateString(),
        'location' => 'Gedung Uji',
        'amount' => 750000,
        'proof' => UploadedFile::fake()->image('bukti.jpg'),
    ]);

    $booking = Booking::where('name', 'Data Salah')->firstOrFail();
    $payment = $booking->payments()->firstOrFail();

    $this->actingAs($admin)->post(route('admin.bookings.verify-dp', $booking), [
        'amount' => 900000,
        'method' => 'transfer',
    ])->assertSessionHasErrors('email');

    expect($booking->refresh()->status)->toBe(Booking::STATUS_PENDING);
    expect($booking->client_id)->toBeNull();
    expect($payment->refresh()->status)->toBe(Payment::STATUS_PENDING);
    expect((float) $payment->amount)->toBe(750000.0);
    expect($booking->schedules()->where('type', 'hari_h')->exists())->toBeFalse();
});

it('rejects non-client ids in booking management and client routes', function () {
    $admin = User::where('email', 'admin@anitamua.com')->firstOrFail();
    $owner = User::where('email', 'owner@anitamua.com')->firstOrFail();
    $package = Package::firstOrFail();

    $this->actingAs($admin)->post('/admin/bookings', [
        'client_mode' => 'existing',
        'client_id' => $owner->id,
        'package_id' => $package->id,
        'event_date' => now()->addMonths(2)->toDateString(),
        'dp1_amount' => 1000000,
    ])->assertSessionHasErrors('client_id');

    $booking = Booking::firstOrFail();
    $booking->update(['client_id' => $owner->id]);
    $this->actingAs($owner)->get(route('client.booking', $booking))->assertForbidden();
});

it('does not book a pending booking when a non-DP1 payment is verified', function () {
    $admin = User::where('email', 'admin@anitamua.com')->firstOrFail();
    $package = Package::firstOrFail();
    $booking = Booking::create([
        'code' => Booking::generateCode(),
        'package_id' => $package->id,
        'package_price' => $package->price,
        'name' => 'Tahap Salah',
        'phone' => '081200000002',
        'email' => 'tahap-salah@example.com',
        'event_date' => now()->addMonths(2)->toDateString(),
        'status' => Booking::STATUS_PENDING,
    ]);
    $payment = $booking->payments()->create([
        'type' => 'PELUNASAN',
        'amount' => 500000,
        'status' => Payment::STATUS_PENDING,
    ]);

    $this->actingAs($admin)->post(route('admin.payments.verify', $payment), [
        'amount' => 500000,
    ])->assertRedirect();

    expect($booking->refresh()->status)->toBe(Booking::STATUS_PENDING);
    expect($booking->client_id)->toBeNull();
});

it('previews and repairs corrupted booking client links with the backfill command', function () {
    Mail::fake();
    $owner = User::where('email', 'owner@anitamua.com')->firstOrFail();
    $client = User::where('email', 'client@anitamua.com')->firstOrFail();
    $package = Package::firstOrFail();
    $wrongLink = Booking::create([
        'code' => Booking::generateCode(),
        'client_id' => $owner->id,
        'package_id' => $package->id,
        'package_price' => $package->price,
        'name' => $client->name,
        'phone' => $client->phone,
        'email' => $owner->email,
        'instagram' => '@salah',
        'event_date' => now()->addMonths(2)->toDateString(),
        'status' => Booking::STATUS_BOOKED,
    ]);
    $missingLink = Booking::create([
        'code' => Booking::generateCode(),
        'package_id' => $package->id,
        'package_price' => $package->price,
        'name' => 'Client Backfill',
        'phone' => '081277766655',
        'email' => 'backfill@example.com',
        'event_date' => now()->addMonths(3)->toDateString(),
        'status' => Booking::STATUS_BOOKED,
    ]);
    $mismatchedIdentity = Booking::create([
        'code' => Booking::generateCode(),
        'client_id' => $client->id,
        'package_id' => $package->id,
        'package_price' => $package->price,
        'name' => 'Nama Lama',
        'phone' => '080000000000',
        'email' => 'lama@example.com',
        'instagram' => '@lama',
        'event_date' => now()->addMonths(4)->toDateString(),
        'status' => Booking::STATUS_COMPLETED,
    ]);

    $this->artisan('bookings:backfill-clients')->assertExitCode(0);
    expect($wrongLink->refresh()->client_id)->toBe($owner->id);
    expect($missingLink->refresh()->client_id)->toBeNull();
    expect($mismatchedIdentity->refresh()->name)->toBe('Nama Lama');

    $this->artisan('bookings:backfill-clients', ['--force' => true])->assertExitCode(0);

    expect($wrongLink->refresh()->client_id)->toBe($client->id);
    expect($wrongLink->name)->toBe($client->name);
    expect($wrongLink->phone)->toBe($client->phone);
    expect($wrongLink->email)->toBe($client->email);
    expect($wrongLink->instagram)->toBe($client->instagram);
    $createdClient = User::where('email', 'backfill@example.com')->firstOrFail();
    expect($missingLink->refresh()->client_id)->toBe($createdClient->id);
    $mismatchedIdentity->refresh();
    expect($mismatchedIdentity->name)->toBe($client->name);
    expect($mismatchedIdentity->phone)->toBe($client->phone);
    expect($mismatchedIdentity->email)->toBe($client->email);
    expect($mismatchedIdentity->instagram)->toBe($client->instagram);
    Mail::assertNothingSent();
});

it('synchronizes the client and invoice when Hari H is finished', function () {
    Mail::fake();
    $admin = User::where('email', 'admin@anitamua.com')->firstOrFail();
    $package = Package::firstOrFail();
    $booking = Booking::create([
        'code' => Booking::generateCode(),
        'package_id' => $package->id,
        'package_price' => $package->price,
        'name' => 'Hari H Client',
        'phone' => '081266655544',
        'email' => 'hari-h@example.com',
        'event_date' => now()->addDay()->toDateString(),
        'status' => Booking::STATUS_BOOKED,
    ]);
    $schedule = $booking->schedules()->create([
        'type' => Schedule::TYPE_HARI_H,
        'date' => $booking->event_date,
        'status' => Schedule::STATUS_SCHEDULED,
    ]);

    $this->actingAs($admin)->post(route('admin.schedules.status', $schedule), [
        'status' => Schedule::STATUS_FINISHED,
    ])->assertRedirect();

    expect($schedule->refresh()->status)->toBe(Schedule::STATUS_FINISHED);
    expect($booking->refresh()->status)->toBe(Booking::STATUS_COMPLETED);
    expect($booking->client?->role)->toBe(User::ROLE_CLIENT);
    expect($booking->invoice)->not->toBeNull();
});

it('rolls back finishing Hari H when its booking is still pending', function () {
    $admin = User::where('email', 'admin@anitamua.com')->firstOrFail();
    $package = Package::firstOrFail();
    $booking = Booking::create([
        'code' => Booking::generateCode(),
        'package_id' => $package->id,
        'package_price' => $package->price,
        'name' => 'Hari H Pending',
        'phone' => '081266655533',
        'email' => 'hari-h-pending@example.com',
        'event_date' => now()->addDay()->toDateString(),
        'status' => Booking::STATUS_PENDING,
    ]);
    $schedule = $booking->schedules()->create([
        'type' => Schedule::TYPE_HARI_H,
        'date' => $booking->event_date,
        'status' => Schedule::STATUS_SCHEDULED,
    ]);

    $this->actingAs($admin)->post(route('admin.schedules.status', $schedule), [
        'status' => Schedule::STATUS_FINISHED,
    ])->assertSessionHasErrors('status');

    expect($schedule->refresh()->status)->toBe(Schedule::STATUS_SCHEDULED);
    expect($booking->refresh()->status)->toBe(Booking::STATUS_PENDING);
});

it('admin payment verification can correct the client submitted nominal', function () {
    $admin = User::where('email', 'admin@anitamua.com')->firstOrFail();
    $booking = Booking::firstOrFail();
    $payment = $booking->payments()->create([
        'type' => 'DP1',
        'amount' => 1800000,
        'method' => 'transfer',
        'proof' => 'uploads/proofs/client-proof.jpg',
        'status' => Payment::STATUS_PENDING,
    ]);

    $this->actingAs($admin)
        ->post(route('admin.payments.verify', $payment), ['amount' => 2250000])
        ->assertRedirect();

    expect($payment->refresh()->amount)->toBe('2250000.00')
        ->and($payment->status)->toBe(Payment::STATUS_VERIFIED);

    $this->actingAs($admin)
        ->post(route('admin.payments.verify', $payment), ['amount' => 3000000])
        ->assertRedirect()
        ->assertSessionHas('warning');

    $this->actingAs($admin)
        ->patch(route('admin.payments.amount.update', $payment), ['amount' => 0])
        ->assertRedirect()
        ->assertSessionHas('success');

    expect($payment->refresh()->amount)->toBe('0.00')
        ->and($payment->status)->toBe(Payment::STATUS_VERIFIED);
});

it('keeps verified payments immutable to client proof uploads', function () {
    Storage::fake('public');
    $client = User::where('email', 'client@anitamua.com')->firstOrFail();
    $booking = $client->bookings()->firstOrFail();

    $payment = $booking->payments()->where('status', Payment::STATUS_VERIFIED)->firstOrFail();
    $amount = $payment->amount;

    $this->actingAs($client)
        ->post(route('client.booking.proof', $booking), [
            'payment_id' => $payment->id,
            'method' => 'transfer',
            'proof' => UploadedFile::fake()->image('ulang-bukti.jpg'),
        ])
        ->assertRedirect()
        ->assertSessionHas('warning');

    expect($payment->refresh()->status)->toBe(Payment::STATUS_VERIFIED)
        ->and($payment->amount)->toBe($amount);
});

it('does not reverify DP for a booking that is already booked', function () {
    $admin = User::where('email', 'admin@anitamua.com')->firstOrFail();
    $booking = Booking::firstOrFail();
    $payment = $booking->payments()->oldest('id')->firstOrFail();
    $amount = $payment->amount;

    $this->actingAs($admin)
        ->post(route('admin.bookings.verify-dp', $booking), [
            'amount' => 3000000,
            'method' => 'transfer',
        ])
        ->assertRedirect()
        ->assertSessionHas('warning');

    expect($payment->refresh()->amount)->toBe($amount)
        ->and($booking->refresh()->status)->toBe(Booking::STATUS_BOOKED);
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

it('shows one dashboard row per assigned booking with a fieldwork action', function () {
    $team = User::where('email', 'team@anitamua.com')->firstOrFail();
    $booking = Booking::firstOrFail()->replicate();
    $booking->code = Booking::generateCode();
    $booking->name = 'Booking Dashboard Tim Unik';
    $booking->save();

    foreach ([Schedule::TYPE_SURVEY, Schedule::TYPE_FITTING] as $index => $type) {
        $booking->schedules()->create([
            'type' => $type,
            'title' => 'Tugas '.$type,
            'date' => now()->addDays($index + 1)->toDateString(),
            'pic_user_id' => $team->id,
            'status' => Schedule::STATUS_SCHEDULED,
        ]);
    }

    $response = $this->actingAs($team)
        ->get('/dashboard')
        ->assertOk()
        ->assertSee('Buka Tugas')
        ->assertSee('Checklist')
        ->assertSee(route('admin.bookings.packing', $booking), false)
        ->assertSee($booking->code);

    expect(substr_count($response->getContent(), $booking->code))->toBe(1);
});

it('shows client dashboard with booking summary', function () {
    $client = User::where('email', 'client@anitamua.com')->first();

    $this->actingAs($client)
        ->get('/dashboard')
        ->assertOk()
        ->assertSee('Dewi & Andi')
        ->assertSee('Booked');
});

it('blocks team from admin booking pages while keeping the calendar available', function () {
    $team = User::where('email', 'team@anitamua.com')->first();
    $booking = Booking::first();

    $this->actingAs($team)->get('/admin/bookings')->assertForbidden();
    $this->actingAs($team)->get('/admin/bookings/'.$booking->id)->assertForbidden();
    $this->actingAs($team)->get('/admin/calendar')->assertOk();
    $this->actingAs($team)->get('/admin/payments')->assertForbidden();
    $this->actingAs($team)->get('/admin/packages')->assertForbidden();
});

it('lets field team manage inventory and categories like admin', function () {
    $team = User::where('email', 'team@anitamua.com')->firstOrFail();
    $admin = User::where('email', 'admin@anitamua.com')->firstOrFail();

    $this->actingAs($team)
        ->get('/dashboard')
        ->assertOk()
        ->assertSee('Inventory');

    $this->actingAs($team)
        ->get(route('admin.inventory.index'))
        ->assertOk()
        ->assertSee('Inventory Wardrobe')
        ->assertSee('Dipakai')
        ->assertSee('Disewa')
        ->assertSee('Dilaundry')
        ->assertSee('Dipermak')
        ->assertSee('Tambah Barang')
        ->assertSee('openEdit(', false);

    $this->actingAs($team)->get(route('admin.inventory-categories.index'))->assertOk();
    $this->actingAs($team)->post(route('admin.inventory-categories.store'), [
        'name' => 'Kategori Team Uji',
    ])->assertRedirect();
    $category = InventoryCategory::where('name', 'Kategori Team Uji')->firstOrFail();
    $this->actingAs($team)->put(route('admin.inventory-categories.update', $category), [
        'name' => 'Kategori Team Baru',
    ])->assertRedirect();
    expect($category->refresh()->name)->toBe('Kategori Team Baru');

    $this->actingAs($team)->post(route('admin.inventory.store'), [
        'name' => 'Gaun Team Uji',
        'inventory_category_id' => $category->id,
        'condition' => 'good',
        'status' => 'available',
    ])->assertRedirect();
    $item = InventoryItem::where('name', 'Gaun Team Uji')->firstOrFail();
    $this->actingAs($team)->put(route('admin.inventory.update', $item), [
        'name' => 'Gaun Team Baru',
        'inventory_category_id' => $category->id,
        'condition' => 'fair',
        'status' => 'in_use',
    ])->assertRedirect();
    expect($item->refresh()->name)->toBe('Gaun Team Baru');

    $this->actingAs($team)->delete(route('admin.inventory.destroy', $item))->assertRedirect();
    $this->assertDatabaseMissing('inventory_items', ['id' => $item->id]);
    $this->actingAs($team)->delete(route('admin.inventory-categories.destroy', $category))->assertRedirect();
    $this->assertDatabaseMissing('inventory_categories', ['id' => $category->id]);

    $this->actingAs($admin)
        ->get(route('admin.inventory.index'))
        ->assertOk()
        ->assertSee('Tambah Barang')
        ->assertSee('Aksi');
});

it('blocks client from back office pages', function () {
    $client = User::where('email', 'client@anitamua.com')->first();

    $this->actingAs($client)->get('/admin/bookings')->assertForbidden();
});

it('lets client view their booking detail', function () {
    $client = User::where('email', 'client@anitamua.com')->first();
    $booking = $client->bookings()->first();
    $booking->payments()->create([
        'type' => 'DP2',
        'amount' => 1250000,
        'method' => 'transfer',
        'status' => Payment::STATUS_PENDING,
    ]);
    ActivityLog::create([
        'booking_id' => $booking->id,
        'user_id' => $client->id,
        'action' => 'test_client_history',
        'title' => 'Riwayat client',
        'description' => 'Bukti pembayaran diterima.',
    ]);

    $response = $this->actingAs($client)->get("/client/booking/{$booking->id}")->assertOk();
    $response->assertSee('id="paymentType"', false)
        ->assertSee('id="paymentAmount"', false)
        ->assertSee('data-pending-payment', false)
        ->assertSee('Data Survey')
        ->assertSee('Data Fitting')
        ->assertSeeInOrder(['Data Survey', 'Data Fitting', 'Aktivitas'])
        ->assertSee('max-h-48 overflow-y-auto overscroll-contain pr-2', false)
        ->assertSee('&middot;', false)
        ->assertDontSee('id="paymentSelect"', false);
});

it('hides survey and fitting summaries from clients when they have no data', function () {
    $client = User::where('email', 'client@anitamua.com')->firstOrFail();
    $reference = $client->bookings()->firstOrFail();
    $booking = Booking::create([
        'code' => Booking::generateCode(),
        'client_id' => $client->id,
        'package_id' => $reference->package_id,
        'package_price' => $reference->package_price,
        'name' => 'Booking Tanpa Data Lapangan',
        'phone' => $client->phone,
        'email' => $client->email,
        'event_date' => now()->addMonth()->toDateString(),
        'status' => Booking::STATUS_PENDING,
    ]);

    $this->actingAs($client)->get(route('client.booking', $booking))
        ->assertOk()
        ->assertDontSee('Data Survey')
        ->assertDontSee('Data Fitting');
});

it('lets a client submit and update a testimonial after their booking is completed', function () {
    Storage::fake('public');
    $client = User::where('email', 'client@anitamua.com')->firstOrFail();
    $owner = User::where('email', 'owner@anitamua.com')->firstOrFail();
    $booking = $client->bookings()->firstOrFail();

    $this->actingAs($client)->get(route('client.booking', $booking))
        ->assertOk()
        ->assertDontSee('Bagikan Pengalaman');
    $this->actingAs($client)->get(route('client.testimonials'))
        ->assertOk()
        ->assertDontSee('data-testimonial-form', false);
    $this->actingAs($client)->post(route('client.booking.testimonial', $booking), [
        'rating' => 5,
        'content' => 'Belum boleh dikirim.',
    ])->assertForbidden();

    $booking->update(['status' => Booking::STATUS_COMPLETED]);
    $this->actingAs($client)->get(route('client.booking', $booking))
        ->assertOk()
        ->assertDontSee('Bagikan Pengalaman');
    $this->actingAs($client)->get(route('client.testimonials'))
        ->assertOk()
        ->assertSee($booking->code)
        ->assertSee('data-testimonial-form', false);

    $this->actingAs($client)->post(route('client.booking.testimonial', $booking), [
        'rating' => 5,
        'content' => 'Tim ANITA membuat hari pernikahan kami terasa tenang dan istimewa.',
        'photos' => [
            UploadedFile::fake()->image('testimoni-1.jpg'),
            UploadedFile::fake()->image('testimoni-2.jpg'),
        ],
    ])->assertRedirect()->assertSessionHas('success');

    $testimonial = $booking->testimonial()->firstOrFail();
    $removedPhoto = $testimonial->photos[0];
    expect($booking->testimonial()->count())->toBe(1)
        ->and($testimonial->client_name)->toBe($booking->name)
        ->and($testimonial->rating)->toBe(5)
        ->and($testimonial->status)->toBe('hidden')
        ->and($testimonial->photos)->toHaveCount(2);
    Storage::disk('public')->assertExists($removedPhoto);
    expect(ActivityLog::where('booking_id', $booking->id)->where('action', 'testimonial_submitted')->exists())->toBeTrue();

    $this->actingAs($client)->get(route('client.testimonials'))
        ->assertOk()
        ->assertSee($testimonial->content)
        ->assertSee('Edit Testimoni')
        ->assertDontSee('Menunggu persetujuan')
        ->assertDontSee('Owner/Admin akan memeriksa testimoni');

    $testimonial->update(['status' => 'published']);
    $this->actingAs($client)->post(route('client.booking.testimonial', $booking), [
        'rating' => 4,
        'content' => 'Pelayanan sangat baik dan tim selalu membantu kami.',
        'remove_photos' => [$removedPhoto],
        'photos' => [UploadedFile::fake()->image('testimoni-baru.jpg')],
    ])->assertRedirect()->assertSessionHas('success');

    $testimonial->refresh();
    expect($booking->testimonial()->count())->toBe(1)
        ->and($testimonial->rating)->toBe(4)
        ->and($testimonial->status)->toBe('hidden')
        ->and($testimonial->photos)->toHaveCount(2)
        ->and($testimonial->photos)->not->toContain($removedPhoto);
    Storage::disk('public')->assertMissing($removedPhoto);
    expect(ActivityLog::where('booking_id', $booking->id)->where('action', 'testimonial_updated')->exists())->toBeTrue();

    $this->get(route('testimonials'))->assertOk()->assertDontSee($testimonial->content);
    $this->actingAs($owner)->put(route('admin.content.testimonials.update', $testimonial), [
        'client_name' => $testimonial->client_name,
        'rating' => $testimonial->rating,
        'content' => $testimonial->content,
        'status' => 'published',
    ])->assertRedirect();
    $this->get(route('testimonials'))->assertOk()->assertSee($testimonial->content);
    $this->actingAs($owner)->get(route('admin.content.testimonials'))
        ->assertOk()
        ->assertSee('Booking '.$booking->code);
});

it('protects client testimonial ownership and validates its fields', function () {
    Storage::fake('public');
    $client = User::where('email', 'client@anitamua.com')->firstOrFail();
    $booking = $client->bookings()->firstOrFail();
    $booking->update(['status' => Booking::STATUS_COMPLETED]);
    $otherClient = User::create([
        'name' => 'Klien Lain',
        'email' => 'klien-lain@example.com',
        'password' => bcrypt('password'),
        'role' => User::ROLE_CLIENT,
        'is_active' => true,
    ]);

    $this->actingAs($otherClient)->post(route('client.booking.testimonial', $booking), [
        'rating' => 5,
        'content' => 'Bukan booking milik saya.',
    ])->assertForbidden();

    $this->actingAs($client)->post(route('client.booking.testimonial', $booking), [
        'rating' => 6,
        'content' => '',
        'photos' => [UploadedFile::fake()->image('terlalu-besar.jpg')->size(3073)],
    ])->assertSessionHasErrors(['rating', 'content', 'photos.0']);
    expect($booking->testimonial()->exists())->toBeFalse();
});

it('lets admins manage reference types and clients upload booking references', function () {
    Storage::fake('public');

    expect(ReferenceType::whereIn('name', ['Dekor', 'Tenda', 'Foto Prewedding'])->count())->toBe(3);

    $owner = User::where('email', 'owner@anitamua.com')->firstOrFail();
    $client = User::where('email', 'client@anitamua.com')->firstOrFail();
    $booking = $client->bookings()->firstOrFail();

    $this->actingAs($owner)->post(route('admin.reference-types.store'), [
        'name' => 'Dekor Impian',
    ])->assertRedirect()->assertSessionHas('success');

    $referenceType = ReferenceType::where('name', 'Dekor Impian')->firstOrFail();
    $this->actingAs($client)->get(route('client.references'))
        ->assertOk()
        ->assertSee('Dekor Impian')
        ->assertDontSee('Booking '.$booking->code);

    $this->actingAs($client)->post(route('client.references.store'), [
        'reference_type_id' => $referenceType->id,
        'notes' => 'Warna pastel dan bunga tidak terlalu ramai.',
        'photos' => [
            UploadedFile::fake()->image('referensi-1.jpg'),
            UploadedFile::fake()->image('referensi-2.jpg'),
        ],
    ])->assertRedirect()->assertSessionHas('success');

    $reference = ClientReference::firstOrFail();
    expect($reference->client_id)->toBe($client->id)
        ->and($reference->booking_id)->toBe($booking->id)
        ->and($reference->photos)->toHaveCount(2);
    Storage::disk('public')->assertExists($reference->photos[0]);
    expect(ActivityLog::where('booking_id', $booking->id)->where('action', 'reference_uploaded')->exists())->toBeTrue();

    $removedPhoto = $reference->photos[0];
    $this->actingAs($client)->put(route('client.references.update', $reference), [
        'reference_type_id' => $referenceType->id,
        'notes' => 'Catatan referensi diperbarui klien.',
        'remove_photos' => [$removedPhoto],
        'photos' => [UploadedFile::fake()->image('referensi-baru.jpg')],
    ])->assertRedirect()->assertSessionHas('success');
    $reference->refresh();
    expect($reference->notes)->toBe('Catatan referensi diperbarui klien.')
        ->and($reference->photos)->toHaveCount(2)
        ->and($reference->photos)->not->toContain($removedPhoto);
    Storage::disk('public')->assertMissing($removedPhoto);
    $this->actingAs($client)->get(route('client.references'))
        ->assertOk()
        ->assertSee('Catatan referensi diperbarui klien.')
        ->assertSee('fa-pen', false)
        ->assertDontSee('Booking '.$booking->code);

    $defaultType = ReferenceType::where('name', 'Tenda')->firstOrFail();
    $this->actingAs($owner)->post(route('admin.bookings.references.store', $booking), [
        'reference_type_id' => $defaultType->id,
        'notes' => 'Referensi ditambahkan admin.',
        'photos' => [UploadedFile::fake()->image('referensi-admin.jpg')],
    ])->assertRedirect()->assertSessionHas('success');
    $adminReference = ClientReference::where('id', '!=', $reference->id)->firstOrFail();

    $this->actingAs($owner)->put(route('admin.bookings.references.update', [$booking, $adminReference]), [
        'reference_type_id' => $defaultType->id,
        'notes' => 'Referensi diperbarui admin.',
    ])->assertRedirect()->assertSessionHas('success');

    $this->actingAs($owner)->get(route('admin.bookings.show', $booking))
        ->assertOk()
        ->assertSee('Referensi Klien')
        ->assertSee('Dekor Impian')
        ->assertSee('Catatan referensi diperbarui klien.')
        ->assertSee('Referensi diperbarui admin.')
        ->assertSee('Tambah', false);

    $adminPhoto = $adminReference->photos[0];
    $this->actingAs($owner)->delete(route('admin.bookings.references.destroy', [$booking, $adminReference]))
        ->assertRedirect()
        ->assertSessionHas('success');
    Storage::disk('public')->assertMissing($adminPhoto);

    $this->actingAs($owner)->delete(route('admin.reference-types.destroy', $referenceType))
        ->assertRedirect()
        ->assertSessionHas('error');

    $otherClient = User::create([
        'name' => 'Klien Referensi Lain',
        'email' => 'referensi-lain@example.com',
        'password' => bcrypt('password'),
        'role' => User::ROLE_CLIENT,
        'is_active' => true,
    ]);
    $this->actingAs($otherClient)->post(route('client.references.store'), [
        'reference_type_id' => $referenceType->id,
        'photos' => [UploadedFile::fake()->image('tanpa-booking.jpg')],
    ])->assertStatus(422);
    expect(ClientReference::count())->toBe(1);

    $this->actingAs($otherClient)->put(route('client.references.update', $reference), [
        'reference_type_id' => $referenceType->id,
    ])->assertForbidden();
    $this->actingAs($otherClient)->delete(route('client.references.destroy', $reference))->assertForbidden();

    $photo = $reference->photos[0];
    $this->actingAs($client)->delete(route('client.references.destroy', $reference))
        ->assertRedirect()
        ->assertSessionHas('success');
    Storage::disk('public')->assertMissing($photo);
    expect(ClientReference::count())->toBe(0);

    $this->actingAs($owner)->delete(route('admin.reference-types.destroy', $referenceType))
        ->assertRedirect()
        ->assertSessionHas('success');
    expect(ReferenceType::whereKey($referenceType->id)->exists())->toBeFalse();
});

it('keeps reference photos intact when an edit cannot be saved', function () {
    Storage::fake('public');

    $owner = User::where('email', 'owner@anitamua.com')->firstOrFail();
    $client = User::where('email', 'client@anitamua.com')->firstOrFail();
    $booking = $client->bookings()->firstOrFail();
    $type = ReferenceType::firstOrFail();
    $oldPhoto = UploadedFile::fake()->image('lama.jpg')->store('uploads/references', 'public');
    $reference = ClientReference::create([
        'booking_id' => $booking->id,
        'client_id' => $client->id,
        'reference_type_id' => $type->id,
        'photos' => [$oldPhoto],
    ]);

    ClientReference::updating(function () {
        throw new RuntimeException('Simulasi gagal menyimpan referensi.');
    });

    try {
        foreach ([
            [$owner, route('admin.bookings.references.update', [$booking, $reference])],
            [$client, route('client.references.update', $reference)],
        ] as [$user, $url]) {
            $this->actingAs($user)->put($url, [
                'reference_type_id' => $type->id,
                'remove_photos' => [$oldPhoto],
                'photos' => [UploadedFile::fake()->image('baru.jpg')],
            ])->assertInternalServerError();

            expect($reference->fresh()->photos)->toBe([$oldPhoto]);
            Storage::disk('public')->assertExists($oldPhoto);
            expect(Storage::disk('public')->allFiles('uploads/references'))->toBe([$oldPhoto]);
        }
    } finally {
        ClientReference::flushEventListeners();
    }
});

it('shows all booking activities in one scrollable history ordered newest first', function () {
    $admin = User::where('email', 'admin@anitamua.com')->firstOrFail();
    $booking = Booking::firstOrFail();
    $booking->activityLogs()->delete();

    foreach (range(1, 7) as $number) {
        $activity = ActivityLog::create([
            'booking_id' => $booking->id,
            'user_id' => $admin->id,
            'action' => 'test_activity_'.$number,
            'title' => 'Aktivitas '.$number,
            'description' => 'Aktivitas '.$number,
        ]);
        $activity->forceFill([
            'created_at' => now()->addSeconds($number),
            'updated_at' => now()->addSeconds($number),
        ])->save();
    }

    $response = $this->actingAs($admin)->get(route('admin.bookings.show', $booking))->assertOk();
    $response->assertSeeInOrder(['Aktivitas 7', 'Aktivitas 6', 'Aktivitas 5', 'Aktivitas 4', 'Aktivitas 3', 'Aktivitas 2', 'Aktivitas 1']);
    $response->assertSee('data-activities-scroll', false)
        ->assertSee('max-h-48', false)
        ->assertDontSee('Aktivitas sebelumnya')
        ->assertDontSee('data-recent-activities', false)
        ->assertDontSee('data-older-activities', false);
});

it('shows the booking location and maps link in booking information', function () {
    $admin = User::where('email', 'admin@anitamua.com')->firstOrFail();
    $booking = Booking::firstOrFail();
    $booking->update([
        'location' => 'Gedung Serbaguna ANITA',
        'maps_url' => 'https://maps.google.com/?q=Gedung+Serbaguna+ANITA',
    ]);

    $this->actingAs($admin)->get(route('admin.bookings.show', $booking))
        ->assertOk()
        ->assertSee('grid-cols-2 md:grid-cols-4', false)
        ->assertSee('Link Maps')
        ->assertSee('Gedung Serbaguna ANITA')
        ->assertSee('data-copy-client-info', false)
        ->assertSee('Salin Data Klien')
        ->assertSee('const clientCopyText', false)
        ->assertSee('Nama Pengantin:', false)
        ->assertSee('No. HP:', false);
});

it('renders scoped reset and hide controls for survey and fitting forms', function () {
    $admin = User::where('email', 'admin@anitamua.com')->firstOrFail();
    $team = User::where('email', 'team@anitamua.com')->firstOrFail();
    $booking = Booking::firstOrFail();

    $createHtml = $this->actingAs($admin)->get(route('admin.bookings.create'))->assertOk()->getContent();
    $editHtml = $this->actingAs($admin)->get(route('admin.bookings.edit', $booking))->assertOk()->getContent();
    $fieldworkHtml = $this->actingAs($team)->get(route('admin.fieldwork.booking', $booking))->assertOk()->getContent();

    foreach ([$createHtml, $fieldworkHtml] as $html) {
        expect(substr_count($html, '<button type="button" data-fieldwork-reset'))->toBe(2)
            ->and(substr_count($html, '<button type="button" data-fieldwork-toggle'))->toBe(2);
    }

    expect(substr_count($editHtml, '<button type="button" data-fieldwork-reset'))->toBe(3)
        ->and(substr_count($editHtml, '<button type="button" data-fieldwork-toggle'))->toBe(3);
});

it('client can add a new payment stage with custom label and nominal', function () {
    Storage::fake('public');
    $client = User::where('email', 'client@anitamua.com')->first();
    $booking = $client->bookings()->first();
    $before = $booking->payments()->count();

    $this->actingAs($client)->post("/client/booking/{$booking->id}/proof", [
        'type' => 'Tambahan Keluarga',
        'amount' => 0,
        'method' => 'transfer',
        'proof' => UploadedFile::fake()->image('pelunasan.jpg'),
    ])->assertRedirect()->assertSessionHas('success');

    $payment = $booking->payments()->latest('id')->first();
    expect($payment)->not->toBeNull();
    expect($booking->payments()->count())->toBe($before + 1);
    expect($payment->type)->toBe('Tambahan Keluarga');
    expect(Payment::typeLabel($payment->type))->toBe('Tambahan Keluarga');
    expect((float) $payment->amount)->toBe(0.0);
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

it('creates one invoice per booked booking and updates it from verified payments', function () {
    $admin = User::where('email', 'admin@anitamua.com')->firstOrFail();
    $client = User::where('email', 'client@anitamua.com')->firstOrFail();
    $package = Package::firstOrFail();

    $booking = Booking::create([
        'code' => Booking::generateCode(),
        'client_id' => $client->id,
        'package_id' => $package->id,
        'package_price' => $package->price,
        'name' => 'Andi & Siska',
        'phone' => '081234567890',
        'email' => $client->email,
        'event_date' => now()->addMonths(2)->toDateString(),
        'location' => 'Gedung Anita',
        'status' => Booking::STATUS_BOOKED,
    ]);
    $booking->addons()->create(['name' => 'Extra Touch Up', 'price' => 250000]);
    $booking->payments()->create([
        'type' => 'DP1',
        'amount' => 1000000,
        'method' => 'transfer',
        'status' => Payment::STATUS_VERIFIED,
        'paid_at' => now(),
        'verified_by' => $admin->id,
        'verified_at' => now(),
    ]);

    $invoice = app(InvoiceService::class)->sync($booking);

    expect($invoice)->toBeInstanceOf(Invoice::class)
        ->and($invoice->invoice_number)->toBe('INV-AS-'.$booking->code)
        ->and((float) $invoice->total_amount)->toBe((float) $package->price + 250000.0)
        ->and((float) $invoice->paid_amount)->toBe(1000000.0)
        ->and($invoice->status)->toBe(Invoice::STATUS_PARTIAL);
    expect($invoice->statusLabel())->toBe('Partial');

    $package->update(['price' => (float) $package->price + 1000000]);
    $invoice = app(InvoiceService::class)->sync($booking->fresh());
    expect((float) $invoice->total_amount)->toBe((float) $booking->package_price + 250000.0);

    $newDueDate = now()->addMonths(2)->subDays(7)->toDateString();
    $this->actingAs($admin)
        ->patch(route('admin.invoices.due-date.update', $invoice), ['due_date' => $newDueDate])
        ->assertRedirect()
        ->assertSessionHas('success');
    expect($invoice->refresh()->due_date->toDateString())->toBe($newDueDate);

    $booking->payments()->create([
        'type' => 'Pelunasan',
        'amount' => max(1000, (float) $invoice->remaining_amount),
        'method' => 'transfer',
        'status' => Payment::STATUS_PENDING,
    ]);
    $pending = app(InvoiceService::class)->sync($booking->fresh());
    expect($pending->status)->toBe(Invoice::STATUS_PARTIAL);

    $booking->payments()->latest('id')->first()->update([
        'status' => Payment::STATUS_VERIFIED,
        'paid_at' => now(),
        'verified_by' => $admin->id,
        'verified_at' => now(),
    ]);
    $paid = app(InvoiceService::class)->sync($booking->fresh());
    expect($paid->status)->toBe(Invoice::STATUS_PAID)
        ->and($paid->statusLabel())->toBe('Paid')
        ->and(Invoice::where('booking_id', $booking->id)->count())->toBe(1);

    SiteSetting::set('company_name', 'ANITA Makeup Art');
    SiteSetting::set('address', 'Jl. Mawar No. 1, Cirebon');
    SiteSetting::set('bank_name', 'BCA');
    SiteSetting::set('bank_account_number', '1234567890');
    SiteSetting::set('bank_account_name', 'Anita Makeup Art');
    SiteSetting::set('invoice_greeting', 'Terima kasih atas kepercayaan Anda.');

    $this->actingAs($admin)
        ->get(route('admin.invoices.show', $paid))
        ->assertOk()
        ->assertSee('Cetak Invoice')
        ->assertSee('Jl. Mawar No. 1, Cirebon')
        ->assertSee('Terima kasih atas kepercayaan Anda.')
        ->assertDontSee('Kirim via WhatsApp');

    $this->actingAs($admin)
        ->get(route('admin.invoices.pdf', $paid))
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf')
        ->assertHeader('content-disposition', 'inline; filename="Invoice-'.$paid->invoice_number.'.pdf"');
});

it('prints booking details with survey and fitting data in an admin-only PDF', function () {
    Storage::fake('public');

    $owner = User::where('email', 'owner@anitamua.com')->firstOrFail();
    $client = User::where('email', 'client@anitamua.com')->firstOrFail();
    $booking = $client->bookings()->firstOrFail();
    $photo = UploadedFile::fake()->image('pilihan-pelaminan.jpg')->store('uploads/surveys', 'public');

    $booking->survey()->firstOrNew()->fill([
        'pic' => 'Tim Survey',
        'flower_color' => 'Putih dan merah muda',
        'wedding_stage_photo_path' => $photo,
        'notes' => 'Akses mobil lewat gerbang selatan.',
    ])->save();
    $fitting = $booking->fittings()->firstOrNew()->fill([
        'date' => now()->toDateString(), 'pic' => 'Tim Fitting', 'status' => 'finished',
    ]);
    $fitting->cpw_busana_akad_notes = 'Kain putih dengan bordir';
    $fitting->cpw_busana_akad_photo_path = $photo;
    $fitting->save();

    $html = view('admin.bookings.pdf', ['booking' => $booking, 'companyName' => 'ANITA'])->render();
    expect($html)->toContain('Informasi Booking dan Klien', 'Data Survey', 'Data Fitting',
        'Putih dan merah muda', 'Akses mobil lewat gerbang selatan.', 'Kain putih dengan bordir', 'data:image/jpeg;base64,');

    $this->actingAs($owner)->get(route('admin.bookings.show', $booking))
        ->assertOk()->assertSee('Cetak PDF');
    $response = $this->actingAs($owner)->get(route('admin.bookings.pdf', $booking))
        ->assertOk()->assertHeader('content-type', 'application/pdf');
    expect(substr($response->getContent(), 0, 4))->toBe('%PDF');

    $this->actingAs($client)->get(route('admin.bookings.pdf', $booking))->assertForbidden();
});

it('applies multiple booking discounts to totals and invoices', function () {
    $admin = User::where('email', 'admin@anitamua.com')->firstOrFail();
    $client = User::where('email', 'client@anitamua.com')->firstOrFail();
    $package = Package::firstOrFail();

    $booking = Booking::create([
        'code' => Booking::generateCode(),
        'client_id' => $client->id,
        'package_id' => $package->id,
        'package_price' => $package->price,
        'discounts' => [
            ['amount' => 100000, 'note' => 'Promo awal tahun'],
            ['amount' => 250000, 'note' => 'Potongan vendor'],
        ],
        'bonuses' => [
            ['amount' => 200000, 'note' => 'Free touch up'],
            ['amount' => 300000, 'note' => 'Aksesori tambahan'],
        ],
        'name' => 'Diskon Persen',
        'phone' => '081234567890',
        'email' => $client->email,
        'event_date' => now()->addMonths(2)->toDateString(),
        'status' => Booking::STATUS_BOOKED,
    ]);
    $booking->addons()->create(['name' => 'Tambahan', 'price' => 250000]);

    $subtotal = (float) $package->price + 250000;
    $discountTotal = 350000.0;
    expect($booking->refresh()->discount_amount)->toBe($discountTotal)
        ->and($booking->discount_label)->toBe('Diskon — Promo awal tahun, Diskon — Potongan vendor')
        ->and($booking->total_price)->toBe($subtotal - $discountTotal);

    $invoice = app(InvoiceService::class)->sync($booking->fresh());
    expect((float) $invoice->total_amount)->toBe((float) ($subtotal - $discountTotal))
        ->and((float) collect($invoice->items)->slice(-2)->sum('total'))->toBe((float) -$discountTotal)
        ->and(collect($invoice->items)->slice(-2)->pluck('name')->all())->toBe([
            'Diskon — Promo awal tahun',
            'Diskon — Potongan vendor',
        ]);

    $this->actingAs($admin)->get(route('admin.invoices.show', $invoice))
        ->assertOk()
        ->assertSee('class="invoice-line--discount"', false)
        ->assertSee('Promo awal tahun')
        ->assertSee('>Bonus</div>', false)
        ->assertSee('Free touch up')
        ->assertSee('<strong>Rp 200.000</strong>', false)
        ->assertSee('<strong>Rp 300.000</strong>', false)
        ->assertDontSee('<s>Rp 200.000</s>', false)
        ->assertDontSee('<s>Rp 300.000</s>', false)
        ->assertSee('<div class="invoice-bonus-total"><span>Total</span><strong><s>Rp 500.000</s></strong></div>', false)
        ->assertDontSee('invoice-discount-marker', false);

    $pdfHtml = view('invoices.pdf', ['invoice' => $invoice, 'settings' => [], 'logoSrc' => null])->render();
    expect($pdfHtml)->toContain('<div class="cell">Rp 200.000</div>')
        ->toContain('<div class="cell">Rp 300.000</div>')
        ->not->toContain('<s>Rp 200.000</s>')
        ->not->toContain('<s>Rp 300.000</s>');
    expect($pdfHtml)->toContain('<div class="bonus-total table"><div class="cell">Total</div><div class="cell"><s>Rp 500.000</s></div></div>');
    $this->actingAs($admin)->get(route('admin.invoices.pdf', $invoice))->assertOk();

    $booking->update(['discounts' => null, 'discount_type' => 'fixed', 'discount_value' => 100000]);
    expect($booking->refresh()->discount_amount)->toBe(100000.0)
        ->and($booking->total_price)->toBe($subtotal - 100000.0);

    $booking->update(['discounts' => null, 'discount_type' => 'percentage', 'discount_value' => 100]);
    $booking->payments()->create([
        'type' => 'DP1',
        'amount' => 0,
        'method' => 'transfer',
        'status' => Payment::STATUS_VERIFIED,
        'paid_at' => now(),
    ]);
    $zeroInvoice = app(InvoiceService::class)->sync($booking->fresh());
    $progress = BookingProgress::calculate($booking->fresh(['payments', 'survey', 'fittings', 'schedules']));
    expect((float) $zeroInvoice->total_amount)->toBe(0.0)
        ->and($zeroInvoice->status)->toBe(Invoice::STATUS_PAID)
        ->and(collect($progress['steps'])->firstWhere('key', 'pelunasan')['state'])->toBe('done');
});

it('validates discount changes against type, rupiah precision, and received payments', function () {
    $admin = User::where('email', 'admin@anitamua.com')->firstOrFail();
    $client = User::where('email', 'client@anitamua.com')->firstOrFail();
    $package = Package::firstOrFail();
    $booking = Booking::create([
        'code' => Booking::generateCode(),
        'client_id' => $client->id,
        'package_id' => $package->id,
        'package_price' => $package->price,
        'discount_type' => 'percentage',
        'discount_value' => 10,
        'discount_note' => 'Promo lama',
        'name' => 'Validasi Diskon',
        'phone' => '081234567890',
        'email' => $client->email,
        'referral_source' => 'Instagram',
        'event_date' => now()->addMonths(2)->toDateString(),
        'status' => Booking::STATUS_BOOKED,
    ]);
    $payload = fn (array $discount) => array_merge([
        'package_id' => $package->id,
        'name' => $booking->name,
        'phone' => $booking->phone,
        'email' => $booking->email,
        'referral_source' => $booking->referral_source,
        'event_date' => $booking->event_date->toDateString(),
    ], $discount);

    $this->actingAs($admin)
        ->patch(route('admin.bookings.update', $booking), $payload(['discounts' => []]))
        ->assertRedirect()
        ->assertSessionDoesntHaveErrors();
    expect($booking->refresh()->discount_type)->toBeNull()
        ->and((float) $booking->discount_value)->toBe(0.0)
        ->and($booking->discount_note)->toBeNull();
    $subtotal = $booking->subtotal_price;

    $this->actingAs($admin)
        ->patch(route('admin.bookings.update', $booking), $payload(['discounts' => [
            ['amount' => 100000, 'note' => 'Promo awal tahun'],
            ['amount' => 250000, 'note' => 'Potongan vendor'],
        ], 'bonuses' => [
            ['amount' => 500000, 'note' => 'Tambahan touch up'],
        ]]))
        ->assertRedirect()
        ->assertSessionDoesntHaveErrors();
    expect($booking->refresh()->discounts)->toBe([
        ['amount' => 100000, 'note' => 'Promo awal tahun'],
        ['amount' => 250000, 'note' => 'Potongan vendor'],
    ])->and($booking->bonuses)->toBe([
        ['amount' => 500000, 'note' => 'Tambahan touch up'],
    ])->and($booking->total_price)->toBe($subtotal - 350000.0);

    $this->actingAs($admin)
        ->patch(route('admin.bookings.update', $booking), $payload([
            'discounts' => [
                ['amount' => 100000.50, 'note' => 'Potongan tidak valid'],
            ],
        ]))
        ->assertSessionHasErrors('discounts.0.amount');

    $booking->payments()->create([
        'type' => 'DP1',
        'amount' => $booking->subtotal_price,
        'method' => 'transfer',
        'status' => Payment::STATUS_VERIFIED,
        'paid_at' => now(),
    ]);
    $this->actingAs($admin)
        ->patch(route('admin.bookings.update', $booking), $payload([
            'discounts' => [
                ['amount' => 100000, 'note' => 'Potongan baru'],
            ],
        ]))
        ->assertSessionHasErrors('discounts');
    expect($booking->refresh()->discounts)->toHaveCount(2);
});

it('admin can update the invoice greeting from site settings', function () {
    $admin = User::where('email', 'admin@anitamua.com')->firstOrFail();
    $greeting = 'Terima kasih, pembayaran Anda sudah kami terima.';

    $this->actingAs($admin)
        ->post(route('admin.content.settings.store'), [
            'invoice_greeting' => $greeting,
            'booking_referral_sources' => implode("\n", SiteSetting::DEFAULT_BOOKING_REFERRAL_SOURCES),
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    expect(SiteSetting::get('invoice_greeting'))->toBe($greeting);
});

it('normalizes booking referral source settings and keeps historical choices visible', function () {
    $admin = User::where('email', 'admin@anitamua.com')->firstOrFail();

    $this->actingAs($admin)->post(route('admin.content.settings.store'), [
        'booking_referral_sources' => " Instagram \nTikTok\ninstagram\n\nWebsite ",
    ])->assertRedirect()->assertSessionHas('success');

    expect(SiteSetting::bookingReferralSources())->toBe(['Instagram', 'TikTok', 'Website']);

    $booking = Booking::firstOrFail();
    $booking->update(['referral_source' => 'Pameran Lama']);
    $this->actingAs($admin)->get('/admin/bookings/'.$booking->id.'/edit')
        ->assertOk()
        ->assertSee('Pameran Lama');
});

it('requires a valid referral source for new public bookings', function () {
    $package = Package::firstOrFail();

    $this->post('/booking', [
        'package_id' => $package->id,
        'name' => 'Rani & Raka',
        'phone' => '081200000222',
        'email' => 'rani-raka@example.com',
        'referral_source' => 'Pilihan Tidak Terdaftar',
        'event_date' => now()->addMonth()->toDateString(),
        'amount' => 1000000,
        'proof' => UploadedFile::fake()->image('proof.jpg'),
    ])->assertSessionHasErrors('referral_source');
});

it('backfills only missing invoices and uses the first verified payment date', function () {
    $client = User::where('email', 'client@anitamua.com')->firstOrFail();
    $package = Package::firstOrFail();
    $paymentDate = now()->subDays(12)->startOfDay();
    $booking = Booking::create([
        'code' => Booking::generateCode(),
        'client_id' => $client->id,
        'package_id' => $package->id,
        'package_price' => $package->price,
        'name' => 'Rina & Fajar',
        'phone' => '081234567891',
        'email' => 'rinafajar@example.com',
        'event_date' => now()->addMonths(2)->toDateString(),
        'status' => Booking::STATUS_BOOKED,
    ]);
    $booking->payments()->create([
        'type' => 'DP Awal',
        'amount' => 1000000,
        'method' => 'transfer',
        'status' => Payment::STATUS_VERIFIED,
        'paid_at' => $paymentDate,
        'verified_at' => $paymentDate,
    ]);

    $this->artisan('invoices:backfill')
        ->expectsOutput('Preview: 1 invoice akan dibuat. Jalankan dengan --force untuk menyimpan.')
        ->assertSuccessful();
    expect(Invoice::where('booking_id', $booking->id)->exists())->toBeFalse();

    $this->artisan('invoices:backfill --force')
        ->expectsOutput('Invoice dibuat: 1')
        ->assertSuccessful();

    $invoice = Invoice::where('booking_id', $booking->id)->firstOrFail();
    expect($invoice->issue_date->toDateString())->toBe($paymentDate->toDateString());

    $this->artisan('invoices:backfill --force')
        ->expectsOutput('Invoice dibuat: 0')
        ->assertSuccessful();
    expect(Invoice::where('booking_id', $booking->id)->count())->toBe(1);
});

it('backfills missing vendor snapshots without changing existing booking vendors', function () {
    $package = Package::firstOrFail();
    $vendor = Vendor::firstOrFail();
    $package->vendors()->sync([$vendor->id => ['price' => 1234567]]);
    $booking = Booking::create([
        'code' => Booking::generateCode(),
        'package_id' => $package->id,
        'package_price' => $package->price,
        'name' => 'Sari & Bima',
        'phone' => '081234567892',
        'email' => 'saribima@example.com',
        'event_date' => now()->addMonths(2)->toDateString(),
        'status' => Booking::STATUS_BOOKED,
    ]);

    $this->artisan('bookings:backfill-vendors')
        ->expectsOutput('Preview: 1 booking akan diberi snapshot vendor. Jalankan dengan --force untuk menyimpan.')
        ->assertSuccessful();
    expect($booking->bookingVendors()->exists())->toBeFalse();

    $this->artisan('bookings:backfill-vendors --force')
        ->expectsOutput('Snapshot vendor dibuat: 1')
        ->assertSuccessful();
    expect((float) $booking->bookingVendors()->value('price'))->toBe(1234567.0);

    $this->artisan('bookings:backfill-vendors --force')
        ->expectsOutput('Snapshot vendor dibuat: 0')
        ->assertSuccessful();
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
        ->get("/admin/bookings/{$booking->id}/packing")
        ->assertOk()
        ->assertSee('Checklist Packing H-1');

    // Modul fase 2 sudah aktif
    foreach (['/admin/inventory', '/admin/inventory-categories', '/admin/benefits', '/admin/benefit-categories',
        '/admin/users/staff', '/admin/users/clients',
        '/admin/content/testimonials', '/admin/content/gallery', '/admin/content/faqs', '/admin/content/settings'] as $url) {
        $this->actingAs($owner)->get($url)->assertOk();
    }

    foreach (['/admin/vendors', '/admin/vendor-categories'] as $url) {
        $this->actingAs($owner)->get($url)->assertOk();
    }

    foreach (['/admin/wedding-stages', '/admin/tents', '/admin/entrance-gates'] as $url) {
        $this->actingAs($owner)->get($url)->assertOk();
    }

    // Timeline dan Reminder masih dinonaktifkan sementara
    $this->actingAs($owner)->get('/admin/finances')->assertOk();
    foreach (['/admin/timeline', '/admin/reminders'] as $url) {
        $this->actingAs($owner)->get($url)->assertNotFound();
    }
});

it('owner can record a finance transaction', function () {
    $owner = User::where('email', 'owner@anitamua.com')->firstOrFail();
    $booking = Booking::firstOrFail();

    $this->actingAs($owner)
        ->post('/admin/finances', [
            'booking_id' => $booking->id,
            'type' => Finance::TYPE_EXPENSE,
            'category' => 'Vendor',
            'amount' => 2480000,
            'description' => 'Pembayaran vendor makeup',
            'transaction_date' => now()->toDateString(),
        ])
        ->assertRedirect();

    $finance = Finance::where('description', 'Pembayaran vendor makeup')->latest('id')->first();
    expect($finance)->not->toBeNull();
    expect($finance->booking_id)->toBe($booking->id);
    expect($finance->type)->toBe(Finance::TYPE_EXPENSE);
    expect((float) $finance->amount)->toBe(2480000.0);
});

it('admin can manage clients without gaining staff management access', function () {
    $admin = User::where('email', 'admin@anitamua.com')->firstOrFail();
    $client = User::where('email', 'client@anitamua.com')->firstOrFail();
    $owner = User::where('email', 'owner@anitamua.com')->firstOrFail();

    $this->actingAs($admin)->get('/admin/users/clients')->assertOk();
    $this->actingAs($admin)->post('/admin/users/clients', [
        'name' => 'Klien Admin',
        'email' => 'klien-admin@example.com',
        'phone' => '081234567899',
        'password' => 'rahasia123',
    ])->assertRedirect();

    $this->actingAs($admin)->patch('/admin/users/'.$client->id, [
        'name' => $client->name,
        'email' => $client->email,
        'phone' => $client->phone,
        'role' => User::ROLE_ADMIN,
    ])->assertRedirect();

    expect($client->refresh()->role)->toBe(User::ROLE_CLIENT);
    $this->actingAs($admin)->get('/admin/users/'.$owner->id.'/edit')->assertForbidden();
    $this->actingAs($admin)->get('/admin/users/staff')->assertForbidden();
});

it('filters clients by contact detail and account status', function () {
    $owner = User::where('email', 'owner@anitamua.com')->firstOrFail();
    User::factory()->create([
        'name' => 'Nadia Aktif',
        'email' => 'nadia-filter@example.com',
        'phone' => '081234560001',
        'role' => User::ROLE_CLIENT,
        'is_active' => true,
    ]);
    User::factory()->create([
        'name' => 'Rani Nonaktif',
        'email' => 'rani-filter@example.com',
        'phone' => '081234560002',
        'role' => User::ROLE_CLIENT,
        'is_active' => false,
    ]);

    $this->actingAs($owner)->get('/admin/users/clients?q=nadia-filter@example.com')
        ->assertOk()
        ->assertSee('Nadia Aktif')
        ->assertDontSee('Rani Nonaktif');

    $this->actingAs($owner)->get('/admin/users/clients?status=inactive')
        ->assertOk()
        ->assertSee('Rani Nonaktif')
        ->assertDontSee('Nadia Aktif');
});

it('stores more than ten photos for a testimonial', function () {
    Storage::fake('public');
    $owner = User::where('email', 'owner@anitamua.com')->firstOrFail();

    $this->actingAs($owner)->post('/admin/content/testimonials', [
        'client_name' => 'Dinda & Rama',
        'rating' => 5,
        'content' => 'Tim ANITA sangat membantu dari awal hingga acara selesai.',
        'status' => 'published',
        'photos' => collect(range(1, 11))
            ->map(fn (int $number) => UploadedFile::fake()->image("dinda-rama-{$number}.jpg"))
            ->all(),
    ])->assertRedirect();

    $testimonial = Testimonial::where('client_name', 'Dinda & Rama')->firstOrFail();
    expect($testimonial->photos)->toHaveCount(11)
        ->and($testimonial->photo)->toBe($testimonial->photos[0])
        ->and($testimonial->photo_urls)->toHaveCount(11);
    Storage::disk('public')->assertExists($testimonial->photos[0]);
    Storage::disk('public')->assertExists($testimonial->photos[10]);

    $removedPhoto = $testimonial->photos[0];
    $this->actingAs($owner)->put('/admin/content/testimonials/'.$testimonial->id, [
        'client_name' => 'Dinda & Rama Diperbarui',
        'rating' => 4,
        'content' => 'Testimoni ini sudah diperbarui.',
        'status' => 'hidden',
        'remove_photos' => [$removedPhoto],
        'photos' => [UploadedFile::fake()->image('dinda-rama-baru.jpg')],
    ])->assertRedirect();

    $testimonial->refresh();
    expect($testimonial->client_name)->toBe('Dinda & Rama Diperbarui')
        ->and($testimonial->rating)->toBe(4)
        ->and($testimonial->status)->toBe('hidden')
        ->and($testimonial->photos)->toHaveCount(11)
        ->and($testimonial->photos)->not->toContain($removedPhoto);
    Storage::disk('public')->assertMissing($removedPhoto);

    $this->actingAs($owner)->get('/admin/content/testimonials')
        ->assertOk()
        ->assertSee('Dinda &amp; Rama Diperbarui', false)
        ->assertSee('data-gallery-lightbox', false);

    $this->actingAs($owner)->delete('/admin/content/testimonials/'.$testimonial->id)->assertRedirect();
    Storage::disk('public')->assertMissing($testimonial->photos[0]);
    Storage::disk('public')->assertMissing($testimonial->photos[10]);
});

it('shows testimonial photos on the landing page', function () {
    Testimonial::create([
        'client_name' => 'Dinda & Rama',
        'rating' => 5,
        'content' => 'Kami sangat puas dengan hasil riasannya.',
        'photo' => 'uploads/testimonials/dinda-rama-1.jpg',
        'photos' => ['uploads/testimonials/dinda-rama-1.jpg', 'uploads/testimonials/dinda-rama-2.jpg'],
        'status' => 'published',
    ]);

    $this->get('/testimoni')
        ->assertOk()
        ->assertSee('Foto Dinda &amp; Rama', false)
        ->assertSee('data-gallery-lightbox', false);
});

it('limits each testimonial photo to three megabytes', function () {
    $owner = User::where('email', 'owner@anitamua.com')->firstOrFail();

    $this->actingAs($owner)->post('/admin/content/testimonials', [
        'client_name' => 'Foto Terlalu Besar',
        'rating' => 5,
        'content' => 'Foto ini melebihi batas ukuran.',
        'status' => 'published',
        'photos' => [UploadedFile::fake()->image('terlalu-besar.jpg')->size(3073)],
    ])->assertSessionHasErrors('photos.0');
});

it('calculates finance from verified payments and booking vendor prices', function () {
    $owner = User::where('email', 'owner@anitamua.com')->firstOrFail();
    $booking = Booking::firstOrFail();
    $year = now()->year;
    $cancelled = Booking::create([
        'code' => Booking::generateCode(),
        'client_id' => $booking->client_id,
        'package_id' => $booking->package_id,
        'package_price' => $booking->package_price,
        'name' => 'Booking Dibatalkan',
        'phone' => '081200000001',
        'email' => 'cancelled-finance@example.com',
        'event_date' => now()->addMonth()->toDateString(),
        'status' => Booking::STATUS_CANCELLED,
    ]);
    $cancelled->payments()->create([
        'type' => 'DP1',
        'amount' => 999999,
        'method' => 'transfer',
        'status' => Payment::STATUS_VERIFIED,
        'paid_at' => now(),
    ]);
    $expectedIncome = (float) Payment::where('status', Payment::STATUS_VERIFIED)
        ->whereHas('booking', fn ($query) => $query->where('status', '!=', Booking::STATUS_CANCELLED))
        ->sum('amount');
    $expectedVendorExpense = (float) $booking->bookingVendors()
        ->where('status', '!=', 'cancelled')
        ->whereNotNull('price')
        ->sum('price');
    $response = $this->actingAs($owner)->get('/admin/finances?year='.$year);

    $response->assertOk();
    expect((float) $response->viewData('incomes'))->toBe($expectedIncome)
        ->and((float) $response->viewData('expenses'))->toBe($expectedVendorExpense)
        ->and($response->viewData('monthly')->every(fn (array $month) => $month['profit'] === $month['income'] - $month['expense']))->toBeTrue();
    $response->assertSee("label: 'Profit'", false);
});

it('lists completed or fully paid bookings with contract income and vendor expenses', function () {
    $owner = User::where('email', 'owner@anitamua.com')->firstOrFail();
    $client = User::where('email', 'client@anitamua.com')->firstOrFail();
    $package = Package::firstOrFail();
    $year = now()->year + 1;
    $category = VendorCategory::create(['name' => 'Vendor Profit Booking', 'slug' => 'vendor-profit-booking']);
    $vendor = Vendor::create([
        'vendor_category_id' => $category->id,
        'name' => 'Vendor Profit Booking',
        'price' => 250000,
        'status' => 'active',
    ]);

    $completed = Booking::create([
        'code' => Booking::generateCode(),
        'client_id' => $client->id,
        'package_id' => $package->id,
        'package_price' => $package->price,
        'name' => 'Booking Selesai Profit',
        'phone' => $client->phone,
        'email' => $client->email,
        'event_date' => now()->setYear($year)->toDateString(),
        'status' => Booking::STATUS_COMPLETED,
    ]);
    $completed->addons()->create(['name' => 'Tambahan Dekor', 'price' => 100000]);
    $completed->update(['discount_type' => 'fixed', 'discount_value' => 50000]);
    $completed->bookingVendors()->create([
        'vendor_id' => $vendor->id,
        'role' => $category->name,
        'price' => $vendor->price,
        'custom_additions' => [['name' => 'Transport', 'price' => 75000]],
        'status' => 'confirmed',
    ]);

    $paid = Booking::create([
        'code' => Booking::generateCode(),
        'client_id' => $client->id,
        'package_id' => $package->id,
        'package_price' => $package->price,
        'name' => 'Booking Lunas Profit',
        'phone' => $client->phone,
        'email' => $client->email,
        'event_date' => now()->setYear($year)->addDay()->toDateString(),
        'status' => Booking::STATUS_BOOKED,
    ]);
    $paid->payments()->create([
        'type' => 'pelunasan',
        'amount' => $paid->total_price,
        'method' => 'transfer',
        'status' => Payment::STATUS_VERIFIED,
        'verified_at' => now(),
    ]);

    $cancelled = Booking::create([
        'code' => Booking::generateCode(),
        'client_id' => $client->id,
        'package_id' => $package->id,
        'package_price' => $package->price,
        'name' => 'Booking Cancelled Profit',
        'phone' => $client->phone,
        'email' => $client->email,
        'event_date' => now()->setYear($year)->addDays(2)->toDateString(),
        'status' => Booking::STATUS_CANCELLED,
    ]);
    $cancelled->payments()->create([
        'type' => 'pelunasan',
        'amount' => $cancelled->total_price,
        'method' => 'transfer',
        'status' => Payment::STATUS_VERIFIED,
        'verified_at' => now(),
    ]);

    $response = $this->actingAs($owner)->get('/admin/finances?year='.$year)->assertOk();
    $bookings = $response->viewData('bookingProfits')->keyBy(fn ($row) => $row['booking']->id);
    $orderedIds = $response->viewData('bookingProfits')->pluck('booking.id')->all();

    expect($bookings->has($completed->id))->toBeTrue()
        ->and($bookings->has($paid->id))->toBeTrue()
        ->and($bookings->has($cancelled->id))->toBeFalse()
        ->and(array_search($paid->id, $orderedIds))->toBeLessThan(array_search($completed->id, $orderedIds))
        ->and($bookings[$completed->id]['income'])->toBe($completed->total_price)
        ->and($bookings[$completed->id]['expense'])->toBe(325000.0)
        ->and($bookings[$completed->id]['profit'])->toBe($completed->total_price - 325000.0);
});

it('paginates the finance booking list by ten while keeping newest event dates first', function () {
    $owner = User::where('email', 'owner@anitamua.com')->firstOrFail();
    $client = User::where('email', 'client@anitamua.com')->firstOrFail();
    $package = Package::firstOrFail();
    $year = now()->year + 10;
    $bookings = collect(range(1, 11))->map(fn (int $day) => Booking::create([
        'code' => Booking::generateCode(),
        'client_id' => $client->id,
        'package_id' => $package->id,
        'package_price' => $package->price,
        'name' => 'Finance Pagination '.$day,
        'phone' => $client->phone,
        'email' => $client->email,
        'event_date' => sprintf('%d-01-%02d', $year, $day),
        'status' => Booking::STATUS_COMPLETED,
    ]));

    $pageOne = $this->actingAs($owner)
        ->get('/admin/finances?year='.$year)
        ->assertOk()
        ->assertSee('page=2')
        ->assertSee('Menampilkan', false)
        ->assertSee('aria-label="Pagination Navigation"', false)
        ->assertSee('aria-label="Halaman 2"', false)
        ->viewData('bookingProfits');

    expect($pageOne->total())->toBe(11)
        ->and($pageOne->perPage())->toBe(10)
        ->and($pageOne->count())->toBe(10)
        ->and($pageOne->first()['booking']->id)->toBe($bookings->last()->id);

    $pageTwo = $this->actingAs($owner)
        ->get('/admin/finances?year='.$year.'&page=2')
        ->assertOk()
        ->viewData('bookingProfits');

    expect($pageTwo->currentPage())->toBe(2)
        ->and($pageTwo->count())->toBe(1)
        ->and($pageTwo->first()['booking']->id)->toBe($bookings->first()->id);
});

it('builds booking source and monthly charts from booking creation dates and all statuses', function () {
    $owner = User::where('email', 'owner@anitamua.com')->firstOrFail();
    $package = Package::firstOrFail();
    $year = now()->year;

    foreach ([
        ['Instagram', Booking::STATUS_BOOKED, 2],
        ['Instagram', Booking::STATUS_CANCELLED, 2],
        [null, Booking::STATUS_PENDING, 3],
    ] as $index => [$source, $status, $month]) {
        $chartBooking = Booking::create([
            'code' => Booking::generateCode(), 'package_id' => $package->id,
            'package_price' => $package->price, 'name' => 'Grafik '.$index,
            'phone' => '0812000000'.$index, 'email' => 'grafik'.$index.'@example.com',
            'referral_source' => $source, 'event_date' => now()->addMonths(6), 'status' => $status,
        ]);
        $chartBooking->forceFill(['created_at' => now()->setMonth($month)->startOfMonth()])->saveQuietly();
    }

    $response = $this->actingAs($owner)->get('/admin/finances?year='.$year)->assertOk();
    $sources = $response->viewData('bookingSources')->keyBy('label');
    $months = $response->viewData('monthlyBookings');

    expect($sources['Instagram']['count'])->toBeGreaterThanOrEqual(2)
        ->and($sources['Tidak diketahui']['count'])->toBeGreaterThanOrEqual(1)
        ->and($months[1]['count'])->toBeGreaterThanOrEqual(2)
        ->and($months[2]['count'])->toBeGreaterThanOrEqual(1);
});

it('renders booking detail page for admin', function () {
    $admin = User::where('email', 'admin@anitamua.com')->first();
    $booking = Booking::first();

    $this->actingAs($admin)
        ->get("/admin/bookings/{$booking->id}")
        ->assertOk()
        ->assertSee('Tambah Pembayaran')
        ->assertSee('Daftar Vendor')
        ->assertDontSee('Ganti Vendor')
        ->assertDontSee('Data survey bersifat read-only')
        ->assertDontSee('Jadwal hanya dapat diubah melalui Edit Booking.');

    $this->actingAs($admin)
        ->get("/admin/bookings/{$booking->id}/edit")
        ->assertOk()
        ->assertSee('Daftar Vendor');
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

it('requires at least three photos for each gallery item', function () {
    Storage::fake('public');
    $owner = User::where('email', 'owner@anitamua.com')->first();

    $this->actingAs($owner)
        ->post('/admin/content/gallery', [
            'title' => 'Wedding Baru',
            'photos' => [UploadedFile::fake()->image('one.jpg'), UploadedFile::fake()->image('two.jpg')],
        ])
        ->assertSessionHasErrors('photos');

    $this->actingAs($owner)
        ->post('/admin/content/gallery', [
            'title' => 'Wedding Baru',
            'photos' => [
                UploadedFile::fake()->image('one.jpg'),
                UploadedFile::fake()->image('two.jpg'),
                UploadedFile::fake()->image('three.jpg'),
            ],
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    $gallery = Gallery::latest('id')->first();
    expect($gallery->photos)->toHaveCount(3);

    $removedPhoto = $gallery->photos[0];
    $this->actingAs($owner)
        ->put('/admin/content/gallery/'.$gallery->id, [
            'title' => 'Wedding Baru Diperbarui',
            'category' => 'wedding',
            'remove_photos' => [$removedPhoto],
            'photos' => [UploadedFile::fake()->image('four.jpg')],
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    $gallery->refresh();
    expect($gallery->title)->toBe('Wedding Baru Diperbarui')
        ->and($gallery->category)->toBe('wedding')
        ->and($gallery->photos)->toHaveCount(3)
        ->and($gallery->photos)->not->toContain($removedPhoto);
    Storage::disk('public')->assertMissing($removedPhoto);

    $this->actingAs($owner)
        ->put('/admin/content/gallery/'.$gallery->id, [
            'title' => $gallery->title,
            'category' => $gallery->category,
            'remove_photos' => [$gallery->photos[0]],
        ])
        ->assertSessionHasErrors('photos');
});

it('generates reminders via command', function () {
    $this->artisan('reminders:generate')->assertSuccessful();

    expect(Reminder::where('type', 'h30')->exists())->toBeTrue();
    expect(Reminder::where('type', 'h7')->exists())->toBeTrue();
});

it('keeps the separate packing checklist synced with every filled fitting item', function () {
    $owner = User::where('email', 'owner@anitamua.com')->firstOrFail();
    $booking = Booking::firstOrFail();
    $fitting = $booking->fittings()->firstOrFail();
    $inventory = InventoryItem::firstOrFail();
    $inventoryStatus = $inventory->status;
    $allFittingKeys = collect(Fitting::CHECKLIST)
        ->flatMap(fn (array $items) => array_keys($items))
        ->values();

    $fitting->forceFill([
        'cpw_busana_akad_notes' => 'Gaun akad ivory.',
        'cpw_stylist_akad_notes' => 'Hijab organza.',
        'cpw_bb_tb_ld_notes' => '50 kg / 160 cm / 90 cm',
        'among_ibu_hajat_notes' => 'Kebaya ibu warna dusty pink.',
        'item_sizes' => ['cpw_busana_akad' => 'M'],
    ])->save();

    $packingPage = $this->actingAs($owner)
        ->get(route('admin.bookings.packing', $booking))
        ->assertOk()
        ->assertSee('Checklist Packing H-1')
        ->assertSee('id="packing-item-cpw_busana_akad"', false)
        ->assertSee('id="packing-item-cpw_stylist_akad"', false)
        ->assertSee('id="packing-item-cpw_bb_tb_ld"', false)
        ->assertSee('id="packing-item-cpp_busana_akad"', false)
        ->assertSee('id="packing-item-among_ibu_hajat"', false)
        ->assertDontSee('id="packing-note-cpw_stylist_akad"', false)
        ->assertSee('name="items[cpw_busana_akad][condition]"', false)
        ->assertSee(route('admin.packing.update', $booking), false)
        ->assertSee('Simpan Checklist')
        ->assertDontSee('requestSubmit()', false)
        ->assertSee('accent-emerald-600', false)
        ->assertSee('Gaun akad ivory.')
        ->assertSee('Hijab organza.');

    expect(substr_count($packingPage->getContent(), 'id="packing-item-cpw_busana_akad"'))->toBe(1);

    $this->actingAs($owner)
        ->get(route('admin.fieldwork.booking', $booking))
        ->assertOk()
        ->assertDontSee('Checklist Packing H-1')
        ->assertDontSee('id="packing-item-cpw_busana_akad"', false)
        ->assertDontSee('id="packing-note-cpw_stylist_akad"', false)
        ->assertSee('Gaun akad ivory.')
        ->assertSee('Hijab organza.');

    $sourceKeys = collect($fitting->packingSourceItems())->pluck('key');
    expect($sourceKeys->sort()->values()->all())->toBe($allFittingKeys->sort()->values()->all());

    expect($fitting->fresh()->packing_checklist)->toBeNull();

    $fitting->forceFill(['packing_checklist' => [
        ['key' => 'cpw_busana_akad', 'packed' => true, 'note' => 'Catatan lama'],
    ]])->save();

    expect($fitting->fresh()->packingChecklistState())
        ->toBe(['checked' => ['cpw_busana_akad'], 'conditions' => [], 'notes' => ['cpw_busana_akad' => 'Catatan lama']]);

    $fitting->forceFill(['packing_checklist' => null])->save();

    $this->actingAs($owner)
        ->post(route('admin.packing.update', $booking), [
            'items' => [
                'cpw_busana_akad' => [
                    'packed' => true,
                    'condition' => 'laundry',
                ],
            ],
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    expect($fitting->fresh()->packingChecklistState()['checked'])->toContain('cpw_busana_akad');
    expect($fitting->fresh()->packingChecklistState()['conditions']['cpw_busana_akad'])->toBe('laundry');

    $fitting->forceFill([
        'cpw_busana_akad_notes' => 'Gaun akad diperbarui.',
        'among_ibu_hajat_notes' => 'Kebaya ibu warna dusty pink.',
    ])->save();

    $this->actingAs($owner)
        ->get(route('admin.bookings.packing', $booking))
        ->assertOk()
        ->assertSee('Gaun akad diperbarui.')
        ->assertSee('Kebaya ibu warna dusty pink.')
        ->assertSee('Sedang dicuci')
        ->assertDontSee('Gaun akad ivory.');

    expect($fitting->fresh()->packingChecklistState()['checked'])
        ->toContain('cpw_busana_akad')
        ->and($inventory->fresh()->status)->toBe($inventoryStatus);

    $this->actingAs($owner)
        ->post(route('admin.packing.update', $booking), [
            'items' => [
                'cpw_busana_akad' => [
                    'condition' => 'sewing',
                ],
            ],
        ])
        ->assertRedirect();

    expect($fitting->fresh()->packingChecklistState()['checked'])->toBe([])
        ->and($fitting->fresh()->packingChecklistState()['conditions']['cpw_busana_akad'])->toBe('sewing');
});

it('allows team members to access all fieldwork regardless of schedule PIC', function () {
    $team = User::where('email', 'team@anitamua.com')->firstOrFail();
    $booking = Booking::firstOrFail();
    $unassignedBooking = $booking->replicate();
    $unassignedBooking->code = Booking::generateCode();
    $unassignedBooking->name = 'Tugas Tim Lain';
    $unassignedBooking->save();
    $unassignedSchedule = $unassignedBooking->schedules()->create([
        'type' => Schedule::TYPE_FITTING,
        'title' => 'Fitting — Tugas Tim Lain',
        'date' => now()->addDay()->toDateString(),
        'status' => Schedule::STATUS_SCHEDULED,
    ]);

    $this->actingAs($team)
        ->get('/dashboard')
        ->assertOk()
        ->assertSee('Tugas Tim Lain');

    $this->actingAs($team)
        ->get('/admin/calendar?month='.now()->month.'&year='.now()->year)
        ->assertOk()
        ->assertSee('Buka Tugas')
        ->assertSee('Tugas Tim Lain');

    $owner = User::where('email', 'owner@anitamua.com')->firstOrFail();
    $this->actingAs($owner)
        ->get('/admin/calendar?month='.now()->month.'&year='.now()->year)
        ->assertOk()
        ->assertSee('Detail Booking');

    $this->actingAs($team)
        ->get(route('admin.fieldwork.index'))
        ->assertOk()
        ->assertSee('Checklist')
        ->assertSee('Tugas Tim Lain');

    $this->actingAs($team)
        ->get(route('admin.fieldwork.booking', $unassignedBooking))
        ->assertOk();
    $this->actingAs($team)
        ->get(route('admin.bookings.packing', $unassignedBooking))
        ->assertOk();
    $this->actingAs($team)
        ->post(route('admin.schedules.status', $unassignedSchedule), ['status' => 'finished'])
        ->assertRedirect();
    expect($unassignedSchedule->fresh()->status)->toBe(Schedule::STATUS_FINISHED);
    $this->actingAs($team)
        ->get('/admin/bookings')
        ->assertForbidden();
});

it('filters fieldwork by its schedule date and lets team update inventory status', function () {
    $team = User::where('email', 'team@anitamua.com')->firstOrFail();
    $source = Booking::firstOrFail();
    $date = now()->addMonths(5)->toDateString();

    $matching = $source->replicate();
    $matching->code = Booking::generateCode();
    $matching->name = 'Tugas Tanggal Cocok';
    $matching->event_date = $date;
    $matching->save();
    $matching->schedules()->create(['type' => Schedule::TYPE_SURVEY, 'title' => 'Survey cocok', 'date' => $date, 'status' => Schedule::STATUS_SCHEDULED]);

    $other = $source->replicate();
    $other->code = Booking::generateCode();
    $other->name = 'Tugas Tanggal Lain';
    $other->event_date = now()->addMonths(6)->toDateString();
    $other->save();
    $other->schedules()->create(['type' => Schedule::TYPE_FITTING, 'title' => 'Fitting lain', 'date' => now()->addMonths(6)->toDateString(), 'status' => Schedule::STATUS_SCHEDULED]);

    $this->actingAs($team)->get(route('admin.fieldwork.index', ['date' => $date]))
        ->assertOk()->assertSee('Tugas Tanggal Cocok')->assertDontSee('Tugas Tanggal Lain');

    $item = InventoryItem::firstOrFail();
    $this->actingAs($team)->patch(route('admin.inventory.status.update', $item), ['status' => 'laundering'])->assertRedirect();
    expect($item->fresh()->status)->toBe('laundering')
        ->and(ActivityLog::where('action', 'inventory_status_updated')->exists())->toBeTrue();

    $this->actingAs($team)->put(route('admin.inventory.update', $item), [])->assertSessionHasErrors(['name', 'inventory_category_id', 'condition', 'status']);
});

it('uses dynamic package types across package management and public packages', function () {
    $owner = User::where('email', 'owner@anitamua.com')->firstOrFail();

    $this->actingAs($owner)->post(route('admin.packages.types.store'), ['name' => 'Engagement'])->assertRedirect();
    $type = PackageType::where('name', 'Engagement')->firstOrFail();
    $package = Package::create([
        'name' => 'Paket Engagement', 'type' => 'makeup', 'package_type_id' => $type->id,
        'price' => 2000000, 'status' => 'active', 'color' => '#d4739a',
    ]);

    $this->actingAs($owner)->get(route('admin.packages.create'))->assertOk()->assertSee('Engagement');
    $this->get('/paket')->assertOk()->assertSee('Engagement')->assertSee($package->name);
    $this->actingAs($owner)->delete(route('admin.packages.types.destroy', $type))->assertRedirect();
    expect(PackageType::find($type->id))->not->toBeNull();
});

it('stores CPW reception three and head accessories as fitting and packing items', function () {
    Storage::fake('public');
    $team = User::where('email', 'team@anitamua.com')->firstOrFail();
    $booking = Booking::firstOrFail();

    expect(array_keys(Fitting::CHECKLIST['cpw']))->toContain('cpw_busana_resepsi_3', 'cpw_aksesori_kepala_resepsi_3')
        ->and(array_keys(Fitting::CHECKLIST['cpp']))->not->toContain('cpw_busana_resepsi_3');

    $this->actingAs($team)->post(route('admin.fieldwork.fitting'), [
        'booking_id' => $booking->id,
        'date' => now()->addWeek()->toDateString(),
        'status' => 'scheduled',
        'items' => [
            'cpw_busana_resepsi_3' => ['notes' => 'Busana resepsi ketiga', 'size' => 'L', 'photo' => UploadedFile::fake()->image('resepsi-3.jpg')],
            'cpw_aksesori_kepala_resepsi_3' => ['notes' => 'Mahkota perak', 'photo' => UploadedFile::fake()->image('mahkota.jpg')],
        ],
    ])->assertRedirect();

    $fitting = $booking->fittings()->firstOrFail();
    expect($fitting->cpw_busana_resepsi_3_notes)->toBe('Busana resepsi ketiga')
        ->and($fitting->cpw_aksesori_kepala_resepsi_3_notes)->toBe('Mahkota perak')
        ->and($fitting->item_sizes['cpw_busana_resepsi_3'])->toBe('L')
        ->and($fitting->item_sizes['cpw_aksesori_kepala_resepsi_3'] ?? null)->toBeNull()
        ->and(collect($fitting->packingSourceItems())->pluck('key'))->toContain('cpw_busana_resepsi_3', 'cpw_aksesori_kepala_resepsi_3');
});

it('shows past completed and cancelled bookings when booking filters are used', function () {
    $owner = User::where('email', 'owner@anitamua.com')->firstOrFail();
    $source = Booking::firstOrFail();

    foreach ([Booking::STATUS_COMPLETED => 'Arsip Selesai', Booking::STATUS_CANCELLED => 'Arsip Batal'] as $status => $name) {
        $booking = $source->replicate();
        $booking->code = Booking::generateCode();
        $booking->name = $name;
        $booking->event_date = now()->subMonth()->toDateString();
        $booking->status = $status;
        $booking->save();
    }

    $this->actingAs($owner)->get(route('admin.bookings.index', ['status' => Booking::STATUS_COMPLETED]))
        ->assertOk()->assertSee('Arsip Selesai')->assertDontSee('Arsip Batal');
    $this->actingAs($owner)->get(route('admin.bookings.index', ['start_date' => now()->subMonths(2)->toDateString(), 'end_date' => now()->subDay()->toDateString()]))
        ->assertOk()->assertSee('Arsip Selesai')->assertSee('Arsip Batal');
});
