<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->decimal('package_price', 15, 2)->nullable();
        });

        DB::table('bookings')->orderBy('id')->chunkById(100, function ($bookings) {
            $prices = DB::table('packages')
                ->whereIn('id', collect($bookings)->pluck('package_id')->filter())
                ->pluck('price', 'id');

            foreach ($bookings as $booking) {
                DB::table('bookings')->where('id', $booking->id)->update([
                    'package_price' => $prices[$booking->package_id] ?? null,
                ]);
            }
        });

        foreach ([
            'dp1' => 'DP1',
            'dp10' => 'DP1',
            'dp25' => 'DP 25% (Saat Fitting)',
            'dp75' => 'DP 75% (H-7)',
            'pelunasan' => 'Pelunasan',
        ] as $legacyType => $label) {
            DB::table('payments')->where('type', $legacyType)->update(['type' => $label]);
        }
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn('package_price');
        });
    }
};
