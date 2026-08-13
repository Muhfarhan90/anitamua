<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Finance;
use App\Services\ActivityLogger;
use Carbon\Carbon;
use Illuminate\Http\Request;

class FinanceController extends Controller
{
    public function index(Request $request)
    {
        $year = (int) ($request->year ?? Carbon::now()->year);

        $transactionsQuery = Finance::with(['booking', 'creator'])
            ->when($request->type, fn ($q, $t) => $q->where('type', $t));

        $yearly = clone $transactionsQuery;
        $incomes = $yearly->where('type', 'income')->whereYear('transaction_date', $year)->sum('amount');
        $expenses = $yearly->where('type', 'expense')->whereYear('transaction_date', $year)->sum('amount');
        $profit = $incomes - $expenses;
        $margin = $incomes > 0 ? round(($profit / $incomes) * 100, 1) : 0;

        $perProject = Finance::with('booking.package')
            ->whereYear('transaction_date', $year)
            ->get()
            ->filter(fn ($f) => $f->booking_id !== null)
            ->groupBy('booking_id')
            ->map(function ($items) {
                $project = $items->first()->booking;

                return [
                    'project' => $project,
                    'income' => $items->where('type', 'income')->sum('amount'),
                    'expense' => $items->where('type', 'expense')->sum('amount'),
                    'profit' => $items->where('type', 'income')->sum('amount') - $items->where('type', 'expense')->sum('amount'),
                ];
            })
            ->values()
            ->sortByDesc('profit');

        $monthly = Finance::whereYear('transaction_date', $year)
            ->get()
            ->groupBy(fn ($f) => Carbon::parse($f->transaction_date)->translatedFormat('M'))
            ->mapWithKeys(fn ($items, $month) => [
                $month => [
                    'income' => $items->where('type', 'income')->sum('amount'),
                    'expense' => $items->where('type', 'expense')->sum('amount'),
                    'profit' => $items->where('type', 'income')->sum('amount') - $items->where('type', 'expense')->sum('amount'),
                ],
            ]);

        $transactions = Finance::with(['booking', 'creator'])
            ->when($request->type, fn ($q, $t) => $q->where('type', $t))
            ->orderByDesc('transaction_date')
            ->paginate(20)
            ->withQueryString();

        $years = Finance::pluck('transaction_date')
            ->map(fn ($d) => (int) $d->format('Y'))
            ->unique()
            ->sortDesc()
            ->values();

        return view('admin.finances.index', compact(
            'incomes', 'expenses', 'profit', 'margin', 'perProject', 'monthly', 'transactions', 'year', 'years'
        ));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'booking_id' => ['nullable', 'exists:bookings,id'],
            'type' => ['required', 'in:income,expense'],
            'category' => ['required', 'string', 'max:50'],
            'amount' => ['required', 'numeric', 'min:0'],
            'description' => ['nullable', 'string'],
            'transaction_date' => ['required', 'date'],
        ]);

        $data['created_by'] = auth()->id();

        $finance = Finance::create($data);

        ActivityLogger::log(
            'finance_created',
            'Transaksi '.$finance->type,
            ($finance->type === 'income' ? 'Pemasukan' : 'Pengeluaran').' '.number_format($finance->amount).' ('.$finance->category.') dicatat oleh '.auth()->user()->name,
            $finance->booking_id,
        );

        return back()->with('success', 'Transaksi berhasil dicatat.');
    }

    public function destroy(Finance $finance)
    {
        $finance->delete();

        ActivityLogger::log('finance_deleted', 'Transaksi dihapus', 'Transaksi '.$finance->category.' sebesar '.$finance->amount.' dihapus.', $finance->booking_id);

        return back()->with('success', 'Transaksi dihapus.');
    }
}
