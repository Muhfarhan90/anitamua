<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Fitting;
use App\Models\Package;
use App\Models\Payment;
use App\Models\Schedule;
use App\Models\Survey;
use App\Models\User;
use App\Models\Vendor;
use App\Models\WeddingStage;
use App\Services\ActivityLogger;
use App\Services\ClientAccountService;
use App\Services\ImageCompressor;
use App\Services\InvoiceService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BookingManagementController extends Controller
{
    public function index(Request $request)
    {
        $query = Booking::with(['client', 'package', 'payments'])
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->when($request->event_date, fn ($q, $date) => $q->whereDate('event_date', $date))
            ->when($request->q, fn ($q, $s) => $q->where(function ($qq) use ($s) {
                $qq->where('name', 'like', "%{$s}%")
                    ->orWhere('code', 'like', "%{$s}%")
                    ->orWhere('phone', 'like', "%{$s}%");
            }))
            ->orderByDesc('created_at');

        $bookings = $query->paginate(15)->withQueryString();

        return view('admin.bookings.index', compact('bookings'));
    }

    public function show(Booking $booking, InvoiceService $invoiceService)
    {
        $booking->load([
            'client', 'package', 'payments', 'invoice', 'bookingVendors.vendor.category',
            'addons',
            'schedules.picUser', 'survey.weddingStage', 'fittings', 'packingLists.items.inventoryItem',
            'activityLogs.user', 'packageChangeRequests.oldPackage', 'packageChangeRequests.newPackage',
        ]);
        if (in_array($booking->status, [Booking::STATUS_BOOKED, Booking::STATUS_COMPLETED], true)) {
            $invoiceService->sync($booking);
            $booking->load('invoice');
        }
        $packages = Package::where('status', 'active')->get();
        $staff = User::whereIn('role', [User::ROLE_OWNER, User::ROLE_ADMIN, User::ROLE_TEAM])->get();
        $teamMembers = User::where('role', User::ROLE_TEAM)->where('is_active', true)->orderBy('name')->get();
        $currentDecorationId = $booking->survey?->wedding_stage_id;
        $weddingStages = WeddingStage::query()
            ->where(function ($query) use ($currentDecorationId) {
                $query->where('is_active', true);
                if ($currentDecorationId) {
                    $query->orWhere('id', $currentDecorationId);
                }
            })
            ->orderBy('name')
            ->get();

        return view('admin.bookings.show', compact('booking', 'packages', 'staff', 'teamMembers', 'weddingStages'));
    }

    public function create()
    {
        $packages = Package::where('status', 'active')->get();
        $clients = User::where('role', User::ROLE_CLIENT)->get();
        $weddingStages = WeddingStage::where('is_active', true)->orderBy('name')->get();
        $teamMembers = User::where('role', User::ROLE_TEAM)->where('is_active', true)->orderBy('name')->get();

        $subTypeLabels = [
            'makeup' => 'Makeup Only',
            'akad' => 'Akad',
            'makeup_attire' => 'Makeup & Attire',
            'rumahan' => 'Rumahan',
            'gedung' => 'Gedung',
        ];

        return view('admin.bookings.create', compact('packages', 'clients', 'subTypeLabels', 'weddingStages', 'teamMembers'));
    }

    public function edit(Booking $booking)
    {
        $booking->load(['client', 'package', 'addons', 'survey.weddingStage', 'fittings']);
        $packages = Package::where('status', 'active')->get();
        $clients = User::where('role', User::ROLE_CLIENT)->get();
        $teamMembers = User::where('role', User::ROLE_TEAM)->where('is_active', true)->orderBy('name')->get();
        $currentDecorationId = $booking->survey?->wedding_stage_id;
        $weddingStages = WeddingStage::query()
            ->where(function ($query) use ($currentDecorationId) {
                $query->where('is_active', true);
                if ($currentDecorationId) {
                    $query->orWhere('id', $currentDecorationId);
                }
            })
            ->orderBy('name')
            ->get();

        $subTypeLabels = [
            'makeup' => 'Makeup Only',
            'akad' => 'Akad',
            'makeup_attire' => 'Makeup & Attire',
            'rumahan' => 'Rumahan',
            'gedung' => 'Gedung',
        ];

        return view('admin.bookings.edit', compact('booking', 'packages', 'clients', 'subTypeLabels', 'weddingStages', 'teamMembers'));
    }

    public function store(Request $request, InvoiceService $invoiceService)
    {
        $data = $request->validate([
            'client_mode' => ['required', 'in:existing,new'],
            'client_id' => ['required_if:client_mode,existing', 'nullable', 'exists:users,id'],
            'new_client_name' => ['required_if:client_mode,new', 'nullable', 'string', 'max:255'],
            'new_client_email' => ['required_if:client_mode,new', 'nullable', 'email', 'unique:users,email'],
            'new_client_phone' => ['required_if:client_mode,new', 'nullable', 'string', 'max:30'],
            'new_client_instagram' => ['nullable', 'string', 'max:100', 'regex:/^@?[A-Za-z0-9._]+$/'],
            'package_id' => ['required', 'exists:packages,id'],
            'event_date' => ['required', 'date'],
            'event_time' => ['nullable', 'date_format:H:i'],
            'survey_date' => ['nullable', 'date'],
            'fitting_date' => ['nullable', 'date'],
            'location' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
            'proof' => ['nullable', 'image', 'max:3072'], // bukti opsional — tanpa bukti pun tetap verified
            'dp1_amount' => ['required', 'numeric', 'min:0'],
            'addons' => ['nullable', 'array'],
            'addons.*.name' => ['required', 'string', 'max:255'],
            'addons.*.price' => ['required', 'numeric', 'min:0'],
        ] + $this->bookingSurveyRules() + $this->bookingFittingRules());

        $addons = $data['addons'] ?? [];
        unset($data['addons']);

        $clientWasCreated = $data['client_mode'] === 'new';
        $client = $clientWasCreated
            ? User::create([
                'name' => $data['new_client_name'],
                'email' => $data['new_client_email'],
                'phone' => $data['new_client_phone'],
                'instagram' => $data['new_client_instagram'] ?? null,
                'password' => User::generateDefaultPassword($data['new_client_name']),
                'role' => User::ROLE_CLIENT,
            ])
            : User::findOrFail($data['client_id']);

        // Kontak & nama acara diambil dari data klien.
        $data['name'] = $client->name;
        $data['phone'] = $client->phone;
        $data['email'] = $client->email;
        $data['instagram'] = $clientWasCreated ? ($data['new_client_instagram'] ?? null) : $client->instagram;
        unset($data['client_mode'], $data['new_client_name'], $data['new_client_email'], $data['new_client_phone'], $data['new_client_instagram']);

        $data['code'] = Booking::generateCode();
        $data['created_by'] = auth()->id();
        $data['status'] = Booking::STATUS_BOOKED; // dibuat admin → langsung sah
        $data['package_price'] = Package::findOrFail($data['package_id'])->price;

        $booking = Booking::create($data);
        $booking->addons()->createMany($addons);
        $booking->syncVendorsFromPackage();
        DB::transaction(fn () => $this->saveBookingFieldwork($request, $booking));

        ActivityLogger::log('booking_created', 'Booking dibuat oleh Admin', 'Booking '.$booking->code.' untuk '.$booking->name, $booking->id);

        // Tahap DP langsung diverifikasi (dibuat oleh admin — bukti opsional, tanpa bukti pun verified)
        $paymentData = [
            'type' => 'DP1',
            'amount' => $data['dp1_amount'],
            'due_date' => Carbon::parse($booking->event_date)->subDays(30)->toDateString(),
            'method' => 'transfer',
            'status' => Payment::STATUS_VERIFIED,
            'paid_at' => now(),
            'verified_by' => auth()->id(),
            'verified_at' => now(),
        ];

        if ($request->hasFile('proof')) {
            $paymentData['proof'] = ImageCompressor::compressAndStore($request->file('proof'));
        }

        $booking->payments()->create($paymentData);
        $invoiceService->sync($booking->fresh());

        ActivityLogger::log('dp_verified', 'DP dikonfirmasi', 'DP1 dikonfirmasi oleh '.auth()->user()->name.' (booking dibuat via form admin)', $booking->id);

        // Jadwal Hari H otomatis masuk kalender
        $booking->ensureHariHSchedule();

        $message = 'Booking berhasil dibuat. DP1 terverifikasi, status BOOKED, dan jadwal Hari H masuk kalender.';

        if ($clientWasCreated) {
            ActivityLogger::log('user_created', 'Klien ditambahkan', 'Akun klien '.$client->name.' dibuat saat booking oleh '.auth()->user()->name);
            $message .= ' Akun '.$client->email.' dibuat dengan password default: '.User::generateDefaultPassword($client->name).'.';
        }

        return redirect()->route('admin.bookings.show', $booking)->with('success', $message);
    }

    public function update(Booking $booking, Request $request, InvoiceService $invoiceService)
    {
        $data = $request->validate([
            'client_id' => ['nullable', 'exists:users,id'],
            'package_id' => ['required', 'exists:packages,id'],
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:30'],
            'email' => ['required', 'email'],
            'event_date' => ['required', 'date'],
            'event_time' => ['nullable', 'date_format:H:i'],
            'survey_date' => ['nullable', 'date'],
            'fitting_date' => ['nullable', 'date'],
            'location' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
            'status' => ['required', 'in:pending,booked,completed,cancelled'],
            'addons' => ['nullable', 'array'],
            'addons.*.name' => ['required', 'string', 'max:255'],
            'addons.*.price' => ['required', 'numeric', 'min:0'],
        ] + $this->bookingSurveyRules() + $this->bookingFittingRules());

        $addons = $data['addons'] ?? [];
        unset($data['addons']);

        $packageChanged = (int) $data['package_id'] !== (int) $booking->package_id;
        if ($packageChanged) {
            $data['package_price'] = Package::findOrFail($data['package_id'])->price;
        }

        $old = $booking->only(array_keys($data));
        DB::transaction(function () use ($booking, $data, $addons, $request, $packageChanged) {
            $booking->update($data);
            $booking->addons()->delete();
            $booking->addons()->createMany($addons);
            if ($packageChanged) {
                $booking->unsetRelation('package');
                $booking->syncVendorsFromPackage();
            }
            $this->saveBookingFieldwork($request, $booking);
        });

        if ($booking->wasChanged(['event_date', 'event_time', 'location', 'name'])) {
            $booking->schedules()
                ->where('type', Schedule::TYPE_HARI_H)
                ->update([
                    'title' => Schedule::typeLabel(Schedule::TYPE_HARI_H).' - '.$booking->name,
                    'date' => $booking->event_date,
                    'time' => $booking->event_time,
                    'location' => $booking->location,
                    'notes' => 'Hari H - acara '.$booking->name,
                ]);
        }

        $invoiceService->sync($booking->fresh());

        ActivityLogger::log(
            'booking_updated',
            'Booking diperbarui',
            'Booking '.$booking->code.' diperbarui oleh '.auth()->user()->name,
            $booking->id,
            $old,
            $booking->only(array_keys($data)),
        );

        return redirect()->route('admin.bookings.show', $booking)->with('success', 'Data booking berhasil diperbarui.');
    }

    public function verifyDp(Booking $booking, Request $request, InvoiceService $invoiceService)
    {
        if ($booking->status !== Booking::STATUS_PENDING) {
            return back()->with('warning', 'DP hanya dapat diverifikasi saat booking berstatus Pending. Gunakan edit nominal untuk pembayaran yang sudah Verified.');
        }

        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:0'],
            'method' => ['required', 'string'],
        ]);

        $payment = $booking->payments()->oldest('id')->first();
        if ($payment && $payment->status !== Payment::STATUS_PENDING) {
            return back()->with('warning', 'Pembayaran awal tidak lagi berstatus Pending.');
        }
        $payment ??= new Payment(['booking_id' => $booking->id, 'type' => 'DP1']);

        $payment->fill([
            'amount' => $data['amount'],
            'method' => $data['method'],
            'status' => Payment::STATUS_VERIFIED,
            'paid_at' => now(),
            'verified_by' => auth()->id(),
            'verified_at' => now(),
        ])->save();

        $booking->update(['status' => Booking::STATUS_BOOKED]);

        $booking->ensureHariHSchedule();

        $invoiceService->sync($booking->fresh());
        $account = app(ClientAccountService::class)->ensure($booking);

        ActivityLogger::log(
            'dp_verified',
            'DP1 dikonfirmasi',
            'DP1 sebesar '.number_format($data['amount']).' dikonfirmasi oleh '.auth()->user()->name,
            $booking->id,
            null,
            ['amount' => $data['amount']],
        );

        if ($account) {
            ActivityLogger::log(
                'client_account_created',
                'Akun portal dibuat otomatis',
                'Akun portal client '.$account->email.' dibuat otomatis setelah DP terverifikasi.',
                $booking->id,
            );

            return back()->with('success', 'DP1 terverifikasi. Booking berstatus BOOKED. Akun dashboard untuk '.$account->email.' dibuat otomatis (password default: '.User::generateDefaultPassword($booking->name).').');
        }

        return back()->with('success', 'DP1 terverifikasi. Booking berstatus BOOKED.');
    }

    public function addPayment(Booking $booking, Request $request)
    {
        $data = $request->validate([
            'type' => ['required', 'string', 'max:100'],
            'amount' => ['required', 'numeric', 'min:0'],
            'proof' => ['required', 'image', 'max:5120'],
            'method' => ['required', 'string', 'in:transfer,qris,cash'],
        ]);

        $payment = $booking->payments()->create([
            'type' => $data['type'],
            'amount' => $data['amount'],
            'method' => $data['method'],
            'status' => Payment::STATUS_PENDING,
        ]);

        $payment->update([
            'proof' => ImageCompressor::compressAndStore($request->file('proof'), 'uploads/proofs'),
            'method' => $data['method'],
            'paid_at' => now(),
            'status' => Payment::STATUS_PENDING,
        ]);

        ActivityLogger::log(
            'payment_added',
            'Pembayaran ditambahkan',
            'Tahap '.Payment::typeLabel($payment->type).' sebesar Rp '.number_format($payment->amount, 0, ',', '.').' ditambahkan oleh '.auth()->user()->name.' dan menunggu verifikasi.',
            $booking->id,
        );

        return back()->with('success', 'Pembayaran berhasil ditambahkan dan menunggu verifikasi.');
    }

    public function cancel(Booking $booking, Request $request)
    {
        $request->validate([
            'reason' => ['required', 'string', 'max:500'],
        ]);

        $booking->update([
            'status' => Booking::STATUS_CANCELLED,
            'cancelled_reason' => $request->reason,
            'cancelled_at' => now(),
        ]);

        $booking->payments()->where('status', Payment::STATUS_PENDING)->update(['status' => Payment::STATUS_CANCELLED]);

        ActivityLogger::log('booking_cancelled', 'Booking dibatalkan', 'Booking '.$booking->code.' dibatalkan. Alasan: '.$request->reason.'. DP hangus.', $booking->id);

        return back()->with('success', 'Booking dibatalkan. DP dinyatakan hangus.');
    }

    public function changePackage(Booking $booking, Request $request, InvoiceService $invoiceService)
    {
        $data = $request->validate([
            'package_id' => ['required', 'exists:packages,id'],
        ]);

        if ((int) $data['package_id'] === (int) $booking->package_id) {
            return back()->with('warning', 'Paket yang dipilih sama dengan paket saat ini.');
        }

        $old = $booking->package;
        $new = Package::findOrFail($data['package_id']);

        $booking->update(['package_id' => $new->id, 'package_price' => $new->price]);

        $this->syncVendorsFromPackage($booking);
        $invoiceService->sync($booking->fresh());

        ActivityLogger::log(
            'package_changed',
            'Paket diubah',
            'Paket diubah dari '.$old->name.' ke '.$new->name.' oleh '.auth()->user()->name,
            $booking->id,
            ['package' => $old->name, 'price' => $old->price],
            ['package' => $new->name, 'price' => $new->price],
        );

        return back()->with('success', 'Paket diperbarui ke '.$new->name.'. Vendor disinkronkan dari Master Vendor.');
    }

    public function approvePackageRequest(Booking $booking, Request $request, InvoiceService $invoiceService)
    {
        $changeRequest = $booking->packageChangeRequests()
            ->where('status', 'pending')
            ->findOrFail($request->change_request_id);

        $action = $request->input('action');

        if ($action === 'approve') {
            $changeRequest->update([
                'status' => 'approved',
                'processed_by' => auth()->id(),
                'processed_at' => now(),
            ]);

            $old = $booking->package;
            $new = $changeRequest->newPackage;

            $booking->update(['package_id' => $new->id, 'package_price' => $new->price]);
            $this->syncVendorsFromPackage($booking);
            $invoiceService->sync($booking->fresh());

            ActivityLogger::log(
                'package_changed',
                'Perubahan paket disetujui',
                'Perubahan paket '.$old->name.' → '.$new->name.' disetujui oleh '.auth()->user()->name,
                $booking->id,
                ['package' => $old->name],
                ['package' => $new->name],
            );

            $message = 'Perubahan paket disetujui.';
        } else {
            $changeRequest->update([
                'status' => 'rejected',
                'processed_by' => auth()->id(),
                'processed_at' => now(),
            ]);

            ActivityLogger::log('package_change_rejected', 'Perubahan paket ditolak', 'Perubahan paket ditolak oleh '.auth()->user()->name, $booking->id);

            $message = 'Perubahan paket ditolak.';
        }

        return back()->with('success', $message);
    }

    public function changeVendor(Booking $booking, Request $request)
    {
        $data = $request->validate([
            'booking_vendor_id' => ['required', 'exists:booking_vendors,id'],
            'vendor_id' => ['required', 'exists:vendors,id'],
        ]);

        $bookingVendor = $booking->bookingVendors()->findOrFail($data['booking_vendor_id']);
        $oldVendor = $bookingVendor->vendor;
        $newVendor = Vendor::findOrFail($data['vendor_id']);

        $bookingVendor->update([
            'vendor_id' => $newVendor->id,
            'price' => $newVendor->price,
            'status' => 'changed',
        ]);

        ActivityLogger::log(
            'vendor_changed',
            'Vendor diganti',
            'Vendor '.$oldVendor->name.' ('.$oldVendor->category->name.') diganti menjadi '.$newVendor->name,
            $booking->id,
            ['vendor' => $oldVendor->name],
            ['vendor' => $newVendor->name],
        );

        return back()->with('success', 'Vendor berhasil diganti.');
    }

    public function syncVendors(Booking $booking)
    {
        $this->syncVendorsFromPackage($booking);

        ActivityLogger::log('vendor_synced', 'Vendor disinkronkan', 'Vendor project '.$booking->code.' disinkronkan dari paket '.$booking->package->name, $booking->id);

        return back()->with('success', 'Vendor disinkronkan dari Master Vendor paket '.$booking->package->name.'.');
    }

    private function bookingSurveyRules(): array
    {
        $rules = [
            'survey_wedding_stage_id' => ['nullable', 'exists:wedding_stages,id'],
            'survey_flower_color' => ['nullable', 'string', 'max:255'],
            'survey_stage_size' => ['nullable', 'string', 'max:255'],
            'survey_stage_size_other' => ['nullable', 'string', 'max:255'],
            'survey_chair_option' => ['nullable', 'string', 'max:255'],
            'survey_chair_option_other' => ['nullable', 'string', 'max:255'],
            'survey_stage_option' => ['nullable', 'string', 'max:255'],
            'survey_stage_option_other' => ['nullable', 'string', 'max:255'],
            'survey_fabric_color' => ['nullable', 'string', 'max:255'],
            'survey_tent_sizes' => ['nullable', 'array'],
            'survey_tent_sizes.*' => ['string', 'max:255'],
            'survey_tent_size_quantities' => ['nullable', 'array'],
            'survey_tent_size_quantities.*' => ['nullable', 'integer', 'min:0'],
            'survey_tent_sizes_other' => ['nullable', 'string', 'max:255'],
            'survey_tent_additions' => ['nullable', 'array'],
            'survey_tent_additions.*' => ['string', 'max:255'],
            'survey_tent_addition_quantities' => ['nullable', 'array'],
            'survey_tent_addition_quantities.*' => ['nullable', 'integer', 'min:0'],
            'survey_tent_additions_other' => ['nullable', 'string', 'max:255'],
            'survey_tent_shape' => ['nullable', 'string', 'max:255'],
            'survey_tent_shape_other' => ['nullable', 'string', 'max:255'],
            'survey_entrance' => ['nullable', 'string', 'max:255'],
            'survey_entrance_other' => ['nullable', 'string', 'max:255'],
            'survey_buffet' => ['nullable', 'string', 'max:255'],
            'survey_buffet_other' => ['nullable', 'string', 'max:255'],
            'survey_tableware' => ['nullable', 'string', 'max:255'],
            'survey_tableware_other' => ['nullable', 'string', 'max:255'],
            'survey_gallery_booth' => ['nullable', 'string', 'max:255'],
            'survey_envelope_box' => ['nullable', 'string', 'max:255'],
            'survey_fruit_shed' => ['nullable', 'string', 'max:255'],
            'survey_akad_table' => ['nullable', 'string', 'max:255'],
            'survey_diesel_lights' => ['nullable', 'string', 'max:255'],
            'survey_photo_stand' => ['nullable', 'string', 'max:255'],
            'survey_carpet' => ['nullable', 'string', 'max:255'],
            'survey_vip_table' => ['nullable', 'string', 'max:255'],
            'survey_snack_shed' => ['nullable', 'string', 'max:255'],
            'survey_blower' => ['nullable', 'string', 'max:255'],
            'survey_welcome_sign' => ['nullable', 'string', 'max:255'],
            'survey_center_point' => ['nullable', 'string', 'max:255'],
            'survey_gallery_booth_other' => ['nullable', 'string', 'max:255'],
            'survey_envelope_box_other' => ['nullable', 'string', 'max:255'],
            'survey_fruit_shed_other' => ['nullable', 'string', 'max:255'],
            'survey_akad_table_other' => ['nullable', 'string', 'max:255'],
            'survey_diesel_lights_other' => ['nullable', 'string', 'max:255'],
            'survey_photo_stand_other' => ['nullable', 'string', 'max:255'],
            'survey_carpet_other' => ['nullable', 'string', 'max:255'],
            'survey_vip_table_other' => ['nullable', 'string', 'max:255'],
            'survey_snack_shed_other' => ['nullable', 'string', 'max:255'],
            'survey_blower_other' => ['nullable', 'string', 'max:255'],
            'survey_welcome_sign_other' => ['nullable', 'string', 'max:255'],
            'survey_center_point_other' => ['nullable', 'string', 'max:255'],
            'survey_location' => ['nullable', 'string', 'max:255'],
            'survey_maps_url' => ['nullable', 'url', 'max:500'],
            'survey_pic' => ['nullable', 'string', 'max:255'],
            'survey_notes' => ['nullable', 'string'],
            'survey_photos' => ['nullable', 'array'],
            'survey_photos.*' => ['image', 'max:5120'],
            'survey_videos' => ['nullable', 'array'],
            'survey_videos.*' => ['max:51200'],
        ];

        return $rules;
    }

    private function bookingFittingRules(): array
    {
        return [
            'fitting_date' => ['nullable', 'date'],
            'fitting_pic' => ['nullable', 'string', 'max:255'],
            'fitting_notes' => ['nullable', 'string'],
            'fitting_status' => ['nullable', 'in:scheduled,on_going,finished'],
            'items' => ['nullable', 'array'],
            'items.*.notes' => ['nullable', 'string', 'max:2000'],
            'items.*.photo' => ['nullable', 'image', 'max:5120'],
            'fitting_photos' => ['nullable', 'array'],
            'fitting_photos.*' => ['image', 'max:5120'],
        ];
    }

    private function saveBookingFieldwork(Request $request, Booking $booking): void
    {
        $surveyFields = [
            'location', 'maps_url', 'pic', 'notes', 'wedding_stage_id', 'flower_color',
            'stage_size', 'stage_size_other', 'chair_option', 'chair_option_other',
            'stage_option', 'stage_option_other', 'fabric_color', 'tent_sizes',
            'tent_size_quantities', 'tent_sizes_other', 'tent_additions',
            'tent_addition_quantities', 'tent_additions_other', 'tent_shape',
            'tent_shape_other', 'entrance', 'entrance_other', 'buffet', 'buffet_other',
            'tableware', 'tableware_other', 'gallery_booth', 'envelope_box', 'fruit_shed',
            'akad_table', 'diesel_lights', 'photo_stand', 'carpet', 'vip_table',
            'snack_shed', 'blower', 'welcome_sign', 'center_point',
            'gallery_booth_other', 'envelope_box_other', 'fruit_shed_other', 'akad_table_other',
            'diesel_lights_other', 'photo_stand_other', 'carpet_other', 'vip_table_other',
            'snack_shed_other', 'blower_other', 'welcome_sign_other', 'center_point_other',
        ];

        $hasSurveyData = collect($surveyFields)->contains(fn ($field) => filled($request->input('survey_'.$field)))
            || $request->hasFile('survey_photos')
            || $request->hasFile('survey_videos');

        if ($hasSurveyData) {
            $existingSurvey = $booking->survey()->first();
            $stageId = $request->input('survey_wedding_stage_id');
            $stage = $stageId ? WeddingStage::findOrFail($stageId) : null;

            if ($stage && ! $stage->is_active && $existingSurvey?->wedding_stage_id !== $stage->id) {
                throw ValidationException::withMessages([
                    'survey_wedding_stage_id' => 'Pelaminan yang tidak aktif tidak dapat dipilih.',
                ]);
            }

            $surveyData = [];
            foreach ($surveyFields as $field) {
                if (in_array($field, ['tent_sizes', 'tent_additions'], true)) {
                    $surveyData[$field] = array_values($request->input('survey_'.$field, []));
                } elseif (in_array($field, ['tent_size_quantities', 'tent_addition_quantities'], true)) {
                    $surveyData[$field] = collect($request->input('survey_'.$field, []))
                        ->map(fn ($quantity) => (int) $quantity)
                        ->all();
                } else {
                    $surveyData[$field] = $request->input('survey_'.$field);
                }
            }
            $surveyData['photos'] = array_merge($existingSurvey?->photos ?? [], $this->storeBookingFiles($request, 'survey_photos'));
            $surveyData['videos'] = array_merge($existingSurvey?->videos ?? [], $this->storeBookingFiles($request, 'survey_videos', false));
            $surveyData['created_by'] = auth()->id();

            Survey::updateOrCreate(['booking_id' => $booking->id], $surveyData);
        }

        $items = $request->input('items', []);
        $hasItemData = collect($items)->contains(fn ($item) => filled($item['notes'] ?? null)) || $request->hasFile('items');
        $hasFittingData = filled($request->input('fitting_date'))
            || filled($request->input('fitting_pic'))
            || filled($request->input('fitting_notes'))
            || $hasItemData
            || $request->hasFile('fitting_photos');

        if (! $hasFittingData) {
            return;
        }

        $date = $request->input('fitting_date');
        if (! $date) {
            throw ValidationException::withMessages([
                'fitting_date' => 'Tanggal fitting wajib diisi jika data fitting dilengkapi.',
            ]);
        }

        $existingFitting = $booking->fittings()->first();
        $fitting = Fitting::updateOrCreate(['booking_id' => $booking->id], [
            'date' => $date,
            'time' => $existingFitting?->getRawOriginal('time'),
            'pic' => $request->input('fitting_pic'),
            'notes' => $request->input('fitting_notes'),
            'status' => $request->input('fitting_status', Fitting::STATUS_SCHEDULED),
            'photos' => array_merge($existingFitting?->photos ?? [], $this->storeBookingFiles($request, 'fitting_photos')),
            'created_by' => auth()->id(),
        ]);

        $checklistData = [];
        foreach (Fitting::CHECKLIST as $checklist) {
            foreach ($checklist as $itemKey => $label) {
                $item = $items[$itemKey] ?? [];
                $photoColumn = $itemKey.'_photo_path';
                $checklistData[$itemKey.'_notes'] = $item['notes'] ?? null;
                $checklistData[$photoColumn] = $fitting->{$photoColumn};

                if ($request->hasFile("items.{$itemKey}.photo")) {
                    $checklistData[$photoColumn] = ImageCompressor::compressAndStore($request->file("items.{$itemKey}.photo"), 'uploads/photos');
                }
            }
        }

        $fitting->forceFill($checklistData)->save();
        $booking->update(['fitting_date' => $date]);
    }

    private function storeBookingFiles(Request $request, string $key, bool $isImage = true): array
    {
        if (! $request->hasFile($key)) {
            return [];
        }

        return collect($request->file($key))->map(fn ($file) => $isImage
            ? ImageCompressor::compressAndStore($file, 'uploads/photos')
            : $file->store('uploads/'.$key, 'public'))->all();
    }

    private function syncVendorsFromPackage(Booking $booking): void
    {
        $booking->unsetRelation('package');
        $booking->syncVendorsFromPackage();
    }

    public function complete(Booking $booking, InvoiceService $invoiceService)
    {
        $booking->update(['status' => Booking::STATUS_COMPLETED]);
        $invoiceService->sync($booking->fresh());

        ActivityLogger::log('booking_completed', 'Project selesai', 'Project '.$booking->code.' ditandai selesai.', $booking->id);

        return back()->with('success', 'Project ditandai selesai.');
    }
}
