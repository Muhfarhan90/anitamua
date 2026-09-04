<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('surveys', function (Blueprint $table) {
            foreach ([
                'gallery_booth', 'envelope_box', 'fruit_shed', 'akad_table', 'diesel_lights',
                'photo_stand', 'carpet', 'vip_table', 'snack_shed', 'blower', 'welcome_sign',
                'center_point',
            ] as $field) {
                $table->string($field.'_other')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('surveys', function (Blueprint $table) {
            $table->dropColumn([
                'gallery_booth_other', 'envelope_box_other', 'fruit_shed_other', 'akad_table_other',
                'diesel_lights_other', 'photo_stand_other', 'carpet_other', 'vip_table_other',
                'snack_shed_other', 'blower_other', 'welcome_sign_other', 'center_point_other',
            ]);
        });
    }
};
