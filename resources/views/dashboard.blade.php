@extends('layouts.app')
@section('title', 'Dashboard')
@section('page-title', 'Dashboard')

@section('content')

{{-- ── Greeting bar ──────────────────────────────────────────────────── --}}
<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:28px;flex-wrap:wrap;gap:12px;">
    <div>
        <h1 style="font-size:22px;font-weight:700;color:var(--text-primary);margin:0;letter-spacing:-0.02em;">
            Good {{ now()->hour < 12 ? 'morning' : (now()->hour < 17 ? 'afternoon' : 'evening') }}
        </h1>
        <p style="font-size:13px;color:var(--text-tertiary);margin:2px 0 0;">
            {{ now()->format('l, d F Y') }} &nbsp;·&nbsp; Here's what's happening
        </p>
    </div>
    <div style="display:flex;gap:8px;">
        <a href="{{ route('campaigns.create') }}" class="btn btn-primary btn-sm">
            <i class="bi bi-plus-lg me-1"></i>New Campaign
        </a>
        <a href="{{ route('clients.create') }}" class="btn btn-ghost btn-sm">
            <i class="bi bi-person-plus me-1"></i>Add Client
        </a>
    </div>
</div>

{{-- ── KPI strip ─────────────────────────────────────────────────────── --}}
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:12px;margin-bottom:24px;">

    @php
    $kpis = [
        [
            'label' => 'Revenue Collected',
            'value' => 'R '.number_format($stats['revenue_collected'], 2),
            'sub'   => 'R '.number_format($stats['outstanding'], 2).' outstanding',
            'icon'  => 'bi-cash-stack',
            'color' => 'var(--color-success)',
            'bg'    => '#F0FAF4',
            'link'  => route('invoices.index'),
        ],
        [
            'label' => 'Estimated Profit',
            'value' => 'R '.number_format($stats['estimated_profit'], 2),
            'sub'   => 'Completed campaigns',
            'icon'  => 'bi-graph-up-arrow',
            'color' => 'var(--color-brand)',
            'bg'    => 'var(--color-brand-subtle)',
            'link'  => route('campaigns.index'),
        ],
        [
            'label' => 'Clients',
            'value' => number_format($stats['total_clients']),
            'sub'   => 'Active accounts',
            'icon'  => 'bi-people',
            'color' => 'var(--color-info)',
            'bg'    => '#EEF5FD',
            'link'  => route('clients.index'),
        ],
        [
            'label' => 'SMS Delivered',
            'value' => number_format($stats['delivered_sms']),
            'sub'   => number_format($stats['failed_sms']).' failed',
            'icon'  => 'bi-check2-circle',
            'color' => 'var(--color-success)',
            'bg'    => '#F0FAF4',
            'link'  => route('campaigns.index'),
        ],
        [
            'label' => 'Scheduled',
            'value' => number_format($stats['scheduled']),
            'sub'   => 'Campaigns queued',
            'icon'  => 'bi-calendar-event',
            'color' => 'var(--color-brand)',
            'bg'    => 'var(--color-brand-subtle)',
            'link'  => route('campaigns.index', ['status' => 'scheduled']),
        ],
        [
            'label' => 'Pending Reminders',
            'value' => number_format($stats['pending_reminders']),
            'sub'   => $stats['reminders_due'] > 0 ? number_format($stats['reminders_due']).' due now' : 'None due now',
            'icon'  => 'bi-bell',
            'color' => $stats['reminders_due'] > 0 ? 'var(--color-warning)' : 'var(--color-neutral)',
            'bg'    => $stats['reminders_due'] > 0 ? '#FEF8EC' : 'var(--surface-bg)',
            'link'  => route('reminders.index'),
        ],
    ];
    @endphp

    @foreach($kpis as $kpi)
    <a href="{{ $kpi['link'] }}" style="
        display:block;
        background:{{ $kpi['bg'] }};
        border:1px solid var(--surface-border);
        border-radius:10px;
        padding:16px;
        text-decoration:none;
        transition:box-shadow 0.15s,border-color 0.15s;
        " onmouseover="this.style.boxShadow='0 2px 12px rgba(0,0,0,0.08)';this.style.borderColor='var(--surface-border-strong)'"
           onmouseout="this.style.boxShadow='none';this.style.borderColor='var(--surface-border)'">
        <div style="display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:10px;">
            <div style="
                width:32px;height:32px;border-radius:8px;
                background:{{ $kpi['color'] }};
                display:grid;place-items:center;
                opacity:0.15;
                "></div>
            <i class="bi {{ $kpi['icon'] }}" style="font-size:16px;color:{{ $kpi['color'] }};position:relative;margin-top:8px;margin-right:2px;"></i>
        </div>
        <div style="font-size:22px;font-weight:700;color:{{ $kpi['color'] }};letter-spacing:-0.02em;line-height:1;">{{ $kpi['value'] }}</div>
        <div style="font-size:11px;font-weight:600;color:var(--text-primary);margin-top:4px;text-transform:uppercase;letter-spacing:0.05em;">{{ $kpi['label'] }}</div>
        <div style="font-size:11px;color:var(--text-tertiary);margin-top:2px;">{{ $kpi['sub'] }}</div>
    </a>
    @endforeach
</div>

{{-- ── Pipeline + Activity ───────────────────────────────────────────── --}}
<div class="row g-3 mb-3">

    {{-- Campaign pipeline --}}
    <div class="col-md-4">
        <div class="card h-100">
            <div class="card-header">
                <span class="card-header-title"><i class="bi bi-funnel"></i> Campaign Pipeline</span>
            </div>
            <div class="card-body">
                @php
                $pipeline = [
                    ['label'=>'Draft',            'status'=>'draft',             'icon'=>'bi-file-earmark',       'color'=>'var(--color-neutral)'],
                    ['label'=>'Recipients Added',  'status'=>'recipients_uploaded','icon'=>'bi-people',            'color'=>'var(--color-info)'],
                    ['label'=>'Invoice Sent',      'status'=>'invoice_generated', 'icon'=>'bi-receipt',            'color'=>'var(--color-info)'],
                    ['label'=>'Awaiting Payment',  'status'=>'awaiting_payment',  'icon'=>'bi-hourglass-split',    'color'=>'var(--color-warning)'],
                    ['label'=>'Ready to Schedule', 'status'=>'ready_to_schedule', 'icon'=>'bi-calendar-check',     'color'=>'var(--color-success)'],
                    ['label'=>'Scheduled',         'status'=>'scheduled',         'icon'=>'bi-send-check',         'color'=>'var(--color-brand)'],
                    ['label'=>'Sending',           'status'=>'sending',           'icon'=>'bi-arrow-repeat',       'color'=>'var(--color-info)'],
                    ['label'=>'Completed',         'status'=>'completed',         'icon'=>'bi-check-circle',       'color'=>'var(--color-success)'],
                    ['label'=>'Partially Done',    'status'=>'partially_completed','icon'=>'bi-exclamation-circle','color'=>'var(--color-warning)'],
                    ['label'=>'Failed',            'status'=>'failed',            'icon'=>'bi-x-circle',           'color'=>'var(--color-danger)'],
                ];
                @endphp

                @foreach($pipeline as $stage)
                @php $count = $campaignsByStatus[$stage['status']] ?? 0; @endphp
                @if($count > 0)
                <a href="{{ route('campaigns.index', ['status' => $stage['status']]) }}"
                   style="display:flex;align-items:center;justify-content:space-between;padding:8px 0;border-bottom:1px solid var(--surface-border);text-decoration:none;"
                   onmouseover="this.style.opacity='0.75'" onmouseout="this.style.opacity='1'">
                    <span style="display:flex;align-items:center;gap:8px;font-size:13px;color:var(--text-secondary);">
                        <i class="bi {{ $stage['icon'] }}" style="color:{{ $stage['color'] }};font-size:13px;width:16px;text-align:center;"></i>
                        {{ $stage['label'] }}
                    </span>
                    <span style="font-size:13px;font-weight:600;color:{{ $stage['color'] }};">{{ $count }}</span>
                </a>
                @endif
                @endforeach

                @if($campaignsByStatus->isEmpty())
                <p style="font-size:13px;color:var(--text-tertiary);margin:0;text-align:center;padding:24px 0;">No campaigns yet</p>
                @endif
            </div>
        </div>
    </div>

    {{-- Recent activity --}}
    <div class="col-md-8">
        <div class="card h-100">
            <div class="card-header">
                <span class="card-header-title"><i class="bi bi-activity"></i> Recent Activity</span>
            </div>
            <div class="card-body-flush">
                @if($recentActivity->isEmpty())
                    <div style="padding:48px;text-align:center;color:var(--text-tertiary);font-size:13px;">
                        <i class="bi bi-inbox" style="font-size:2rem;display:block;margin-bottom:8px;"></i>
                        No activity yet
                    </div>
                @else
                    @foreach($recentActivity as $log)
                    <div style="display:flex;align-items:flex-start;gap:12px;padding:11px 20px;border-bottom:1px solid var(--surface-border);">
                        <div style="
                            width:28px;height:28px;border-radius:50%;
                            background:var(--color-brand-subtle);
                            display:grid;place-items:center;
                            flex-shrink:0;margin-top:1px;
                            ">
                            <i class="bi bi-person" style="font-size:12px;color:var(--color-brand);"></i>
                        </div>
                        <div style="flex:1;min-width:0;">
                            <div style="font-size:13px;color:var(--text-primary);">
                                <span style="font-weight:500;">{{ $log->user?->name ?? 'System' }}</span>
                                <span style="color:var(--text-secondary);"> {{ str_replace('_', ' ', $log->action) }}</span>
                                @if($log->description)
                                    <span style="color:var(--text-tertiary);"> — {{ $log->description }}</span>
                                @endif
                            </div>
                        </div>
                        <div style="font-size:11px;color:var(--text-tertiary);white-space:nowrap;flex-shrink:0;padding-top:2px;">
                            {{ $log->created_at->diffForHumans(null, true, true) }}
                        </div>
                    </div>
                    @endforeach
                @endif
            </div>
        </div>
    </div>
</div>

{{-- ── Needs Attention + Upcoming ───────────────────────────────────── --}}
<div class="row g-3">

    {{-- Needs attention --}}
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <span class="card-header-title"><i class="bi bi-exclamation-triangle" style="color:var(--color-warning);"></i> Needs Attention</span>
            </div>
            @if($needsAttention->isEmpty())
                <div style="padding:32px;text-align:center;color:var(--text-tertiary);font-size:13px;">
                    <i class="bi bi-check2-all" style="font-size:1.5rem;display:block;margin-bottom:6px;color:var(--color-success);"></i>
                    All campaigns are on track
                </div>
            @else
            <div class="card-body-flush">
                @foreach($needsAttention as $c)
                <a href="{{ route('campaigns.show', $c) }}" style="display:flex;align-items:center;justify-content:space-between;padding:11px 20px;border-bottom:1px solid var(--surface-border);text-decoration:none;"
                   onmouseover="this.style.background='var(--surface-bg)'" onmouseout="this.style.background='transparent'">
                    <div>
                        <div style="font-size:13px;font-weight:500;color:var(--text-primary);">{{ $c->name }}</div>
                        <div style="font-size:11px;color:var(--text-tertiary);">{{ $c->client?->name }}</div>
                    </div>
                    @if($c->status === 'awaiting_payment')
                        <span class="badge badge-warning badge-dot">Awaiting Payment</span>
                    @else
                        <span class="badge badge-success badge-dot">Ready to Schedule</span>
                    @endif
                </a>
                @endforeach
            </div>
            @endif
        </div>
    </div>

    {{-- Upcoming scheduled --}}
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <span class="card-header-title"><i class="bi bi-calendar3"></i> Upcoming Scheduled</span>
                <a href="{{ route('campaigns.index', ['status' => 'scheduled']) }}" style="font-size:12px;color:var(--text-tertiary);">View all</a>
            </div>
            @if($upcomingCampaigns->isEmpty())
                <div style="padding:32px;text-align:center;color:var(--text-tertiary);font-size:13px;">
                    <i class="bi bi-calendar-x" style="font-size:1.5rem;display:block;margin-bottom:6px;"></i>
                    No campaigns scheduled
                </div>
            @else
            <div class="card-body-flush">
                @foreach($upcomingCampaigns as $c)
                <a href="{{ route('campaigns.show', $c) }}" style="display:flex;align-items:center;justify-content:space-between;padding:11px 20px;border-bottom:1px solid var(--surface-border);text-decoration:none;"
                   onmouseover="this.style.background='var(--surface-bg)'" onmouseout="this.style.background='transparent'">
                    <div>
                        <div style="font-size:13px;font-weight:500;color:var(--text-primary);">{{ $c->name }}</div>
                        <div style="font-size:11px;color:var(--text-tertiary);">{{ $c->client?->name }}</div>
                    </div>
                    <div style="text-align:right;flex-shrink:0;">
                        <div style="font-size:12px;font-weight:600;color:var(--color-brand);">{{ $c->scheduled_at?->format('d M') }}</div>
                        <div style="font-size:11px;color:var(--text-tertiary);">{{ $c->scheduled_at?->format('H:i') }}</div>
                    </div>
                </a>
                @endforeach
            </div>
            @endif
        </div>
    </div>

</div>

@endsection
