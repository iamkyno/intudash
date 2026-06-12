@extends('layouts.app')
@section('title', 'Clients')
@section('page-title', 'Clients')

@section('content')
<div class="page-header">
    <div>
        <h1>Clients</h1>
        <p>{{ $clients->total() }} total accounts</p>
    </div>
    <a href="{{ route('clients.create') }}" class="btn btn-primary btn-sm">
        <i class="bi bi-plus-circle"></i> Add Client
    </a>
</div>

<div class="card">
    <div class="card-body-flush">
        <table class="table">
            <thead>
                <tr>
                    <th>Company</th>
                    <th>Contact</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>SMS Rate</th>
                    <th>Campaigns</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($clients as $client)
                <tr>
                    <td>
                        <a href="{{ route('clients.show', $client) }}" class="table-link">
                            {{ $client->company_name }}
                        </a>
                    </td>
                    <td>{{ $client->contact_person }}</td>
                    <td class="table-muted">{{ $client->email }}</td>
                    <td class="table-muted">{{ $client->phone }}</td>
                    <td style="font-size:13px;color:var(--color-mist);">R {{ number_format($client->default_sms_rate, 4) }}</td>
                    <td style="font-size:13px;color:var(--color-mist);">{{ $client->campaigns_count }}</td>
                    <td>
                        @if($client->status === 'active')
                            <span class="badge badge-success badge-dot">Active</span>
                        @else
                            <span class="badge badge-neutral badge-dot">Inactive</span>
                        @endif
                    </td>
                    <td>
                        <div class="d-flex gap-1">
                            <a href="{{ route('clients.show', $client) }}" class="btn btn-ghost btn-sm btn-icon"><i class="bi bi-eye"></i></a>
                            <a href="{{ route('clients.edit', $client) }}" class="btn btn-ghost btn-sm btn-icon"><i class="bi bi-pencil"></i></a>
                            <form action="{{ route('clients.destroy', $client) }}" method="POST" onsubmit="return confirm('Delete this client?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-danger-ghost btn-sm btn-icon"><i class="bi bi-trash"></i></button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" style="text-align:center;padding:40px;color:var(--color-mist-tertiary);">
                        No clients yet. <a href="{{ route('clients.create') }}" class="text-brand">Add your first client</a>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="mt-3">{{ $clients->links() }}</div>
@endsection
