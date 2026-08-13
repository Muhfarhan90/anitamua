<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('benefit_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        // Pindahkan kategori text ke master benefit_categories
        $distinct = DB::table('benefits')->distinct()->pluck('category');

        foreach ($distinct as $i => $name) {
            DB::table('benefit_categories')->insert([
                'name' => $name,
                'sort_order' => $i + 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        Schema::table('benefits', function (Blueprint $table) {
            $table->foreignId('benefit_category_id')->nullable()->after('category')->constrained('benefit_categories')->cascadeOnDelete();
        });

        $rows = DB::table('benefits')->get();
        foreach ($rows as $row) {
            $categoryId = DB::table('benefit_categories')->where('name', $row->category)->value('id');
            DB::table('benefits')->where('id', $row->id)->update(['benefit_category_id' => $categoryId]);
        }

        Schema::table('benefits', function (Blueprint $table) {
            $table->dropColumn('category');
        });
    }

    public function down(): void
    {
        Schema::table('benefits', function (Blueprint $table) {
            $table->string('category')->default('Umum')->after('name');
        });

        $rows = DB::table('benefits')->get();
        foreach ($rows as $row) {
            $name = DB::table('benefit_categories')->where('id', $row->benefit_category_id)->value('name');
            DB::table('benefits')->where('id', $row->id)->update(['category' => $name ?? 'Umum']);
        }

        Schema::table('benefits', function (Blueprint $table) {
            $table->dropForeign(['benefit_category_id']);
            $table->dropColumn('benefit_category_id');
        });

        Schema::dropIfExists('benefit_categories');
    }
};
