<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('surveys', function (Blueprint $table) {
            $table->string('wedding_stage_photo_path')->nullable()->after('wedding_stage_id');
            $table->string('tent_photo_path')->nullable()->after('tent_id');
            $table->string('entrance_gate_photo_path')->nullable()->after('entrance_gate_id');
        });
    }

    public function down(): void
    {
        Schema::table('surveys', function (Blueprint $table) {
            $table->dropColumn(['wedding_stage_photo_path', 'tent_photo_path', 'entrance_gate_photo_path']);
        });
    }
};
