<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('package_types', function (Blueprint $table) {
            $table->string('display_group', 20)->default('wedding');
        });

        $regularNames = [
            'Engagement' => ['%engagement%', '%engangement%'],
            'Graduation' => ['%graduation%'],
            'Prewed' => ['%prewed%', '%pre-wed%', '%pre wedding%'],
        ];

        foreach ($regularNames as $name => $patterns) {
            $matchingNames = function ($query) use ($patterns) {
                foreach ($patterns as $pattern) {
                    $query->orWhereRaw('LOWER(name) LIKE ?', [$pattern]);
                }
            };

            DB::table('package_types')->where($matchingNames)->update(['display_group' => 'regular']);

            $packages = DB::table('packages')
                ->where($matchingNames)
                ->where(function ($query) use ($matchingNames) {
                    $query->whereNull('package_type_id')
                        ->orWhereNotIn('package_type_id', DB::table('package_types')->where($matchingNames)->select('id'));
                });

            if (! $packages->exists()) {
                continue;
            }

            $typeId = DB::table('package_types')->whereRaw('LOWER(name) = ?', [strtolower($name)])->value('id');
            if (! $typeId) {
                $sourceTypeId = (clone $packages)->whereNotNull('package_type_id')->orderBy('id')->value('package_type_id');
                $sourceType = $sourceTypeId ? DB::table('package_types')->where('id', $sourceTypeId)->first() : null;
                $typeId = DB::table('package_types')->insertGetId([
                    'name' => $name,
                    'display_group' => 'regular',
                    'is_data_survey' => $sourceType?->is_data_survey ?? true,
                    'is_data_fitting' => $sourceType?->is_data_fitting ?? true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            $packages->update(['package_type_id' => $typeId]);
        }
    }

    public function down(): void
    {
        // Keep package links: restoring old links could overwrite later admin edits.
        Schema::table('package_types', function (Blueprint $table) {
            $table->dropColumn('display_group');
        });
    }
};
