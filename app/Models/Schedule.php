<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Schedule extends Model
{
    use HasFactory;

    public const TYPE_SURVEY = 'survey';

    public const TYPE_FITTING = 'fitting';

    public const TYPE_HARI_H = 'hari_h';

    public const STATUS_SCHEDULED = 'scheduled';

    public const STATUS_ONGOING = 'on_going';

    public const STATUS_FINISHED = 'finished';

    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'booking_id', 'type', 'title', 'date', 'time',
        'location', 'pic_user_id', 'notes', 'status',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'time' => 'datetime:H:i',
        ];
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function picUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pic_user_id');
    }

    public static function typeLabel(string $type): string
    {
        return match ($type) {
            self::TYPE_SURVEY => 'Survey',
            self::TYPE_FITTING => 'Fitting',
            self::TYPE_HARI_H => 'Hari H',
            default => ucfirst($type),
        };
    }
}
