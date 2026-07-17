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
                <div class="d-flex gap-1">
                    <a href="{{ route('clients.edit', $client) }}" class="btn btn-sm btn-outline-secondary">
                        <i class="bi bi-pencil"></i>
                    </a>
                    <form action="{{ route('clients.destroy', $client) }}" method="POST"
                        onsubmit="return confirm('Delete this client? This cannot be undone.')">
                        @csrf @method('DELETE')
                        <input type="hidden" name="force" value="0">
                        <button type="submit" class="btn btn-sm btn-outline-danger">
                            <i class="bi bi-trash"></i>
                        </button>
                    </form>
                    @if($activeCampaignCount > 0)
                    <form action="{{ route('clients.destroy', $client) }}" method="POST"
                        onsubmit="return confirm('Force delete this client including {{ $activeCampaignCount }} active campaign(s)? This cannot be undone.')">
                        @csrf @method('DELETE')
                        <input type="hidden" name="force" value="1">
                        <button type="submit" class="btn btn-sm btn-danger">
                            <i class="bi bi-trash-fill"></i> Force Delete
                        </button>
                    </form>
                    @endif
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

        {{-- Recipients / audience --}}
        <div class="card mt-3">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-people me-1"></i>Recipients</span>
                <a href="{{ route('clients.recipients.index', $client) }}" class="btn btn-ghost btn-sm">
                    <i class="bi bi-plus-lg"></i>
                </a>
            </div>
            <div class="card-body">
                @php
                    $recipientCount = $client->recipients()->count();
                    $groupCount = $client->recipientGroups()->count();
                @endphp
                <div class="d-flex justify-content-between align-items-center mb-1" style="font-size:13px;">
                    <span>{{ number_format($recipientCount) }} recipient{{ $recipientCount === 1 ? '' : 's' }}</span>
                    <span class="badge bg-secondary">{{ $groupCount }} group{{ $groupCount === 1 ? '' : 's' }}</span>
                </div>
                <a href="{{ route('clients.recipients.index', $client) }}" class="btn btn-ghost btn-sm mt-1 w-100">
                    Manage Recipients
                </a>
            </div>
        </div>

        {{-- Reminder API access --}}
        <div class="card mt-3">
            <div class="card-header"><i class="bi bi-key me-1"></i>Reminder API Token</div>
            <div class="card-body">
                @if(session('new_api_token'))
                    <div class="alert alert-warning py-2 px-2 small mb-2">
                        <strong>Copy this token now — it won't be shown again:</strong>
                        <div class="input-group input-group-sm mt-1">
                            <input type="text" class="form-control form-control-sm" readonly value="{{ session('new_api_token') }}" id="newToken">
                            <button type="button" class="btn btn-outline-secondary btn-sm" onclick="navigator.clipboard.writeText(document.getElementById('newToken').value)">
                                <i class="bi bi-clipboard"></i>
                            </button>
                        </div>
                    </div>
                @endif

                @if($client->api_token)
                    <p class="small mb-2" style="color:var(--text-secondary);">
                        Token active <span class="text-muted">(ends …{{ $client->api_token_last_four }})</span>,
                        generated {{ $client->api_token_generated_at?->format('d M Y') }}.
                    </p>
                @else
                    <p class="small mb-2" style="color:var(--text-secondary);">No API token yet. Generate one to let this client push reminders via the API.</p>
                @endif

                <p class="small mb-1" style="color:var(--text-tertiary);">Endpoint:</p>
                <div class="input-group input-group-sm mb-2">
                    <input type="text" class="form-control form-control-sm" readonly value="{{ url('/api/v1/reminders') }}" id="apiUrl">
                    <button type="button" class="btn btn-outline-secondary btn-sm" onclick="navigator.clipboard.writeText(document.getElementById('apiUrl').value)">
                        <i class="bi bi-clipboard"></i>
                    </button>
                </div>

                <form action="{{ route('clients.api-token', $client) }}" method="POST"
                      onsubmit="return confirm('{{ $client->api_token ? 'Regenerate token? The old one stops working immediately.' : 'Generate an API token?' }}')">
                    @csrf
                    <button class="btn btn-ghost btn-sm">
                        <i class="bi bi-arrow-repeat"></i> {{ $client->api_token ? 'Regenerate Token' : 'Generate Token' }}
                    </button>
                </form>
            </div>
        </div>

        {{-- Data sources --}}
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-database me-1"></i>Data Sources</span>
                <a href="{{ route('clients.data-sources.create', $client) }}" class="btn btn-ghost btn-sm">
                    <i class="bi bi-plus-lg"></i>
                </a>
            </div>
            <div class="card-body">
                @php $sources = $client->dataSources()->where('active', true)->get(); @endphp
                @if($sources->isEmpty())
                    <p class="small mb-2" style="color:var(--text-secondary);">No database connections yet.</p>
                @else
                    @foreach($sources as $ds)
                    <div class="d-flex justify-content-between align-items-center mb-1" style="font-size:13px;">
                        <span>{{ $ds->name }}</span>
                        <span class="badge bg-secondary">{{ strtoupper($ds->driver) }}</span>
                    </div>
                    @endforeach
                @endif
                <a href="{{ route('clients.data-sources.index', $client) }}" class="btn btn-ghost btn-sm mt-1 w-100">
                    Manage Data Sources
                </a>
            </div>
        </div>

        {{-- Sending domains (friendly marketing domains) --}}
        <div class="card mt-3">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-globe me-1"></i>Sending Domains</span>
                <a href="{{ route('clients.sending-domains.index', $client) }}" class="btn btn-ghost btn-sm">
                    <i class="bi bi-plus-lg"></i>
                </a>
            </div>
            <div class="card-body">
                @php $sendingDomains = $client->sendingDomains()->get(); @endphp
                @if($sendingDomains->isEmpty())
                    <p class="small mb-2" style="color:var(--text-secondary);">No custom domain yet — campaigns use the agency default.</p>
                @else
                    @foreach($sendingDomains as $sd)
                    <div class="d-flex justify-content-between align-items-center mb-1" style="font-size:13px;">
                        <span>{{ $sd->domain }}</span>
                        <span class="badge {{ $sd->isVerified() ? 'bg-success' : 'bg-warning text-dark' }}">
                            {{ $sd->isVerified() ? 'Verified' : 'Pending' }}
                        </span>
                    </div>
                    @endforeach
                @endif
                <a href="{{ route('clients.sending-domains.index', $client) }}" class="btn btn-ghost btn-sm mt-1 w-100">
                    Manage Sending Domains
                </a>
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
