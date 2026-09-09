<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Survey extends Model
{
    use HasFactory;

    protected $fillable = [
        'booking_id', 'location', 'maps_url', 'pic',
        'notes', 'photos', 'videos', 'created_by',
        'wedding_stage_id', 'tent_id', 'entrance_gate_id', 'flower_color', 'stage_size', 'stage_size_other',
        'chair_option', 'chair_option_other', 'stage_option', 'stage_option_other',
        'fabric_color', 'tent_sizes', 'tent_sizes_other', 'tent_additions',
        'tent_size_quantities', 'tent_addition_quantities', 'tent_additions_other', 'tent_shape', 'tent_shape_other', 'entrance',
        'entrance_other', 'buffet', 'buffet_other', 'tableware', 'tableware_other',
        'gallery_booth', 'envelope_box', 'fruit_shed', 'akad_table', 'diesel_lights',
        'photo_stand', 'carpet', 'vip_table', 'snack_shed', 'blower', 'welcome_sign',
        'center_point', 'gallery_booth_other', 'envelope_box_other', 'fruit_shed_other',
        'akad_table_other', 'diesel_lights_other', 'photo_stand_other', 'carpet_other',
        'vip_table_other', 'snack_shed_other', 'blower_other', 'welcome_sign_other',
        'center_point_other',
    ];

    protected function casts(): array
    {
        return [
            'photos' => 'array',
            'videos' => 'array',
            'tent_sizes' => 'array',
            'tent_additions' => 'array',
            'tent_size_quantities' => 'array',
            'tent_addition_quantities' => 'array',
        ];
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function weddingStage(): BelongsTo
    {
        return $this->belongsTo(WeddingStage::class, 'wedding_stage_id');
    }

    public function tent(): BelongsTo
    {
        return $this->belongsTo(Tent::class);
    }

    public function entranceGate(): BelongsTo
    {
        return $this->belongsTo(EntranceGate::class);
    }
}
