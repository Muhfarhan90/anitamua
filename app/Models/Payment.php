<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    use HasFactory;

    public const TYPE_DP10 = 'dp10';

    public const TYPE_DP25 = 'dp25';

    public const TYPE_DP75 = 'dp75';

    public const TYPE_PELUNASAN = 'pelunasan';

    public const STATUS_PENDING = 'pending';

    public const STATUS_VERIFIED = 'verified';

    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'booking_id', 'type', 'amount', 'due_date', 'paid_at',
        'method', 'proof', 'status', 'notes', 'verified_by', 'verified_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'due_date' => 'date',
            'paid_at' => 'datetime',
            'verified_at' => 'datetime',
        ];
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public static function typeLabel(string $type): string
    {
        // Label tahap pembayaran fleksibel: label lama tetap dipetakan,
        // selain itu tampilkan label yang diketik admin apa adanya.
        return match ($type) {
            self::TYPE_DP10 => 'DP 10%',
            self::TYPE_DP25 => 'DP 25% (Saat Fitting)',
            self::TYPE_DP75 => 'DP 75% (H-7)',
            self::TYPE_PELUNASAN => 'Pelunasan',
            default => $type !== '' ? $type : 'Tahap',
        };
    }
}
