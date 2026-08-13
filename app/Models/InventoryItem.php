<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InventoryItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'code', 'name', 'inventory_category_id',
        'color', 'size', 'brand', 'storage_location',
        'condition', 'status', 'photo',
        'last_used_project', 'last_pic',
        'purchase_date', 'purchase_price',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'purchase_date' => 'date',
            'purchase_price' => 'decimal:2',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(InventoryCategory::class, 'inventory_category_id');
    }

    public function packingItems(): HasMany
    {
        return $this->hasMany(PackingItem::class);
    }
}
