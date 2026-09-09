<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SiteSetting extends Model
{
    use HasFactory;

    public const DEFAULT_LANDING_HERO_IMAGE = 'https://images.unsplash.com/photo-1519741497674-611481863552?q=80&w=1920&auto=format&fit=crop';
    public const DEFAULT_ABOUT_IMAGE = 'https://images.unsplash.com/photo-1583939003579-730e3918a45a?q=80&w=900&auto=format&fit=crop';

    public const DEFAULT_BOOKING_REFERRAL_SOURCES = [
        'Instagram', 'TikTok', 'Website', 'WhatsApp', 'Rekomendasi', 'Lainnya',
    ];

    protected $fillable = ['key', 'value', 'group'];

    public static function get(string $key, ?string $default = null): ?string
    {
        return static::query()->where('key', $key)->value('value') ?? $default;
    }

    public static function set(string $key, ?string $value, string $group = 'general'): void
    {
        static::updateOrCreate(['key' => $key], ['value' => $value, 'group' => $group]);
    }

    public static function imageUrl(?string $path, string $fallback): string
    {
        if (blank($path)) {
            return $fallback;
        }

        return str_starts_with($path, 'http') ? $path : asset('storage/'.$path);
    }

    public static function bookingReferralSources(): array
    {
        $stored = json_decode(static::get('booking_referral_sources', '') ?? '', true);

        return is_array($stored) && $stored !== []
            ? array_values(array_filter($stored, fn ($value) => is_string($value) && filled($value)))
            : static::DEFAULT_BOOKING_REFERRAL_SOURCES;
    }
}
