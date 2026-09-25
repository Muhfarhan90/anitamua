<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PackageType extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'is_data_survey', 'is_data_fitting'];

    protected function casts(): array
    {
        return ['is_data_survey' => 'boolean', 'is_data_fitting' => 'boolean'];
    }

    public function packages(): HasMany
    {
        return $this->hasMany(Package::class);
    }
}
