@extends('layouts.app')
@section('title', 'Dashboard')
@section('page-title', 'Dashboard')

@section('content')

{{-- Top stat row --}}
<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3">
        <div class="stat-card">
            <div class="stat-label">Total Clients</div>
            <div class="stat-value">{{ number_format($stats['total_clients']) }}</div>
            <div class="stat-meta">Active accounts</div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="stat-card">
            <div class="stat-label">Draft Campaigns</div>
            <div class="stat-value">{{ number_format($stats['draft_campaigns']) }}</div>
            <div class="stat-meta">Awaiting recipients</div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="stat-card">
            <div class="stat-label">Awaiting Payment</div>
            <div class="stat-value warning">{{ number_format($stats['awaiting_payment']) }}</div>
            <div class="stat-meta">Invoice outstanding</div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="stat-card">
            <div class="stat-label">Ready to Schedule</div>
            <div class="stat-value success">{{ number_format($stats['ready_to_schedule']) }}</div>
            <div class="stat-meta">Paid &amp; confirmed</div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="stat-card">
            <div class="stat-label">Scheduled</div>
            <div class="stat-value brand">{{ number_format($stats['scheduled']) }}</div>
            <div class="stat-meta">Queued for dispatch</div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="stat-card">
            <div class="stat-label">Sent Campaigns</div>
            <div class="stat-value">{{ number_format($stats['sent_campaigns']) }}</div>
            <div class="stat-meta">Completed this period</div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="stat-card">
            <div class="stat-label">Delivered SMS</div>
            <div class="stat-value success">{{ number_format($stats['delivered_sms']) }}</div>
            <div class="stat-meta">Confirmed delivery</div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="stat-card">
            <div class="stat-label">Failed SMS</div>
            <div class="stat-value danger">{{ number_format($stats['failed_sms']) }}</div>
            <div class="stat-meta">Undelivered / expired</div>
        </div>
    </div>
</div>

{{-- Revenue row --}}
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="stat-card">
            <div class="stat-label">Quarterly SMS Volume</div>
            <div class="stat-value brand">{{ number_format($stats['quarterly_sms']) }}</div>
            <div class="stat-meta">{{ now()->startOfQuarter()->format('d M') }} — {{ now()->endOfQuarter()->format('d M Y') }}</div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card">
            <div class="stat-label">Revenue Collected</div>
            <div class="stat-value success">R {{ number_format($stats['revenue_collected'], 2) }}</div>
            <div class="stat-meta">Paid invoices only</div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card">
            <div class="stat-label">Estimated Profit</div>
            <div class="stat-value success">R {{ number_format($stats['estimated_profit'], 2) }}</div>
            <div class="stat-meta">Across completed campaigns</div>
        </div>
    </div>
</div>

<div class="row g-3">
    {{-- Activity log --}}
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <span class="card-header-title"><i class="bi bi-activity"></i> Recent Activity</span>
            </div>
            <div class="card-body-flush">
                @if($recentActivity->isEmpty())
                    <div style="padding:32px;text-align:center;color:var(--text-tertiary);font-size:13px;">
                        No activity yet
                    </div>
                @else
                    <table class="table">
                        <thead>
                            <tr><th>Action</th><th>User</th><th>When</th></tr>
                        </thead>
                        <tbody>
                            @foreach($recentActivity as $log)
                            <tr>
                                <td>
                                    <span class="badge badge-neutral">{{ str_replace('_', ' ', $log->action) }}</span>
                                    @if($log->description)
                                        <span class="table-muted ms-1">{{ $log->description }}</span>
                                    @endif
                                </td>
                                <td class="table-muted">{{ $log->user?->name ?? 'System' }}</td>
                                <td class="table-muted">{{ $log->created_at->diffForHumans() }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>
        </div>
    </div>

    {{-- Right column --}}
    <div class="col-md-4 d-flex flex-column gap-3">
        {{-- Campaign breakdown --}}
        <div class="card">
            <div class="card-header">
                <span class="card-header-title"><i class="bi bi-bar-chart"></i> By Status</span>
            </div>
            <div class="card-body">
                @php
                $statusMap = [
                    'draft'              => ['label' => 'Draft',              'class' => 'badge-neutral'],
                    'recipients_uploaded'=> ['label' => 'Recipients Uploaded','class' => 'badge-info'],
                    'invoice_generated'  => ['label' => 'Invoice Generated',  'class' => 'badge-info'],
                    'awaiting_payment'   => ['label' => 'Awaiting Payment',   'class' => 'badge-warning'],
                    'ready_to_schedule'  => ['label' => 'Ready to Schedule',  'class' => 'badge-success'],
                    'scheduled'          => ['label' => 'Scheduled',          'class' => 'badge-brand'],
                    'sending'            => ['label' => 'Sending',            'class' => 'badge-info'],
                    'completed'          => ['label' => 'Completed',          'class' => 'badge-success'],
                    'partially_completed'=> ['label' => 'Partial',            'class' => 'badge-warning'],
                    'failed'             => ['label' => 'Failed',             'class' => 'badge-danger'],
                    'cancelled'          => ['label' => 'Cancelled',          'class' => 'badge-neutral'],
                ];
                @endphp
                @forelse($campaignsByStatus as $status => $count)
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="badge {{ $statusMap[$status]['class'] ?? 'badge-neutral' }} badge-dot">
                            {{ $statusMap[$status]['label'] ?? $status }}
                        </span>
                        <span style="font-size:13px;font-weight:600;color:var(--text-primary);">{{ $count }}</span>
                    </div>
                @empty
                    <span style="font-size:13px;color:var(--text-tertiary);">No campaigns yet</span>
                @endforelse
            </div>
        </div>

        {{-- Quick actions --}}
        <div class="card">
            <div class="card-header">
                <span class="card-header-title"><i class="bi bi-lightning"></i> Quick Actions</span>
            </div>
            <div class="card-body d-flex flex-column gap-2">
                <a href="{{ route('campaigns.create') }}" class="btn btn-primary btn-sm">
                    <i class="bi bi-plus-circle"></i> New Campaign
                </a>
                <a href="{{ route('clients.create') }}" class="btn btn-ghost btn-sm">
                    <i class="bi bi-person-plus"></i> Add Client
                </a>
                <a href="{{ route('campaigns.index', ['status' => 'ready_to_schedule']) }}" class="btn btn-ghost btn-sm">
                    <i class="bi bi-calendar-check"></i> Schedule Campaigns
                </a>
                <a href="{{ route('invoices.index') }}" class="btn btn-ghost btn-sm">
                    <i class="bi bi-receipt"></i> View Invoices
                </a>
            </div>
        </div>
    </div>
</div>

@endsection
