@extends('layouts.app')
@section('title', 'Archived Campaigns')
@section('page-title', 'Archived Campaigns')

@section('content')
<div class="page-header">
    <div class="d-flex align-items-center gap-3">
        <a href="{{ route('campaigns.index') }}" class="btn btn-ghost btn-sm btn-icon"><i class="bi bi-arrow-left"></i></a>
        <div>
            <h1>Archived Campaigns</h1>
            <p>{{ $campaigns->total() }} archived</p>
        </div>
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
                    <th>Archived</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @php
                $badgeMap = ['draft'=>'badge-neutral','recipients_uploaded'=>'badge-info','invoice_generated'=>'badge-info','awaiting_payment'=>'badge-warning','ready_to_schedule'=>'badge-success','scheduled'=>'badge-brand','sending'=>'badge-info','completed'=>'badge-success','partially_completed'=>'badge-warning','failed'=>'badge-danger','cancelled'=>'badge-neutral','paused'=>'badge-warning'];
                $labelMap = ['draft'=>'Draft','recipients_uploaded'=>'Recipients Uploaded','invoice_generated'=>'Invoice Generated','awaiting_payment'=>'Awaiting Payment','ready_to_schedule'=>'Ready to Schedule','scheduled'=>'Scheduled','sending'=>'Sending','completed'=>'Completed','partially_completed'=>'Partial','failed'=>'Failed','cancelled'=>'Cancelled','paused'=>'Paused'];
                @endphp
                @forelse($campaigns as $campaign)
                <tr>
                    <td>
                        <a href="{{ route('campaigns.show', $campaign) }}" class="table-link">{{ $campaign->name }}</a>
                    </td>
                    <td>
                        @if($campaign->client->deleted_at)
                            <span style="color:var(--text-tertiary);text-decoration:line-through;">{{ $campaign->client->company_name }}</span>
                            <span class="badge badge-neutral ms-1">Deleted</span>
                        @else
                            <span class="table-muted">{{ $campaign->client->company_name }}</span>
                        @endif
                    </td>
                    <td>
                        <span class="badge {{ $badgeMap[$campaign->status] ?? 'badge-neutral' }} badge-dot">
                            {{ $labelMap[$campaign->status] ?? $campaign->status }}
                        </span>
                    </td>
                    <td style="font-size:13px;color:var(--text-secondary);">
                        {{ number_format($campaign->actual_recipients ?: $campaign->estimated_recipients) }}
                    </td>
                    <td style="font-size:13px;color:var(--text-secondary);">
                        R {{ number_format($campaign->actual_charge ?: $campaign->estimated_charge, 2) }}
                    </td>
                    <td class="table-muted">{{ $campaign->archived_at?->format('d M Y') ?? '—' }}</td>
                    <td>
                        <div class="d-flex gap-1">
                            <a href="{{ route('campaigns.show', $campaign) }}" class="btn btn-ghost btn-sm btn-icon"><i class="bi bi-eye"></i></a>
                            <form action="{{ route('campaigns.restore-archive', $campaign) }}" method="POST">
                                @csrf
                                <button type="submit" class="btn btn-ghost btn-sm" title="Restore">
                                    <i class="bi bi-arrow-counterclockwise"></i> Restore
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" style="text-align:center;padding:40px;color:var(--text-tertiary);">No archived campaigns.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3">{{ $campaigns->links() }}</div>
@endsection
