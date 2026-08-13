<?php

namespace Database\Seeders;

use App\Models\Benefit;
use App\Models\BenefitCategory;
use App\Models\Package;
use App\Services\ActivityLogger;
use Illuminate\Database\Seeder;

class WeddingPackageSeeder extends Seeder
{
    public function run(): void
    {
        $benefitCategories = [
            'Makeup & Attire' => [
                'Makeup & Retouch',
                'Acc Adat (Non paes)',
                'Fresh Melati',
                '1 Pasang Busana Akad',
                '2 Pasang Busana Resepsi',
                '2 Pasang Busana Ibu + Makeup',
                '2 Pasang Busana Bapak',
                '4 Pasang Busana Pagar Ayu + Makeup',
                '2 Makeup Family',
                '4 Makeup Family',
                '4pax Makeup Family',
            ],
            'Decoration' => [
                'Pelaminan 4m',
                'Pelaminan 6m',
                'Pelaminan 8m',
                'Pelaminan 12m',
                'Seat Sofa Pelaminan',
                'Seat Meja Akad',
                'Bunga Artificial',
                'Hand Bouquet',
                'Welcome Sign',
                'Kotak Amplop',
                'Meja Tamu',
                'Standing Mirror & Photo',
                'Dekorasi Lorong',
                'Center Point',
                'Photobooth',
                'Artifical Mix Fresh Flower',
                'Lighting Him (total Bunyi)',
                'Voging',
                'Full Kain Tenda',
                'Dekorasi Lorong + Lighting',
            ],
            'Tenda & Peralatan' => [
                '4 Lokal Tenda Sisir (4x4)',
                '1 Lokal Tenda (8x8)',
                '2 Lokal Tenda (8x8)',
                '4 Lokal Tenda (4x4)',
                '2 Lokal Tenda Gelembung (8x10)',
                '100 Kursi + Cover',
                '150 Kursi + Cover',
                '200 Kursi + Cover',
                '300 Kursi + Cover',
                '1 Seat Prasmanan (Garpu,sendok piring 100pcs)',
                '1 Seat Prasmanan Standart (Garpu,sendok piring 100pcs)',
                '1 Seat Prasmanan Rolltop (Garpu,sendok piring 100pcs)',
                '1 Unit Blower',
                '2 Unit Blower',
                '3 Unit Blower',
                'Karpet Jalan',
                'Full Karpet',
                'Lampu Diesel 2 Malam',
                'Full Accesoris Tenda',
            ],
            'Dokumentasi' => [
                'Album 2 Roll',
                'Album Roll',
                'Album Roll Magnetik',
                'Album Hardcover',
                'Album Magazine',
                'Album Magnetic 2 Roll',
                '80 File Cetak',
                'All File by G.drive',
                'All File by Flashdisk',
                'Video Cinematic',
                'Video Cinematic 3-5 Menit',
                'Video Teaser 30 dtk',
                'Video Liputan',
            ],
            'Wedding Organizer' => [
                'MC Akad & Resepsi',
                '3 Crew One Day Service (Manager Single Bridal, Bridal Assistant Crew, Runner Crew)',
                'Buku Panduan Pernikahan',
                '2 Buku Tamu Undangan Eksklusif',
                '2 Coventy Party',
                'HT 2pcs',
                'VIP Reservasi Label',
                'Home Visit Konsultasi',
                'Meeting Vendor 1x',
                'Durasi Maksimal 7jam',
            ],
            'Entertainment' => [
                'MAPAG PENGANTIN (1 Basku, 1 Ambu, 4 Pemayang, Tari Persembahan)',
                'LIVE MUSIK',
                'Soundsystem (2 Vocal, Saxo, Gitar, Drummer, Player, Bass)',
            ],
            'Free' => [
                'White Henna/Nude',
                'Fake Nails',
                'Softlens (No minus)',
                'MC Akad',
                'Ballon Hellium 30pcs',
            ],
        ];

        $benefitIds = [];
        foreach ($benefitCategories as $categoryName => $names) {
            $category = BenefitCategory::firstOrCreate(['name' => $categoryName], ['sort_order' => BenefitCategory::max('sort_order') + 1]);
            foreach ($names as $i => $name) {
                $benefit = Benefit::firstOrCreate(['name' => $name], ['benefit_category_id' => $category->id, 'sort_order' => $i + 1, 'status' => 'active']);
                $benefitIds[$name] = $benefit->id;
            }
        }

        $weddingPackages = [
            [
                'name' => 'Daisy', 'sub_type' => 'rumahan', 'price' => 15500000, 'color' => '#f5d5e0',
                'benefits' => [
                    'Makeup & Attire' => ['Makeup & Retouch', 'Acc Adat (Non paes)', 'Fresh Melati', '1 Pasang Busana Akad', '2 Pasang Busana Resepsi', '2 Pasang Busana Ibu + Makeup', '2 Pasang Busana Bapak', '4 Pasang Busana Pagar Ayu + Makeup'],
                    'Decoration' => ['Pelaminan 4m', 'Seat Sofa Pelaminan', 'Seat Meja Akad', 'Bunga Artificial', 'Hand Bouquet', 'Welcome Sign', 'Kotak Amplop', 'Meja Tamu', 'Standing Mirror & Photo'],
                    'Tenda & Peralatan' => ['4 Lokal Tenda Sisir (4x4)', '100 Kursi + Cover', '1 Seat Prasmanan (Garpu,sendok piring 100pcs)', '1 Unit Blower', 'Karpet Jalan'],
                    'Dokumentasi' => ['Album 2 Roll', '80 File Cetak'],
                    'Free' => ['White Henna/Nude', 'Fake Nails', 'Softlens (No minus)'],
                ],
            ],
            [
                'name' => 'Dandelion', 'sub_type' => 'rumahan', 'price' => 17500000, 'color' => '#e8d5f5',
                'benefits' => [
                    'Makeup & Attire' => ['Makeup & Retouch', 'Acc Adat (Non paes)', 'Fresh Melati', '1 Pasang Busana Akad', '2 Pasang Busana Resepsi', '2 Pasang Busana Ibu + Makeup', '2 Pasang Busana Bapak', '4 Pasang Busana Pagar Ayu + Makeup'],
                    'Decoration' => ['Pelaminan 6m', 'Seat Sofa Pelaminan', 'Seat Meja Akad', 'Bunga Artificial', 'Hand Bouquet', 'Welcome Sign', 'Kotak Amplop', 'Meja Tamu', 'Standing Mirror & Photo'],
                    'Tenda & Peralatan' => ['4 Lokal Tenda Sisir (4x4)', '100 Kursi + Cover', '1 Seat Prasmanan Standart (Garpu,sendok piring 100pcs)', '1 Unit Blower', 'Karpet Jalan', 'Lampu Diesel 2 Malam'],
                    'Dokumentasi' => ['Album Roll', '80 File Cetak', 'All File by G.drive'],
                    'Free' => ['White Henna/Nude', 'Fake Nails', 'Softlens (No minus)'],
                ],
            ],
            [
                'name' => 'Aster', 'sub_type' => 'gedung', 'price' => 22500000, 'color' => '#c9a227',
                'benefits' => [
                    'Makeup & Attire' => ['Makeup & Retouch', 'Acc Adat (Non paes)', 'Fresh Melati', '1 Pasang Busana Akad', '2 Pasang Busana Resepsi', '2 Pasang Busana Ibu + Makeup', '2 Pasang Busana Bapak', '4 Pasang Busana Pagar Ayu + Makeup', '2 Makeup Family'],
                    'Decoration' => ['Pelaminan 8m', 'Seat Sofa Pelaminan', 'Seat Meja Akad', 'Bunga Artificial', 'Hand Bouquet', 'Welcome Sign', 'Kotak Amplop', 'Meja Tamu', 'Standing Mirror & Photo'],
                    'Tenda & Peralatan' => ['1 Lokal Tenda (8x8)', '2 Lokal Tenda (4x4)', '150 Kursi + Cover', 'Karpet Jalan', '1 Seat Prasmanan Rolltop (Garpu,sendok piring 100pcs)', '1 Unit Blower', 'Lampu Diesel 2 Malam'],
                    'Dokumentasi' => ['Album Roll Magnetik', '80 File Cetak', 'All File by G.drive', 'Video Cinematic'],
                    'Free' => ['MC Akad', 'White Henna/Nude', 'Fake Nails', 'Softlens (No minus)'],
                ],
            ],
            [
                'name' => 'Chaliteya', 'sub_type' => 'gedung', 'price' => 26500000, 'color' => '#60a5fa',
                'benefits' => [
                    'Makeup & Attire' => ['Makeup & Retouch', 'Acc Adat (Non paes)', 'Fresh Melati', '1 Pasang Busana Akad', '2 Pasang Busana Resepsi', '2 Pasang Busana Ibu + Makeup', '2 Pasang Busana Bapak', '4 Pasang Busana Pagar Ayu + Makeup', '2 Makeup Family'],
                    'Decoration' => ['Pelaminan 8m', 'Seat Sofa Pelaminan', 'Seat Meja Akad', 'Bunga Artificial', 'Hand Bouquet', 'Welcome Sign', 'Kotak Amplop', 'Meja Tamu', 'Standing Mirror & Photo'],
                    'Tenda & Peralatan' => ['1 Lokal Tenda (8x8)', '4 Lokal Tenda (4x4)', '150 Kursi + Cover', 'Full Karpet', '1 Seat Prasmanan Rolltop (Garpu,sendok piring 100pcs)', '1 Unit Blower', 'Lampu Diesel 2 Malam'],
                    'Dokumentasi' => ['Album Hardcover', 'All File by Flashdisk', 'Video Cinematic'],
                    'Free' => ['MC Akad', 'White Henna/Nude', 'Fake Nails', 'Softlens (No minus)'],
                ],
            ],
            [
                'name' => 'Gardenia', 'sub_type' => 'gedung', 'price' => 32500000, 'color' => '#a855f7',
                'benefits' => [
                    'Makeup & Attire' => ['Makeup & Retouch', 'Acc Adat (Non paes)', 'Fresh Melati', '1 Pasang Busana Akad', '2 Pasang Busana Resepsi', '2 Pasang Busana Ibu + Makeup', '4 Pasang Busana Pagar Ayu + Makeup', '4 Makeup Family'],
                    'Decoration' => ['Pelaminan 8m', 'Seat Sofa Pelaminan', 'Seat Meja Akad', 'Artifical Mix Fresh Flower', 'Hand Bouquet', 'Center Point', 'Welcome Sign', 'Dekorasi Lorong', 'Kotak Amplop', 'Meja Tamu', 'Standing Mirror & Photo', 'Lighting Him (total Bunyi)', 'Voging'],
                    'Tenda & Peralatan' => ['2 Lokal Tenda (8x8)', '4 Lokal Tenda (4x4)', '150 Kursi + Cover', 'Full Karpet', 'Full Kain Tenda', '1 Seat Prasmanan Rolltop (Garpu,sendok piring 100pcs)', '1 Unit Blower', 'Lampu Diesel 2 Malam'],
                    'Dokumentasi' => ['Album Hardcover', 'Album Magnetic 2 Roll', 'All File by Flashdisk', 'Video Cinematic', 'Video Teaser 30 dtk', 'Video Liputan'],
                    'Free' => ['Ballon Hellium 30pcs', 'MC Akad', 'White Henna/Nude', 'Fake Nails', 'Softlens (No minus)'],
                ],
            ],
            [
                'name' => 'Platinum 1', 'sub_type' => 'gedung', 'price' => 34500000, 'color' => '#d4739a',
                'benefits' => [
                    'Makeup & Attire' => ['Makeup & Retouch', 'Acc Adat (Non paes)', 'Fresh Melati', '1 Pasang Busana Akad', '2 Pasang Busana Resepsi', '2 Pasang Busana Ibu + Makeup', '2 Pasang Busana Bapak', '4 Pasang Busana Pagar Ayu + Makeup', '4pax Makeup Family'],
                    'Decoration' => ['Pelaminan 8m', 'Seat Sofa Pelaminan', 'Seat Meja Akad', 'Artifical Mix Fresh Flower', 'Hand Bouquet', 'Center Point', 'Dekorasi Lorong', 'Welcome Sign', 'Kotak Amplop', 'Meja Tamu', 'Standing Mirror & Photo', 'Lighting Him (total Bunyi)', 'Voging'],
                    'Tenda & Peralatan' => ['150 Kursi + Cover', '1 Seat Prasmanan Rolltop (Garpu,sendok piring 100pcs)', '2 Unit Blower'],
                    'Wedding Organizer' => ['MC Akad & Resepsi', '3 Crew One Day Service (Manager Single Bridal, Bridal Assistant Crew, Runner Crew)', 'Buku Panduan Pernikahan', '2 Buku Tamu Undangan Eksklusif', '2 Coventy Party', 'HT 2pcs', 'VIP Reservasi Label', 'Home Visit Konsultasi', 'Meeting Vendor 1x', 'Durasi Maksimal 7jam'],
                    'Dokumentasi' => ['Album Magazine', 'Album Magnetic 2 Roll', 'All File by Flashdisk', 'Video Cinematic 3-5 Menit', 'Video Teaser 30 dtk', 'Video Liputan'],
                    'Free' => ['Ballon Hellium 30pcs', 'White Henna/Nude', 'Fake Nails', 'Softlens (No minus)'],
                ],
            ],
            [
                'name' => 'Platinum 2', 'sub_type' => 'gedung', 'price' => 37500000, 'color' => '#d4739a',
                'benefits' => [
                    'Makeup & Attire' => ['Makeup & Retouch', 'Acc Adat (Non paes)', 'Fresh Melati', '1 Pasang Busana Akad', '2 Pasang Busana Resepsi', '2 Pasang Busana Ibu + Makeup', '2 Pasang Busana Bapak', '4 Pasang Busana Pagar Ayu + Makeup', '4pax Makeup Family'],
                    'Decoration' => ['Pelaminan 8m', 'Seat Sofa Pelaminan', 'Seat Meja Akad', 'Artifical Mix Fresh Flower', 'Hand Bouquet', 'Center Point', 'Dekorasi Lorong', 'Photobooth', 'Welcome Sign', 'Kotak Amplop', 'Meja Tamu', 'Standing Mirror & Photo', 'Lighting Him (total Bunyi)', 'Voging'],
                    'Tenda & Peralatan' => ['200 Kursi + Cover', '1 Seat Prasmanan Rolltop (Garpu,sendok piring 100pcs)', '3 Unit Blower'],
                    'Wedding Organizer' => ['MC Akad & Resepsi', '3 Crew One Day Service (Manager Single Bridal, Bridal Assistant Crew, Runner Crew)', 'Buku Panduan Pernikahan', '2 Buku Tamu Undangan Eksklusif', '2 Coventy Party', 'HT 2pcs', 'VIP Reservasi Label', 'Home Visit Konsultasi', 'Meeting Vendor 1x', 'Durasi Maksimal 7jam'],
                    'Entertainment' => ['LIVE MUSIK', 'Soundsystem (2 Vocal, Saxo, Gitar, Drummer, Player, Bass)'],
                    'Dokumentasi' => ['Album Magazine', 'Album Magnetic 2 Roll', 'All File by Flashdisk', 'Video Cinematic 3-5 Menit', 'Video Teaser 30 dtk', 'Video Liputan'],
                    'Free' => ['Ballon Hellium 30pcs', 'White Henna/Nude', 'Fake Nails', 'Softlens (No minus)'],
                ],
            ],
            [
                'name' => 'Platinum 3', 'sub_type' => 'gedung', 'price' => 39500000, 'color' => '#d4739a',
                'benefits' => [
                    'Makeup & Attire' => ['Makeup & Retouch', 'Acc Adat (Non paes)', 'Fresh Melati', '1 Pasang Busana Akad', '2 Pasang Busana Resepsi', '2 Pasang Busana Ibu + Makeup', '2 Pasang Busana Bapak', '4 Pasang Busana Pagar Ayu + Makeup', '4pax Makeup Family'],
                    'Decoration' => ['Pelaminan 8m', 'Seat Sofa Pelaminan', 'Seat Meja Akad', 'Artifical Mix Fresh Flower', 'Hand Bouquet', 'Center Point', 'Dekorasi Lorong', 'Photobooth', 'Welcome Sign', 'Kotak Amplop', 'Meja Tamu', 'Standing Mirror & Photo', 'Lighting Him (total Bunyi)', 'Voging'],
                    'Tenda & Peralatan' => ['300 Kursi + Cover', 'Full Karpet', '1 Seat Prasmanan Rolltop (Garpu,sendok piring 100pcs)', '3 Unit Blower'],
                    'Wedding Organizer' => ['MC Akad & Resepsi', '3 Crew One Day Service (Manager Single Bridal, Bridal Assistant Crew, Runner Crew)', 'Buku Panduan Pernikahan', '2 Buku Tamu Undangan Eksklusif', '2 Coventy Party', 'HT 2pcs', 'VIP Reservasi Label', 'Home Visit Konsultasi', 'Meeting Vendor 1x', 'Durasi Maksimal 7jam'],
                    'Dokumentasi' => ['Album Magazine', 'Album Magnetic 2 Roll', 'All File by Flashdisk', 'Video Cinematic 3-5 Menit', 'Video Teaser 30 dtk', 'Video Liputan'],
                    'Free' => ['Ballon Hellium 30pcs', 'White Henna/Nude', 'Fake Nails', 'Softlens (No minus)'],
                ],
            ],
            [
                'name' => 'Platinum 4', 'sub_type' => 'gedung', 'price' => 45500000, 'color' => '#d4739a',
                'benefits' => [
                    'Makeup & Attire' => ['Makeup & Retouch', 'Acc Adat (Non paes)', 'Fresh Melati', '1 Pasang Busana Akad', '2 Pasang Busana Resepsi', '2 Pasang Busana Ibu + Makeup', '2 Pasang Busana Bapak', '4 Pasang Busana Pagar Ayu + Makeup', '4pax Makeup Family'],
                    'Decoration' => ['Pelaminan 12m', 'Seat Sofa Pelaminan', 'Seat Meja Akad', 'Artifical Mix Fresh Flower', 'Hand Bouquet', 'Center Point', 'Dekorasi Lorong', 'Photobooth', 'Welcome Sign', 'Kotak Amplop', 'Meja Tamu', 'Standing Mirror & Photo', 'Lighting Him (total Bunyi)', 'Voging'],
                    'Tenda & Peralatan' => ['300 Kursi + Cover', 'Full Karpet', '1 Seat Prasmanan Rolltop (Garpu,sendok piring 100pcs)', '2 Unit Blower'],
                    'Wedding Organizer' => ['MC Akad & Resepsi', '3 Crew One Day Service (Manager Single Bridal, Bridal Assistant Crew, Runner Crew)', 'Buku Panduan Pernikahan', '2 Buku Tamu Undangan Eksklusif', '2 Coventy Party', 'HT 2pcs', 'VIP Reservasi Label', 'Home Visit Konsultasi', 'Meeting Vendor 1x', 'Durasi Maksimal 7jam'],
                    'Entertainment' => ['MAPAG PENGANTIN (1 Basku, 1 Ambu, 4 Pemayang, Tari Persembahan)', 'LIVE MUSIK', 'Soundsystem (2 Vocal, Saxo, Gitar, Drummer, Player, Bass)'],
                    'Dokumentasi' => ['Album Magazine', 'Album Magnetic 2 Roll', 'All File by Flashdisk', 'Video Cinematic 3-5 Menit', 'Video Teaser 30 dtk', 'Video Liputan'],
                    'Free' => ['Ballon Hellium 30pcs', 'White Henna/Nude', 'Fake Nails', 'Softlens (No minus)'],
                ],
            ],
            [
                'name' => 'Platinum 5', 'sub_type' => 'gedung', 'price' => 50500000, 'color' => '#d4739a',
                'benefits' => [
                    'Makeup & Attire' => ['Makeup & Retouch', 'Acc Adat (Non paes)', 'Fresh Melati', '1 Pasang Busana Akad', '2 Pasang Busana Resepsi', '2 Pasang Busana Ibu + Makeup', '2 Pasang Busana Bapak', '4 Pasang Busana Pagar Ayu + Makeup', '4pax Makeup Family'],
                    'Decoration' => ['Pelaminan 8m', 'Seat Sofa Pelaminan', 'Seat Meja Akad', 'Artifical Mix Fresh Flower', 'Hand Bouquet', 'Center Point', 'Dekorasi Lorong', 'Photobooth', 'Welcome Sign', 'Kotak Amplop', 'Meja Tamu', 'Standing Mirror & Photo', 'Lighting Him (total Bunyi)', 'Voging'],
                    'Tenda & Peralatan' => ['2 Lokal Tenda Gelembung (8x10)', '4 Lokal Tenda (4x4)', 'Full Accesoris Tenda', 'Full Karpet', 'Full Kain Tenda', '1 Seat Prasmanan Rolltop (Garpu,sendok piring 100pcs)', '1 Unit Blower', 'Lampu Diesel 2 Malam', 'Voging'],
                    'Wedding Organizer' => ['MC Akad & Resepsi', '3 Crew One Day Service (Manager Single Bridal, Bridal Assistant Crew, Runner Crew)', 'Buku Panduan Pernikahan', '2 Buku Tamu Undangan Eksklusif', '2 Coventy Party', 'HT 2pcs', 'VIP Reservasi Label', 'Home Visit Konsultasi', 'Meeting Vendor 1x', 'Durasi Maksimal 7jam'],
                    'Entertainment' => ['MAPAG PENGANTIN (1 Basku, 1 Ambu, 4 Pemayang, Tari Persembahan)', 'LIVE MUSIK', 'Soundsystem (2 Vocal, Saxo, Gitar, Drummer, Player, Bass)'],
                    'Dokumentasi' => ['Album Magazine', 'Album Magnetic 2 Roll', 'All File by Flashdisk', 'Video Cinematic 3-5 Menit', 'Video Teaser 30 dtk', 'Video Liputan'],
                    'Free' => ['Ballon Hellium 30pcs', 'MC Akad', 'White Henna/Nude', 'Fake Nails', 'Softlens (No minus)'],
                ],
            ],
        ];

        foreach ($weddingPackages as $data) {
            $package = Package::create([
                'name' => $data['name'],
                'type' => 'full',
                'sub_type' => $data['sub_type'],
                'price' => $data['price'],
                'description' => 'Paket '.$data['name'].' — Full Wedding Package dari ANITA Make Up Artist.',
                'color' => $data['color'],
                'status' => 'active',
            ]);

            $syncBenefits = [];
            $syncCategories = [];
            foreach ($data['benefits'] as $categoryName => $benefitNames) {
                foreach ($benefitNames as $benefitName) {
                    if (isset($benefitIds[$benefitName])) {
                        $syncBenefits[] = $benefitIds[$benefitName];
                    }
                }
                $category = BenefitCategory::where('name', $categoryName)->first();
                if ($category) {
                    $syncCategories[] = $category->id;
                }
            }
            $package->benefits()->sync(array_unique($syncBenefits));
            $package->benefitCategories()->sync(array_unique($syncCategories));
        }
    }
}
