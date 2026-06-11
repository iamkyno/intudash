@extends('layouts.app')
@section('title', 'Campaign Report')
@section('page-title', 'Report — ' . $campaign->name)

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <a href="{{ route('campaigns.show', $campaign) }}" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i>Back
    </a>
    <div class="d-flex gap-2">
        <a href="{{ route('campaigns.export-report', $campaign) }}" class="btn btn-sm btn-outline-primary">
            <i class="bi bi-file-earmark-csv me-1"></i>Export CSV
        </a>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">Campaign Summary</div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-6 text-muted">Client</dt>
                    <dd class="col-6">{{ $campaign->client->company_name }}</dd>
                    <dt class="col-6 text-muted">Status</dt>
                    <dd class="col-6"><span class="badge bg-{{ $campaign->status_color }}">{{ $campaign->status_label }}</span></dd>
                    <dt class="col-6 text-muted">Sent At</dt>
                    <dd class="col-6">{{ $campaign->sent_at?->format('d M Y H:i') ?? '—' }}</dd>
                    <dt class="col-6 text-muted">Completed At</dt>
                    <dd class="col-6">{{ $campaign->completed_at?->format('d M Y H:i') ?? '—' }}</dd>
                    <dt class="col-6 text-muted">SMS Segments</dt>
                    <dd class="col-6">{{ $campaign->sms_segments }}</dd>
                    <dt class="col-6 text-muted">Total SMS Sent</dt>
                    <dd class="col-6">{{ number_format($campaign->actual_recipients * $campaign->sms_segments) }}</dd>
                </dl>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">Financial Summary</div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-6 text-muted">Internal Cost</dt>
                    <dd class="col-6">R {{ number_format($campaign->actual_cost, 2) }}</dd>
                    <dt class="col-6 text-muted">Client Charge</dt>
                    <dd class="col-6">R {{ number_format($campaign->actual_charge, 2) }}</dd>
                    <dt class="col-6 text-muted fw-semibold">Gross Profit</dt>
                    <dd class="col-6 fw-semibold text-success">R {{ number_format($campaign->actual_profit, 2) }}</dd>
                </dl>
            </div>
        </div>
    </div>
</div>

@if($failedRecipients->isNotEmpty())
<div class="card">
    <div class="card-header text-danger"><i class="bi bi-x-circle me-2"></i>Failed / Undelivered Numbers</div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm mb-0">
                <thead><tr><th>Phone</th><th>Status</th><th>Reason</th><th>Sent At</th></tr></thead>
                <tbody>
                    @foreach($failedRecipients as $log)
                    <tr>
                        <td><code>{{ $log->recipient_number }}</code></td>
                        <td><span class="badge bg-{{ $log->status_color }}">{{ $log->status }}</span></td>
                        <td class="text-muted small">{{ $log->failure_reason ?? '—' }}</td>
                        <td class="text-muted small">{{ $log->sent_at?->format('d M H:i') ?? '—' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endif
@endsection
