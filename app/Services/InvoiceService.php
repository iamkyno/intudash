<?php

namespace App\Services;

use App\Models\Campaign;
use App\Services\AuditLogService;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\AppSetting;

class InvoiceService
{
    public function generateFromCampaign(Campaign $campaign): Invoice
    {
        $vatEnabled = AppSetting::get('vat_enabled', config('app.vat_enabled', true));
        $vatRate = (float) AppSetting::get('vat_rate', config('app.vat_rate', 15));
        $prefix = AppSetting::get('invoice_prefix', 'INV');

        $quantity = $campaign->validRecipients()->count() * $campaign->sms_segments;
        $rate = (float) $campaign->client_rate_per_sms;
        $subtotal = round($quantity * $rate, 2);
        $vatAmount = $vatEnabled ? round($subtotal * ($vatRate / 100), 2) : 0;
        $total = $subtotal + $vatAmount;

        $invoice = Invoice::create([
            'client_id' => $campaign->client_id,
            'campaign_id' => $campaign->id,
            'invoice_number' => $this->generateInvoiceNumber($prefix),
            'status' => 'draft',
            'sms_quantity' => $quantity,
            'sms_rate' => $rate,
            'subtotal' => $subtotal,
            'vat_enabled' => $vatEnabled,
            'vat_rate' => $vatRate,
            'vat_amount' => $vatAmount,
            'total' => $total,
            'due_date' => now()->addDays(7),
        ]);

        InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'description' => "Bulk SMS - {$campaign->name} ({$quantity} SMS @ R{$rate} each)",
            'quantity' => $quantity,
            'unit_price' => $rate,
            'total' => $subtotal,
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
