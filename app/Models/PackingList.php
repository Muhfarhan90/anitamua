<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PackingList extends Model
{
    use HasFactory;

    public const TYPE_BEFORE = 'before_fitting';

    public const TYPE_AFTER = 'after_fitting';

    protected $fillable = [
        'booking_id', 'type', 'status', 'created_by', 'closed_at',
    ];

    protected function casts(): array
    {
        return [
            'closed_at' => 'datetime',
        ];
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PackingItem::class)->with('inventoryItem');
    }

    public function getMissingItemsAttribute()
    {
        return $this->items()->where('status', PackingItem::STATUS_MISSING)->get();
    }
}
