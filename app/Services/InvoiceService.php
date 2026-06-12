<?php

namespace App\Services;

use App\Models\Campaign;
use App\Services\AuditLogService;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\AppSetting;

class InvoiceService
{
    public function generateFromCampaign(Campaign $campaign, int $runs = 1): Invoice
    {
        $vatRegistered = AppSetting::get('vat_registered', '0') == '1';
        $vatRate = $vatRegistered ? (float) AppSetting::get('vat_rate', 15) : 0;
        $prefix = AppSetting::get('invoice_prefix', 'INV');

        $recipientCount = $campaign->validRecipients()->count() ?: ($campaign->estimated_recipients ?? 0);
        $smsPerRun = $recipientCount * $campaign->sms_segments;
        $quantity = $smsPerRun * $runs;
        $rate = (float) $campaign->client_rate_per_sms;

        // When VAT registered: subtotal is ex-VAT, total includes VAT
        // When not VAT registered: total is VAT-inclusive, no breakdown shown
        $subtotal = round($quantity * $rate, 2);
        $vatAmount = $vatRegistered ? round($subtotal * ($vatRate / 100), 2) : 0;
        $total = $subtotal + $vatAmount;

        $invoice = Invoice::create([
            'client_id'    => $campaign->client_id,
            'campaign_id'  => $campaign->id,
            'invoice_number' => $this->generateInvoiceNumber($prefix),
            'status'       => 'draft',
            'sms_quantity' => $quantity,
            'sms_rate'     => $rate,
            'subtotal'     => $subtotal,
            'vat_enabled'  => $vatRegistered,
            'vat_rate'     => $vatRate,
            'vat_amount'   => $vatAmount,
            'total'        => $total,
            'due_date'     => now()->addDays(7),
        ]);

        $runLabel = $runs > 1 ? " × {$runs} run" . ($runs > 1 ? 's' : '') : '';
        $segLabel = $campaign->sms_segments > 1 ? ", {$campaign->sms_segments} segments/msg" : '';
        InvoiceItem::create([
            'invoice_id'  => $invoice->id,
            'description' => "Bulk SMS — {$campaign->name}{$runLabel} | " . number_format($recipientCount) . " recipients{$segLabel}",
            'quantity'    => $quantity,
            'unit_price'  => $rate,
            'total'       => $subtotal,
        ]);

        $campaign->update(['status' => 'invoice_generated']);

        return $invoice;
    }

    public function markAsPaid(Invoice $invoice): void
    {
        $invoice->update(['status' => 'paid', 'paid_at' => now()]);

        if ($invoice->campaign) {
            $invoice->campaign->update(['status' => 'ready_to_schedule']);
        }

        AuditLogService::log('invoice_paid', $invoice, null, ['status' => 'paid']);
    }

    private function generateInvoiceNumber(string $prefix): string
    {
        $year = date('Y');
        $last = Invoice::withTrashed()
            ->where('invoice_number', 'like', "{$prefix}-{$year}-%")
            ->orderByDesc('id')
            ->first();

        $seq = $last
            ? ((int) explode('-', $last->invoice_number)[2]) + 1
            : 1;

        return "{$prefix}-{$year}-" . str_pad($seq, 4, '0', STR_PAD_LEFT);
    }
}
