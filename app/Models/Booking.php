<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class Booking extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';

    public const STATUS_BOOKED = 'booked';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_CANCELLED = 'cancelled';

    public static function generateCode(): string
    {
        do {
            $code = Str::upper(Str::random(5));
        } while (self::where('code', $code)->exists());

        return $code;
    }

    protected $fillable = [
        'code', 'client_id', 'package_id', 'name', 'phone', 'email', 'instagram',
        'event_date', 'event_time', 'event_type', 'number_of_guests',
        'survey_date', 'fitting_date', 'location', 'notes',
        'status', 'cancelled_reason', 'cancelled_at', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'event_date' => 'date',
            'survey_date' => 'date',
            'fitting_date' => 'date',
            'cancelled_at' => 'datetime',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(User::class, 'client_id');
    }

    public function package(): BelongsTo
    {
        return $this->belongsTo(Package::class);
    }

    public function addons(): HasMany
    {
        return $this->hasMany(BookingAddon::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function bookingVendors(): HasMany
    {
        return $this->hasMany(BookingVendor::class);
    }

    public function vendors(): HasMany
    {
        return $this->bookingVendors()->with('vendor');
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(Schedule::class);
    }

    public function survey(): HasOne
    {
        return $this->hasOne(Survey::class);
    }

    public function fittings(): HasMany
    {
        return $this->hasMany(Fitting::class);
    }

    public function packingLists(): HasMany
    {
        return $this->hasMany(PackingList::class);
    }

    public function reminders(): HasMany
    {
        return $this->hasMany(Reminder::class);
    }

    public function finances(): HasMany
    {
        return $this->hasMany(Finance::class);
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(ActivityLog::class);
    }

    public function packageChangeRequests(): HasMany
    {
        return $this->hasMany(PackageChangeRequest::class);
    }

    public function testimonial(): HasOne
    {
        return $this->hasOne(Testimonial::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', '!=', self::STATUS_CANCELLED);
    }

    public function scopeWithStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function getTotalPaidAttribute(): float
    {
        return (float) $this->payments()
            ->where('status', Payment::STATUS_VERIFIED)
            ->sum('amount');
    }

    public function getTotalPriceAttribute(): float
    {
        return (float) ($this->package?->price ?? 0) + (float) $this->addons->sum('price');
    }

    public function getIsBookedAttribute(): bool
    {
        return $this->status === self::STATUS_BOOKED;
    }

    public function getLabelAttribute(): string
    {
        return $this->name.' ('.$this->code.')';
    }

    /**
     * Buat jadwal Hari H otomatis (muncul di kalender) jika belum ada.
     * Dipanggil saat booking menjadi BOOKED — berlaku untuk alur landing maupun admin.
     */
    public function ensureHariHSchedule(): void
    {
        if ($this->schedules()->where('type', Schedule::TYPE_HARI_H)->exists()) {
            return;
        }

        $this->schedules()->create([
            'type' => Schedule::TYPE_HARI_H,
            'title' => Schedule::typeLabel(Schedule::TYPE_HARI_H).' — '.$this->name,
            'date' => $this->event_date,
            'time' => $this->event_time,
            'location' => $this->location,
            'notes' => 'Hari H — acara '.$this->name,
            'status' => Schedule::STATUS_SCHEDULED,
        ]);
    }
}
