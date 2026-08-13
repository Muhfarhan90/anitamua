<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VendorCategory extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'slug'];

    public function vendors(): HasMany
    {
        return $this->hasMany(Vendor::class);
    }
}
