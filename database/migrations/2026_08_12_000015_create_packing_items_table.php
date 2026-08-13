<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('packing_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('packing_list_id')->constrained()->cascadeOnDelete();
            $table->foreignId('inventory_item_id')->constrained()->cascadeOnDelete();
            $table->string('status')->default('packed'); // packed | missing
            $table->timestamps();
            $table->unique(['packing_list_id', 'inventory_item_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('packing_items');
    }
};
