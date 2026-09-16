<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('bookings', 'discounts')) {
            Schema::table('bookings', function (Blueprint $table) {
                $table->json('discounts')->nullable()->after('discount_note');
            });
        }

        if (! Schema::hasColumn('bookings', 'bonuses')) {
            Schema::table('bookings', function (Blueprint $table) {
                $table->json('bonuses')->nullable()->after('discounts');
            });
        }
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn(['discounts', 'bonuses']);
        });
    }
};
