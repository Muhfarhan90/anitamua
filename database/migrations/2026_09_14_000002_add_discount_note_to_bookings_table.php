<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->string('discount_note')->nullable()->after('discount_value');
            $table->json('discounts')->nullable()->after('discount_note');
            $table->json('bonuses')->nullable()->after('discounts');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn(['discount_note', 'discounts', 'bonuses']);
        });
    }
};
