<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\BookingVendor;
use App\Models\Finance;
use App\Models\Payment;
use App\Services\ActivityLogger;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class FinanceController extends Controller
{
    public function index(Request $request)
    {
        $year = max(2000, min(2100, (int) ($request->input('year') ?: now()->year)));
        $allTransactions = $this->transactions();
        $yearTransactions = $allTransactions
            ->filter(fn (array $transaction) => $transaction['date']->year === $year)
            ->values();

        $incomes = $yearTransactions->where('type', 'income')->sum('amount');
        $expenses = $yearTransactions->where('type', 'expense')->sum('amount');
        $profit = $incomes - $expenses;
        $margin = $incomes > 0 ? round(($profit / $incomes) * 100, 1) : 0;

        $monthly = collect(range(1, 12))->map(function (int $month) use ($yearTransactions, $year) {
            $items = $yearTransactions->filter(fn (array $transaction) => $transaction['date']->month === $month);
            $income = $items->where('type', 'income')->sum('amount');
            $expense = $items->where('type', 'expense')->sum('amount');

            return [
                'label' => Carbon::create($year, $month, 1)->translatedFormat('M'),
                'income' => $income,
                'expense' => $expense,
                'profit' => $income - $expense,
            ];
        });

        $years = $allTransactions
            ->pluck('date')
            ->map(fn (Carbon $date) => $date->year)
            ->push($year)
            ->unique()
            ->sortDesc()
            ->values();
        return view('admin.finances.index', compact(
            'incomes', 'expenses', 'profit', 'margin', 'monthly', 'year', 'years'
        ));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'booking_id' => ['nullable', 'exists:bookings,id'],
            'category' => ['required', 'string', 'max:50'],
            'amount' => ['required', 'numeric', 'min:0'],
            'description' => ['required', 'string'],
            'transaction_date' => ['required', 'date'],
        ]);

        $finance = Finance::create($data + [
            'type' => Finance::TYPE_EXPENSE,
            'created_by' => auth()->id(),
        ]);

        ActivityLogger::log(
            'finance_created',
            'Pengeluaran dicatat',
            'Pengeluaran '.number_format($finance->amount).' ('.$finance->category.') dicatat oleh '.auth()->user()->name,
            $finance->booking_id,
        );

        return back()->with('success', 'Pengeluaran berhasil dicatat.');
    }

    public function destroy(Finance $finance)
    {
        $finance->delete();

        ActivityLogger::log(
            'finance_deleted',
            'Pengeluaran dihapus',
            'Pengeluaran '.$finance->category.' sebesar '.$finance->amount.' dihapus.',
            $finance->booking_id,
        );

        return back()->with('success', 'Pengeluaran dihapus.');
    }

    private function transactions(): Collection
    {
        $payments = Payment::with('booking')
            ->where('status', Payment::STATUS_VERIFIED)
            ->get()
            ->map(function (Payment $payment) {
                $date = $payment->paid_at ?? $payment->verified_at ?? $payment->created_at;
                $label = Payment::typeLabel($payment->type);

                return [
                    'id' => 'payment-'.$payment->id,
                    'type' => 'income',
                    'category' => $label,
                    'amount' => (float) $payment->amount,
                    'date' => Carbon::parse($date),
                    'description' => 'Pembayaran '.$label.' — '.($payment->booking?->name ?? 'Booking'),
                ];
            });

        $vendorExpenses = BookingVendor::with(['booking', 'vendor.category'])
            ->where('status', '!=', 'cancelled')
            ->whereNotNull('price')
            ->where('price', '>', 0)
            ->get()
            ->filter(fn (BookingVendor $bookingVendor) =>
                $bookingVendor->booking && $bookingVendor->booking->status !== Booking::STATUS_CANCELLED
            )
            ->map(function (BookingVendor $bookingVendor) {
                $booking = $bookingVendor->booking;
                $vendor = $bookingVendor->vendor;
                $date = $bookingVendor->created_at ?? $booking->created_at;

                return [
                    'id' => 'vendor-'.$bookingVendor->id,
                    'type' => 'expense',
                    'category' => $vendor?->category?->name ?? 'Vendor',
                    'amount' => (float) $bookingVendor->price,
                    'date' => Carbon::parse($date),
                    'description' => 'Biaya '.($vendor?->name ?? 'Vendor').' — '.$booking->name,
                ];
            });

        return $payments->concat($vendorExpenses);
    }
}
