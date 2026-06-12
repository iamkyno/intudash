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

    public function generateFromCampaign(Campaign $campaign): Quote
    {
        $vatEnabled = AppSetting::get('vat_enabled', config('app.vat_enabled', true));
        $vatRate = (float) AppSetting::get('vat_rate', config('app.vat_rate', 15));

        $quantity = (($campaign->estimated_recipients > 0 ? $campaign->estimated_recipients : $campaign->validRecipients()->count())) * $campaign->sms_segments;
        $rate = (float) $campaign->client_rate_per_sms;
        $subtotal = round($quantity * $rate, 2);
        $vatAmount = $vatEnabled ? round($subtotal * ($vatRate / 100), 2) : 0;
        $total = $subtotal + $vatAmount;

        $quote = Quote::create([
            'client_id' => $campaign->client_id,
            'campaign_id' => $campaign->id,
            'quote_number' => Quote::getNextQuoteNumber(),
            'status' => 'draft',
            'sms_quantity' => $quantity,
            'sms_rate' => $rate,
            'subtotal' => $subtotal,
            'vat_enabled' => $vatEnabled,
            'vat_rate' => $vatRate,
            'vat_amount' => $vatAmount,
            'total' => $total,
            'valid_until' => now()->addDays(30),
        ]);

        QuoteItem::create([
            'quote_id' => $quote->id,
            'description' => "Bulk SMS - {$campaign->name} ({$quantity} SMS @ R{$rate} each)",
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
