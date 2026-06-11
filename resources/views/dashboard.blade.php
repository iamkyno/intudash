@extends('layouts.app')

@section('title', 'Dashboard')
@section('page-title', 'Dashboard Overview')

@section('content')
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="stat-card">
            <div class="stat-icon bg-primary bg-opacity-10 text-primary"><i class="bi bi-people-fill"></i></div>
            <div class="stat-value">{{ number_format($stats['total_clients']) }}</div>
            <div class="stat-label">Total Clients</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card">
            <div class="stat-icon bg-secondary bg-opacity-10 text-secondary"><i class="bi bi-file-earmark-text-fill"></i></div>
            <div class="stat-value">{{ number_format($stats['draft_campaigns']) }}</div>
            <div class="stat-label">Draft Campaigns</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card">
            <div class="stat-icon bg-warning bg-opacity-10 text-warning"><i class="bi bi-hourglass-split"></i></div>
            <div class="stat-value">{{ number_format($stats['awaiting_payment']) }}</div>
            <div class="stat-label">Awaiting Payment</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card">
            <div class="stat-icon bg-success bg-opacity-10 text-success"><i class="bi bi-calendar-check-fill"></i></div>
            <div class="stat-value">{{ number_format($stats['ready_to_schedule']) }}</div>
            <div class="stat-label">Ready to Schedule</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card">
            <div class="stat-icon bg-info bg-opacity-10 text-info"><i class="bi bi-clock-fill"></i></div>
            <div class="stat-value">{{ number_format($stats['scheduled']) }}</div>
            <div class="stat-label">Scheduled</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card">
            <div class="stat-icon bg-success bg-opacity-10 text-success"><i class="bi bi-send-check-fill"></i></div>
            <div class="stat-value">{{ number_format($stats['sent_campaigns']) }}</div>
            <div class="stat-label">Sent Campaigns</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card">
            <div class="stat-icon bg-success bg-opacity-10 text-success"><i class="bi bi-check-circle-fill"></i></div>
            <div class="stat-value">{{ number_format($stats['delivered_sms']) }}</div>
            <div class="stat-label">Delivered SMS</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card">
            <div class="stat-icon bg-danger bg-opacity-10 text-danger"><i class="bi bi-x-circle-fill"></i></div>
            <div class="stat-value">{{ number_format($stats['failed_sms']) }}</div>
            <div class="stat-label">Failed SMS</div>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="stat-card h-100">
            <div class="stat-icon bg-primary bg-opacity-10 text-primary"><i class="bi bi-graph-up-arrow"></i></div>
            <div class="stat-value">{{ number_format($stats['quarterly_sms']) }}</div>
            <div class="stat-label">SMS Volume This Quarter</div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card h-100">
            <div class="stat-icon bg-success bg-opacity-10 text-success"><i class="bi bi-cash-stack"></i></div>
            <div class="stat-value">R {{ number_format($stats['revenue_collected'], 2) }}</div>
            <div class="stat-label">Revenue Collected</div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card h-100">
            <div class="stat-icon bg-info bg-opacity-10 text-info"><i class="bi bi-piggy-bank-fill"></i></div>
            <div class="stat-value">R {{ number_format($stats['estimated_profit'], 2) }}</div>
            <div class="stat-label">Estimated Profit</div>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-clock-history me-2 text-primary"></i>Recent Activity</span>
            </div>
            <div class="card-body p-0">
                @if($recentActivity->isEmpty())
                    <div class="text-center text-muted py-4">No recent activity</div>
                @else
                    <div class="table-responsive">
                        <table class="table table-sm table-hover mb-0">
                            <thead><tr><th>Action</th><th>User</th><th>Time</th></tr></thead>
                            <tbody>
                                @foreach($recentActivity as $log)
                                <tr>
                                    <td>
                                        <span class="badge bg-light text-dark">{{ str_replace('_', ' ', $log->action) }}</span>
                                        @if($log->description)
                                            <small class="text-muted ms-1">{{ $log->description }}</small>
                                        @endif
                                    </td>
                                    <td class="text-muted small">{{ $log->user?->name ?? 'System' }}</td>
                                    <td class="text-muted small">{{ $log->created_at->diffForHumans() }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-header"><i class="bi bi-pie-chart-fill me-2 text-primary"></i>Campaigns by Status</div>
            <div class="card-body">
                @php
                $statusColors = ['draft'=>'secondary','recipients_uploaded'=>'info','invoice_generated'=>'primary','awaiting_payment'=>'warning','ready_to_schedule'=>'success','scheduled'=>'primary','sending'=>'info','completed'=>'success','partially_completed'=>'warning','failed'=>'danger','cancelled'=>'secondary'];
                $statusLabels = ['draft'=>'Draft','recipients_uploaded'=>'Recipients Uploaded','invoice_generated'=>'Invoice Generated','awaiting_payment'=>'Awaiting Payment','ready_to_schedule'=>'Ready to Schedule','scheduled'=>'Scheduled','sending'=>'Sending','completed'=>'Completed','partially_completed'=>'Partially Completed','failed'=>'Failed','cancelled'=>'Cancelled'];
                @endphp
                @forelse($campaignsByStatus as $status => $count)
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="badge bg-{{ $statusColors[$status] ?? 'secondary' }}">{{ $statusLabels[$status] ?? $status }}</span>
                        <strong>{{ $count }}</strong>
                    </div>
                @empty
                    <div class="text-muted text-center">No campaigns yet</div>
                @endforelse
            </div>
        </div>

        <div class="card mt-3">
            <div class="card-header"><i class="bi bi-lightning-charge-fill me-2 text-warning"></i>Quick Actions</div>
            <div class="card-body d-grid gap-2">
                <a href="{{ route('campaigns.create') }}" class="btn btn-primary btn-sm">
                    <i class="bi bi-plus-circle me-1"></i>New Campaign
                </a>
                <a href="{{ route('clients.create') }}" class="btn btn-outline-primary btn-sm">
                    <i class="bi bi-person-plus me-1"></i>Add Client
                </a>
                <a href="{{ route('campaigns.index', ['status' => 'ready_to_schedule']) }}" class="btn btn-outline-success btn-sm">
                    <i class="bi bi-calendar-check me-1"></i>Schedule Campaigns
                </a>
                <a href="{{ route('invoices.index') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-receipt me-1"></i>View Invoices
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
