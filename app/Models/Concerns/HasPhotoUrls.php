<?php

namespace App\Models\Concerns;

trait HasPhotoUrls
{
    public function getPhotoUrlsAttribute(): array
    {
        $photos = $this->photos ?: array_filter([$this->photo_path]);

        return collect($photos)
            ->filter()
            ->map(fn (string $photo) => str_starts_with($photo, 'http') ? $photo : asset('storage/'.$photo))
            ->values()
            ->all();
    }
}
