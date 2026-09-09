<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookingVendor extends Model
{
    use HasFactory;

    protected $fillable = ['booking_id', 'vendor_id', 'role', 'price', 'custom_additions', 'status'];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'custom_additions' => 'array',
        ];
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function getCustomAdditionsTotalAttribute(): float
    {
        return (float) collect($this->custom_additions ?? [])->sum('price');
    }

    public function getTotalPriceAttribute(): float
    {
        return (float) $this->price + $this->custom_additions_total;
    }
}
