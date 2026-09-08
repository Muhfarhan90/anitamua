<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gallery', function (Blueprint $table) {
            $table->json('photos')->nullable()->after('photo');
        });

        $now = now();
        DB::table('site_settings')->insertOrIgnore([
            [
                'key' => 'landing_hero_image',
                'value' => 'https://images.unsplash.com/photo-1519741497674-611481863552?q=80&w=1920&auto=format&fit=crop',
                'group' => 'content',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'about_image',
                'value' => 'https://images.unsplash.com/photo-1583939003579-730e3918a45a?q=80&w=900&auto=format&fit=crop',
                'group' => 'content',
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
    }

    public function down(): void
    {
        Schema::table('gallery', function (Blueprint $table) {
            $table->dropColumn('photos');
        });

        DB::table('site_settings')->whereIn('key', ['landing_hero_image', 'about_image'])->delete();
    }
};
