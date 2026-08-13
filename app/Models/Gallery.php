<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Gallery extends Model
{
    use HasFactory;

    protected $table = 'gallery';

    protected $fillable = ['booking_id', 'title', 'photo', 'category'];

    protected $appends = ['image_url'];

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function getImageUrlAttribute(): string
    {
        if ($this->photo && file_exists(public_path('storage/'.$this->photo))) {
            return asset('storage/'.$this->photo);
        }

        return 'https://images.unsplash.com/photo-1519741497674-611481863552?q=80&w=800&auto=format&fit=crop';
    }
}
