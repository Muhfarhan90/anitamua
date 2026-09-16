<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Testimonial extends Model
{
    use HasFactory;

    protected $fillable = [
        'booking_id', 'client_name', 'rating', 'content', 'photo', 'photos', 'status',
    ];

    protected $casts = [
        'photos' => 'array',
    ];

    protected $appends = ['photo_urls'];

    public function getPhotoUrlsAttribute(): array
    {
        return collect($this->photos ?: [$this->photo])
            ->filter()
            ->map(fn (string $photo) => str_starts_with($photo, 'http') ? $photo : asset('storage/'.$photo))
            ->values()
            ->all();
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }
}
