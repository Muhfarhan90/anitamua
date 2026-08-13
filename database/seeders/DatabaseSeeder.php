<?php

namespace Database\Seeders;

use App\Models\Benefit;
use App\Models\BenefitCategory;
use App\Models\Booking;
use App\Models\Finance as FinanceModel;
use App\Models\Fitting;
use App\Models\InventoryCategory;
use App\Models\InventoryItem;
use App\Models\Package;
use App\Models\PackingItem;
use App\Models\PackingList;
use App\Models\Payment;
use App\Models\Reminder;
use App\Models\Schedule;
use App\Models\Survey;
use App\Models\User;
use App\Services\ActivityLogger;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Data inti (user, paket, CMS) — sama dengan produksi
        $this->call(ProdSeeder::class);

        // Data demo untuk pengembangan & testing (booking, pembayaran, dll)
        $this->seedMasterData();
        $this->seedDemoProject();
    }

    private function seedMasterData(): void
    {
        // // VENDOR (modul dinonaktifkan sementara)
        // $categories = [
        //     'Photography', 'Videography', 'Decoration', 'Catering',
        //     'MC', 'Entertainment', 'Wardrobe', 'Venue', 'Cake',
        // ];

        // foreach ($categories as $name) {
        //     VendorCategory::create(['name' => $name, 'slug' => Str::slug($name)]);
        // }

        $inventoryCategories = ['Wardrobe', 'Accessory', 'Makeup Kit', 'Footwear', 'Perlengkapan'];
        foreach ($inventoryCategories as $name) {
            InventoryCategory::create(['name' => $name]);
        }

        // // VENDOR DATA (modul dinonaktifkan sementara)
        // $vendorData = [
        //     ['Eterna Photography', 'Photography', 8500000, 4.8, '...', 'active', 'jakarta', 'eterna.jpg'],
        // ];

        // foreach ($vendorData as [$name, $cat, $price, $rating, $notes, $status, $address, $logo]) {
        //     Vendor::create([
        //         'vendor_category_id' => VendorCategory::where('name', $cat)->first()->id,
        //         'name' => $name,
        //         'phone' => '08'.random_int(1000000000, 9999999999),
        //         'email' => strtolower(str_replace([' ', "'"], ['.', ''], $name)).'@example.com',
        //         'instagram' => '@'.Str::slug($name),
        //         'address' => ucfirst($address),
        //         'logo' => $logo,
        //         'price' => $price,
        //         'rating' => $rating,
        //         'status' => $status,
        //         'notes' => $notes,
        //     ]);
        // }

        // ── MASTER BENEFIT & KATEGORI (dipakai ulang di banyak paket) ──
        $benefitCategories = [
            'Makeup & Attire' => [
                'Makeup & Retouch', 'Hair Do', 'Touch Up', 'Premium Wardrobe', 'Premium Accessories',
                'Free Trial Make Up', 'VIP Consultation', 'Bridal Shower Planning',
            ],
            'Decoration' => [
                'Pelaminan 4m', 'Backdrop', 'Welcome Gate', 'Kursi Tamu', 'Meja & Taplak',
            ],
            'Dokumentasi' => [
                'Album 2 Roll', 'Pre-Wedding Session', 'Video Cinematic', 'Premium Photo Session', 'Documentation Support',
            ],
            'Free' => [
                'White Henna / Nude', 'Hantaran Kecil', 'Fotocopy Undangan', 'Cake & Snack',
            ],
        ];

        $benefitIds = [];
        foreach ($benefitCategories as $categoryName => $names) {
            $category = BenefitCategory::firstOrCreate(['name' => $categoryName], ['sort_order' => BenefitCategory::max('sort_order') + 1]);

            foreach ($names as $i => $name) {
                $benefit = Benefit::firstOrCreate(
                    ['name' => $name],
                    ['benefit_category_id' => $category->id, 'sort_order' => $i + 1, 'status' => 'active'],
                );
                $benefitIds[$name] = $benefit->id;
            }
        }

        $packageData = [
            'Diamond Wedding' => ['type' => 'full', 'sub_type' => 'gedung', 'price' => 24800000, 'color' => '#60a5fa', 'benefits' => [
                'Makeup & Retouch', 'Hair Do', 'Touch Up', 'Premium Wardrobe',
                'Premium Accessories', 'VIP Consultation', 'Free Trial Make Up', 'Documentation Support',
                'Pelaminan 4m', 'Backdrop', 'Album 2 Roll', 'Video Cinematic',
            ]],
            'Luxury Wedding' => ['type' => 'full', 'sub_type' => 'gedung', 'price' => 35000000, 'color' => '#a855f7', 'benefits' => [
                'Makeup & Retouch', 'Hair Do', 'Touch Up', 'Premium Wardrobe',
                'Premium Accessories', 'VIP Consultation', 'Free Trial Make Up', 'Documentation Support',
                'Premium Photo Session', 'Bridal Shower Planning', 'Pelaminan 4m', 'Backdrop',
                'Welcome Gate', 'Video Cinematic', 'White Henna / Nude',
            ]],
        ];

        foreach ($packageData as $name => $data) {
            $package = Package::create([
                'name' => $name,
                'type' => $data['type'],
                'sub_type' => $data['sub_type'] ?? null,
                'price' => $data['price'],
                'description' => "Paket {$name} dari ANITA Make Up Artist — solusi lengkap untuk hari spesial Anda.",
                'color' => $data['color'],
                'status' => 'active',
            ]);

            $package->benefits()->sync(array_values(array_intersect_key($benefitIds, array_flip($data['benefits']))));

            // Kategori yang dipakai = kategori dari benefit terpilih
            $usedCategoryIds = $package->benefits->map(fn ($b) => $b->benefit_category_id)->unique()->values();
            $package->benefitCategories()->sync($usedCategoryIds);
        }

        $inventoryData = [
            ['WD-00123', 'Gaun Pengantin Amina', 'Wardrobe', 'Ivory', 'M', 'ANITA Collection', 'Rack A - 01', 'good', 'available', 'Rina Anggraini', '2025-01-10', 12500000],
            ['AC-00087', 'Crystal Crown Deluxe', 'Accessory', 'Silver', '-', 'ANITA', 'Box C - 04', 'good', 'in_use', 'Rina Anggraini', '2025-01-10', 3500000],
            ['MK-00456', 'Makeup Kit Premium', 'Makeup Kit', 'Black', '-', 'ProMake', 'Cabinet B - 02', 'good', 'in_use', 'Dewi P.', '2025-03-15', 2800000],
            ['WD-00234', 'Gaun Bridesmaid Rose', 'Wardrobe', 'Blush Pink', 'S', 'ANITA Collection', 'Rack B - 03', 'fair', 'in_use', 'Sinta L.', '2025-02-20', 4500000],
            ['AC-00111', 'Wedding Veil Luxury', 'Accessory', 'White', '-', 'ANITA', 'Box D - 01', 'good', 'available', 'Dewi P.', '2025-04-05', 1800000],
            ['WD-00345', 'Gaun Reception Putih', 'Wardrobe', 'White', 'L', 'ANITA Collection', 'Rack A - 02', 'good', 'available', 'Rina Anggraini', '2025-01-10', 9800000],
            ['AC-00222', 'Kalung Pearl Set', 'Accessory', 'White', '-', 'Pearl House', 'Box A - 01', 'good', 'available', 'Dewi P.', '2025-05-10', 2200000],
            ['WD-00456', 'Gaun Adat Jawa', 'Wardrobe', 'Gold', 'M', 'ANITA Collection', 'Rack C - 01', 'good', 'available', 'Rina Anggraini', '2024-11-15', 15000000],
            ['MK-00123', 'Lipstick Set Pro', 'Makeup Kit', 'Multi', '-', 'MAC Pro', 'Cabinet A - 01', 'good', 'available', 'Dewi P.', '2025-06-01', 1200000],
            ['AC-00333', 'Bros Antique Gold', 'Accessory', 'Gold', '-', 'ANITA', 'Box B - 02', 'fair', 'available', 'Sinta L.', '2025-02-20', 950000],
        ];

        foreach ($inventoryData as [$code, $name, $cat, $color, $size, $brand, $storage, $condition, $status, $lastPic, $purchaseDate, $purchasePrice]) {
            InventoryItem::create([
                'code' => $code,
                'name' => $name,
                'inventory_category_id' => InventoryCategory::where('name', $cat)->first()->id,
                'color' => $color,
                'size' => $size,
                'brand' => $brand,
                'storage_location' => $storage,
                'condition' => $condition,
                'status' => $status,
                'last_used_project' => null,
                'last_pic' => $lastPic,
                'purchase_date' => $purchaseDate,
                'purchase_price' => $purchasePrice,
            ]);
        }
    }

    private function seedDemoProject(): void
    {
        // Akun client demo (hanya untuk dev/testing — tidak ada di ProdSeeder)
        $client = User::firstOrCreate(
            ['email' => 'client@anitamua.com'],
            [
                'name' => 'Dewi Anggraini',
                'password' => Hash::make('password'),
                'role' => User::ROLE_CLIENT,
                'phone' => '081234567893',
                'position' => 'Bride',
            ],
        );

        $owner = User::where('email', 'owner@anitamua.com')->first();
        $admin = User::where('email', 'admin@anitamua.com')->first();
        $team = User::where('email', 'team@anitamua.com')->first();

        $package = Package::where('name', 'Diamond Wedding')->first();
        $eventDate = Carbon::now()->addDays(18)->startOfDay();

        $booking = Booking::create([
            'code' => 'AMU-'.date('Y').'-0001',
            'client_id' => $client->id,
            'package_id' => $package->id,
            'name' => 'Dewi & Andi',
            'phone' => $client->phone,
            'email' => $client->email,
            'event_date' => $eventDate->toDateString(),
            'event_time' => '09:00',
            'event_type' => 'wedding',
            'number_of_guests' => 200,
            'survey_date' => $eventDate->copy()->subDays(21)->toDateString(),
            'fitting_date' => $eventDate->copy()->subDays(14)->toDateString(),
            'location' => 'The Glass House, Jakarta Selatan',
            'notes' => 'Tema warna blush pink & gold, 200 undangan.',
            'status' => Booking::STATUS_BOOKED,
            'created_by' => $admin->id,
        ]);

        ActivityLogger::log('booking_created', 'Booking dibuat', 'Booking '.$booking->code.' oleh '.$admin->name.' untuk '.$booking->name, $booking->id);

        $payments = [
            [Payment::TYPE_DP10, round($package->price * 0.10), 'transfer', Payment::STATUS_VERIFIED, $eventDate->copy()->subDays(30)],
            [Payment::TYPE_DP25, round($package->price * 0.25), 'transfer', Payment::STATUS_VERIFIED, $eventDate->copy()->subDays(14)],
            [Payment::TYPE_DP75, round($package->price * 0.75), 'transfer', Payment::STATUS_PENDING, $eventDate->copy()->subDays(7)],
            [Payment::TYPE_PELUNASAN, 0, 'transfer', Payment::STATUS_PENDING, $eventDate->copy()->subDays(1)],
        ];

        foreach ($payments as [$type, $amount, $method, $status, $due]) {
            $verified = $status === Payment::STATUS_VERIFIED;
            Payment::create([
                'booking_id' => $booking->id,
                'type' => $type,
                'amount' => $amount,
                'method' => $method,
                'status' => $status,
                'due_date' => $due->toDateString(),
                'paid_at' => $verified ? $due->copy()->subHours(2) : null,
                'verified_by' => $verified ? $admin->id : null,
                'verified_at' => $verified ? $due->copy()->subHours(2) : null,
            ]);
        }

        ActivityLogger::log('dp_verified', 'DP 10% dikonfirmasi', 'DP 10% sebesar '.$payments[0][1].' dikonfirmasi oleh '.$admin->name, $booking->id);

        // // VENDOR BOOKING (modul vendor dinonaktifkan sementara)
        // foreach ($package->vendors as $vendor) {
        //     BookingVendor::create([
        //         'booking_id' => $booking->id,
        //         'vendor_id' => $vendor->id,
        //         'role' => $vendor->category->name,
        //         'price' => $vendor->pivot->price,
        //         'status' => 'confirmed',
        //     ]);
        // }

        $scheduleData = [
            ['survey', $eventDate->copy()->subDays(21), '10:00', 'The Glass House', 'Lokasi resepsi', Schedule::STATUS_FINISHED],
            ['fitting', $eventDate->copy()->subDays(14), '13:00', 'Studio ANITA', 'Fitting gaun utama', Schedule::STATUS_SCHEDULED],
            ['hari_h', $eventDate, '06:00', 'The Glass House', 'Hari H — Make up dimulai pukul 06.00', Schedule::STATUS_SCHEDULED],
        ];

        foreach ($scheduleData as [$type, $date, $time, $loc, $notes, $status]) {
            Schedule::create([
                'booking_id' => $booking->id,
                'type' => $type,
                'title' => Schedule::typeLabel($type).' — '.$booking->name,
                'date' => $date->toDateString(),
                'time' => $time,
                'location' => $loc,
                'pic_user_id' => $team->id,
                'notes' => $notes,
                'status' => $status,
            ]);
        }

        Survey::create([
            'booking_id' => $booking->id,
            'location' => 'The Glass House, Jakarta Selatan',
            'maps_url' => 'https://maps.google.com/?q=The+Glass+House+Jakarta',
            'pic' => $team->name,
            'notes' => 'Panggung di tengah, meja 20. Pencahayaan bagus.',
            'photos' => [],
            'created_by' => $team->id,
        ]);

        $fitting = Fitting::create([
            'booking_id' => $booking->id,
            'date' => $eventDate->copy()->subDays(14)->toDateString(),
            'time' => '13:00',
            'pic' => $team->name,
            'notes' => 'Sesuaikan ukuran gaun Diamond, tambah payet di pinggang.',
            'photos' => [],
            'status' => Fitting::STATUS_SCHEDULED,
            'created_by' => $team->id,
        ]);

        $packingBefore = PackingList::create([
            'booking_id' => $booking->id,
            'type' => PackingList::TYPE_BEFORE,
            'status' => 'done',
            'created_by' => $team->id,
        ]);

        $items = InventoryItem::limit(10)->get();
        foreach ($items as $item) {
            PackingItem::create([
                'packing_list_id' => $packingBefore->id,
                'inventory_item_id' => $item->id,
                'status' => PackingItem::STATUS_PACKED,
            ]);
        }

        $reminders = [
            [Reminder::TYPE_H30, 'Reminder Fitting', $eventDate->copy()->subDays(30), 'client'],
            [Reminder::TYPE_H7, 'Reminder DP 75%', $eventDate->copy()->subDays(7), 'client'],
            [Reminder::TYPE_H2, 'Reminder Pelunasan', $eventDate->copy()->subDays(2), 'client'],
            [Reminder::TYPE_H1, 'Reminder Hari H', $eventDate->copy()->subDays(1), 'client'],
        ];

        foreach ($reminders as [$type, $title, $date, $audience]) {
            Reminder::create([
                'booking_id' => $booking->id,
                'type' => $type,
                'title' => $title,
                'message' => "Jangan lupa: {$title} untuk acara {$booking->name}.",
                'scheduled_at' => $date->toDateString(),
                'audience' => $audience,
                'channel' => 'whatsapp',
                'status' => 'pending',
            ]);
        }

        $income = $booking->payments()->where('status', Payment::STATUS_VERIFIED)->sum('amount');

        FinanceModel::create([
            'booking_id' => $booking->id,
            'type' => 'income',
            'category' => 'dp',
            'amount' => $income,
            'description' => 'Pembayaran DP project '.$booking->code,
            'transaction_date' => $eventDate->copy()->subDays(14)->toDateString(),
            'created_by' => $owner->id,
        ]);

        FinanceModel::create([
            'booking_id' => null,
            'type' => 'expense',
            'category' => 'transport',
            'amount' => 500000,
            'description' => 'Bensin & transport tim lapangan',
            'transaction_date' => Carbon::now()->subDays(5)->toDateString(),
            'created_by' => $owner->id,
        ]);

        ActivityLogger::log('project_updated', 'Project demo dibuat', 'Project demo '.$booking->code.' berhasil dibuat dengan seluruh data.', $booking->id);
    }
}
