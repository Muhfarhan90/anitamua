<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClientReference extends Model
{
    use HasFactory;

    protected $table = 'references';

    protected $fillable = ['booking_id', 'client_id', 'reference_type_id', 'notes', 'photos'];

    protected $casts = [
        'photos' => 'array',
    ];

    protected $appends = ['photo_urls'];

    public function getPhotoUrlsAttribute(): array
    {
        return collect($this->photos)
            ->filter()
            ->map(fn (string $photo) => asset('storage/'.$photo))
            ->values()
            ->all();
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(User::class, 'client_id');
    }

    public function referenceType(): BelongsTo
    {
        return $this->belongsTo(ReferenceType::class);
    }
}
