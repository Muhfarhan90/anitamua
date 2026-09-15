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
            'cpw_aksesori_kepala_akad' => 'Aksesori kepala akad',
            'cpw_stylist_akad' => 'Stylist / Hijab akad',
            'cpw_busana_resepsi_1' => 'Busana resepsi 1',
            'cpw_aksesori_kepala_resepsi_1' => 'Aksesori kepala resepsi 1',
            'cpw_stylist_resepsi_1' => 'Stylist / Hijab resepsi 1',
            'cpw_busana_resepsi_2' => 'Busana resepsi 2',
            'cpw_aksesori_kepala_resepsi_2' => 'Aksesori kepala resepsi 2',
            'cpw_stylist_resepsi_2' => 'Stylist / Hijab resepsi 2',
            'cpw_busana_resepsi_3' => 'Busana resepsi 3',
            'cpw_aksesori_kepala_resepsi_3' => 'Aksesori kepala resepsi 3',
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

    public const PACKING_CONDITIONS = [
        'laundry' => 'Dilaundry',
        'sewing' => 'Dipermak',
        'rental' => 'Disewa',
        'used' => 'Dipakai',
        'broken' => 'Rusak',
        // 'unavailable' => 'Tidak ada',
        // 'other' => 'Lainnya',
    ];

    public const STATUS_SCHEDULED = 'scheduled';

    public const STATUS_ONGOING = 'on_going';

    public const STATUS_FINISHED = 'finished';

    protected $fillable = [
        'booking_id', 'date', 'time', 'pic', 'notes',
        'photos', 'item_sizes', 'status', 'created_by',
        'packing_checklist',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'time' => 'datetime:H:i',
            'photos' => 'array',
            'item_sizes' => 'array',
            'packing_checklist' => 'array',
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

    public function packingSourceItems(): array
    {
        $items = [];

        foreach (self::CHECKLIST as $checklist) {
            foreach ($checklist as $key => $label) {
                $notes = trim((string) $this->{$key.'_notes'});
                $size = trim((string) (($this->item_sizes ?? [])[$key] ?? ''));
                $photoPath = $this->{$key.'_photo_path'};

                $items[] = [
                    'key' => $key,
                    'label' => $label,
                    'notes' => $notes,
                    'size' => $size,
                    'photo_path' => $photoPath,
                ];
            }
        }

        return $items;
    }

    public function packingChecklistState(): array
    {
        $saved = $this->packing_checklist ?? [];

        if (isset($saved['checked']) || isset($saved['conditions']) || isset($saved['notes'])) {
            return [
                'checked' => array_values(array_unique(array_filter(
                    $saved['checked'] ?? [],
                    fn ($key) => is_string($key) && $key !== '',
                ))),
                'conditions' => array_filter(
                    $saved['conditions'] ?? [],
                    fn ($condition, $key) => is_string($key) && array_key_exists($condition, self::PACKING_CONDITIONS),
                    ARRAY_FILTER_USE_BOTH,
                ),
                'notes' => $saved['notes'] ?? [],
            ];
        }

        $checked = [];
        $conditions = [];
        $notes = [];

        foreach ($saved as $item) {
            if (is_string($item)) {
                $checked[] = $item;
            } elseif (is_array($item) && isset($item['key'])) {
                if ($item['packed'] ?? false) {
                    $checked[] = $item['key'];
                }

                if (filled($item['note'] ?? null)) {
                    $notes[$item['key']] = $item['note'];
                }
            }
        }

        return ['checked' => array_values(array_unique($checked)), 'conditions' => $conditions, 'notes' => $notes];
    }
}
