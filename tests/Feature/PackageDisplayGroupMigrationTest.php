<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

it('backfills regular package names without moving wedding packages', function () {
    $originalConnection = DB::getDefaultConnection();
    config()->set('database.connections.package_group_test', [
        'driver' => 'sqlite',
        'database' => ':memory:',
        'prefix' => '',
    ]);
    DB::setDefaultConnection('package_group_test');

    try {
        Schema::create('package_types', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->boolean('is_data_survey')->default(true);
            $table->boolean('is_data_fitting')->default(true);
            $table->timestamps();
        });
        Schema::create('packages', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('package_type_id')->nullable();
        });

        $weddingType = DB::table('package_types')->insertGetId(['name' => 'Make Up & Attire', 'is_data_fitting' => false]);
        $regularType = DB::table('package_types')->insertGetId(['name' => 'Prewed Artist']);
        DB::table('packages')->insert([
            ['name' => 'Paket Engangement Deluxe', 'package_type_id' => $weddingType],
            ['name' => 'Paket Graduation', 'package_type_id' => $weddingType],
            ['name' => 'Paket Pre-wedding', 'package_type_id' => $weddingType],
            ['name' => 'Paket Wedding Akad', 'package_type_id' => $weddingType],
            ['name' => 'Basic', 'package_type_id' => $regularType],
        ]);

        (require database_path('migrations/2026_10_26_000001_add_display_group_to_package_types_table.php'))->up();

        foreach ([
            'Paket Engangement Deluxe' => 'Engagement',
            'Paket Graduation' => 'Graduation',
            'Paket Pre-wedding' => 'Prewed',
            'Paket Wedding Akad' => 'Make Up & Attire',
            'Basic' => 'Prewed Artist',
        ] as $packageName => $typeName) {
            $type = DB::table('packages')
                ->join('package_types', 'packages.package_type_id', '=', 'package_types.id')
                ->where('packages.name', $packageName)
                ->select('package_types.name', 'package_types.display_group')
                ->first();

            expect($type->name)->toBe($typeName);
            expect($type->display_group)->toBe($typeName === 'Make Up & Attire' ? 'wedding' : 'regular');
        }

        expect(DB::table('package_types')->where('name', 'Engagement')->value('is_data_fitting'))->toBe(0);
    } finally {
        DB::setDefaultConnection($originalConnection);
        DB::purge('package_group_test');
    }
});
