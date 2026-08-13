<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('booking_vendors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained()->cascadeOnDelete();
            $table->foreignId('vendor_id')->constrained();
            $table->string('role')->nullable()->comment('Peran vendor pada project ini');
            $table->decimal('price', 15, 2)->nullable()->comment('Harga kesepakatan');
            $table->string('status')->default('confirmed'); // confirmed | changed | cancelled
            $table->timestamps();
            $table->unique(['booking_id', 'vendor_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_vendors');
    }
};
