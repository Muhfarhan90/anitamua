<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('surveys', function (Blueprint $table) {
            $table->foreignId('wedding_stage_id')->nullable()->after('booking_id')->constrained('wedding_stages')->nullOnDelete();
            $table->string('flower_color')->nullable();
            $table->string('stage_size')->nullable();
            $table->string('stage_size_other')->nullable();
            $table->string('chair_option')->nullable();
            $table->string('chair_option_other')->nullable();
            $table->string('stage_option')->nullable();
            $table->string('stage_option_other')->nullable();
            $table->string('fabric_color')->nullable();
            $table->json('tent_sizes')->nullable();
            $table->string('tent_sizes_other')->nullable();
            $table->json('tent_additions')->nullable();
            $table->string('tent_additions_other')->nullable();
            $table->string('tent_shape')->nullable();
            $table->string('tent_shape_other')->nullable();
            $table->string('entrance')->nullable();
            $table->string('entrance_other')->nullable();
            $table->string('buffet')->nullable();
            $table->string('buffet_other')->nullable();
            $table->string('tableware')->nullable();
            $table->string('tableware_other')->nullable();
            $table->string('gallery_booth')->nullable();
            $table->string('envelope_box')->nullable();
            $table->string('fruit_shed')->nullable();
            $table->string('akad_table')->nullable();
            $table->string('diesel_lights')->nullable();
            $table->string('photo_stand')->nullable();
            $table->string('carpet')->nullable();
            $table->string('vip_table')->nullable();
            $table->string('snack_shed')->nullable();
            $table->string('blower')->nullable();
            $table->string('welcome_sign')->nullable();
            $table->string('center_point')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('surveys', function (Blueprint $table) {
            $table->dropForeign(['wedding_stage_id']);
            $table->dropColumn([
                'wedding_stage_id', 'flower_color', 'stage_size', 'stage_size_other',
                'chair_option', 'chair_option_other', 'stage_option', 'stage_option_other',
                'fabric_color', 'tent_sizes', 'tent_sizes_other', 'tent_additions',
                'tent_additions_other', 'tent_shape', 'tent_shape_other', 'entrance',
                'entrance_other', 'buffet', 'buffet_other', 'tableware', 'tableware_other',
                'gallery_booth', 'envelope_box', 'fruit_shed', 'akad_table', 'diesel_lights',
                'photo_stand', 'carpet', 'vip_table', 'snack_shed', 'blower', 'welcome_sign',
                'center_point',
            ]);
        });
    }
};
