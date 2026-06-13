<?php

namespace App\Services;

use App\Models\Campaign;
use App\Services\AuditLogService;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\AppSetting;
use Illuminate\Support\Facades\DB;

class InvoiceService
{
    public function generateFromCampaign(Campaign $campaign, int $runs = 1): Invoice
    {
        $vatRegistered = AppSetting::get('vat_registered', '0') == '1';
        $vatRate = $vatRegistered ? (float) AppSetting::get('vat_rate', 15) : 0;
        $prefix = AppSetting::get('invoice_prefix', 'INV');

        $type = $campaign->campaign_type ?? 'sms';

        // SMS line
        $smsSubtotal = 0; $smsQty = 0; $smsRate = 0;
        if (in_array($type, ['sms', 'both'])) {
            $smsRecipients = $campaign->validRecipients()->count() ?: ($campaign->estimated_recipients ?? 0);
            $smsQty     = $smsRecipients * $campaign->sms_segments * $runs;
            $smsRate    = (float) $campaign->client_rate_per_sms;
            $smsSubtotal = round($smsQty * $smsRate, 2);
        }

        // Email line
        $emailSubtotal = 0; $emailQty = 0; $emailRate = 0;
        if (in_array($type, ['email', 'both'])) {
            $emailRecipients = $campaign->validRecipients()->whereNotNull('email')->count()
                ?: ($campaign->estimated_email_recipients ?? 0);
            $emailQty     = $emailRecipients * $runs;
            $emailRate    = (float) $campaign->client_rate_per_email;
            $emailSubtotal = round($emailQty * $emailRate, 2);
        }

        $subtotal  = $smsSubtotal + $emailSubtotal;
        $vatAmount = $vatRegistered ? round($subtotal * ($vatRate / 100), 2) : 0;
        $total     = $subtotal + $vatAmount;

        return DB::transaction(function () use (
            $campaign, $runs, $prefix, $smsQty, $smsRate, $smsSubtotal,
            $emailQty, $emailRate, $emailSubtotal, $subtotal, $vatRegistered, $vatRate, $vatAmount, $total
        ) {
        $invoice = Invoice::create([
            'client_id'      => $campaign->client_id,
            'campaign_id'    => $campaign->id,
            'invoice_number' => $this->generateInvoiceNumber($prefix),
            'status'         => 'draft',
            'sms_quantity'   => $smsQty,
            'sms_rate'       => $smsRate,
            'email_quantity' => $emailQty,
            'email_rate'     => $emailRate,
            'subtotal'       => $subtotal,
            'vat_enabled'    => $vatRegistered,
            'vat_rate'       => $vatRate,
            'vat_amount'     => $vatAmount,
            'total'          => $total,
            'due_date'       => now()->addDays(7),
        ]);

        if ($smsQty > 0) {
            $smsRecipients = $campaign->validRecipients()->count() ?: ($campaign->estimated_recipients ?? 0);
            $runLabel = $runs > 1 ? " × {$runs} runs" : '';
            $segLabel = $campaign->sms_segments > 1 ? ", {$campaign->sms_segments} segments/msg" : '';
            InvoiceItem::create([
                'invoice_id'  => $invoice->id,
                'description' => "Bulk SMS — {$campaign->name}{$runLabel} | " . number_format($smsRecipients) . " recipients{$segLabel}",
                'quantity'    => $smsQty,
                'unit_price'  => $smsRate,
                'total'       => $smsSubtotal,
            ]);
        }

        if ($emailQty > 0) {
            $emailRecipients = $campaign->validRecipients()->whereNotNull('email')->count()
                ?: ($campaign->estimated_email_recipients ?? 0);
            $runLabel = $runs > 1 ? " × {$runs} runs" : '';
            InvoiceItem::create([
                'invoice_id'  => $invoice->id,
                'description' => "Email Campaign (SES) — {$campaign->name}{$runLabel} | " . number_format($emailRecipients) . " recipients",
                'quantity'    => $emailQty,
                'unit_price'  => $emailRate,
                'total'       => $emailSubtotal,
            ]);
        }

        $campaign->update(['status' => 'invoice_generated']);

        return $invoice;
        });
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
