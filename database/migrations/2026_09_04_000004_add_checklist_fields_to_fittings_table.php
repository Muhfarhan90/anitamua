<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const ITEMS = [
        'cpw_busana_akad', 'cpw_stylist_akad', 'cpw_busana_resepsi_1', 'cpw_stylist_resepsi_1',
        'cpw_busana_resepsi_2', 'cpw_stylist_resepsi_2', 'cpw_heels_selop',
        'cpp_busana_akad', 'cpp_busana_resepsi_1', 'cpp_busana_resepsi_2',
        'ukuran_bb_tb_ld', 'among_ibu_hajat', 'among_bapak_hajat', 'among_ibu_besan',
        'among_bapak_besan', 'among_pagar_ayu', 'among_pagar_bagus',
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
