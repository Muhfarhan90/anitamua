<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->string('maps_url', 500)->nullable()->after('location');
        });

        Schema::table('surveys', function (Blueprint $table) {
            $table->dropColumn(['location', 'maps_url']);
        });
    }

    public function down(): void
    {
        Schema::table('surveys', function (Blueprint $table) {
            $table->string('location')->nullable()->after('booking_id');
            $table->string('maps_url', 500)->nullable()->after('location');
        });

        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn('maps_url');
        });
    }
};
