<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique()->comment('Kode booking, ex: AMU-2026-0001');
            $table->foreignId('client_id')->nullable()->constrained('users')->nullOnDelete()->comment('Nullable: akun client dibuat otomatis saat DP 10% diverifikasi (PRD)');
            $table->foreignId('package_id')->constrained();
            $table->string('name');
            $table->string('phone', 30);
            $table->string('email')->nullable();
            $table->date('event_date');
            $table->time('event_time')->nullable();
            $table->string('event_type')->nullable()->default('wedding'); // wedding | prewedding | engagement | adat
            $table->unsignedInteger('number_of_guests')->nullable();
            $table->date('survey_date')->nullable();
            $table->date('fitting_date')->nullable();
            $table->string('location')->nullable();
            $table->text('notes')->nullable();
            $table->string('status')->default('pending')->index();
            // pending (booking masuk) -> booked (DP 10% verified) -> completed | cancelled
            $table->string('cancelled_reason')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
