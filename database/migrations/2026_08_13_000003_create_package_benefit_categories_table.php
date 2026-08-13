<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('package_benefit_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('package_id')->constrained()->cascadeOnDelete();
            $table->foreignId('benefit_category_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['package_id', 'benefit_category_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('package_benefit_categories');
    }
};
