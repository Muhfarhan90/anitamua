<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reminders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained()->cascadeOnDelete();
            $table->string('type'); // h30 | h7 | h2 | h1 | custom
            $table->string('title');
            $table->text('message')->nullable();
            $table->date('scheduled_at');
            $table->string('audience')->default('client'); // client | admin | owner
            $table->string('channel')->default('email'); // email | whatsapp | inapp
            $table->string('status')->default('pending'); // pending | sent | cancelled
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reminders');
    }
};
