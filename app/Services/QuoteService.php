<?php

namespace App\Services;

use App\Models\AppSetting;
use App\Models\Campaign;
use App\Models\Invoice;
use App\Models\Quote;
use App\Models\QuoteItem;

class QuoteService
{
    public function __construct(private InvoiceService $invoiceService) {}

    public function generateFromCampaign(Campaign $campaign, int $runs = 1): Quote
    {
        $vatRegistered = AppSetting::get('vat_registered', '0') == '1';
        $vatRate = $vatRegistered ? (float) AppSetting::get('vat_rate', 15) : 0;

        $recipientCount = ($campaign->estimated_recipients > 0 ? $campaign->estimated_recipients : $campaign->validRecipients()->count());
        $smsPerRun = $recipientCount * $campaign->sms_segments;
        $quantity = $smsPerRun * $runs;
        $rate = (float) $campaign->client_rate_per_sms;
        $subtotal = round($quantity * $rate, 2);
        $vatAmount = $vatRegistered ? round($subtotal * ($vatRate / 100), 2) : 0;
        $total = $subtotal + $vatAmount;

        $quote = Quote::create([
            'client_id' => $campaign->client_id,
            'campaign_id' => $campaign->id,
            'quote_number' => Quote::getNextQuoteNumber(),
            'status' => 'draft',
            'sms_quantity' => $quantity,
            'sms_rate' => $rate,
            'subtotal' => $subtotal,
            'vat_enabled' => $vatRegistered,
            'vat_rate' => $vatRate,
            'vat_amount' => $vatAmount,
            'total' => $total,
            'valid_until' => now()->addDays(30),
        ]);

        $runLabel = $runs > 1 ? " × {$runs} runs" : '';
        $segLabel = $campaign->sms_segments > 1 ? ", {$campaign->sms_segments} segments/msg" : '';
        QuoteItem::create([
            'quote_id' => $quote->id,
            'description' => "Bulk SMS — {$campaign->name}{$runLabel} | " . number_format($recipientCount) . " recipients{$segLabel}",
            'quantity' => $quantity,
            'unit_price' => $rate,
            'total' => $subtotal,
        ]);

        return $quote;
    }

    public function acceptQuote(Quote $quote): Invoice
    {
        $invoice = $this->invoiceService->generateFromCampaign($quote->campaign);

        $quote->update([
            'status' => 'accepted',
            'accepted_at' => now(),
            'invoice_id' => $invoice->id,
        ]);

        return $invoice;
    }

    public function declineQuote(Quote $quote): void
    {
        $quote->update(['status' => 'declined']);
    }
}
