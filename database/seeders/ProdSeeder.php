<?php

namespace Database\Seeders;

use App\Models\Faq;
use App\Models\Gallery;
use App\Models\SiteSetting;
use App\Models\Testimonial;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Seeder PRODUKSI: hanya data inti yang dibutuhkan untuk menjalankan bisnis.
 *  - User (semua role)
 *  - Paket (via WeddingPackageSeeder & MakeupPackageSeeder)
 *  - CMS (FAQ, testimoni, galeri, pengaturan situs)
 * TIDAK termasuk data booking/pembayaran/demo.
 *
 * Jalankan: php artisan migrate:fresh --seeder=ProdSeeder
 */
class ProdSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedUsers();
        $this->seedContent();
        $this->call(WeddingPackageSeeder::class);
        $this->call(MakeupPackageSeeder::class);
    }

    private function seedUsers(): void
    {
        User::factory()->create([
            'name' => 'Anita Owner',
            'email' => 'owner@anitamua.com',
            'password' => Hash::make('password'),
            'role' => User::ROLE_OWNER,
            'phone' => '081234567890',
            'position' => 'Owner',
        ]);

        User::factory()->create([
            'name' => 'Anita Admin',
            'email' => 'admin@anitamua.com',
            'password' => Hash::make('password'),
            'role' => User::ROLE_ADMIN,
            'phone' => '081234567891',
            'position' => 'Admin',
        ]);

        User::factory()->create([
            'name' => 'Dewi P.',
            'email' => 'team@anitamua.com',
            'password' => Hash::make('password'),
            'role' => User::ROLE_TEAM,
            'phone' => '081234567892',
            'position' => 'Tim Lapangan',
        ]);
    }

    private function seedContent(): void
    {
        Faq::create([
            'question' => 'Apakah paket dapat diubah?',
            'answer' => 'Bisa. Ajukan perubahan paket melalui portal, dan admin/owner akan menyetujui perubahan tersebut.',
            'sort_order' => 1,
        ]);
        Faq::create([
            'question' => 'Bagaimana jika ingin menambah layanan?',
            'answer' => 'Anda bisa mengajukan penambahan layanan (add-on) melalui portal client.',
            'sort_order' => 2,
        ]);
        Faq::create([
            'question' => 'Kapan vendor akan dikonfirmasi?',
            'answer' => 'Vendor akan dikonfirmasi oleh admin setelah booking sah (DP1 terverifikasi).',
            'sort_order' => 3,
        ]);
        Faq::create([
            'question' => 'Bagaimana proses fitting?',
            'answer' => 'Fitting dijadwalkan oleh admin. Anda akan mendapat reminder H-30 sebelum Hari H.',
            'sort_order' => 4,
        ]);

        Testimonial::create([
            'client_name' => 'Rina & Andi',
            'rating' => 5,
            'content' => 'Make up ANITA luar biasa! Semua vendor diurus satu pintu, kami tinggal santai.',
            'status' => 'published',
        ]);
        Testimonial::create([
            'client_name' => 'Sinta & Dimas',
            'rating' => 5,
            'content' => 'Prosesnya rapi banget, ada dashboard yang memantau semua progress acara.',
            'status' => 'published',
        ]);

        Gallery::create(['title' => 'Wedding Rina & Andi', 'photo' => 'gallery/wedding-1.jpg', 'category' => 'wedding']);
        Gallery::create(['title' => 'Prewedding Sinta & Dimas', 'photo' => 'gallery/wedding-2.jpg', 'category' => 'prewedding']);
        Gallery::create(['title' => 'Wedding Dinda & Raka', 'photo' => 'gallery/wedding-3.jpg', 'category' => 'wedding']);

        SiteSetting::set('company_name', 'ANITA', 'general');
        SiteSetting::set('tagline', 'Make Up Artist', 'general');
        SiteSetting::set('about', 'ANITA Make Up Artist adalah penyedia jasa rias pengantin profesional yang telah melayani ratusan pasangan sejak 2015.', 'general');
        SiteSetting::set('address', 'Jakarta, Indonesia', 'contact');
        SiteSetting::set('phone', '0812-3456-7890', 'contact');
        SiteSetting::set('email', 'anita.makeup.artist@gmail.com', 'contact');
        SiteSetting::set('instagram', '@anitamakeup.artist', 'social');
        SiteSetting::set('whatsapp', '6281234567890', 'contact');
        SiteSetting::set('bank_name', 'BCA', 'contact');
        SiteSetting::set('bank_account_number', '1234567890', 'contact');
        SiteSetting::set('bank_account_name', 'ANITA MUA', 'contact');
        SiteSetting::set('invoice_greeting', 'Terima kasih telah mempercayakan momen spesial Anda kepada ANITA. Invoice ini mengikuti status pembayaran yang sudah diverifikasi.', 'invoice');
        SiteSetting::set('booking_referral_sources', json_encode(SiteSetting::DEFAULT_BOOKING_REFERRAL_SOURCES, JSON_UNESCAPED_UNICODE), 'booking');
    }
}
