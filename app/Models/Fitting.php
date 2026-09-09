<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Fitting extends Model
{
    use HasFactory;

    public const CHECKLIST = [
        'cpw' => [
            'cpw_busana_akad' => 'Busana akad / Pemberkatan',
            'cpw_stylist_akad' => 'Stylist / Hijab akad',
            'cpw_busana_resepsi_1' => 'Busana resepsi 1',
            'cpw_stylist_resepsi_1' => 'Stylist / Hijab resepsi 1',
            'cpw_busana_resepsi_2' => 'Busana resepsi 2',
            'cpw_stylist_resepsi_2' => 'Stylist / Hijab resepsi 2',
            'cpw_heels_selop' => 'Ukuran flatshoes / heels / selop',
        ],
        'cpp' => [
            'cpp_busana_akad' => 'Busana akad',
            'cpp_busana_resepsi_1' => 'Busana resepsi 1',
            'cpp_busana_resepsi_2' => 'Busana resepsi 2',
        ],
        'ukuran' => [
            'cpw_bb_tb_ld' => 'CPW - Tulis / isi BB / TB / LD',
            'cpp_bb_tb_ld' => 'CPP - Tulis / isi BB / TB / LD',
        ],
        'among_hajat' => [
            'among_ibu_hajat' => 'Busana ibu hajat',
            'among_bapak_hajat' => 'Busana bapak hajat',
            'among_ibu_besan' => 'Busana ibu besan',
            'among_bapak_besan' => 'Busana bapak besan',
            'among_pagar_ayu' => 'Busana pagar ayu',
            'among_pagar_bagus' => 'Busana pagar bagus',
        ],
    ];

    public const STATUS_SCHEDULED = 'scheduled';

    public const STATUS_ONGOING = 'on_going';

    public const STATUS_FINISHED = 'finished';

    protected $fillable = [
        'booking_id', 'date', 'time', 'pic', 'notes',
        'photos', 'item_sizes', 'status', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'time' => 'datetime:H:i',
            'photos' => 'array',
            'item_sizes' => 'array',
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

}
