<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_items', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->foreignId('inventory_category_id')->constrained()->cascadeOnDelete();
            $table->string('color')->nullable();
            $table->string('size')->nullable();
            $table->string('brand')->nullable();
            $table->string('storage_location')->nullable()->comment('Rack, Box, Cabinet');
            $table->string('condition')->default('good'); // good | fair | damaged
            $table->string('status')->default('available'); // available | in_use | out | packing | laundry | damaged | lost
            $table->string('photo')->nullable();
            $table->string('last_used_project')->nullable();
            $table->string('last_pic')->nullable();
            $table->date('purchase_date')->nullable();
            $table->decimal('purchase_price', 15, 2)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_items');
    }
};
