<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Reminder extends Model
{
    use HasFactory;

    public const TYPE_H30 = 'h30';

    public const TYPE_H7 = 'h7';

    public const TYPE_H2 = 'h2';

    public const TYPE_H1 = 'h1';

    protected $fillable = [
        'booking_id', 'type', 'title', 'message',
        'scheduled_at', 'audience', 'channel', 'status', 'sent_at',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_at' => 'date',
            'sent_at' => 'datetime',
        ];
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }
}
