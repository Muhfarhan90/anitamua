<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gallery', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title')->nullable();
            $table->string('photo');
            $table->string('category')->nullable(); // prewedding | wedding | dll
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gallery');
    }
};
