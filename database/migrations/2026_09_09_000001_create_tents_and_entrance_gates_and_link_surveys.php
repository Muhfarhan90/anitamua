<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tents', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('photo_path')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('entrance_gates', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('photo_path')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::table('surveys', function (Blueprint $table) {
            $table->foreignId('tent_id')->nullable()->after('fabric_color')->constrained('tents')->nullOnDelete();
            $table->foreignId('entrance_gate_id')->nullable()->after('tent_shape_other')->constrained('entrance_gates')->nullOnDelete();
        });

        $now = now();
        foreach (['Gelembung', 'Sisir'] as $name) {
            DB::table('tents')->updateOrInsert(['name' => $name], [
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
        foreach (['Gapura', 'Lorong'] as $name) {
            DB::table('entrance_gates')->updateOrInsert(['name' => $name], [
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        DB::table('surveys')->orderBy('id')->chunkById(100, function ($surveys) use ($now) {
            foreach ($surveys as $survey) {
                $tentName = $survey->tent_shape === 'Lainnya'
                    ? $survey->tent_shape_other
                    : $survey->tent_shape;
                $gateName = $survey->entrance === 'Lainnya'
                    ? $survey->entrance_other
                    : $survey->entrance;

                $updates = [];
                if (filled($tentName)) {
                    $tentName = trim($tentName);
                    DB::table('tents')->updateOrInsert(['name' => $tentName], [
                        'is_active' => true,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                    $updates['tent_id'] = DB::table('tents')->where('name', $tentName)->value('id');
                }
                if (filled($gateName)) {
                    $gateName = trim($gateName);
                    DB::table('entrance_gates')->updateOrInsert(['name' => $gateName], [
                        'is_active' => true,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                    $updates['entrance_gate_id'] = DB::table('entrance_gates')->where('name', $gateName)->value('id');
                }

                if ($updates !== []) {
                    DB::table('surveys')->where('id', $survey->id)->update($updates);
                }
            }
        });
    }

    public function down(): void
    {
        Schema::table('surveys', function (Blueprint $table) {
            $table->dropConstrainedForeignId('entrance_gate_id');
            $table->dropConstrainedForeignId('tent_id');
        });

        Schema::dropIfExists('entrance_gates');
        Schema::dropIfExists('tents');
    }
};
