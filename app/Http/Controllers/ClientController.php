<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Package;
use App\Models\PackageChangeRequest;
use App\Models\Payment;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;

class ClientController extends Controller
{
    public function booking(Booking $booking)
    {
        abort_unless($booking->client_id === auth()->id(), 403);

        $booking->load([
            'package.benefits', 'payments', 'bookingVendors.vendor.category',
            'schedules.picUser', 'survey', 'fittings', 'activityLogs.user',
            'packageChangeRequests.newPackage',
        ]);

        return view('client.booking', compact('booking'));
    }

    public function uploadProof(Booking $booking, Request $request)
    {
        abort_unless($booking->client_id === auth()->id(), 403);

        $data = $request->validate([
            'payment_id' => ['nullable', 'exists:payments,id'],
            'type' => ['required_without:payment_id', 'nullable', 'string', 'max:100'],
            'amount' => ['required_without:payment_id', 'nullable', 'numeric', 'min:1000'],
            'proof' => ['required', 'image', 'max:5120'],
            'method' => ['required', 'string'],
        ]);

        if (! empty($data['payment_id'])) {
            // Bayar tahap yang sudah ada (mis. DP1)
            $payment = Payment::findOrFail($data['payment_id']);
            abort_unless($payment->booking_id === $booking->id, 403);
        } else {
            // Client menambah tahap baru sendiri (label & nominal diketik client)
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
