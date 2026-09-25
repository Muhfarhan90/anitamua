<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('package_types', function (Blueprint $table) {
            $table->boolean('is_data_survey')->default(true);
            $table->boolean('is_data_fitting')->default(true);
        });
    }

    public function down(): void
    {
        Schema::table('package_types', function (Blueprint $table) {
            $table->dropColumn(['is_data_survey', 'is_data_fitting']);
        });
    }
};
