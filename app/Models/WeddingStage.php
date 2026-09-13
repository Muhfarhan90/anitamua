<?php

namespace App\Models;

use App\Models\Concerns\HasPhotoUrls;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WeddingStage extends Model
{
    use HasFactory;
    use HasPhotoUrls;

    protected $table = 'wedding_stages';

    protected $fillable = ['name', 'photo_path', 'photos', 'is_active'];
    protected $appends = ['photo_urls'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'photos' => 'array'];
    }

    public function surveys(): HasMany
    {
        return $this->hasMany(Survey::class, 'wedding_stage_id');
    }
}
