<?php

namespace Database\Seeders;

use App\Models\Benefit;
use App\Models\BenefitCategory;
use App\Models\Package;
use Illuminate\Database\Seeder;

class MakeupPackageSeeder extends Seeder
{
    public function run(): void
    {
        $benefitCategories = [
            'Makeup & Attire' => [
                'Makeup Wedding (Tanpa retouch)',
                'Makeup & Retouch',
                'Sepasang selop pengantin',
                'Accessories/siger/jawa',
                'Accessories Crown etc',
                'Melati Fresh (Premium)',
                'White Henna / Maroon',
                '1 Busana akad CPP CPW',
                '1 Busana resepsi CPP CPW',
                '2 Busana resepsi CPP CPW',
                'Nail Fake',
                'Free Softlens (Normal)',
                'Free Softlens (No Minus)',
                'Hijab Doo',
                'Makeup Ibu hajat & Besan',
                'Makeup pagar ayu 4',
                'Busana Ibu hajat & besan',
                'Busana pager ayu 4',
                'Busana Bapak hajat & besan',
            ],
            'Dokumentasi' => [
                'Foto 1 Roll (Album)',
                'Foto 2 Roll album (80 file foto)',
                'FOTO 2ROLL (album)',
                'Unlimited Photo sesion',
                'ALL File by g.Drive',
                'Foto album magazine 20x30',
                'Foto Album roll',
                'All file by flashdisk',
                'Edited',
                'Video cinematic',
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

        $makeupPackages = [
            [
                'name' => 'Makeup Only', 'sub_type' => 'makeup', 'price' => 2699000, 'color' => '#f5d5e0',
                'benefits' => ['Makeup & Attire' => ['Makeup Wedding (Tanpa retouch)', 'Nail Fake', 'Free Softlens (Normal)']],
            ],
            [
                'name' => 'Akad Only Basic', 'sub_type' => 'akad', 'price' => 4575000, 'color' => '#e8d5f5',
                'benefits' => ['Makeup & Attire' => ['Makeup & Retouch', '1 Busana akad CPP CPW', 'Sepasang selop pengantin', 'Accessories/siger/jawa', 'Melati Fresh (Premium)', 'White Henna / Maroon', 'Softlens (Normal)']],
            ],
            [
                'name' => 'Akad Only', 'sub_type' => 'akad', 'price' => 6975000, 'color' => '#d4739a',
                'benefits' => [
                    'Makeup & Attire' => ['Makeup & Retouch', '1 Busana akad CPP CPW', 'Sepasang selop pengantin', 'Accessories/siger/jawa', 'Melati Fresh (Premium)', 'White Henna / Maroon', 'Softlens (Normal)', 'Makeup Ibu hajat & Besan', 'Makeup pagar ayu 4', 'Busana Ibu hajat & besan', 'Busana pager ayu 4', 'Busana Bapak hajat & besan'],
                    'Dokumentasi' => ['Foto 1 Roll (Album)'],
                ],
            ],
            [
                'name' => 'Makeup & Attire Basic', 'sub_type' => 'makeup_attire', 'price' => 7175000, 'color' => '#60a5fa',
                'benefits' => [
                    'Makeup & Attire' => ['Makeup & Retouch', '1 Busana akad CPP CPW', 'Sepasang selop pengantin', 'Accessories/siger/jawa', 'Melati Fresh (Premium)', 'White Henna / Maroon', '1 Busana resepsi CPP CPW', 'Softlens (Normal)', 'Makeup Ibu hajat & Besan', 'Makeup pagar ayu 4', 'Busana Ibu hajat & besan', 'Busana pager ayu 4', 'Busana Bapak hajat & besan'],
                ],
            ],
            [
                'name' => 'Makeup & Attire Crown', 'sub_type' => 'makeup_attire', 'price' => 7525000, 'color' => '#a855f7',
                'benefits' => [
                    'Makeup & Attire' => ['Makeup & Retouch', '1 Busana akad CPP CPW', 'Sepasang selop pengantin', 'Melati Fresh (Premium)', 'White Henna / Maroon', 'Nail Fake', 'Free Softlens (No Minus)', '2 Busana resepsi', 'Accessories Crown etc', 'Hijab Doo'],
                ],
            ],
            [
                'name' => 'Makeup & Attire Foto', 'sub_type' => 'makeup_attire', 'price' => 8175000, 'color' => '#c9a227',
                'benefits' => [
                    'Makeup & Attire' => ['Makeup & Retouch', '1 Busana akad CPP CPW', 'Sepasang selop pengantin', 'Accessories/siger/jawa', 'Melati Fresh (Premium)', 'White Henna / Maroon', '2 Busana resepsi CPP CPW', 'Softlens (Normal)'],
                    'Dokumentasi' => ['FOTO 2ROLL (album)', 'Unlimited Photo sesion', 'ALL File by g.Drive'],
                ],
            ],
            [
                'name' => 'Makeup & Attire Family', 'sub_type' => 'makeup_attire', 'price' => 9175000, 'color' => '#f97316',
                'benefits' => [
                    'Makeup & Attire' => ['Makeup & Retouch', '1 Busana akad CPP CPW', 'Sepasang selop pengantin', 'Accessories/siger/jawa', 'Melati Fresh (Premium)', 'White Henna / Maroon', '2 Busana resepsi CPP CPW', 'Softlens (Normal)', 'Makeup Ibu hajat & Besan', 'Makeup pagar ayu 4', 'Busana Ibu hajat & besan', 'Busana pager ayu 4', 'Busana Bapak hajat & besan'],
                ],
            ],
            [
                'name' => 'Makeup & Attire Foto + Family', 'sub_type' => 'makeup_attire', 'price' => 10175000, 'color' => '#10b981',
                'benefits' => [
                    'Makeup & Attire' => ['Makeup & Retouch', '1 Busana akad CPP CPW', 'Sepasang selop pengantin', 'Accessories/siger/jawa', 'Melati Fresh (Premium)', 'White Henna / Maroon', '2 Busana resepsi CPP CPW', 'Softlens (Normal)', 'Makeup Ibu hajat & Besan', 'Makeup pagar ayu 4', 'Busana Ibu hajat & besan', 'Busana pager ayu 4', 'Busana Bapak hajat & besan'],
                    'Dokumentasi' => ['Foto 2 Roll album (80 file foto)'],
                ],
            ],
            [
                'name' => 'Makeup & Attire Premium', 'sub_type' => 'makeup_attire', 'price' => 13175000, 'color' => '#ef4444',
                'benefits' => [
                    'Makeup & Attire' => ['Makeup & Retouch', '1 Busana akad CPP CPW', 'Sepasang selop pengantin', 'Accessories/siger/jawa', 'Melati Fresh (Premium)', 'White Henna / Maroon', '2 Busana resepsi CPP CPW', 'Softlens (Normal)', 'Makeup Ibu hajat & Besan', 'Makeup pagar ayu 4', 'Busana Ibu hajat & besan', 'Busana pager ayu 4', 'Busana Bapak hajat & besan'],
                    'Dokumentasi' => ['Foto album magazine 20x30', 'Foto Album roll', 'All file by flashdisk', 'Edited', 'Video cinematic'],
                ],
            ],
        ];

        foreach ($makeupPackages as $data) {
            $package = Package::create([
                'name' => $data['name'],
                'type' => 'makeup',
                'sub_type' => $data['sub_type'],
                'price' => $data['price'],
                'description' => 'Paket '.$data['name'].' dari ANITA Make Up Artist.',
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
