<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Gallery extends Model
{
    use HasFactory;

    protected $table = 'gallery';

    protected $fillable = ['booking_id', 'title', 'photo', 'photos', 'category'];

    protected $casts = [
        'photos' => 'array',
    ];

    protected $appends = ['image_url', 'photo_urls'];

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function getImageUrlAttribute(): string
    {
        return $this->imageUrlFor($this->photo);
    }

    public function getPhotoUrlsAttribute(): array
    {
        $photos = $this->photos ?: [$this->photo];

        return collect($photos)
            ->filter()
            ->map(fn (string $photo) => $this->imageUrlFor($photo))
            ->values()
            ->all();
    }

    private function imageUrlFor(?string $photo): string
    {
        if ($photo && str_starts_with($photo, 'http')) {
            return $photo;
        }

        if ($photo && file_exists(public_path('storage/'.$photo))) {
            return asset('storage/'.$photo);
        }

        return 'https://images.unsplash.com/photo-1519741497674-611481863552?q=80&w=800&auto=format&fit=crop';
    }
}
