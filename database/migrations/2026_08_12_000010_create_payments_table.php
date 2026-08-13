<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained()->cascadeOnDelete();
            $table->string('type')->comment('dp10 | dp25 | dp75 | pelunasan');
            $table->decimal('amount', 15, 2);
            $table->date('due_date')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->string('method', 50)->nullable(); // transfer | cash | qris
            $table->string('proof')->nullable()->comment('Bukti transfer');
            $table->string('status')->default('pending')->index(); // pending | verified | cancelled
            $table->text('notes')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
