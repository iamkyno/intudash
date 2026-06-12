@extends('layouts.app')
@section('title', 'Campaigns')
@section('page-title', 'Campaigns')

@section('content')
<div class="page-header">
    <div>
        <h1>Campaigns</h1>
        <p>{{ $campaigns->total() }} total</p>
    </div>
    <a href="{{ route('campaigns.create') }}" class="btn btn-primary btn-sm">
        <i class="bi bi-plus-circle"></i> New Campaign
    </a>
</div>

{{-- Filters --}}
<div class="card mb-3">
    <div class="card-body">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-4">
                <input type="text" name="search" class="form-control form-control-sm"
                    placeholder="Search campaigns…" value="{{ request('search') }}">
            </div>
            <div class="col-md-3">
                <select name="status" class="form-select form-select-sm">
                    <option value="">All statuses</option>
                    @foreach(['draft','recipients_uploaded','invoice_generated','awaiting_payment','ready_to_schedule','scheduled','sending','completed','partially_completed','failed','cancelled'] as $s)
                        <option value="{{ $s }}" {{ request('status') === $s ? 'selected' : '' }}>
                            {{ ucwords(str_replace('_', ' ', $s)) }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <select name="client_id" class="form-select form-select-sm">
                    <option value="">All clients</option>
                    @foreach($clients as $id => $name)
                        <option value="{{ $id }}" {{ request('client_id') == $id ? 'selected' : '' }}>{{ $name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-ghost btn-sm w-100">Filter</button>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body-flush">
        <table class="table">
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
                @php
                $badgeMap = ['draft'=>'badge-neutral','recipients_uploaded'=>'badge-info','invoice_generated'=>'badge-info','awaiting_payment'=>'badge-warning','ready_to_schedule'=>'badge-success','scheduled'=>'badge-brand','sending'=>'badge-info','completed'=>'badge-success','partially_completed'=>'badge-warning','failed'=>'badge-danger','cancelled'=>'badge-neutral'];
                $labelMap = ['draft'=>'Draft','recipients_uploaded'=>'Recipients Uploaded','invoice_generated'=>'Invoice Generated','awaiting_payment'=>'Awaiting Payment','ready_to_schedule'=>'Ready to Schedule','scheduled'=>'Scheduled','sending'=>'Sending','completed'=>'Completed','partially_completed'=>'Partial','failed'=>'Failed','cancelled'=>'Cancelled'];
                @endphp
                <tr>
                    <td>
                        <a href="{{ route('campaigns.show', $campaign) }}" class="table-link">{{ $campaign->name }}</a>
                    </td>
                    <td class="table-muted">{{ $campaign->client->company_name }}</td>
                    <td>
                        <span class="badge {{ $badgeMap[$campaign->status] ?? 'badge-neutral' }} badge-dot">
                            {{ $labelMap[$campaign->status] ?? $campaign->status }}
                        </span>
                    </td>
                    <td style="font-size:13px;color:var(--color-mist);">
                        {{ number_format($campaign->actual_recipients ?: $campaign->estimated_recipients) }}
                    </td>
                    <td style="font-size:13px;color:var(--color-mist);">
                        R {{ number_format($campaign->actual_charge ?: $campaign->estimated_charge, 2) }}
                    </td>
                    <td class="table-muted">{{ $campaign->scheduled_at?->format('d M H:i') ?? '—' }}</td>
                    <td>
                        <div class="d-flex gap-1">
                            <a href="{{ route('campaigns.show', $campaign) }}" class="btn btn-ghost btn-sm btn-icon"><i class="bi bi-eye"></i></a>
                            @if($campaign->status === 'draft')
                                <a href="{{ route('campaigns.edit', $campaign) }}" class="btn btn-ghost btn-sm btn-icon"><i class="bi bi-pencil"></i></a>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" style="text-align:center;padding:40px;color:var(--color-mist-tertiary);">
                        No campaigns found. <a href="{{ route('campaigns.create') }}" class="text-brand">Create your first</a>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3">{{ $campaigns->links() }}</div>
@endsection
