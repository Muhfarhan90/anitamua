<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Invoice extends Model
{
    use HasFactory;

    public const STATUS_UNPAID = 'unpaid';

    public const STATUS_PARTIAL = 'partial';

    public const STATUS_PAID = 'paid';

    protected $fillable = [
        'booking_id', 'invoice_number', 'issue_date', 'due_date', 'items',
        'total_amount', 'paid_amount', 'remaining_amount', 'status', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'issue_date' => 'date',
            'due_date' => 'date',
            'items' => 'array',
            'total_amount' => 'decimal:2',
            'paid_amount' => 'decimal:2',
            'remaining_amount' => 'decimal:2',
        ];
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public static function generateInvoiceNumber(Booking $booking): string
    {
        $name = Str::ascii($booking->name ?: $booking->client?->name ?: 'Anita MUA');
        preg_match_all('/\b([A-Za-z])/', $name, $matches);
        $initials = Str::upper(implode('', array_slice($matches[1] ?? [], 0, 4))) ?: 'AMU';

        return 'INV-'.$initials.'-'.$booking->code;
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_PAID => 'Paid',
            self::STATUS_PARTIAL => 'Partial',
            default => 'Unpaid',
        };
    }
}
