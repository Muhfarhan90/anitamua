<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Vendor extends Model
{
    use HasFactory;

    protected $fillable = [
        'vendor_category_id', 'name', 'phone', 'email', 'instagram',
        'address', 'logo', 'price', 'rating', 'status', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'rating' => 'decimal:1',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(VendorCategory::class, 'vendor_category_id');
    }

    public function packages(): BelongsToMany
    {
        return $this->belongsToMany(Package::class)->withPivot('price')->withTimestamps();
    }

    public function bookingVendors()
    {
        return $this->hasMany(BookingVendor::class);
    }

    public function getProjectCountAttribute(): int
    {
        return $this->bookingVendors()->count();
    }
}
