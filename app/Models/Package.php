<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Package extends Model
{
    use HasFactory;

    public const TYPE_MAKEUP = 'makeup';

    public const TYPE_FULL = 'full';

    public static function typeLabel(string $type): string
    {
        return match ($type) {
            self::TYPE_MAKEUP => 'Make Up & Attire',
            self::TYPE_FULL => 'Full WO Package',
            default => ucfirst($type),
        };
    }

    protected $fillable = ['name', 'type', 'sub_type', 'price', 'description', 'color', 'status'];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
        ];
    }

    public function benefits(): BelongsToMany
    {
        return $this->belongsToMany(Benefit::class, 'package_benefits')->withTimestamps();
    }

    public function benefitCategories(): BelongsToMany
    {
        return $this->belongsToMany(BenefitCategory::class, 'package_benefit_categories')->withTimestamps();
    }

    public function vendors(): BelongsToMany
    {
        return $this->belongsToMany(Vendor::class)->withPivot('price')->withTimestamps();
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }
}
