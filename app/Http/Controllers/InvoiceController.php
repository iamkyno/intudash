<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use App\Models\Invoice;
use App\Services\AuditLogService;
use App\Services\InvoiceService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class InvoiceController extends Controller
{
    public function __construct(private InvoiceService $invoiceService) {}

    public function index()
    {
        $invoices = Invoice::with(['client', 'campaign'])
            ->latest()
            ->paginate(20);

        return view('invoices.index', compact('invoices'));
    }

    public function generate(Campaign $campaign)
    {
        abort_if(!in_array($campaign->status, ['recipients_uploaded', 'invoice_generated', 'awaiting_payment']), 403, 'Campaign is not ready for invoicing.');

        $runs = $campaign->campaign_group_id
            ? Campaign::where('campaign_group_id', $campaign->campaign_group_id)->count()
            : 1;
        $invoice = $this->invoiceService->generateFromCampaign($campaign, max(1, $runs));

        return redirect()->route('invoices.show', $invoice)
            ->with('success', 'Invoice generated successfully.');
    }

    public function show(Invoice $invoice)
    {
        $invoice->load(['client', 'campaign', 'items']);
        return view('invoices.show', compact('invoice'));
    }

    public function updateStatus(Request $request, Invoice $invoice)
    {
        $request->validate(['status' => 'required|in:draft,sent,paid,overdue,cancelled']);

        $oldStatus = $invoice->status;
        $newStatus = $request->status;

        if ($newStatus === 'paid') {
            $this->invoiceService->markAsPaid($invoice);
        } else {
            $invoice->update(['status' => $newStatus]);
        }

        AuditLogService::log('invoice_status_changed', $invoice, ['status' => $oldStatus], ['status' => $newStatus]);

        if ($newStatus === 'paid' && $invoice->campaign) {
            return redirect()->route('invoices.show', $invoice)
                ->with('success', 'Invoice marked as paid. Campaign is now ready to schedule.');
        }

        return redirect()->route('invoices.show', $invoice)
            ->with('success', "Invoice status updated to {$newStatus}.");
    }

    public function pdf(Invoice $invoice)
    {
        $invoice->load(['client', 'campaign', 'items']);
        $pdf = Pdf::loadView('invoices.pdf', compact('invoice'));

        return $pdf->download("invoice_{$invoice->invoice_number}.pdf");
    }
}
