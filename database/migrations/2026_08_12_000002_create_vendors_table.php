<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vendors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_category_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('phone', 30)->nullable();
            $table->string('email')->nullable();
            $table->string('instagram', 100)->nullable();
            $table->string('address')->nullable();
            $table->string('logo')->nullable();
            $table->decimal('price', 15, 2)->default(0)->comment('Harga kerjasama');
            $table->decimal('rating', 2, 1)->default(0)->comment('Rating internal 0-5');
            $table->string('status')->default('active'); // active | busy | inactive
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vendors');
    }
};
