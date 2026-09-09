<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('booking_vendors', function (Blueprint $table) {
            $table->json('custom_additions')->nullable()->after('price');
        });
    }

    public function down(): void
    {
        Schema::table('booking_vendors', function (Blueprint $table) {
            $table->dropColumn('custom_additions');
        });
    }
};
