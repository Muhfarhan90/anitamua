<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fittings', function (Blueprint $table) {
            $table->json('packing_checklist')->nullable()->after('item_sizes');
            $table->string('packing_status')->nullable()->after('packing_checklist');
            $table->foreignId('packing_created_by')->nullable()->after('packing_status')->constrained('users')->nullOnDelete();
            $table->timestamp('packing_closed_at')->nullable()->after('packing_created_by');
        });
    }

    public function down(): void
    {
        Schema::table('fittings', function (Blueprint $table) {
            $table->dropForeign(['packing_created_by']);
            $table->dropColumn([
                'packing_checklist',
                'packing_status',
                'packing_created_by',
                'packing_closed_at',
            ]);
        });
    }
};
