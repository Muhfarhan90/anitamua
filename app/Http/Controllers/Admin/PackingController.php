<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Fitting;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PackingController extends Controller
{
    public function show(Booking $booking)
    {
        // $this->ensureBookingAccess($booking);
        $booking->load('package');
        $fitting = Fitting::where('booking_id', $booking->id)->first();

        return view('admin.packing.show', compact('booking', 'fitting'));
    }

    public function update(Booking $booking, Request $request)
    {
        // $this->ensureBookingAccess($booking);
        $data = $request->validate([
            'items' => ['required', 'array'],
            'items.*.packed' => ['nullable', 'boolean'],
            'items.*.condition' => ['nullable', 'string', Rule::in(array_keys(Fitting::PACKING_CONDITIONS))],
        ]);

        $fitting = Fitting::where('booking_id', $booking->id)->firstOrFail();
        $packingState = $fitting->packingChecklistState();
        $checked = $packingState['checked'];
        $conditions = $packingState['conditions'];

        foreach ($fitting->packingSourceItems() as $item) {
            $key = $item['key'];

            if (! array_key_exists($key, $data['items'])) {
                continue;
            }

            $checked = array_values(array_diff($checked, [$key]));

            if ($request->boolean('items.'.$key.'.packed')) {
                $checked[] = $key;
            }

            $condition = $data['items'][$key]['condition'] ?? '';

            if ($condition === '') {
                unset($conditions[$key]);
            } else {
                $conditions[$key] = $condition;
            }
        }

        $fitting->update(['packing_checklist' => [
            'checked' => array_values(array_unique($checked)),
            'conditions' => $conditions,
            'notes' => $packingState['notes'],
        ]]);

        return redirect()->to($this->checklistUrl($booking))
            ->with('success', 'Checklist packing berhasil disimpan.');
    }

    // Pemeriksaan PIC lama disimpan sebagai komentar; aktifkan kembali jika pembatasan ditetapkan lagi.
    // private function ensureBookingAccess(Booking $booking): void
    // {
    //     abort_unless($booking->isAssignedTo(auth()->user()), 403, 'Anda tidak memiliki akses ke tugas booking ini.');
    // }

    private function checklistUrl(Booking $booking): string
    {
        return route('admin.bookings.packing', $booking);
    }
}
