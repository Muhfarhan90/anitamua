<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fittings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->time('time')->nullable();
            $table->string('pic')->nullable();
            $table->text('notes')->nullable();
            $table->json('photos')->nullable();
            $table->string('status')->default('scheduled'); // scheduled | on_going | finished
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fittings');
    }
};
