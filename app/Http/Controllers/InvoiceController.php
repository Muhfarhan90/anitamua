<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Invoice;
use App\Models\SiteSetting;
use App\Services\InvoiceService;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Http\Request;

class InvoiceController extends Controller
{
    public function index(Request $request)
    {
        $invoices = Invoice::with(['booking.client'])
            ->whereHas('booking', fn ($query) => $query->whereIn('status', [
                Booking::STATUS_BOOKED,
                Booking::STATUS_COMPLETED,
            ]))
            ->when($request->status, fn ($query, $status) => $query->where('status', $status))
            ->when($request->q, fn ($query, $search) => $query->where(function ($query) use ($search) {
                $query->where('invoice_number', 'like', "%{$search}%")
                    ->orWhereHas('booking', fn ($booking) => $booking
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%"));
            }))
            ->orderByDesc('issue_date')
            ->paginate(20)
            ->withQueryString();

        return view('invoices.index', compact('invoices'));
    }

    public function show(Invoice $invoice, InvoiceService $invoiceService)
    {
        $invoice = $invoiceService->sync($invoice->booking) ?? $invoice;
        $invoice->load(['booking.client', 'booking.package', 'booking.addons', 'booking.payments']);
        $settings = $this->settings();

        return view('invoices.show', compact('invoice', 'settings'));
    }

    public function updateDueDate(Request $request, Invoice $invoice)
    {
        $validated = $request->validate([
            'due_date' => [
                'required',
                'date_format:Y-m-d',
                'after_or_equal:'.$invoice->issue_date->toDateString(),
            ],
        ], [
            'due_date.required' => 'Jatuh tempo wajib diisi.',
            'due_date.date_format' => 'Format jatuh tempo tidak valid.',
            'due_date.after_or_equal' => 'Jatuh tempo tidak boleh sebelum tanggal terbit invoice.',
        ]);

        $invoice->update(['due_date' => $validated['due_date']]);

        return back()->with('success', 'Jatuh tempo pelunasan berhasil diperbarui.');
    }

    public function downloadPdf(Invoice $invoice, InvoiceService $invoiceService)
    {
        $invoiceService->sync($invoice->booking);

        return $this->pdf($invoice->fresh(['booking.client', 'booking.package', 'booking.addons', 'booking.payments']));
    }

    private function pdf(Invoice $invoice)
    {
        $settings = $this->settings();
        $logoSrc = null;
        if (! empty($settings['logo'])) {
            $logoPath = storage_path('app/public/'.$settings['logo']);
            if (is_file($logoPath)) {
                $logoSrc = 'data:'.mime_content_type($logoPath).';base64,'.base64_encode(file_get_contents($logoPath));
            }
        }

        $options = new Options();
        $options->setIsRemoteEnabled(false);
        $options->setIsHtml5ParserEnabled(true);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml(view('invoices.pdf', compact('invoice', 'settings', 'logoSrc'))->render(), 'UTF-8');
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $filename = 'Invoice-'.$invoice->invoice_number.'.pdf';

        return response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$filename.'"',
        ]);
    }

    private function settings(): array
    {
        return SiteSetting::pluck('value', 'key')->toArray();
    }
}
