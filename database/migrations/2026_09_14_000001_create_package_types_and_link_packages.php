<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('package_types', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->timestamps();
        });

        foreach ([
            'makeup' => 'Make Up & Attire',
            'full' => 'Full WO Package',
        ] as $legacyType => $name) {
            DB::table('package_types')->insert([
                'name' => $name,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        Schema::table('packages', function (Blueprint $table) {
            $table->foreignId('package_type_id')->nullable()->after('type')->constrained('package_types')->nullOnDelete();
        });

        $legacyNames = [
            'makeup' => 'Make Up & Attire',
            'full' => 'Full WO Package',
        ];

        foreach (DB::table('packages')->select('type')->distinct()->pluck('type') as $legacyType) {
            $name = $legacyNames[$legacyType] ?? ucwords(str_replace(['_', '-'], ' ', (string) $legacyType));
            $typeId = DB::table('package_types')->where('name', $name)->value('id');

            if (! $typeId) {
                $typeId = DB::table('package_types')->insertGetId([
                    'name' => $name,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            DB::table('packages')->where('type', $legacyType)->update(['package_type_id' => $typeId]);
        }
    }

    public function down(): void
    {
        Schema::table('packages', function (Blueprint $table) {
            $table->dropForeign(['package_type_id']);
            $table->dropColumn('package_type_id');
        });

        Schema::dropIfExists('package_types');
    }
};
