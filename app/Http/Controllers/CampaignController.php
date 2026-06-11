<?php

namespace App\Http\Controllers;

use App\Jobs\SendCampaignJob;
use App\Models\Campaign;
use App\Models\Client;
use App\Services\AuditLogService;
use App\Services\SmsCounter;
use Illuminate\Http\Request;

class CampaignController extends Controller
{
    public function index(Request $request)
    {
        $query = Campaign::with('client')->latest();

        if ($request->status) {
            $query->where('status', $request->status);
        }

        if ($request->client_id) {
            $query->where('client_id', $request->client_id);
        }

        if ($request->search) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        $campaigns = $query->paginate(20)->withQueryString();
        $clients = Client::active()->pluck('company_name', 'id');

        return view('campaigns.index', compact('campaigns', 'clients'));
    }

    public function create()
    {
        $clients = Client::where('status', 'active')->get();
        return view('campaigns.create', compact('clients'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'client_id' => 'required|exists:clients,id',
            'name' => 'required|string|max:255',
            'message' => 'required|string',
            'notes' => 'nullable|string',
            'internal_cost_per_sms' => 'required|numeric|min:0',
            'client_rate_per_sms' => 'required|numeric|min:0',
            'estimated_recipients' => 'nullable|integer|min:0',
            'sender_name' => 'nullable|string|max:11',
        ]);

        $smsCount = SmsCounter::count($validated['message']);
        $validated['sms_segments'] = $smsCount['segments'];
        $validated['user_id'] = auth()->id();

        $campaign = Campaign::create($validated);
        AuditLogService::log('campaign_created', $campaign, null, ['name' => $campaign->name]);

        return redirect()->route('campaigns.show', $campaign)
            ->with('success', 'Campaign created successfully.');
    }

    public function show(Campaign $campaign)
    {
        $campaign->load(['client', 'invoices', 'recipients']);

        $deliveryStats = [
            'total' => $campaign->smsLogs()->count(),
            'submitted' => $campaign->smsLogs()->where('status', 'submitted')->count(),
            'delivered' => $campaign->smsLogs()->where('status', 'delivered')->count(),
            'undelivered' => $campaign->smsLogs()->where('status', 'undelivered')->count(),
            'expired' => $campaign->smsLogs()->where('status', 'expired')->count(),
            'blacklisted' => $campaign->smsLogs()->where('status', 'blacklisted')->count(),
            'no_route' => $campaign->smsLogs()->where('status', 'no_route')->count(),
            'failed' => $campaign->smsLogs()->where('status', 'failed')->count(),
            'pending' => $campaign->smsLogs()->where('status', 'pending')->count(),
        ];

        $total = max($deliveryStats['total'], 1);
        $deliveryStats['delivery_pct'] = round(($deliveryStats['delivered'] / $total) * 100, 1);
        $deliveryStats['failure_pct'] = round(
            (($deliveryStats['undelivered'] + $deliveryStats['failed'] + $deliveryStats['expired'] + $deliveryStats['no_route']) / $total) * 100, 1
        );

        return view('campaigns.show', compact('campaign', 'deliveryStats'));
    }

    public function edit(Campaign $campaign)
    {
        abort_if(!in_array($campaign->status, ['draft']), 403, 'Only draft campaigns can be edited.');
        $clients = Client::where('status', 'active')->get();
        return view('campaigns.edit', compact('campaign', 'clients'));
    }

    public function update(Request $request, Campaign $campaign)
    {
        abort_if(!in_array($campaign->status, ['draft']), 403);

        $validated = $request->validate([
            'client_id' => 'required|exists:clients,id',
            'name' => 'required|string|max:255',
            'message' => 'required|string',
            'notes' => 'nullable|string',
            'internal_cost_per_sms' => 'required|numeric|min:0',
            'client_rate_per_sms' => 'required|numeric|min:0',
            'estimated_recipients' => 'nullable|integer|min:0',
            'sender_name' => 'nullable|string|max:11',
        ]);

        $smsCount = SmsCounter::count($validated['message']);
        $validated['sms_segments'] = $smsCount['segments'];

        $old = $campaign->toArray();
        $campaign->update($validated);
        AuditLogService::log('campaign_updated', $campaign, $old, $validated);

        return redirect()->route('campaigns.show', $campaign)
            ->with('success', 'Campaign updated.');
    }

    public function destroy(Campaign $campaign)
    {
        abort_if(!in_array($campaign->status, ['draft', 'cancelled']), 403);
        AuditLogService::log('campaign_deleted', $campaign);
        $campaign->delete();

        return redirect()->route('campaigns.index')->with('success', 'Campaign deleted.');
    }

    public function schedule(Request $request, Campaign $campaign)
    {
        abort_if(!$campaign->canBeScheduled(), 403, 'Campaign is not ready to schedule.');

        $validated = $request->validate([
            'send_type' => 'required|in:immediate,scheduled',
            'scheduled_at' => 'required_if:send_type,scheduled|nullable|date|after:now',
        ]);

        if ($validated['send_type'] === 'immediate') {
            $campaign->update(['status' => 'sending', 'scheduled_at' => now()]);
            SendCampaignJob::dispatch($campaign);
            return redirect()->route('campaigns.show', $campaign)
                ->with('success', 'Campaign is being sent now.');
        }

        $campaign->update([
            'status' => 'scheduled',
            'scheduled_at' => $validated['scheduled_at'],
        ]);

        return redirect()->route('campaigns.show', $campaign)
            ->with('success', 'Campaign scheduled for ' . $campaign->scheduled_at->format('d M Y H:i'));
    }

    public function cancel(Campaign $campaign)
    {
        abort_if(in_array($campaign->status, ['sending', 'completed']), 403);
        $campaign->update(['status' => 'cancelled']);
        return redirect()->route('campaigns.show', $campaign)->with('success', 'Campaign cancelled.');
    }

    public function report(Campaign $campaign)
    {
        $campaign->load(['client', 'smsLogs']);

        $failedRecipients = $campaign->smsLogs()
            ->whereIn('status', ['undelivered', 'expired', 'failed', 'no_route', 'blacklisted'])
            ->get();

        return view('campaigns.report', compact('campaign', 'failedRecipients'));
    }

    public function exportReport(Campaign $campaign)
    {
        $logs = $campaign->smsLogs()->with('recipient')->get();

        $filename = "campaign_{$campaign->id}_report_" . now()->format('YmdHis') . ".csv";

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($logs, $campaign) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Campaign', 'Recipient', 'Phone', 'Status', 'Sent At', 'Delivered At', 'Failure Reason']);

            foreach ($logs as $log) {
                fputcsv($handle, [
                    $campaign->name,
                    $log->recipient?->name ?? '',
                    $log->recipient_number,
                    $log->status,
                    $log->sent_at?->format('Y-m-d H:i:s') ?? '',
                    $log->delivered_at?->format('Y-m-d H:i:s') ?? '',
                    $log->failure_reason ?? '',
                ]);
            }

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }
}
