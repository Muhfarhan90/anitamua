<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Payment;
use App\Models\Schedule;
use App\Models\User;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        return match ($user->role) {
            User::ROLE_OWNER => $this->owner(),
            User::ROLE_ADMIN => $this->admin(),
            User::ROLE_TEAM => $this->team(),
            default => $this->client(),
        };
    }

    private function owner()
    {
        $stats = [
            'newBookings' => Booking::where('status', Booking::STATUS_PENDING)->count(),
            'activeClients' => User::where('role', User::ROLE_CLIENT)->count(),
            'eventsThisWeek' => Booking::whereBetween('event_date', [Carbon::today(), Carbon::today()->addWeek()])
                ->where('status', '!=', Booking::STATUS_CANCELLED)
                ->count(),
            'pendingPayments' => Payment::where('status', Payment::STATUS_PENDING)->sum('amount'),
            'receivedPayments' => Payment::where('status', Payment::STATUS_VERIFIED)->sum('amount'),
            'bookedEvents' => Booking::where('status', Booking::STATUS_BOOKED)->count(),
        ];

        $upcomingEvents = Booking::with(['client', 'package'])
            ->where('status', Booking::STATUS_BOOKED)
            ->where('event_date', '>=', Carbon::today())
            ->orderBy('event_date')
            ->limit(8)
            ->get();

        $recentBookings = Booking::with(['client', 'package'])
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        return view('dashboard.owner', compact('stats', 'upcomingEvents', 'recentBookings'));
    }

    private function admin()
    {
        $today = Carbon::today();
        $schedules = Schedule::with('booking')
            ->whereBetween('date', [$today->copy()->startOfWeek(), $today->copy()->endOfWeek()])
            ->orderBy('date')
            ->get()
            ->groupBy(fn ($s) => $s->date->format('Y-m-d'));

        $pendingBookings = Booking::with('package')
            ->where('status', Booking::STATUS_PENDING)
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        $pendingPayments = Payment::with('booking')
            ->where('status', Payment::STATUS_PENDING)
            ->orderBy('due_date')
            ->limit(5)
            ->get();

        return view('dashboard.admin', compact('schedules', 'pendingBookings', 'pendingPayments'));
    }

    private function team()
    {
        $today = Carbon::today();
        $tasks = Schedule::with('booking')
            ->where(function ($q) use ($today) {
                $q->where('date', '>=', $today)
                    ->orWhere('status', '!=', Schedule::STATUS_FINISHED);
            })
            ->orderBy('date')
            ->get();

        return view('dashboard.team', compact('tasks'));
    }

    private function client()
    {
        $bookings = Booking::with(['package', 'addons', 'payments', 'schedules'])
            ->where('client_id', auth()->id())
            ->orderByDesc('created_at')
            ->get();

        return view('dashboard.client', compact('bookings'));
    }
}
