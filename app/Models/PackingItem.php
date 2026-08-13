<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PackingItem extends Model
{
    use HasFactory;

    public const STATUS_PACKED = 'packed';

    public const STATUS_MISSING = 'missing';

    protected $fillable = ['packing_list_id', 'inventory_item_id', 'status'];

    public function packingList(): BelongsTo
    {
        return $this->belongsTo(PackingList::class);
    }

    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class);
    }
}
