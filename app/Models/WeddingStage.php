<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WeddingStage extends Model
{
    use HasFactory;

    protected $table = 'wedding_stages';

    protected $fillable = ['name', 'photo_path', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function surveys(): HasMany
    {
        return $this->hasMany(Survey::class, 'wedding_stage_id');
    }
}
