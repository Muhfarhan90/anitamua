<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Schedule;
use App\Models\User;
use App\Services\ActivityLogger;
use App\Services\ClientAccountService;
use App\Services\InvoiceService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ScheduleController extends Controller
{
    public function index(Request $request)
    {
        $month = (int) ($request->month ?? now()->month);
        $year = (int) ($request->year ?? now()->year);
        $date = Carbon::create($year, $month, 1);

        $monthSchedules = Schedule::with('booking')
            ->whereHas('booking', fn ($query) => $query->whereNotIn('status', [
                Booking::STATUS_CANCELLED,
                Booking::STATUS_COMPLETED,
            ]))
            ->whereBetween('date', [$date->copy()->startOfMonth(), $date->copy()->endOfMonth()])
            ->get();

        $grouped = $monthSchedules->groupBy(fn ($s) => $s->date->format('Y-m-d'));

        $calendar = $this->buildCalendar($date->year, $date->month, $grouped);

        $eventsByDate = $monthSchedules
            ->map(fn ($s) => [
                'title' => $s->title ?: Schedule::typeLabel($s->type).' — '.$s->booking->name,
                'type' => $s->type,
                'time' => $s->time ? $s->time->format('H:i') : null,
                'location' => $s->location ?: $s->booking->location,
                'status' => $s->status,
                'booking_id' => $s->booking_id,
                'booking_url' => route('admin.bookings.show', $s->booking_id),
                'date' => $s->date->format('Y-m-d'),
            ])
            ->groupBy('date')
            ->toArray();

        $bookings = Booking::with(['package', 'client'])
            ->whereNotIn('status', [Booking::STATUS_CANCELLED, Booking::STATUS_COMPLETED])
            ->orderBy('event_date')
            ->get();

        $teamMembers = User::where('role', User::ROLE_TEAM)->where('is_active', true)->orderBy('name')->get();

        return view('admin.calendar', compact('calendar', 'date', 'bookings', 'eventsByDate', 'teamMembers'));
    }

    public function store(Request $request)
    {
        $data = $this->validateSchedule($request);

        // Survey & Fitting masing-masing maksimal 1 jadwal per booking (sesuai PRD: satu tahap per project)
        if (in_array($data['type'], [Schedule::TYPE_SURVEY, Schedule::TYPE_FITTING])) {
            $has = Schedule::where('booking_id', $data['booking_id'])
                ->where('type', $data['type'])
                ->exists();

            if ($has) {
                return back()->withErrors([
                    'type' => 'Booking ini sudah memiliki jadwal '.Schedule::typeLabel($data['type']).'. '.Schedule::typeLabel($data['type']).' hanya dijadwalkan 1 kali per booking.',
                ]);
            }
        }

        // Isi judul otomatis jika tidak diisi (form kalender tidak menyediakan field judul)
        if (empty($data['title'])) {
            $data['title'] = Schedule::typeLabel($data['type']).' — '.Booking::find($data['booking_id'])->name;
        }

        $schedule = Schedule::create($data);

        ActivityLogger::log(
            'schedule_created',
            'Jadwal ditambahkan',
            'Jadwal '.Schedule::typeLabel($schedule->type).' untuk '.$schedule->booking->name.' pada '.$schedule->date->format('d M Y'),
            $schedule->booking_id,
        );

        return back()->with('success', 'Jadwal berhasil ditambahkan.');
    }

    public function updateStatus(
        Schedule $schedule,
        Request $request,
        ClientAccountService $clientAccounts,
        InvoiceService $invoiceService,
    ) {
        $data = $request->validate([
            'status' => ['required', 'in:scheduled,on_going,finished,cancelled'],
            'notes' => ['nullable', 'string'],
        ]);

        DB::transaction(function () use ($schedule, $data, $clientAccounts, $invoiceService) {
            $schedule->update($data);

            ActivityLogger::log(
                'schedule_status_changed',
                'Status jadwal diubah',
                'Jadwal '.Schedule::typeLabel($schedule->type).' ('.$schedule->booking->name.') berubah menjadi '.$data['status'],
                $schedule->booking_id,
            );

            if ($schedule->type !== Schedule::TYPE_HARI_H || $data['status'] !== Schedule::STATUS_FINISHED) {
                return;
            }

            $booking = Booking::query()->lockForUpdate()->findOrFail($schedule->booking_id);
            if ($booking->status === Booking::STATUS_COMPLETED) {
                return;
            }
            if ($booking->status !== Booking::STATUS_BOOKED) {
                throw ValidationException::withMessages([
                    'status' => 'Hari H hanya dapat diselesaikan untuk booking berstatus BOOKED.',
                ]);
            }

            $clientAccounts->ensure($booking);
            $booking->update(['status' => Booking::STATUS_COMPLETED]);
            $invoiceService->sync($booking->fresh());

            ActivityLogger::log('booking_completed', 'Project selesai', 'Project '.$booking->code.' selesai setelah Hari H.', $booking->id);
        });

        return back()->with('success', 'Status jadwal diperbarui.');
    }

    public function updatePic(Schedule $schedule, Request $request)
    {
        $data = $request->validate([
            'pic_user_id' => ['nullable', 'exists:users,id'],
        ]);

        $schedule->update(['pic_user_id' => $data['pic_user_id'] ?? null]);

        ActivityLogger::log(
            'schedule_pic_changed',
            'PIC jadwal diubah',
            'PIC jadwal '.Schedule::typeLabel($schedule->type).' ('.$schedule->booking->name.') diubah oleh '.auth()->user()->name,
            $schedule->booking_id,
        );

        return back()->with('success', 'PIC jadwal diperbarui.');
    }

    public function destroy(Schedule $schedule)
    {
        $schedule->delete();

        ActivityLogger::log('schedule_deleted', 'Jadwal dihapus', 'Jadwal '.Schedule::typeLabel($schedule->type).' dihapus oleh '.auth()->user()->name, $schedule->booking_id);

        return back()->with('success', 'Jadwal dihapus.');
    }

    private function validateSchedule(Request $request): array
    {
        $data = $request->validate([
            'booking_id' => ['required', 'exists:bookings,id'],
            'type' => ['required', 'in:survey,fitting,hari_h'],
            'title' => ['nullable', 'string', 'max:255'],
            'date' => ['required', 'date'],
            'time' => ['nullable', 'string', 'max:10'],
            'location' => ['nullable', 'string', 'max:255'],
            'pic_user_id' => ['nullable', 'exists:users,id'],
            'notes' => ['nullable', 'string'],
        ]);

        $booking = Booking::findOrFail($data['booking_id']);

        if ($data['type'] === Schedule::TYPE_SURVEY) {
            $booking->update(['survey_date' => $data['date']]);
        }

        if ($data['type'] === Schedule::TYPE_FITTING) {
            $booking->update(['fitting_date' => $data['date']]);
        }

        return $data;
    }

    private function buildCalendar(int $year, int $month, $grouped): array
    {
        $firstDay = Carbon::create($year, $month, 1);
        // Header kalender dimulai dari Minggu, jadi grid juga harus dimulai dari Minggu.
        $start = $firstDay->copy()->startOfWeek(Carbon::SUNDAY);
        $daysInCalendar = 42;

        $calendar = [];
        for ($i = 0; $i < $daysInCalendar; $i++) {
            $day = $start->copy()->addDays($i);
            $key = $day->format('Y-m-d');

            $schedules = $grouped->get($key, collect());

            $calendar[] = [
                'date' => $day,
                'inMonth' => $day->month === $month,
                'isToday' => $day->isToday(),
                'schedules' => $schedules,
            ];
        }

        return $calendar;
    }
}
