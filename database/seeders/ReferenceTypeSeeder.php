<?php

namespace Database\Seeders;

use App\Models\ReferenceType;
use Illuminate\Database\Seeder;

class ReferenceTypeSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['Dekor', 'Tenda', 'Foto Prewedding'] as $name) {
            ReferenceType::firstOrCreate(['name' => $name]);
        }
    }
}
