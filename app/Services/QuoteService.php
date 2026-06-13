<?php

namespace App\Services;

use App\Models\AppSetting;
use App\Models\Campaign;
use App\Models\Invoice;
use App\Models\Quote;
use App\Models\QuoteItem;
use Illuminate\Support\Facades\DB;

class QuoteService
{
    public function __construct(private InvoiceService $invoiceService) {}

    public function generateFromCampaign(Campaign $campaign, int $runs = 1): Quote
    {
        $vatRegistered = AppSetting::get('vat_registered', '0') == '1';
        $vatRate = $vatRegistered ? (float) AppSetting::get('vat_rate', 15) : 0;

        $type = $campaign->campaign_type ?? 'sms';

        // SMS line
        $smsSubtotal = 0; $smsQty = 0; $smsRate = 0;
        if (in_array($type, ['sms', 'both'])) {
            $smsRecipients = $campaign->estimated_recipients > 0
                ? $campaign->estimated_recipients
                : $campaign->validRecipients()->count();
            $smsQty      = $smsRecipients * $campaign->sms_segments * $runs;
            $smsRate     = (float) $campaign->client_rate_per_sms;
            $smsSubtotal = round($smsQty * $smsRate, 2);
        }

        // Email line
        $emailSubtotal = 0; $emailQty = 0; $emailRate = 0;
        if (in_array($type, ['email', 'both'])) {
            $emailRecipients = $campaign->estimated_email_recipients > 0
                ? $campaign->estimated_email_recipients
                : $campaign->validRecipients()->whereNotNull('email')->count();
            $emailQty      = $emailRecipients * $runs;
            $emailRate     = (float) $campaign->client_rate_per_email;
            $emailSubtotal = round($emailQty * $emailRate, 2);
        }

        $subtotal  = $smsSubtotal + $emailSubtotal;
        $vatAmount = $vatRegistered ? round($subtotal * ($vatRate / 100), 2) : 0;
        $total     = $subtotal + $vatAmount;

        return DB::transaction(function () use (
            $campaign, $runs, $smsQty, $smsRate, $smsSubtotal,
            $emailQty, $emailRate, $emailSubtotal, $subtotal, $vatRegistered, $vatRate, $vatAmount, $total
        ) {
        $quote = Quote::create([
            'client_id'      => $campaign->client_id,
            'campaign_id'    => $campaign->id,
            'quote_number'   => Quote::getNextQuoteNumber(),
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
            'valid_until'    => now()->addDays(30),
        ]);

        if ($smsQty > 0) {
            $smsRecipients = $campaign->estimated_recipients > 0
                ? $campaign->estimated_recipients
                : $campaign->validRecipients()->count();
            $runLabel = $runs > 1 ? " × {$runs} runs" : '';
            $segLabel = $campaign->sms_segments > 1 ? ", {$campaign->sms_segments} segments/msg" : '';
            QuoteItem::create([
                'quote_id'    => $quote->id,
                'description' => "Bulk SMS — {$campaign->name}{$runLabel} | " . number_format($smsRecipients) . " recipients{$segLabel}",
                'quantity'    => $smsQty,
                'unit_price'  => $smsRate,
                'total'       => $smsSubtotal,
            ]);
        }

        if ($emailQty > 0) {
            $emailRecipients = $campaign->estimated_email_recipients > 0
                ? $campaign->estimated_email_recipients
                : $campaign->validRecipients()->whereNotNull('email')->count();
            $runLabel = $runs > 1 ? " × {$runs} runs" : '';
            QuoteItem::create([
                'quote_id'    => $quote->id,
                'description' => "Email Campaign (SES) — {$campaign->name}{$runLabel} | " . number_format($emailRecipients) . " recipients",
                'quantity'    => $emailQty,
                'unit_price'  => $emailRate,
                'total'       => $emailSubtotal,
            ]);
        }

        return $quote;
        });
    }

    public function acceptQuote(Quote $quote): Invoice
    {
        return DB::transaction(function () use ($quote) {
            $invoice = $this->invoiceService->generateFromCampaign($quote->campaign);

            $quote->update([
                'status'     => 'accepted',
                'accepted_at' => now(),
                'invoice_id' => $invoice->id,
            ]);

            return $invoice;
        });
    }

    public function declineQuote(Quote $quote): void
    {
        $quote->update(['status' => 'declined']);
    }
}
