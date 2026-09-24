<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\ClientReference;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Throwable;

class BookingReferenceController extends Controller
{
    public function store(Booking $booking, Request $request)
    {
        abort_unless($booking->client_id, 422, 'Booking belum terhubung dengan akun klien.');
        $data = $this->validateData($request, true);

        $photos = $this->storePhotos($request);
        try {
            ClientReference::create([
                'booking_id' => $booking->id,
                'client_id' => $booking->client_id,
                'reference_type_id' => $data['reference_type_id'],
                'notes' => $data['notes'] ?? null,
                'photos' => $photos,
            ]);
        } catch (Throwable $exception) {
            Storage::disk('public')->delete($photos);
            throw $exception;
        }

        ActivityLogger::log('reference_uploaded', 'Referensi ditambahkan', 'Referensi klien ditambahkan oleh '.auth()->user()->name.'.', $booking->id);

        return back()->with('success', 'Referensi berhasil ditambahkan.');
    }

    public function update(Booking $booking, ClientReference $reference, Request $request)
    {
        abort_unless($reference->booking_id === $booking->id, 404);
        $data = $this->validateData($request, false);
        $currentPhotos = $reference->photos ?? [];
        $removedPhotos = array_values(array_intersect($data['remove_photos'] ?? [], $currentPhotos));
        if (count($currentPhotos) === count($removedPhotos) && ! $request->hasFile('photos')) {
            throw ValidationException::withMessages(['photos' => 'Minimal satu foto referensi harus tersedia.']);
        }

        $newPhotos = $this->storePhotos($request);
        $photos = array_values([...array_diff($currentPhotos, $removedPhotos), ...$newPhotos]);
        try {
            $saved = $reference->update([
                'reference_type_id' => $data['reference_type_id'],
                'notes' => $data['notes'] ?? null,
                'photos' => $photos,
            ]);
            if (! $saved) {
                throw new \RuntimeException('Referensi gagal disimpan.');
            }
        } catch (Throwable $exception) {
            Storage::disk('public')->delete($newPhotos);
            throw $exception;
        }
        Storage::disk('public')->delete($removedPhotos);

        ActivityLogger::log('reference_updated', 'Referensi diperbarui', 'Referensi klien diperbarui oleh '.auth()->user()->name.'.', $booking->id);

        return back()->with('success', 'Referensi berhasil diperbarui.');
    }

    public function destroy(Booking $booking, ClientReference $reference)
    {
        abort_unless($reference->booking_id === $booking->id, 404);

        $photos = $reference->photos ?? [];
        if ($reference->delete() === false) {
            throw new \RuntimeException('Referensi gagal dihapus.');
        }
        Storage::disk('public')->delete($photos);

        ActivityLogger::log('reference_deleted', 'Referensi dihapus', 'Referensi klien dihapus oleh '.auth()->user()->name.'.', $booking->id);

        return back()->with('success', 'Referensi berhasil dihapus.');
    }

    private function validateData(Request $request, bool $photosRequired): array
    {
        return $request->validate([
            'reference_type_id' => ['required', 'exists:reference_types,id'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'photos' => [$photosRequired ? 'required' : 'nullable', 'array', 'max:10'],
            'photos.*' => ['image', 'max:5120'],
            'remove_photos' => ['nullable', 'array'],
            'remove_photos.*' => ['string', 'max:2048'],
        ]);
    }

    private function storePhotos(Request $request): array
    {
        return collect($request->file('photos', []))
            ->map(fn ($photo) => $photo->store('uploads/references', 'public'))
            ->all();
    }
}
