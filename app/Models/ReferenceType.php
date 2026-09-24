<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReferenceType extends Model
{
    use HasFactory;

    protected $fillable = ['name'];

    public function references(): HasMany
    {
        return $this->hasMany(ClientReference::class);
    }
}
