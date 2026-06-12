@extends('layouts.app')
@section('title', $campaign->name)
@section('page-title', $campaign->name)

@section('content')
@php
$badgeMap = ['draft'=>'badge-neutral','recipients_uploaded'=>'badge-info','invoice_generated'=>'badge-info','awaiting_payment'=>'badge-warning','ready_to_schedule'=>'badge-success','scheduled'=>'badge-brand','sending'=>'badge-info','completed'=>'badge-success','partially_completed'=>'badge-warning','failed'=>'badge-danger','cancelled'=>'badge-neutral','paused'=>'badge-warning'];
$labelMap = ['draft'=>'Draft','recipients_uploaded'=>'Recipients Uploaded','invoice_generated'=>'Invoice Generated','awaiting_payment'=>'Awaiting Payment','ready_to_schedule'=>'Ready to Schedule','scheduled'=>'Scheduled','sending'=>'Sending','completed'=>'Completed','partially_completed'=>'Partial','failed'=>'Failed','cancelled'=>'Cancelled','paused'=>'Paused'];
@endphp

<div class="page-header">
    <div class="d-flex align-items-center gap-3">
        <a href="{{ route('campaigns.index') }}" class="btn btn-ghost btn-sm btn-icon"><i class="bi bi-arrow-left"></i></a>
        <div>
            <h1>{{ $campaign->name }}</h1>
            <p>{{ $campaign->client->company_name }}</p>
        </div>
        <span class="badge {{ $badgeMap[$campaign->status] ?? 'badge-neutral' }} badge-dot" style="font-size:12px;padding:5px 10px;">
            {{ $labelMap[$campaign->status] ?? $campaign->status }}
        </span>
    </div>
    <div class="d-flex gap-2">
        @if($campaign->status === 'draft')
            <a href="{{ route('campaigns.edit', $campaign) }}" class="btn btn-ghost btn-sm"><i class="bi bi-pencil"></i> Edit</a>
        @endif
        @if(!in_array($campaign->status, ['sending','completed','cancelled']))
            <form action="{{ route('campaigns.cancel', $campaign) }}" method="POST">
                @csrf
                <button type="submit" class="btn btn-danger-ghost btn-sm" onclick="return confirm('Cancel this campaign?')">
                    Cancel
                </button>
            </form>
        @endif
    </div>
</div>

@include('campaigns._status_guide')

@if($campaign->campaign_group_id)
@php
$siblings = \App\Models\Campaign::where('campaign_group_id', $campaign->campaign_group_id)
    ->orderBy('campaign_group_run')->get();
@endphp
<div class="card mb-3">
    <div class="card-header">
        <span class="card-header-title"><i class="bi bi-collection"></i> Campaign Group — {{ $siblings->count() }} runs</span>
    </div>
    <div class="card-body py-2">
        <div class="d-flex flex-wrap gap-2">
            @foreach($siblings as $sib)
            <a href="{{ route('campaigns.show', $sib) }}"
               class="btn btn-sm {{ $sib->id === $campaign->id ? 'btn-primary' : 'btn-ghost' }}">
                Run {{ $sib->campaign_group_run }}
                <span class="badge {{ $badgeMap[$sib->status] ?? 'badge-neutral' }} ms-1" style="font-size:10px;">{{ $labelMap[$sib->status] ?? $sib->status }}</span>
            </a>
            @endforeach
        </div>
    </div>
</div>
@endif

<div class="row g-3">
    <div class="col-md-8 d-flex flex-column gap-3">
        {{-- Message --}}
        <div class="card">
            <div class="card-header">
                <span class="card-header-title"><i class="bi bi-chat-text"></i> Message</span>
                <span class="badge badge-neutral">{{ $campaign->sms_segments }} segment{{ $campaign->sms_segments > 1 ? 's' : '' }}</span>
            </div>
            <div class="card-body">
                <div style="background:#F7F6F4;border:1px solid var(--surface-border);border-radius:6px;padding:14px 16px;font-size:14px;line-height:1.6;color:var(--text-primary);">
                    {{ $campaign->message }}
                </div>
                @if($campaign->notes)
                    <hr class="divider">
                    <p style="font-size:13px;color:var(--text-secondary);margin:0;">{{ $campaign->notes }}</p>
                @endif
            </div>
        </div>

        {{-- Delivery stats --}}
        @if($deliveryStats['total'] > 0)
        <div class="card">
            <div class="card-header">
                <span class="card-header-title"><i class="bi bi-bar-chart"></i> Delivery</span>
                <span style="font-size:12px;color:var(--text-tertiary);">{{ $deliveryStats['total'] }} messages</span>
            </div>
            <div class="card-body">
                <div class="row g-3 mb-4">
                    @foreach([
                        'delivered'   => ['success', 'Delivered'],
                        'submitted'   => ['info',    'Submitted'],
                        'pending'     => ['neutral',  'Pending'],
                        'undelivered' => ['danger',  'Undelivered'],
                        'expired'     => ['warning', 'Expired'],
                        'blacklisted' => ['danger',  'Blacklisted'],
                        'no_route'    => ['neutral',  'No Route'],
                        'failed'      => ['danger',  'Failed'],
                    ] as $key => [$color, $label])
                    <div class="col-6 col-md-3">
                        <div style="text-align:center;padding:14px 8px;background:#F7F6F4;border:1px solid var(--surface-border);border-radius:6px;">
                            <div style="font-size:22px;font-weight:600;color:var(--color-{{ $color }});">{{ $deliveryStats[$key] }}</div>
                            <div style="font-size:11px;color:var(--text-tertiary);margin-top:3px;">{{ $label }}</div>
                        </div>
                    </div>
                    @endforeach
                </div>

                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="d-flex justify-content-between mb-1" style="font-size:12px;">
                            <span style="color:var(--text-secondary);">Delivery rate</span>
                            <span style="color:var(--color-success);font-weight:600;">{{ $deliveryStats['delivery_pct'] }}%</span>
                        </div>
                        <div class="progress" style="height:5px;">
                            <div class="progress-bar success" style="width:{{ $deliveryStats['delivery_pct'] }}%"></div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="d-flex justify-content-between mb-1" style="font-size:12px;">
                            <span style="color:var(--text-secondary);">Failure rate</span>
                            <span style="color:var(--color-danger);font-weight:600;">{{ $deliveryStats['failure_pct'] }}%</span>
                        </div>
                        <div class="progress" style="height:5px;">
                            <div class="progress-bar danger" style="width:{{ $deliveryStats['failure_pct'] }}%"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endif
    </div>

    <div class="col-md-4 d-flex flex-column gap-3">
        {{-- Financials --}}
        <div class="card">
            <div class="card-header">
                <span class="card-header-title"><i class="bi bi-currency-dollar"></i> Financials</span>
            </div>
            <div class="card-body">
                @php
                $rows = [
                    ['SMS Segments', $campaign->sms_segments],
                    ['Recipients (est.)', number_format($campaign->estimated_recipients)],
                    ['Recipients (actual)', number_format($campaign->actual_recipients)],
                    ['Internal Cost / SMS', 'R '.$campaign->internal_cost_per_sms],
                    ['Client Rate / SMS', 'R '.$campaign->client_rate_per_sms],
                    ['Est. Cost', 'R '.number_format($campaign->estimated_cost, 2)],
                    ['Est. Charge', 'R '.number_format($campaign->estimated_charge, 2)],
                ];
                @endphp
                @foreach($rows as [$label, $val])
                <div class="d-flex justify-content-between mb-2" style="font-size:13px;">
                    <span style="color:var(--text-secondary);">{{ $label }}</span>
                    <span style="color:var(--text-primary);">{{ $val }}</span>
                </div>
                @endforeach
                <hr class="divider">
                <div class="d-flex justify-content-between" style="font-size:14px;font-weight:600;">
                    <span style="color:var(--text-secondary);">Est. Profit</span>
                    <span style="color:var(--color-success);">R {{ number_format($campaign->estimated_profit, 2) }}</span>
                </div>
                @if($campaign->actual_charge > 0)
                    <div class="d-flex justify-content-between mt-2" style="font-size:14px;font-weight:600;">
                        <span style="color:var(--text-secondary);">Actual Profit</span>
                        <span style="color:var(--color-success);">R {{ number_format($campaign->actual_profit, 2) }}</span>
                    </div>
                @endif
            </div>
        </div>

        {{-- Actions --}}
        <div class="card">
            <div class="card-header">
                <span class="card-header-title"><i class="bi bi-lightning"></i> Actions</span>
            </div>
            <div class="card-body d-flex flex-column gap-2">
                @if(in_array($campaign->status, ['draft','recipients_uploaded']))
                    <a href="{{ route('campaigns.recipients', $campaign) }}" class="btn btn-ghost btn-sm">
                        <i class="bi bi-people"></i> Manage Recipients
                        <span class="badge badge-neutral ms-auto">{{ $campaign->validRecipients()->count() }} valid</span>
                    </a>
                @endif

                @if($campaign->status === 'recipients_uploaded' && $campaign->validRecipients()->count() > 0)
                    <form action="{{ route('invoices.generate', $campaign) }}" method="POST">
                        @csrf
                        <button type="submit" class="btn btn-primary btn-sm w-100">
                            <i class="bi bi-receipt"></i> Generate Invoice
                        </button>
                    </form>
                @endif

                @if(
                    ($campaign->status === 'recipients_uploaded' && $campaign->validRecipients()->count() > 0)
                    || ($campaign->status === 'draft' && $campaign->estimated_recipients > 0)
                )
                    <form action="{{ route('quotes.generate', $campaign) }}" method="POST">
                        @csrf
                        <button type="submit" class="btn btn-ghost btn-sm w-100">
                            <i class="bi bi-file-earmark-text"></i> Generate Quote
                        </button>
                    </form>
                @endif

                @if($campaign->quotes->count() > 0)
                    @foreach($campaign->quotes as $qt)
                        <a href="{{ route('quotes.show', $qt) }}" class="btn btn-ghost btn-sm">
                            <i class="bi bi-file-earmark-text"></i> {{ $qt->quote_number }}
                            <span class="badge {{ ['draft'=>'badge-neutral','sent'=>'badge-info','accepted'=>'badge-success','declined'=>'badge-danger','expired'=>'badge-neutral'][$qt->status] ?? 'badge-neutral' }} ms-auto">{{ ucfirst($qt->status) }}</span>
                        </a>
                    @endforeach
                @endif

                @if($campaign->status === 'invoice_generated')
                    @foreach($campaign->invoices as $inv)
                        <a href="{{ route('invoices.show', $inv) }}" class="btn btn-ghost btn-sm">
                            <i class="bi bi-receipt"></i> {{ $inv->invoice_number }}
                            <span class="badge badge-warning ms-auto">Unpaid</span>
                        </a>
                    @endforeach
                @endif

                @if($campaign->canBeScheduled())
                    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#scheduleModal">
                        <i class="bi bi-calendar-check"></i> Schedule / Send
                    </button>
                @endif

                @if($campaign->canBePaused())
                    <form action="{{ route('campaigns.pause', $campaign) }}" method="POST">
                        @csrf
                        <button type="submit" class="btn btn-ghost btn-sm w-100">
                            <i class="bi bi-pause-circle"></i> Pause Campaign
                        </button>
                    </form>
                @endif

                @if($campaign->canBeResumed())
                    <form action="{{ route('campaigns.resume', $campaign) }}" method="POST">
                        @csrf
                        <button type="submit" class="btn btn-primary btn-sm w-100">
                            <i class="bi bi-play-circle"></i> Resume Campaign
                        </button>
                    </form>
                @endif

                @if(in_array($campaign->status, ['sending', 'paused', 'scheduled']))
                    <form action="{{ route('campaigns.cancel', $campaign) }}" method="POST">
                        @csrf
                        <button type="submit" class="btn btn-danger-ghost btn-sm w-100" onclick="return confirm('Stop and cancel this campaign?')">
                            <i class="bi bi-stop-circle"></i> Stop Campaign
                        </button>
                    </form>
                @endif

                @if(in_array($campaign->status, ['completed','partially_completed','sending']))
                    <a href="{{ route('campaigns.report', $campaign) }}" class="btn btn-ghost btn-sm">
                        <i class="bi bi-bar-chart-fill"></i> View Report
                    </a>
                    <a href="{{ route('campaigns.export-report', $campaign) }}" class="btn btn-ghost btn-sm">
                        <i class="bi bi-download"></i> Export CSV
                    </a>
                @endif

                @if(in_array($campaign->status, ['completed','partially_completed','cancelled','failed']) && !$campaign->isArchived())
                    <form action="{{ route('campaigns.archive', $campaign) }}" method="POST">
                        @csrf
                        <button type="submit" class="btn btn-ghost btn-sm w-100" onclick="return confirm('Archive this campaign?')">
                            <i class="bi bi-archive"></i> Archive Campaign
                        </button>
                    </form>
                @endif

                @php
                    $canDelete = in_array($campaign->status, ['draft','scheduled','cancelled','failed'])
                        && !in_array($campaign->status, ['sending','completed','partially_completed'])
                        && ($campaign->status !== 'scheduled' || ($campaign->scheduled_at && $campaign->scheduled_at->isFuture()));
                @endphp
                @if($canDelete)
                    <form action="{{ route('campaigns.destroy', $campaign) }}" method="POST">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn btn-danger-ghost btn-sm w-100" onclick="return confirm('Permanently delete this campaign? This cannot be undone.')">
                            <i class="bi bi-trash"></i> Delete Campaign
                        </button>
                    </form>
                @endif
            </div>
        </div>

        {{-- Provider --}}
        <div class="card">
            <div class="card-header">
                <span class="card-header-title"><i class="bi bi-plug"></i> Provider</span>
            </div>
            <div class="card-body">
                <div class="d-flex justify-content-between" style="font-size:13px;">
                    <span style="color:var(--text-secondary);">Provider</span>
                    <span class="badge badge-neutral text-uppercase">{{ $campaign->provider }}</span>
                </div>
                @if(config('services.smsportal.test_mode'))
                    <div class="d-flex justify-content-between mt-2" style="font-size:13px;">
                        <span style="color:var(--text-secondary);">Mode</span>
                        <span class="badge badge-warning">Test</span>
                    </div>
                @endif
                @if($campaign->provider_campaign_id)
                    <div class="d-flex justify-content-between mt-2" style="font-size:13px;">
                        <span style="color:var(--text-secondary);">Event ID</span>
                        <code>{{ $campaign->provider_campaign_id }}</code>
                    </div>
                @endif
                @if($campaign->scheduled_at)
                    <div class="d-flex justify-content-between mt-2" style="font-size:13px;">
                        <span style="color:var(--text-secondary);">Scheduled At</span>
                        <span style="color:var(--text-primary);">{{ $campaign->scheduled_at->format('d M Y H:i') }}</span>
                    </div>
                @endif
                @if($campaign->scheduled_end_at)
                    <div class="d-flex justify-content-between mt-2" style="font-size:13px;">
                        <span style="color:var(--text-secondary);">Ends At</span>
                        <span style="color:var(--text-primary);">{{ $campaign->scheduled_end_at->format('d M Y H:i') }}</span>
                    </div>
                    <div class="d-flex justify-content-between mt-2" style="font-size:13px;">
                        <span style="color:var(--text-secondary);">Recurring</span>
                        <span class="badge badge-info">Daily batches</span>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

{{-- Schedule Modal --}}
<div class="modal fade" id="scheduleModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Schedule Campaign</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('campaigns.schedule', $campaign) }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="mb-4">
                        <label class="form-label mb-3">Send option</label>
                        <div style="display:flex;flex-direction:column;gap:10px;">
                            <label style="display:flex;align-items:flex-start;gap:10px;padding:12px 14px;border:1px solid var(--surface-border);border-radius:6px;cursor:pointer;" id="opt-immediate">
                                <input type="radio" name="send_type" value="immediate" checked style="margin-top:2px;">
                                <div>
                                    <div style="font-size:13px;font-weight:500;color:var(--text-primary);">Send Immediately</div>
                                    <div style="font-size:12px;color:var(--text-tertiary);">Starts sending right now via the queue</div>
                                </div>
                            </label>
                            <label style="display:flex;align-items:flex-start;gap:10px;padding:12px 14px;border:1px solid var(--surface-border);border-radius:6px;cursor:pointer;" id="opt-scheduled">
                                <input type="radio" name="send_type" value="scheduled" style="margin-top:2px;" id="radioScheduled">
                                <div>
                                    <div style="font-size:13px;font-weight:500;color:var(--text-primary);">Schedule for Later</div>
                                    <div style="font-size:12px;color:var(--text-tertiary);">Pick a date and time (SAST)</div>
                                </div>
                            </label>
                        </div>
                    </div>
                    <div id="scheduleFields" class="d-none">
                        <label class="form-label">Start Date &amp; Time</label>
                        <input type="datetime-local" name="scheduled_at" id="scheduledAt" class="form-control mb-3"
                            min="{{ now()->addMinutes(5)->format('Y-m-d\TH:i') }}">
                        <label class="form-label">End Date &amp; Time <span style="font-size:11px;color:var(--text-tertiary);">(optional — enables recurring schedule)</span></label>
                        <input type="datetime-local" name="scheduled_end_at" id="scheduledEndAt" class="form-control"
                            min="{{ now()->addMinutes(10)->format('Y-m-d\TH:i') }}">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-ghost btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-sm">Confirm</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.querySelectorAll('input[name="send_type"]').forEach(r => {
    r.addEventListener('change', () => {
        document.getElementById('scheduleFields').classList.toggle('d-none', r.value !== 'scheduled' || !r.checked);
    });
});
document.getElementById('radioScheduled')?.addEventListener('change', function() {
    document.getElementById('scheduleFields').classList.toggle('d-none', !this.checked);
});
</script>
@endpush
@endsection
