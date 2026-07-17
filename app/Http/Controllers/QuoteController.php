<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use App\Models\Client;
use App\Models\Quote;
use App\Services\QuoteService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class QuoteController extends Controller
{
    public function __construct(private QuoteService $quoteService) {}

    public function create()
    {
        $clients = Client::where('status', 'active')->get();
        // Every draft/recipients_uploaded campaign is quotable now — one with zero
        // recipients yet just needs a manual pre-sales estimate entered below.
        $campaigns = Campaign::with('client')
            ->whereIn('status', ['draft', 'recipients_uploaded'])
            ->latest()->get();
        return view('quotes.create', compact('clients', 'campaigns'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'campaign_id'                => 'required|exists:campaigns,id',
            'estimated_recipients'       => 'nullable|integer|min:1',
            'estimated_email_recipients' => 'nullable|integer|min:1',
        ]);
        $campaign = Campaign::findOrFail($validated['campaign_id']);

        // Manual pre-sales estimate — only ever applied when the campaign has no
        // real recipients yet. Once real recipients exist they're always the
        // source of truth; this never overrides an actual, uploaded/selected list.
        $hasRealRecipients = $campaign->validRecipients()->exists();
        if (!$hasRealRecipients) {
            $type = $campaign->campaign_type ?? 'sms';
            $updates = [];
            if (in_array($type, ['sms', 'both']) && !empty($validated['estimated_recipients'])) {
                $updates['estimated_recipients'] = $validated['estimated_recipients'];
            }
            if (in_array($type, ['email', 'both']) && !empty($validated['estimated_email_recipients'])) {
                $updates['estimated_email_recipients'] = $validated['estimated_email_recipients'];
            }
            if ($updates) {
                $campaign->update($updates);
            }
        }

        $type = $campaign->campaign_type ?? 'sms';
        $smsReady   = !in_array($type, ['sms', 'both'])   || $campaign->estimated_recipients > 0       || $hasRealRecipients;
        $emailReady = !in_array($type, ['email', 'both']) || $campaign->estimated_email_recipients > 0 || $hasRealRecipients;

        if (!$smsReady || !$emailReady) {
            return back()->withInput()->with('error', 'This campaign has no recipients yet — enter an estimated count to generate a pre-sales quote.');
        }

        $runs = $this->groupRunCount($campaign);
        $quote = $this->quoteService->generateFromCampaign($campaign, $runs);
        return redirect()->route('quotes.show', $quote)->with('success', 'Quote created.');
    }

    public function index()
    {
        $quotes = Quote::with(['client', 'campaign'])
            ->latest()
            ->paginate(20);

        return view('quotes.index', compact('quotes'));
    }

    public function show(Quote $quote)
    {
        $quote->load(['client', 'campaign', 'items', 'invoice']);
        return view('quotes.show', compact('quote'));
    }

    public function generate(Campaign $campaign)
    {
        abort_if(
            !in_array($campaign->status, ['draft','recipients_uploaded','invoice_generated','awaiting_payment','ready_to_schedule'])
            || ($campaign->status === 'draft' && $campaign->estimated_recipients <= 0),
            403,
            'Campaign needs estimated recipients to generate a quote.'
        );

        $runs = $this->groupRunCount($campaign);
        $quote = $this->quoteService->generateFromCampaign($campaign, $runs);

        return redirect()->route('quotes.show', $quote)
            ->with('success', 'Quote generated successfully.');
    }

    public function accept(Quote $quote)
    {
        abort_if(!in_array($quote->status, ['draft', 'sent']), 403, 'Quote cannot be accepted in its current state.');
        abort_if(!$quote->campaign, 403, 'Quote has no linked campaign.');

        $invoice = $this->quoteService->acceptQuote($quote);

        return redirect()->route('invoices.show', $invoice)
            ->with('success', 'Quote accepted. Invoice generated successfully.');
    }

    public function decline(Quote $quote)
    {
        $this->quoteService->declineQuote($quote);

        return redirect()->back()->with('success', 'Quote declined.');
    }

    public function updateStatus(Request $request, Quote $quote)
    {
        $request->validate(['status' => 'required|in:draft,sent,accepted,declined,expired']);

        $quote->update(['status' => $request->status]);

        return redirect()->route('quotes.show', $quote)
            ->with('success', "Quote status updated to {$request->status}.");
    }

    public function pdf(Quote $quote)
    {
        $quote->load(['client', 'campaign', 'items']);
        $pdf = Pdf::loadView('quotes.pdf', compact('quote'));

        return $pdf->download("quote_{$quote->quote_number}.pdf");
    }

    private function groupRunCount(Campaign $campaign): int
    {
        if (!$campaign->campaign_group_id) {
            return 1;
        }
        return Campaign::where('campaign_group_id', $campaign->campaign_group_id)->count() ?: 1;
    }
}
