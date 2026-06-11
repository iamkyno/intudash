@extends('layouts.app')
@section('title', 'Campaigns')
@section('page-title', 'Campaign Management')

@section('content')
<div class="card mb-3">
    <div class="card-body py-2">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-4">
                <input type="text" name="search" class="form-control form-control-sm" placeholder="Search campaigns..." value="{{ request('search') }}">
            </div>
            <div class="col-md-3">
                <select name="status" class="form-select form-select-sm">
                    <option value="">All Statuses</option>
                    @foreach(['draft','recipients_uploaded','invoice_generated','awaiting_payment','ready_to_schedule','scheduled','sending','completed','partially_completed','failed','cancelled'] as $s)
                        <option value="{{ $s }}" {{ request('status') === $s ? 'selected' : '' }}>{{ ucwords(str_replace('_', ' ', $s)) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <select name="client_id" class="form-select form-select-sm">
                    <option value="">All Clients</option>
                    @foreach($clients as $id => $name)
                        <option value="{{ $id }}" {{ request('client_id') == $id ? 'selected' : '' }}>{{ $name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-sm btn-outline-secondary w-100">Filter</button>
            </div>
        </form>
    </div>
</div>

<div class="d-flex justify-content-between align-items-center mb-2">
    <span class="text-muted">{{ $campaigns->total() }} campaigns</span>
    <a href="{{ route('campaigns.create') }}" class="btn btn-primary btn-sm">
        <i class="bi bi-plus-circle me-1"></i>New Campaign
    </a>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Campaign</th>
                        <th>Client</th>
                        <th>Status</th>
                        <th>Recipients</th>
                        <th>Charge</th>
                        <th>Scheduled</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($campaigns as $campaign)
                    <tr>
                        <td>
                            <a href="{{ route('campaigns.show', $campaign) }}" class="fw-semibold text-decoration-none">
                                {{ $campaign->name }}
                            </a>
                        </td>
                        <td>{{ $campaign->client->company_name }}</td>
                        <td><span class="badge bg-{{ $campaign->status_color }}">{{ $campaign->status_label }}</span></td>
                        <td>{{ number_format($campaign->actual_recipients ?: $campaign->estimated_recipients) }}</td>
                        <td>R {{ number_format($campaign->actual_charge ?: $campaign->estimated_charge, 2) }}</td>
                        <td class="small text-muted">{{ $campaign->scheduled_at?->format('d M H:i') ?? '—' }}</td>
                        <td>
                            <div class="d-flex gap-1">
                                <a href="{{ route('campaigns.show', $campaign) }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></a>
                                @if($campaign->status === 'draft')
                                    <a href="{{ route('campaigns.edit', $campaign) }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></a>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center text-muted py-4">
                            No campaigns found. <a href="{{ route('campaigns.create') }}">Create your first campaign</a>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
<div class="mt-3">{{ $campaigns->links() }}</div>
@endsection
