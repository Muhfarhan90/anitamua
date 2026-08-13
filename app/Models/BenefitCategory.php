<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BenefitCategory extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'sort_order'];

    public function benefits(): HasMany
    {
        return $this->hasMany(Benefit::class);
    }
}
