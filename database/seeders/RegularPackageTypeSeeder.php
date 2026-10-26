<?php

namespace Database\Seeders;

use App\Models\PackageType;
use Illuminate\Database\Seeder;

class RegularPackageTypeSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['Engagement', 'Graduation', 'Prewed'] as $name) {
            PackageType::updateOrCreate(['name' => $name], ['display_group' => 'regular']);
        }
    }
}
