<?php

namespace App\Http\Controllers;

use App\Jobs\SendCampaignJob;
use Illuminate\Support\Str;
use App\Models\Campaign;
use App\Models\Client;
use App\Models\SendingDomain;
use App\Services\AuditLogService;
use App\Services\SmsCounter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CampaignController extends Controller
{
    public function index(Request $request)
    {
        $query = Campaign::with(['client' => fn($q) => $q->withTrashed()])->notArchived()->latest();

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
        $sendingDomains = SendingDomain::verified()->orderBy('domain')->get();
        return view('campaigns.create', compact('clients', 'sendingDomains'));
    }

    public function store(Request $request)
    {
        $type = $request->input('campaign_type', 'sms');
        $validated = $request->validate([
            'client_id'                  => 'required|exists:clients,id',
            'name'                       => 'required|string|max:255',
            'campaign_type'              => 'required|in:sms,email,both',
            'message'                    => 'required_if:campaign_type,sms|required_if:campaign_type,both|nullable|string',
            'email_subject'              => 'required_if:campaign_type,email|required_if:campaign_type,both|nullable|string|max:255',
            'email_from_name'            => 'nullable|string|max:255',
            'email_from_address'         => 'nullable|email|max:255',
            'email_reply_to'             => 'nullable|email|max:255',
            'email_body'                 => 'required_if:campaign_type,email|required_if:campaign_type,both|nullable|string',
            'notes'                      => 'nullable|string',
            'internal_cost_per_sms'      => 'nullable|numeric|min:0',
            'client_rate_per_sms'        => 'nullable|numeric|min:0',
            'estimated_recipients'       => 'nullable|integer|min:0',
            'sender_name'                => 'nullable|string|max:11',
            'internal_cost_per_email'    => 'nullable|numeric|min:0',
            'client_rate_per_email'      => 'nullable|numeric|min:0',
            'estimated_email_recipients' => 'nullable|integer|min:0',
            'repeat_enabled'             => 'nullable|boolean',
            'repeat_count'               => 'nullable|integer|min:2|max:52',
        ]);

        if (in_array($type, ['sms', 'both']) && !empty($validated['message'])) {
            $smsCount = SmsCounter::count($validated['message']);
            $validated['sms_segments'] = $smsCount['segments'];
        } else {
            $validated['sms_segments'] = 1;
        }
        $validated['user_id'] = auth()->id();

        $repeatCount = ($validated['repeat_enabled'] ?? false) ? (int) ($validated['repeat_count'] ?? 2) : 1;
        unset($validated['repeat_enabled'], $validated['repeat_count']);

        if ($repeatCount > 1) {
            $groupId = Str::uuid()->toString();
            $baseName = $validated['name'];

            $first = DB::transaction(function () use ($repeatCount, $validated, $baseName, $groupId) {
                $first = null;
                for ($i = 1; $i <= $repeatCount; $i++) {
                    $data = array_merge($validated, [
                        'name' => "{$baseName} (Run {$i} of {$repeatCount})",
                        'campaign_group_id' => $groupId,
                        'campaign_group_run' => $i,
                    ]);
                    $c = Campaign::create($data);
                    AuditLogService::log('campaign_created', $c, null, ['name' => $c->name]);
                    if ($i === 1) $first = $c;
                }
                return $first;
            });

            return redirect()->route('campaigns.show', $first)
                ->with('success', "{$repeatCount} campaign runs created. You're viewing Run 1.");
        }

        $campaign = Campaign::create($validated);
        AuditLogService::log('campaign_created', $campaign, null, ['name' => $campaign->name]);

        return redirect()->route('campaigns.show', $campaign)
            ->with('success', 'Campaign created successfully.');
    }

    public function show(Campaign $campaign)
    {
        $campaign->load(['client', 'invoices', 'quotes', 'recipients']);

        // One grouped query instead of a count() per status.
        $smsCounts = $campaign->smsLogs()
            ->selectRaw('status, count(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        $deliveryStats = [
            'total'       => $smsCounts->sum(),
            'submitted'   => $smsCounts['submitted']   ?? 0,
            'delivered'   => $smsCounts['delivered']   ?? 0,
            'undelivered' => $smsCounts['undelivered'] ?? 0,
            'expired'     => $smsCounts['expired']     ?? 0,
            'blacklisted' => $smsCounts['blacklisted'] ?? 0,
            'no_route'    => $smsCounts['no_route']    ?? 0,
            'failed'      => $smsCounts['failed']      ?? 0,
            'pending'     => $smsCounts['pending']     ?? 0,
        ];

        $total = max($deliveryStats['total'], 1);
        $deliveryStats['delivery_pct'] = round(($deliveryStats['delivered'] / $total) * 100, 1);
        $deliveryStats['failure_pct'] = round(
            (($deliveryStats['undelivered'] + $deliveryStats['failed'] + $deliveryStats['expired'] + $deliveryStats['no_route']) / $total) * 100, 1
        );

        // One grouped query for email delivery stats too.
        $emailCounts = $campaign->emailLogs()
            ->selectRaw('status, count(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        $emailStats = [
            'total'      => $emailCounts->sum(),
            'sent'       => $emailCounts['sent']       ?? 0,
            'delivered'  => $emailCounts['delivered']  ?? 0,
            'bounced'    => $emailCounts['bounced']    ?? 0,
            'complained' => $emailCounts['complained'] ?? 0,
            'rejected'   => $emailCounts['rejected']   ?? 0,
            'failed'     => $emailCounts['failed']     ?? 0,
        ];

        return view('campaigns.show', compact('campaign', 'deliveryStats', 'emailStats'));
    }

    public function edit(Campaign $campaign)
    {
        abort_if(!in_array($campaign->status, ['draft']), 403, 'Only draft campaigns can be edited.');
        $clients = Client::where('status', 'active')->get();
        $sendingDomains = SendingDomain::verified()->orderBy('domain')->get();
        return view('campaigns.edit', compact('campaign', 'clients', 'sendingDomains'));
    }

    public function update(Request $request, Campaign $campaign)
    {
        abort_if(!in_array($campaign->status, ['draft']), 403);

        $type = $request->input('campaign_type', 'sms');
        $validated = $request->validate([
            'client_id'                  => 'required|exists:clients,id',
            'name'                       => 'required|string|max:255',
            'campaign_type'              => 'required|in:sms,email,both',
            'message'                    => 'required_if:campaign_type,sms|required_if:campaign_type,both|nullable|string',
            'email_subject'              => 'required_if:campaign_type,email|required_if:campaign_type,both|nullable|string|max:255',
            'email_from_name'            => 'nullable|string|max:255',
            'email_from_address'         => 'nullable|email|max:255',
            'email_reply_to'             => 'nullable|email|max:255',
            'email_body'                 => 'required_if:campaign_type,email|required_if:campaign_type,both|nullable|string',
            'notes'                      => 'nullable|string',
            'internal_cost_per_sms'      => 'nullable|numeric|min:0',
            'client_rate_per_sms'        => 'nullable|numeric|min:0',
            'estimated_recipients'       => 'nullable|integer|min:0',
            'sender_name'                => 'nullable|string|max:11',
            'internal_cost_per_email'    => 'nullable|numeric|min:0',
            'client_rate_per_email'      => 'nullable|numeric|min:0',
            'estimated_email_recipients' => 'nullable|integer|min:0',
        ]);

        if (in_array($type, ['sms', 'both']) && !empty($validated['message'])) {
            $validated['sms_segments'] = SmsCounter::count($validated['message'])['segments'];
        } else {
            $validated['sms_segments'] = 1;
        }

        $old = $campaign->toArray();
        $campaign->update($validated);
        AuditLogService::log('campaign_updated', $campaign, $old, $validated);

        return redirect()->route('campaigns.show', $campaign)
            ->with('success', 'Campaign updated.');
    }

    public function destroy(Campaign $campaign)
    {
        abort_if(!$campaign->canBeDeleted(), 403, 'Campaign cannot be deleted once it is sending or completed.');
        AuditLogService::log('campaign_deleted', $campaign);
        $campaign->delete();

        return redirect()->route('campaigns.index')->with('success', 'Campaign deleted.');
    }

    public function archived()
    {
        $campaigns = Campaign::with(['client' => fn($q) => $q->withTrashed()])
            ->archived()->latest()->paginate(20);
        return view('campaigns.archived', compact('campaigns'));
    }

    public function archive(Campaign $campaign)
    {
        abort_if(!in_array($campaign->status, ['completed', 'partially_completed', 'cancelled', 'failed']), 403, 'Only finished campaigns can be archived.');
        $campaign->update(['archived_at' => now()]);
        return redirect()->route('campaigns.index')->with('success', 'Campaign archived.');
    }

    public function restoreArchive(Campaign $campaign)
    {
        $campaign->update(['archived_at' => null]);
        return redirect()->route('campaigns.archived')->with('success', 'Campaign restored.');
    }

    public function schedule(Request $request, Campaign $campaign)
    {
        abort_if(!$campaign->canBeScheduled(), 403, 'Campaign is not ready to schedule.');

        $validated = $request->validate([
            'send_type'        => 'required|in:immediate,scheduled',
            'scheduled_at'     => 'required_if:send_type,scheduled|nullable|date|after:now',
            'scheduled_end_at' => 'nullable|date|after:scheduled_at',
        ]);

        if ($validated['send_type'] === 'immediate') {
            $campaign->update(['status' => 'sending', 'scheduled_at' => now()]);
            SendCampaignJob::dispatch($campaign);
            return redirect()->route('campaigns.show', $campaign)
                ->with('success', 'Campaign is being sent now.');
        }

        $hasRange = !empty($validated['scheduled_end_at']);
        $campaign->update([
            'status'               => 'scheduled',
            'scheduled_at'         => $validated['scheduled_at'],
            'scheduled_end_at'     => $hasRange ? $validated['scheduled_end_at'] : null,
            'is_recurring_schedule'=> $hasRange,
        ]);

        return redirect()->route('campaigns.show', $campaign)
            ->with('success', 'Campaign scheduled for ' . $campaign->scheduled_at->format('d M Y H:i'));
    }

    public function cancel(Campaign $campaign)
    {
        abort_if(in_array($campaign->status, ['completed']), 403);
        $campaign->update(['status' => 'cancelled']);
        return redirect()->route('campaigns.show', $campaign)->with('success', 'Campaign stopped.');
    }

    public function pause(Campaign $campaign)
    {
        abort_if(!$campaign->canBePaused(), 403, 'Campaign cannot be paused.');
        $campaign->update(['status' => 'paused']);
        return redirect()->route('campaigns.show', $campaign)->with('success', 'Campaign paused.');
    }

    public function resume(Campaign $campaign)
    {
        abort_if(!$campaign->canBeResumed(), 403, 'Campaign cannot be resumed.');
        $campaign->update(['status' => 'sending']);
        SendCampaignJob::dispatch($campaign);
        return redirect()->route('campaigns.show', $campaign)->with('success', 'Campaign resumed.');
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
