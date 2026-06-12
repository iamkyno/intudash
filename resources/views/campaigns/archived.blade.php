@extends('layouts.app')
@section('title', 'Archived Campaigns')
@section('page-title', 'Archived Campaigns')

@section('content')
<div class="page-header">
    <div class="d-flex align-items-center gap-3">
        <a href="{{ route('campaigns.index') }}" class="btn btn-ghost btn-sm btn-icon"><i class="bi bi-arrow-left"></i></a>
        <div>
            <h1>Archived Campaigns</h1>
            <p>{{ $campaigns->total() }} total</p>
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
                    <th>Completed</th>
                    <th>Archived</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @php
                $badgeMap = ['completed'=>'badge-success','partially_completed'=>'badge-warning','cancelled'=>'badge-neutral','failed'=>'badge-danger'];
                $labelMap = ['completed'=>'Completed','partially_completed'=>'Partial','cancelled'=>'Cancelled','failed'=>'Failed'];
                @endphp
                @forelse($campaigns as $campaign)
                <tr>
                    <td>
                        <a href="{{ route('campaigns.show', $campaign) }}" class="table-link">{{ $campaign->name }}</a>
                    </td>
                    <td class="table-muted">
                        @if($campaign->client->deleted_at)
                            <span style="color:var(--text-tertiary);text-decoration:line-through;">{{ $campaign->client->company_name }}</span>
                            <span class="badge badge-neutral ms-1">Deleted</span>
                        @else
                            {{ $campaign->client->company_name }}
                        @endif
                    </td>
                    <td>
                        <span class="badge {{ $badgeMap[$campaign->status] ?? 'badge-neutral' }} badge-dot">
                            {{ $labelMap[$campaign->status] ?? $campaign->status }}
                        </span>
                    </td>
                    <td class="table-muted">{{ $campaign->completed_at?->format('d M Y') ?? '—' }}</td>
                    <td class="table-muted">{{ $campaign->archived_at?->format('d M Y') ?? '—' }}</td>
                    <td>
                        <div class="d-flex gap-1">
                            <a href="{{ route('campaigns.show', $campaign) }}" class="btn btn-ghost btn-sm btn-icon"><i class="bi bi-eye"></i></a>
                            <form action="{{ route('campaigns.restore-archive', $campaign) }}" method="POST">
                                @csrf
                                <button type="submit" class="btn btn-ghost btn-sm" onclick="return confirm('Restore this campaign from archives?')">
                                    <i class="bi bi-arrow-counterclockwise"></i> Restore
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" style="text-align:center;padding:40px;color:var(--text-tertiary);">
                        No archived campaigns.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3">{{ $campaigns->links() }}</div>
@endsection
