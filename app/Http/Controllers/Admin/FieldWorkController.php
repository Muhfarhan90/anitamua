<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\EntranceGate;
use App\Models\Fitting;
use App\Models\Survey;
use App\Models\Tent;
use App\Models\User;
use App\Models\WeddingStage;
use App\Services\ActivityLogger;
use App\Services\ImageCompressor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class FieldWorkController extends Controller
{
    public function index()
    {
        $bookings = Booking::with('package')
            ->where('status', '!=', Booking::STATUS_CANCELLED)
            ->orderByDesc('event_date')
            ->get();

        return view('admin.fieldwork.index', compact('bookings'));
    }

    public function fieldwork(Booking $booking)
    {
        $booking->load(['survey.weddingStage', 'survey.tent', 'survey.entranceGate', 'fittings', 'package']);

        $currentDecorationId = $booking->survey?->wedding_stage_id;
        $weddingStages = WeddingStage::query()
            ->where(function ($query) use ($currentDecorationId) {
                $query->where('is_active', true);
                if ($currentDecorationId) {
                    $query->orWhere('id', $currentDecorationId);
                }
            })
            ->orderBy('name')
            ->get();

        $currentTentId = $booking->survey?->tent_id;
        $tents = Tent::query()
            ->where(fn ($query) => $query->where('is_active', true)->when($currentTentId, fn ($query) => $query->orWhere('id', $currentTentId)))
            ->orderBy('name')
            ->get();
        $currentEntranceGateId = $booking->survey?->entrance_gate_id;
        $entranceGates = EntranceGate::query()
            ->where(fn ($query) => $query->where('is_active', true)->when($currentEntranceGateId, fn ($query) => $query->orWhere('id', $currentEntranceGateId)))
            ->orderBy('name')
            ->get();

        $teamMembers = User::where('role', User::ROLE_TEAM)->where('is_active', true)->orderBy('name')->get();

        return view('admin.fieldwork.booking', compact('booking', 'teamMembers', 'weddingStages', 'tents', 'entranceGates'));
    }

    public function surveyStore(Request $request)
    {
        $data = $request->validate([
            'booking_id' => ['required', 'exists:bookings,id'],
            'wedding_stage_id' => ['nullable', 'exists:wedding_stages,id'],
            'tent_id' => ['nullable', 'exists:tents,id'],
            'entrance_gate_id' => ['nullable', 'exists:entrance_gates,id'],
            'flower_color' => ['nullable', 'string', 'max:255'],
            'stage_size' => ['nullable', 'string', 'max:255'],
            'stage_size_other' => ['nullable', 'string', 'max:255'],
            'chair_option' => ['nullable', 'string', 'max:255'],
            'chair_option_other' => ['nullable', 'string', 'max:255'],
            'stage_option' => ['nullable', 'string', 'max:255'],
            'stage_option_other' => ['nullable', 'string', 'max:255'],
            'fabric_color' => ['nullable', 'string', 'max:255'],
            'tent_sizes' => ['nullable', 'array'],
            'tent_sizes.*' => ['string', 'max:255'],
            'tent_size_quantities' => ['nullable', 'array'],
            'tent_size_quantities.*' => ['nullable', 'integer', 'min:0'],
            'tent_sizes_other' => ['nullable', 'string', 'max:255'],
            'tent_additions' => ['nullable', 'array'],
            'tent_additions.*' => ['string', 'max:255'],
            'tent_addition_quantities' => ['nullable', 'array'],
            'tent_addition_quantities.*' => ['nullable', 'integer', 'min:0'],
            'tent_additions_other' => ['nullable', 'string', 'max:255'],
            'tent_shape' => ['nullable', 'string', 'max:255'],
            'tent_shape_other' => ['nullable', 'string', 'max:255'],
            'entrance' => ['nullable', 'string', 'max:255'],
            'entrance_other' => ['nullable', 'string', 'max:255'],
            'buffet' => ['nullable', 'string', 'max:255'],
            'buffet_other' => ['nullable', 'string', 'max:255'],
            'tableware' => ['nullable', 'string', 'max:255'],
            'tableware_other' => ['nullable', 'string', 'max:255'],
            'gallery_booth' => ['nullable', 'string', 'max:255'],
            'envelope_box' => ['nullable', 'string', 'max:255'],
            'fruit_shed' => ['nullable', 'string', 'max:255'],
            'akad_table' => ['nullable', 'string', 'max:255'],
            'diesel_lights' => ['nullable', 'string', 'max:255'],
            'photo_stand' => ['nullable', 'string', 'max:255'],
            'carpet' => ['nullable', 'string', 'max:255'],
            'vip_table' => ['nullable', 'string', 'max:255'],
            'snack_shed' => ['nullable', 'string', 'max:255'],
            'blower' => ['nullable', 'string', 'max:255'],
            'welcome_sign' => ['nullable', 'string', 'max:255'],
            'center_point' => ['nullable', 'string', 'max:255'],
            'gallery_booth_other' => ['nullable', 'string', 'max:255'],
            'envelope_box_other' => ['nullable', 'string', 'max:255'],
            'fruit_shed_other' => ['nullable', 'string', 'max:255'],
            'akad_table_other' => ['nullable', 'string', 'max:255'],
            'diesel_lights_other' => ['nullable', 'string', 'max:255'],
            'photo_stand_other' => ['nullable', 'string', 'max:255'],
            'carpet_other' => ['nullable', 'string', 'max:255'],
            'vip_table_other' => ['nullable', 'string', 'max:255'],
            'snack_shed_other' => ['nullable', 'string', 'max:255'],
            'blower_other' => ['nullable', 'string', 'max:255'],
            'welcome_sign_other' => ['nullable', 'string', 'max:255'],
            'center_point_other' => ['nullable', 'string', 'max:255'],
            'location' => ['nullable', 'string', 'max:255'],
            'maps_url' => ['nullable', 'url', 'max:500'],
            'pic' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
            'photos' => ['nullable', 'array'],
            'photos.*' => ['image', 'max:5120'],
            'videos' => ['nullable', 'array'],
            'videos.*' => ['max:51200'],
        ]);

        $existing = Survey::where('booking_id', $data['booking_id'])->first();
        $weddingStage = ! empty($data['wedding_stage_id'])
            ? WeddingStage::findOrFail($data['wedding_stage_id'])
            : null;

        if ($weddingStage && ! $weddingStage->is_active && $existing?->wedding_stage_id !== $weddingStage->id) {
            throw ValidationException::withMessages([
                'wedding_stage_id' => 'Pelaminan yang tidak aktif tidak dapat dipilih.',
            ]);
        }

        $tent = ! empty($data['tent_id']) ? Tent::findOrFail($data['tent_id']) : null;
        if ($tent && ! $tent->is_active && $existing?->tent_id !== $tent->id) {
            throw ValidationException::withMessages(['tent_id' => 'Tenda yang tidak aktif tidak dapat dipilih.']);
        }

        $entranceGate = ! empty($data['entrance_gate_id']) ? EntranceGate::findOrFail($data['entrance_gate_id']) : null;
        if ($entranceGate && ! $entranceGate->is_active && $existing?->entrance_gate_id !== $entranceGate->id) {
            throw ValidationException::withMessages(['entrance_gate_id' => 'Gapura yang tidak aktif tidak dapat dipilih.']);
        }

        $survey = DB::transaction(function () use ($request, $data, $existing) {
            $data['tent_sizes'] = array_values($data['tent_sizes'] ?? []);
            $data['tent_additions'] = array_values($data['tent_additions'] ?? []);
            $data['tent_size_quantities'] = collect($data['tent_size_quantities'] ?? [])
                ->map(fn ($quantity) => (int) $quantity)
                ->all();
            $data['tent_addition_quantities'] = collect($data['tent_addition_quantities'] ?? [])
                ->map(fn ($quantity) => (int) $quantity)
                ->all();
            $data['photos'] = array_merge($existing?->photos ?? [], $this->storeFiles($request, 'photos'));
            $data['videos'] = array_merge($existing?->videos ?? [], $this->storeFiles($request, 'videos', false));
            $data['created_by'] = auth()->id();

            return Survey::updateOrCreate(['booking_id' => $data['booking_id']], $data);
        });

        ActivityLogger::log('survey_saved', 'Survey disimpan', 'Data survey untuk '.$survey->booking->name.' disimpan oleh '.auth()->user()->name, $data['booking_id']);

        return back()->with('success', 'Data survey berhasil disimpan.');
    }

    public function fittingStore(Request $request)
    {
        $data = $request->validate([
            'booking_id' => ['required', 'exists:bookings,id'],
            'date' => ['required', 'date'],
            'time' => ['nullable', 'string', 'max:10'],
            'pic' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
            'status' => ['required', 'in:scheduled,on_going,finished'],
            'items' => ['nullable', 'array'],
            'items.*.notes' => ['nullable', 'string', 'max:2000'],
            'items.*.size' => ['nullable', 'string', 'max:255'],
            'items.*.photo' => ['nullable', 'image', 'max:5120'],
            'photos' => ['nullable', 'array'],
            'photos.*' => ['image', 'max:5120'],
        ]);

        // Simpan file baru, lalu gabungkan dengan foto yang sudah ada
        $existing = Fitting::where('booking_id', $data['booking_id'])->first();
        $data['photos'] = array_merge($existing?->photos ?? [], $this->storeFiles($request, 'photos'));
        $data['item_sizes'] = $existing?->item_sizes ?? [];
        foreach ($request->input('items', []) as $itemKey => $item) {
            if (array_key_exists('size', $item)) {
                $data['item_sizes'][$itemKey] = $item['size'];
            }
        }
        $data['created_by'] = auth()->id();

        // Fitting hanya 1 per booking (sesuai PRD) — update record yang sama
        $fitting = DB::transaction(function () use ($request, $data) {
            $fitting = Fitting::updateOrCreate(['booking_id' => $data['booking_id']], $data);
            $items = $request->input('items', []);
            $checklistData = [];

            foreach (Fitting::CHECKLIST as $category => $checklist) {
                foreach ($checklist as $itemKey => $label) {
                    $photoColumn = $itemKey.'_photo_path';
                    $checklistData[$itemKey.'_notes'] = $items[$itemKey]['notes'] ?? null;
                    $checklistData[$photoColumn] = $fitting->{$photoColumn};

                    if ($request->hasFile("items.{$itemKey}.photo")) {
                        $checklistData[$photoColumn] = $this->storeImage($request->file("items.{$itemKey}.photo"));
                    }
                }
            }

            $fitting->forceFill($checklistData)->save();

            $fitting->booking()->update(['fitting_date' => $data['date']]);

            return $fitting;
        });

        $booking = $fitting->booking;

        ActivityLogger::log(
            $data['status'] === 'finished' ? 'fitting_finished' : 'fitting_saved',
            'Data fitting '.($data['status'] === 'finished' ? 'selesai' : 'disimpan'),
            'Fitting untuk '.$booking->name.' pada '.$data['date'].' oleh '.auth()->user()->name,
            $data['booking_id'],
        );

        return back()->with('success', 'Data fitting berhasil disimpan.');
    }

    private function storeFiles(Request $request, string $key, bool $isImage = true): array
    {
        $paths = [];

        if ($request->hasFile($key)) {
            foreach ($request->file($key) as $file) {
                $paths[] = $isImage
                    ? ImageCompressor::compressAndStore($file, 'uploads/photos')
                    : $file->store('uploads/'.$key, 'public');
            }
        }

        return $paths;
    }

    private function storeImage($file): string
    {
        return ImageCompressor::compressAndStore($file, 'uploads/photos');
    }
}
