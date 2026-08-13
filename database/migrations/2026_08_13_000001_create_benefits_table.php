<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('benefits', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('category')->default('Umum');
            $table->unsignedInteger('sort_order')->default(0);
            $table->string('status')->default('active'); // active | inactive
            $table->timestamps();
        });

        // Pindahkan benefit text lama ke master benefits (kategori default "Umum")
        $distinct = DB::table('package_benefits')->distinct()->pluck('benefit');

        foreach ($distinct as $i => $name) {
            DB::table('benefits')->insert([
                'name' => $name,
                'category' => 'Umum',
                'sort_order' => $i + 1,
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Ubah package_benefits menjadi tabel pivot
        Schema::table('package_benefits', function (Blueprint $table) {
            $table->foreignId('benefit_id')->nullable()->after('package_id')->constrained('benefits')->cascadeOnDelete();
        });

        $rows = DB::table('package_benefits')->get();
        foreach ($rows as $row) {
            $benefitId = DB::table('benefits')->where('name', $row->benefit)->value('id');
            DB::table('package_benefits')->where('id', $row->id)->update(['benefit_id' => $benefitId]);
        }

        Schema::table('package_benefits', function (Blueprint $table) {
            $table->dropColumn('benefit');
            $table->unique(['package_id', 'benefit_id']);
        });
    }

    public function down(): void
    {
        Schema::table('package_benefits', function (Blueprint $table) {
            $table->dropUnique(['package_id', 'benefit_id']);
            $table->string('benefit')->nullable()->after('package_id');
        });

        $rows = DB::table('package_benefits')->get();
        foreach ($rows as $row) {
            $name = DB::table('benefits')->where('id', $row->benefit_id)->value('name');
            DB::table('package_benefits')->where('id', $row->id)->update(['benefit' => $name]);
        }

        Schema::table('package_benefits', function (Blueprint $table) {
            $table->dropForeign(['benefit_id']);
            $table->dropColumn('benefit_id');
        });

        Schema::dropIfExists('benefits');
    }
};
