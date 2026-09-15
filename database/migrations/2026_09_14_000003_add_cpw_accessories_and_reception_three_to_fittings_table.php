<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const ITEMS = [
        'cpw_busana_resepsi_3',
        'cpw_aksesori_kepala_akad',
        'cpw_aksesori_kepala_resepsi_1',
        'cpw_aksesori_kepala_resepsi_2',
        'cpw_aksesori_kepala_resepsi_3',
    ];

    public function up(): void
    {
        Schema::table('fittings', function (Blueprint $table) {
            foreach (self::ITEMS as $item) {
                $table->text($item.'_notes')->nullable();
                $table->string($item.'_photo_path')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('fittings', function (Blueprint $table) {
            foreach (self::ITEMS as $item) {
                $table->dropColumn([$item.'_notes', $item.'_photo_path']);
            }
        });
    }
};
