<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Fitting;
use App\Models\Survey;
use App\Services\ActivityLogger;
use App\Services\ImageCompressor;
use Illuminate\Http\Request;

class FieldWorkController extends Controller
{
    public function surveyStore(Request $request)
    {
        $data = $request->validate([
            'booking_id' => ['required', 'exists:bookings,id'],
            'location' => ['nullable', 'string', 'max:255'],
            'maps_url' => ['nullable', 'url', 'max:500'],
            'pic' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
            'photos' => ['nullable', 'array'],
            'photos.*' => ['image', 'max:5120'],
            'videos' => ['nullable', 'array'],
            'videos.*' => ['max:51200'],
        ]);

        // Simpan file baru, lalu gabungkan dengan foto/video yang sudah ada
        $photos = $this->storeFiles($request, 'photos');
        $videos = $this->storeFiles($request, 'videos', false);

        $existing = Survey::where('booking_id', $data['booking_id'])->first();

        $data['photos'] = array_merge($existing->photos ?? [], $photos);
        $data['videos'] = array_merge($existing->videos ?? [], $videos);
        $data['created_by'] = auth()->id();

        $survey = Survey::updateOrCreate(['booking_id' => $data['booking_id']], $data);

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
            'photos' => ['nullable', 'array'],
            'photos.*' => ['image', 'max:5120'],
        ]);

        // Simpan file baru, lalu gabungkan dengan foto yang sudah ada
        $photos = $this->storeFiles($request, 'photos');
        $existing = Fitting::where('booking_id', $data['booking_id'])->first();
        $data['photos'] = array_merge($existing->photos ?? [], $photos);
        $data['created_by'] = auth()->id();

        // Fitting hanya 1 per booking (sesuai PRD) — update record yang sama
        $fitting = Fitting::updateOrCreate(['booking_id' => $data['booking_id']], $data);

        $booking = $fitting->booking;
        $booking->update(['fitting_date' => $data['date']]);

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
}
