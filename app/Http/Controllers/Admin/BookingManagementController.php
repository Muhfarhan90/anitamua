<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\BookingVendor;
use App\Models\EntranceGate;
use App\Models\Fitting;
use App\Models\Package;
use App\Models\Payment;
use App\Models\Schedule;
use App\Models\SiteSetting;
use App\Models\Survey;
use App\Models\Tent;
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
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class BookingManagementController extends Controller
{
    public function index(Request $request)
    {
        $today = Carbon::today()->toDateString();
        $query = Booking::with(['client', 'package', 'payments'])
            ->whereDate('event_date', '>=', $today)
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->when($request->start_date, fn ($q, $date) => $q->whereDate('event_date', '>=', $date))
            ->when($request->end_date, fn ($q, $date) => $q->whereDate('event_date', '<=', $date))
            ->when($request->q, fn ($q, $s) => $q->where(function ($qq) use ($s) {
                $qq->where('name', 'like', "%{$s}%")
                    ->orWhere('code', 'like', "%{$s}%")
                    ->orWhere('phone', 'like', "%{$s}%");
            }))
            ->orderBy('event_date')
            ->orderByDesc('created_at');

        $bookings = $query->paginate(15)->withQueryString();

        return view('admin.bookings.index', compact('bookings'));
    }

    public function show(Booking $booking, InvoiceService $invoiceService)
    {
        $booking->load([
            'client', 'package', 'payments', 'invoice', 'bookingVendors.vendor.category',
            'addons',
            'schedules.picUser', 'survey.weddingStage', 'survey.tent', 'survey.entranceGate', 'fittings', 'packingLists.items.inventoryItem',
            'activityLogs' => fn ($query) => $query->latest(),
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
        $tents = Tent::where('is_active', true)->orderBy('name')->get();
        $entranceGates = EntranceGate::where('is_active', true)->orderBy('name')->get();
        $referralSources = SiteSetting::bookingReferralSources();
        $teamMembers = User::where('role', User::ROLE_TEAM)->where('is_active', true)->orderBy('name')->get();

        $subTypeLabels = [
            'makeup' => 'Makeup Only',
            'akad' => 'Akad',
            'makeup_attire' => 'Makeup & Attire',
            'rumahan' => 'Rumahan',
            'gedung' => 'Gedung',
        ];

        return view('admin.bookings.create', compact('packages', 'clients', 'subTypeLabels', 'weddingStages', 'tents', 'entranceGates', 'teamMembers', 'referralSources'));
    }

    public function edit(Booking $booking)
    {
        $booking->load(['package', 'addons', 'bookingVendors.vendor.category', 'survey.weddingStage', 'survey.tent', 'survey.entranceGate', 'fittings']);
        $vendors = Vendor::with('category')
            ->where(function ($query) use ($booking) {
                $query->where('status', 'active')
                    ->orWhereIn('id', $booking->bookingVendors->pluck('vendor_id'));
            })
            ->orderBy('name')
            ->get();
        $packages = Package::where('status', 'active')->get();
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
        [$tents, $entranceGates] = $this->surveyMasters($booking->survey);
        $referralSources = $this->referralSourcesFor($booking);

        $subTypeLabels = [
            'makeup' => 'Makeup Only',
            'akad' => 'Akad',
            'makeup_attire' => 'Makeup & Attire',
            'rumahan' => 'Rumahan',
            'gedung' => 'Gedung',
        ];

        return view('admin.bookings.edit', compact('booking', 'vendors', 'packages', 'subTypeLabels', 'weddingStages', 'tents', 'entranceGates', 'teamMembers', 'referralSources'));
    }

    public function store(Request $request, InvoiceService $invoiceService)
    {
        $data = $request->validate([
            'client_mode' => ['required', 'in:existing,new'],
            'client_id' => [
                'required_if:client_mode,existing',
                'nullable',
                Rule::exists('users', 'id')->where('role', User::ROLE_CLIENT),
            ],
            'new_client_name' => ['required_if:client_mode,new', 'nullable', 'string', 'max:255'],
            'new_client_email' => ['required_if:client_mode,new', 'nullable', 'email', 'unique:users,email'],
            'new_client_phone' => ['required_if:client_mode,new', 'nullable', 'string', 'max:30'],
            'new_client_instagram' => ['nullable', 'string', 'max:100', 'regex:/^@?[A-Za-z0-9._]+$/'],
            'referral_source' => ['required', Rule::in(SiteSetting::bookingReferralSources())],
            'package_id' => ['required', 'exists:packages,id'],
            'discount_type' => ['nullable', 'in:percentage,fixed', 'required_with:discount_value'],
            'discount_value' => [
                'nullable', 'numeric', 'min:0', 'required_with:discount_type',
                Rule::when($request->input('discount_type') === 'fixed', ['integer']),
            ],
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
        $dp1Amount = $data['dp1_amount'];
        unset($data['addons'], $data['dp1_amount']);

        $package = Package::findOrFail($data['package_id']);
        [$data['discount_type'], $data['discount_value']] = $this->normalizeDiscount(
            $data['discount_type'] ?? null,
            $data['discount_value'] ?? null,
            (float) $package->price + (float) collect($addons)->sum('price'),
            (float) $dp1Amount,
        );

        $clientWasCreated = $data['client_mode'] === 'new';
        [$booking, $client] = DB::transaction(function () use ($request, $invoiceService, $addons, $dp1Amount, $clientWasCreated, $data, $package) {
            $client = $clientWasCreated
                ? User::create([
                    'name' => $data['new_client_name'],
                    'email' => strtolower(trim($data['new_client_email'])),
                    'phone' => $data['new_client_phone'],
                    'instagram' => $data['new_client_instagram'] ?? null,
                    'password' => User::generateDefaultPassword($data['new_client_name']),
                    'role' => User::ROLE_CLIENT,
                    'position' => 'Bride',
                ])
                : User::where('role', User::ROLE_CLIENT)->findOrFail($data['client_id']);

            // Identitas booking mengikuti akun client yang dipilih/dibuat.
            $data['client_id'] = $client->id;
            $data['name'] = $client->name;
            $data['phone'] = $client->phone;
            $data['email'] = $client->email;
            $data['instagram'] = $client->instagram;
            unset($data['client_mode'], $data['new_client_name'], $data['new_client_email'], $data['new_client_phone'], $data['new_client_instagram']);

            $data['code'] = Booking::generateCode();
            $data['created_by'] = auth()->id();
            $data['status'] = Booking::STATUS_BOOKED; // dibuat admin → langsung sah
            $data['package_price'] = $package->price;

            $booking = Booking::create($data);
            $booking->addons()->createMany($addons);
            $booking->syncVendorsFromPackage();
            $this->saveBookingFieldwork($request, $booking);

            $paymentData = [
                'type' => 'DP1',
                'amount' => $dp1Amount,
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
            $booking->ensureHariHSchedule();
            $invoiceService->sync($booking->fresh());

            ActivityLogger::log('booking_created', 'Booking dibuat oleh Admin', 'Booking '.$booking->code.' untuk '.$booking->name, $booking->id);
            ActivityLogger::log('dp_verified', 'DP dikonfirmasi', 'DP1 dikonfirmasi oleh '.auth()->user()->name.' (booking dibuat via form admin)', $booking->id);

            return [$booking, $client];
        });

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
            'package_id' => ['required', 'exists:packages,id'],
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:30'],
            'email' => ['required', 'email'],
            'referral_source' => ['required', Rule::in($this->referralSourcesFor($booking))],
            'discount_type' => ['nullable', 'in:percentage,fixed', 'required_with:discount_value'],
            'discount_value' => [
                'nullable', 'numeric', 'min:0', 'required_with:discount_type',
                Rule::when($request->input('discount_type') === 'fixed', ['integer']),
            ],
            'event_date' => ['required', 'date'],
            'event_time' => ['nullable', 'date_format:H:i'],
            'survey_date' => ['nullable', 'date'],
            'fitting_date' => ['nullable', 'date'],
            'location' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
            'addons' => ['nullable', 'array'],
            'addons.*.name' => ['required', 'string', 'max:255'],
            'addons.*.price' => ['required', 'numeric', 'min:0'],
            'vendor_additions_present' => ['nullable', 'array'],
            'vendor_additions_present.*' => ['boolean'],
            'vendor_additions' => ['nullable', 'array'],
            'vendor_additions.*' => ['nullable', 'array'],
            'vendor_additions.*.*.name' => ['required', 'string', 'max:255'],
            'vendor_additions.*.*.price' => ['required', 'numeric', 'min:0'],
            'vendor_changes' => ['nullable', 'array'],
            'vendor_changes.*.vendor_id' => ['required', 'exists:vendors,id'],
            'removed_booking_vendor_ids' => ['nullable', 'array'],
            'removed_booking_vendor_ids.*' => ['required', 'integer', 'distinct'],
            'removed_vendor_category_ids' => ['nullable', 'array'],
            'removed_vendor_category_ids.*' => ['required', 'integer', 'distinct', 'exists:vendor_categories,id'],
            'additional_vendor_ids' => ['nullable', 'array'],
            'additional_vendor_ids.*' => ['required', 'integer', 'distinct', 'exists:vendors,id'],
            'additional_vendor_additions' => ['nullable', 'array'],
            'additional_vendor_additions.*' => ['nullable', 'array'],
            'additional_vendor_additions.*.*.name' => ['required', 'string', 'max:255'],
            'additional_vendor_additions.*.*.price' => ['required', 'numeric', 'min:0'],
        ] + $this->bookingSurveyRules() + $this->bookingFittingRules());

        $addons = $data['addons'] ?? [];
        $vendorAdditions = $data['vendor_additions'] ?? [];
        $vendorAdditionsPresent = $data['vendor_additions_present'] ?? [];
        $vendorChanges = $data['vendor_changes'] ?? [];
        $removedBookingVendorIds = $data['removed_booking_vendor_ids'] ?? [];
        $removedVendorCategoryIds = $data['removed_vendor_category_ids'] ?? [];
        $additionalVendorIds = $data['additional_vendor_ids'] ?? [];
        $additionalVendorAdditions = $data['additional_vendor_additions'] ?? [];
        unset($data['addons']);
        unset($data['vendor_additions'], $data['vendor_additions_present'], $data['vendor_changes'], $data['removed_booking_vendor_ids'], $data['removed_vendor_category_ids'], $data['additional_vendor_ids'], $data['additional_vendor_additions']);

        $packageChanged = (int) $data['package_id'] !== (int) $booking->package_id;
        $packagePrice = $packageChanged
            ? (float) Package::findOrFail($data['package_id'])->price
            : (float) ($booking->package_price ?? $booking->package?->price ?? 0);
        [$data['discount_type'], $data['discount_value']] = $this->normalizeDiscount(
            $data['discount_type'] ?? null,
            $data['discount_value'] ?? null,
            $packagePrice + (float) collect($addons)->sum('price'),
            (float) $booking->payments()->where('status', Payment::STATUS_VERIFIED)->sum('amount'),
        );
        if ($packageChanged) {
            $data['package_price'] = $packagePrice;
        }

        $old = $booking->only(array_keys($data));
        DB::transaction(function () use ($booking, $data, $addons, $vendorAdditions, $vendorAdditionsPresent, $vendorChanges, $removedBookingVendorIds, $removedVendorCategoryIds, $additionalVendorIds, $additionalVendorAdditions, $request, $packageChanged, $invoiceService) {
            $booking->update($data);
            $scheduleChanged = $booking->wasChanged(['event_date', 'event_time', 'location', 'name']);
            $booking->addons()->delete();
            $booking->addons()->createMany($addons);

            if ($packageChanged) {
                $booking->unsetRelation('package');
                $booking->syncVendorsFromPackage();
            }

            // Hapus assignment vendor setelah snapshot paket baru terbentuk agar
            // penghapusan kategori tetap berlaku meskipun paket ikut diganti.
            $booking->bookingVendors()
                ->whereIn('id', $removedBookingVendorIds)
                ->delete();
            if ($removedVendorCategoryIds) {
                $booking->bookingVendors()
                    ->whereHas('vendor', fn ($query) => $query->whereIn('vendor_category_id', $removedVendorCategoryIds))
                    ->delete();
            }

            if (! $packageChanged) {
                $bookingVendorRows = $booking->bookingVendors()->with('vendor.category')->get()->keyBy('id');
                $selectedVendorIds = $bookingVendorRows->pluck('vendor_id')->map(fn ($id) => (int) $id)->all();

                foreach ($vendorChanges as $bookingVendorId => $vendorChange) {
                    $bookingVendor = $bookingVendorRows->get((int) $bookingVendorId);
                    if (! $bookingVendor) {
                        throw ValidationException::withMessages([
                            "vendor_changes.{$bookingVendorId}.vendor_id" => 'Vendor booking tidak ditemukan.',
                        ]);
                    }

                    $oldVendor = $bookingVendor->vendor;
                    $newVendor = Vendor::with('category')->find($vendorChange['vendor_id']);
                    if (! $oldVendor || ! $newVendor || $newVendor->vendor_category_id !== $oldVendor->vendor_category_id) {
                        throw ValidationException::withMessages([
                            "vendor_changes.{$bookingVendorId}.vendor_id" => 'Vendor pengganti harus dari kategori yang sama.',
                        ]);
                    }

                    if ($newVendor->status !== 'active' && ! $newVendor->is($oldVendor)) {
                        throw ValidationException::withMessages([
                            "vendor_changes.{$bookingVendorId}.vendor_id" => 'Vendor yang tidak aktif tidak dapat dipilih.',
                        ]);
                    }

                    $selectedVendorIds = array_values(array_diff($selectedVendorIds, [(int) $bookingVendor->vendor_id]));
                    if (in_array((int) $newVendor->id, $selectedVendorIds, true)) {
                        throw ValidationException::withMessages([
                            "vendor_changes.{$bookingVendorId}.vendor_id" => 'Vendor tersebut sudah terdaftar di booking ini.',
                        ]);
                    }
                    $selectedVendorIds[] = (int) $newVendor->id;
                }

                foreach ($vendorChanges as $bookingVendorId => $vendorChange) {
                    $bookingVendor = $bookingVendorRows->get((int) $bookingVendorId);
                    $oldVendor = $bookingVendor->vendor;
                    $newVendor = Vendor::with('category')->findOrFail($vendorChange['vendor_id']);
                    if ($newVendor->is($oldVendor)) {
                        continue;
                    }

                    $bookingVendor->update([
                        'vendor_id' => $newVendor->id,
                        'role' => $newVendor->category?->name,
                        'price' => $newVendor->price,
                        'status' => 'changed',
                    ]);

                    ActivityLogger::log(
                        'vendor_changed',
                        'Vendor diganti',
                        'Vendor '.$oldVendor->name.' ('.$oldVendor->category?->name.') diganti menjadi '.$newVendor->name,
                        $booking->id,
                        ['vendor' => $oldVendor->name],
                        ['vendor' => $newVendor->name],
                    );
                }

                foreach (array_keys($vendorAdditionsPresent) as $bookingVendorId) {
                    $bookingVendor = $booking->bookingVendors()->find($bookingVendorId);
                    if (! $bookingVendor) {
                        continue;
                    }

                    $additions = collect($vendorAdditions[$bookingVendorId] ?? [])->map(fn ($addition) => [
                        'name' => trim($addition['name']),
                        'price' => (float) $addition['price'],
                    ])->values()->all();

                    $bookingVendor->update(['custom_additions' => $additions]);
                }
            }

            foreach ($additionalVendorIds as $vendorId) {
                $vendor = Vendor::with('category')
                    ->where('status', 'active')
                    ->findOrFail($vendorId);

                if (! $vendor->vendor_category_id) {
                    throw ValidationException::withMessages([
                        'additional_vendor_ids' => 'Vendor tambahan harus memiliki kategori.',
                    ]);
                }

                if ($booking->bookingVendors()->where('vendor_id', $vendor->id)->exists()) {
                    throw ValidationException::withMessages([
                        'additional_vendor_ids' => 'Vendor tersebut sudah terdaftar di booking ini.',
                    ]);
                }

                $booking->bookingVendors()->create([
                    'vendor_id' => $vendor->id,
                    'role' => $vendor->category?->name,
                    'price' => $vendor->price,
                    'custom_additions' => collect($additionalVendorAdditions[$vendor->id] ?? [])->map(fn ($addition) => [
                        'name' => trim($addition['name']),
                        'price' => (float) $addition['price'],
                    ])->values()->all(),
                    'status' => 'confirmed',
                ]);

                ActivityLogger::log(
                    'vendor_added',
                    'Vendor tambahan ditambahkan',
                    'Vendor '.$vendor->name.' ('.$vendor->category?->name.') ditambahkan ke booking '.$booking->code,
                    $booking->id,
                );
            }
            $this->saveBookingFieldwork($request, $booking);

            if (in_array($booking->status, [Booking::STATUS_BOOKED, Booking::STATUS_COMPLETED], true)) {
                app(ClientAccountService::class)->ensure($booking);
                $booking->ensureHariHSchedule();
            }

            if ($scheduleChanged) {
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
        });

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

        $account = DB::transaction(function () use ($booking, $data, $invoiceService) {
            $booking = Booking::query()->lockForUpdate()->findOrFail($booking->id);
            if ($booking->status !== Booking::STATUS_PENDING) {
                throw ValidationException::withMessages([
                    'amount' => 'DP hanya dapat diverifikasi saat booking berstatus Pending.',
                ]);
            }

            $payment = $booking->payments()->oldest('id')->lockForUpdate()->first();
            if ($payment && $payment->status !== Payment::STATUS_PENDING) {
                throw ValidationException::withMessages([
                    'amount' => 'Pembayaran awal tidak lagi berstatus Pending.',
                ]);
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
            $account = app(ClientAccountService::class)->ensure($booking);
            $booking->ensureHariHSchedule();
            $invoiceService->sync($booking->fresh());

            ActivityLogger::log(
                'dp_verified',
                'DP1 dikonfirmasi',
                'DP1 sebesar '.number_format($data['amount']).' dikonfirmasi oleh '.auth()->user()->name,
                $booking->id,
                null,
                ['amount' => $data['amount']],
            );

            ActivityLogger::log(
                $account->wasRecentlyCreated ? 'client_account_created' : 'client_account_linked',
                $account->wasRecentlyCreated ? 'Akun portal dibuat otomatis' : 'Booking ditautkan ke akun client',
                'Booking '.$booking->code.' ditautkan ke akun client '.$account->email.'.',
                $booking->id,
            );

            return $account;
        });

        $message = 'DP1 terverifikasi. Booking berstatus BOOKED dan terhubung ke akun '.$account->email.'.';
        if ($account->wasRecentlyCreated) {
            $message .= ' Password default: '.User::generateDefaultPassword($booking->name).'.';
        }

        return back()->with('success', $message);
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

        [$discountType, $discountValue] = $this->normalizeDiscount(
            $booking->discount_type,
            $booking->discount_value,
            (float) $new->price + (float) $booking->addons()->sum('price'),
            (float) $booking->payments()->where('status', Payment::STATUS_VERIFIED)->sum('amount'),
        );

        $booking->update([
            'package_id' => $new->id,
            'package_price' => $new->price,
            'discount_type' => $discountType,
            'discount_value' => $discountValue,
        ]);

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
            $old = $booking->package;
            $new = $changeRequest->newPackage;

            [$discountType, $discountValue] = $this->normalizeDiscount(
                $booking->discount_type,
                $booking->discount_value,
                (float) $new->price + (float) $booking->addons()->sum('price'),
                (float) $booking->payments()->where('status', Payment::STATUS_VERIFIED)->sum('amount'),
            );

            $changeRequest->update([
                'status' => 'approved',
                'processed_by' => auth()->id(),
                'processed_at' => now(),
            ]);

            $booking->update([
                'package_id' => $new->id,
                'package_price' => $new->price,
                'discount_type' => $discountType,
                'discount_value' => $discountValue,
            ]);
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

        abort_unless($newVendor->vendor_category_id === $oldVendor->vendor_category_id, 422, 'Vendor pengganti harus dari kategori yang sama.');

        if ($newVendor->status !== 'active' && ! $newVendor->is($oldVendor)) {
            return back()->with('warning', 'Vendor yang tidak aktif tidak dapat dipilih.');
        }

        if ($newVendor->is($oldVendor)) {
            return back()->with('success', 'Vendor tidak berubah.');
        }

        if ($booking->bookingVendors()->whereKeyNot($bookingVendor->id)->where('vendor_id', $newVendor->id)->exists()) {
            return back()->with('warning', 'Vendor tersebut sudah terdaftar di booking ini.');
        }

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

    public function addVendor(Booking $booking, Request $request)
    {
        $data = $request->validate([
            'vendor_id' => ['required', 'exists:vendors,id'],
        ]);

        $vendor = Vendor::with('category')
            ->where('status', 'active')
            ->findOrFail($data['vendor_id']);

        if (! $vendor->vendor_category_id) {
            return back()->with('warning', 'Vendor tambahan harus memiliki kategori.');
        }

        if ($booking->bookingVendors()->where('vendor_id', $vendor->id)->exists()) {
            return back()->with('warning', 'Vendor tersebut sudah terdaftar di booking ini.');
        }

        $booking->bookingVendors()->create([
            'vendor_id' => $vendor->id,
            'role' => $vendor->category?->name,
            'price' => $vendor->price,
            'status' => 'confirmed',
        ]);

        ActivityLogger::log(
            'vendor_added',
            'Vendor tambahan ditambahkan',
            'Vendor '.$vendor->name.' ('.$vendor->category?->name.') ditambahkan ke booking '.$booking->code,
            $booking->id,
        );

        return back()->with('success', 'Vendor tambahan berhasil ditambahkan.');
    }

    public function syncVendors(Booking $booking)
    {
        $this->syncVendorsFromPackage($booking);

        ActivityLogger::log('vendor_synced', 'Vendor disinkronkan', 'Vendor project '.$booking->code.' disinkronkan dari paket '.$booking->package->name, $booking->id);

        return back()->with('success', 'Vendor disinkronkan dari Master Vendor paket '.$booking->package->name.'.');
    }

    private function normalizeDiscount(?string $type, mixed $value, float $subtotal, float $paidAmount = 0): array
    {
        $value = max(0, (float) ($value ?? 0));

        if (! $type || $value <= 0) {
            return [null, 0];
        }

        if ($type === 'percentage' && $value > 100) {
            throw ValidationException::withMessages([
                'discount_value' => 'Diskon persentase tidak boleh lebih dari 100%.',
            ]);
        }

        if ($type === 'fixed' && $value > $subtotal) {
            throw ValidationException::withMessages([
                'discount_value' => 'Diskon nominal tidak boleh lebih besar dari subtotal booking.',
            ]);
        }

        $discountAmount = $type === 'percentage' ? round($subtotal * $value / 100, 2) : $value;
        if ($paidAmount > $subtotal - $discountAmount) {
            throw ValidationException::withMessages([
                'discount_value' => 'Diskon membuat total tagihan lebih kecil dari pembayaran yang sudah diterima.',
            ]);
        }

        return [$type, round($value, 2)];
    }

    private function bookingSurveyRules(): array
    {
        $rules = [
            'survey_wedding_stage_id' => ['nullable', 'exists:wedding_stages,id'],
            'survey_tent_id' => ['nullable', 'exists:tents,id'],
            'survey_entrance_gate_id' => ['nullable', 'exists:entrance_gates,id'],
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
            'items.*.size' => ['nullable', 'string', 'max:255'],
            'items.*.photo' => ['nullable', 'image', 'max:5120'],
            'fitting_photos' => ['nullable', 'array'],
            'fitting_photos.*' => ['image', 'max:5120'],
        ];
    }

    private function saveBookingFieldwork(Request $request, Booking $booking): void
    {
        $surveyFields = [
            'location', 'maps_url', 'pic', 'notes', 'wedding_stage_id', 'tent_id', 'entrance_gate_id', 'flower_color',
            'stage_size', 'stage_size_other', 'chair_option', 'chair_option_other',
            'stage_option', 'stage_option_other', 'fabric_color', 'tent_sizes',
            'tent_size_quantities', 'tent_sizes_other', 'tent_additions',
            'tent_addition_quantities', 'tent_additions_other', 'buffet', 'buffet_other',
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

            $tentId = $request->input('survey_tent_id');
            $tent = $tentId ? Tent::findOrFail($tentId) : null;
            if ($tent && ! $tent->is_active && $existingSurvey?->tent_id !== $tent->id) {
                throw ValidationException::withMessages(['survey_tent_id' => 'Tenda yang tidak aktif tidak dapat dipilih.']);
            }

            $entranceGateId = $request->input('survey_entrance_gate_id');
            $entranceGate = $entranceGateId ? EntranceGate::findOrFail($entranceGateId) : null;
            if ($entranceGate && ! $entranceGate->is_active && $existingSurvey?->entrance_gate_id !== $entranceGate->id) {
                throw ValidationException::withMessages(['survey_entrance_gate_id' => 'Gapura yang tidak aktif tidak dapat dipilih.']);
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
        $hasItemData = collect($items)->contains(fn ($item) => filled($item['notes'] ?? null) || filled($item['size'] ?? null)) || $request->hasFile('items');
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
        $itemSizes = $existingFitting?->item_sizes ?? [];
        foreach ($items as $itemKey => $item) {
            if (array_key_exists('size', $item)) {
                $itemSizes[$itemKey] = $item['size'];
            }
        }
        $fitting = Fitting::updateOrCreate(['booking_id' => $booking->id], [
            'date' => $date,
            'time' => $existingFitting?->getRawOriginal('time'),
            'pic' => $request->input('fitting_pic'),
            'notes' => $request->input('fitting_notes'),
            'status' => $request->input('fitting_status', Fitting::STATUS_SCHEDULED),
            'photos' => array_merge($existingFitting?->photos ?? [], $this->storeBookingFiles($request, 'fitting_photos')),
            'item_sizes' => $itemSizes,
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

    private function surveyMasters(?Survey $survey): array
    {
        $tents = Tent::query()
            ->where(fn ($query) => $query->where('is_active', true)->when($survey?->tent_id, fn ($query) => $query->orWhere('id', $survey->tent_id)))
            ->orderBy('name')->get();
        $entranceGates = EntranceGate::query()
            ->where(fn ($query) => $query->where('is_active', true)->when($survey?->entrance_gate_id, fn ($query) => $query->orWhere('id', $survey->entrance_gate_id)))
            ->orderBy('name')->get();

        return [$tents, $entranceGates];
    }

    private function referralSourcesFor(Booking $booking): array
    {
        $sources = SiteSetting::bookingReferralSources();
        if (filled($booking->referral_source) && ! in_array($booking->referral_source, $sources, true)) {
            $sources[] = $booking->referral_source;
        }

        return $sources;
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
        if ($booking->status !== Booking::STATUS_BOOKED) {
            return back()->with('warning', 'Hanya booking berstatus BOOKED yang dapat ditandai selesai.');
        }

        DB::transaction(function () use ($booking, $invoiceService) {
            $booking->update(['status' => Booking::STATUS_COMPLETED]);
            app(ClientAccountService::class)->ensure($booking);
            $invoiceService->sync($booking->fresh());
        });

        ActivityLogger::log('booking_completed', 'Project selesai', 'Project '.$booking->code.' ditandai selesai.', $booking->id);

        return back()->with('success', 'Project ditandai selesai.');
    }
}
