@extends('layouts.app')
@section('title', $client->company_name)
@section('page-title', $client->company_name)

@section('content')
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-icon bg-primary bg-opacity-10 text-primary"><i class="bi bi-megaphone-fill"></i></div>
            <div class="stat-value">{{ $stats['total_campaigns'] }}</div>
            <div class="stat-label">Total Campaigns</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-icon bg-success bg-opacity-10 text-success"><i class="bi bi-send-check-fill"></i></div>
            <div class="stat-value">{{ number_format($stats['total_sms_sent']) }}</div>
            <div class="stat-label">Total SMS Sent</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-icon bg-warning bg-opacity-10 text-warning"><i class="bi bi-cash-stack"></i></div>
            <div class="stat-value">R {{ number_format($stats['total_billed'], 2) }}</div>
            <div class="stat-label">Total Billed</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-icon bg-info bg-opacity-10 text-info"><i class="bi bi-piggy-bank-fill"></i></div>
            <div class="stat-value">R {{ number_format($stats['total_profit'], 2) }}</div>
            <div class="stat-label">Total Profit</div>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-md-4">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                Client Info
                <div>
                    <a href="{{ route('clients.edit', $client) }}" class="btn btn-sm btn-outline-secondary">
                        <i class="bi bi-pencil"></i>
                    </a>
                </div>
            </div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-5 text-muted small">Company</dt>
                    <dd class="col-7">{{ $client->company_name }}</dd>
                    <dt class="col-5 text-muted small">Contact</dt>
                    <dd class="col-7">{{ $client->contact_person }}</dd>
                    <dt class="col-5 text-muted small">Email</dt>
                    <dd class="col-7">{{ $client->email }}</dd>
                    <dt class="col-5 text-muted small">Phone</dt>
                    <dd class="col-7">{{ $client->phone }}</dd>
                    <dt class="col-5 text-muted small">VAT No.</dt>
                    <dd class="col-7">{{ $client->vat_number ?? '—' }}</dd>
                    <dt class="col-5 text-muted small">SMS Rate</dt>
                    <dd class="col-7">R {{ number_format($client->default_sms_rate, 4) }}</dd>
                    <dt class="col-5 text-muted small">Status</dt>
                    <dd class="col-7">
                        <span class="badge bg-{{ $client->status === 'active' ? 'success' : 'secondary' }}">
                            {{ ucfirst($client->status) }}
                        </span>
                    </dd>
                </dl>
                @if($client->billing_address)
                <hr>
                <p class="small text-muted mb-1">Billing Address</p>
                <p class="small mb-0">{{ $client->billing_address }}</p>
                @endif
            </div>
        </div>
    </div>
    <div class="col-md-8">
        <div class="card mb-3">
            <div class="card-header d-flex justify-content-between align-items-center">
                Recent Campaigns
                <a href="{{ route('campaigns.create') }}?client_id={{ $client->id }}" class="btn btn-sm btn-primary">
                    <i class="bi bi-plus"></i> New Campaign
                </a>
            </div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    <thead><tr><th>Name</th><th>Status</th><th>SMS</th><th>Charge</th><th></th></tr></thead>
                    <tbody>
                        @forelse($client->campaigns as $campaign)
                        <tr>
                            <td><a href="{{ route('campaigns.show', $campaign) }}" class="text-decoration-none">{{ $campaign->name }}</a></td>
                            <td><span class="badge bg-{{ $campaign->status_color }}">{{ $campaign->status_label }}</span></td>
                            <td>{{ number_format($campaign->actual_recipients) }}</td>
                            <td>R {{ number_format($campaign->actual_charge, 2) }}</td>
                            <td><a href="{{ route('campaigns.show', $campaign) }}" class="btn btn-xs btn-outline-primary btn-sm"><i class="bi bi-eye"></i></a></td>
                        </tr>
                        @empty
                        <tr><td colspan="5" class="text-muted text-center py-3">No campaigns yet</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card">
            <div class="card-header">Recent Invoices</div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    <thead><tr><th>Invoice</th><th>Status</th><th>Total</th><th>Date</th><th></th></tr></thead>
                    <tbody>
                        @forelse($client->invoices as $invoice)
                        <tr>
                            <td>{{ $invoice->invoice_number }}</td>
                            <td><span class="badge bg-{{ $invoice->status_color }}">{{ ucfirst($invoice->status) }}</span></td>
                            <td>R {{ number_format($invoice->total, 2) }}</td>
                            <td>{{ $invoice->created_at->format('d M Y') }}</td>
                            <td><a href="{{ route('invoices.show', $invoice) }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></a></td>
                        </tr>
                        @empty
                        <tr><td colspan="5" class="text-muted text-center py-3">No invoices yet</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
