<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Package;
use App\Models\PackageChangeRequest;
use App\Models\Payment;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ClientController extends Controller
{
    public function booking(Booking $booking)
    {
        abort_unless($booking->client_id === auth()->id(), 403);

        $booking->load([
            'package.benefits', 'addons', 'payments', 'bookingVendors.vendor.category',
            'schedules.picUser', 'survey.weddingStage', 'survey.tent', 'survey.entranceGate', 'fittings', 'activityLogs.user',
            'packageChangeRequests.newPackage',
        ]);

        return view('client.booking', compact('booking'));
    }

    public function testimonials()
    {
        $bookings = auth()->user()->bookings()
            ->where('status', Booking::STATUS_COMPLETED)
            ->with('testimonial')
            ->orderByDesc('event_date')
            ->get();

        return view('client.testimonials', compact('bookings'));
    }

    public function uploadProof(Booking $booking, Request $request)
    {
        abort_unless($booking->client_id === auth()->id(), 403);

        $data = $request->validate([
            'payment_id' => ['nullable', 'exists:payments,id'],
            'type' => ['required_without:payment_id', 'nullable', 'string', 'max:100'],
            'amount' => ['required_without:payment_id', 'nullable', 'numeric', 'min:0'],
            'proof' => ['required', 'image', 'max:5120'],
            'method' => ['required', 'string'],
        ]);

        if (! empty($data['payment_id'])) {
            // Bayar tahap yang sudah ada (mis. DP1)
            $payment = Payment::findOrFail($data['payment_id']);
            abort_unless($payment->booking_id === $booking->id, 403);

            if ($payment->status !== Payment::STATUS_PENDING) {
                return back()->with('warning', 'Bukti hanya dapat diunggah untuk pembayaran berstatus Pending.');
            }
        } else {
            // Nominal dari client menjadi nilai awal; admin tetap memverifikasi dan dapat mengoreksinya.
            $payment = $booking->payments()->create([
                'type' => $data['type'],
                'amount' => $data['amount'],
                'method' => $data['method'],
                'status' => Payment::STATUS_PENDING,
            ]);
        }

        $payment->update([
            'proof' => $request->file('proof')->store('uploads/proofs', 'public'),
            'method' => $data['method'],
            'paid_at' => now(),
            'status' => Payment::STATUS_PENDING,
        ]);

        ActivityLogger::log('payment_proof_uploaded', 'Bukti pembayaran diunggah', 'Client mengunggah bukti '.Payment::typeLabel($payment->type).' menunggu verifikasi admin.', $booking->id);

        return back()->with('success', 'Bukti pembayaran diunggah. Admin akan memverifikasi segera.');
    }

    public function storeTestimonial(Booking $booking, Request $request)
    {
        abort_unless($booking->client_id === auth()->id(), 403);
        abort_unless($booking->status === Booking::STATUS_COMPLETED, 403, 'Testimoni hanya dapat diberikan setelah booking selesai.');

        $data = $request->validate([
            'rating' => ['required', 'integer', 'between:1,5'],
            'content' => ['required', 'string', 'max:3000'],
            'photos' => ['nullable', 'array'],
            'photos.*' => ['image', 'max:3072'],
            'remove_photos' => ['nullable', 'array'],
            'remove_photos.*' => ['string', 'max:2048'],
        ]);

        $testimonial = $booking->testimonial()->first();
        $currentPhotos = array_values(array_filter($testimonial?->photos ?: [$testimonial?->photo]));
        $removedPhotos = array_values(array_intersect($data['remove_photos'] ?? [], $currentPhotos));
        $remainingPhotos = array_values(array_diff($currentPhotos, $removedPhotos));
        $newPhotos = $request->hasFile('photos')
            ? collect($request->file('photos'))
                ->map(fn ($photo) => $photo->store('uploads/testimonials', 'public'))
                ->all()
            : [];
        $photos = array_values([...$remainingPhotos, ...$newPhotos]);

        $testimonial = $booking->testimonial()->updateOrCreate([], [
            'client_name' => $booking->name,
            'rating' => $data['rating'],
            'content' => $data['content'],
            'photo' => $photos[0] ?? null,
            'photos' => $photos,
            'status' => 'hidden',
        ]);

        collect($removedPhotos)->each(fn (string $photo) => Storage::disk('public')->delete($photo));

        ActivityLogger::log(
            $testimonial->wasRecentlyCreated ? 'testimonial_submitted' : 'testimonial_updated',
            $testimonial->wasRecentlyCreated ? 'Testimoni dikirim' : 'Testimoni diperbarui',
            'Testimoni untuk booking '.$booking->code.($testimonial->wasRecentlyCreated ? ' dikirim' : ' diperbarui').' oleh '.auth()->user()->name.'.',
            $booking->id,
        );

        return back()->with('success', $testimonial->wasRecentlyCreated
            ? 'Terima kasih. Testimoni Anda berhasil dikirim.'
            : 'Testimoni berhasil diperbarui.');
    }

    public function requestPackageChange(Booking $booking, Request $request)
    {
        abort_unless($booking->client_id === auth()->id(), 403);

        $data = $request->validate([
            'new_package_id' => ['required', 'exists:packages,id', 'different:current_package'],
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        PackageChangeRequest::create([
            'booking_id' => $booking->id,
            'old_package_id' => $booking->package_id,
            'new_package_id' => $data['new_package_id'],
            'reason' => $data['reason'],
            'status' => PackageChangeRequest::STATUS_PENDING,
            'requested_by' => auth()->id(),
        ]);

        ActivityLogger::log(
            'package_change_requested',
            'Permintaan ganti paket',
            'Client mengajukan perubahan paket dari '.$booking->package->name.' menunggu persetujuan admin/owner.',
            $booking->id,
            ['package' => $booking->package->name],
            ['package' => Package::find($data['new_package_id'])->name],
        );

        return back()->with('success', 'Permintaan perubahan paket diajukan. Menunggu persetujuan admin/owner.');
    }
}
