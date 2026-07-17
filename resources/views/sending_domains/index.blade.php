@extends('layouts.app')
@section('title', 'Sending Domains')
@section('page-title', 'Sending Domains')

@section('content')
<div class="mb-3" style="font-size:13px;color:var(--text-tertiary);">
    @if($client)
        <a href="{{ route('clients.show', $client) }}" style="color:var(--text-tertiary);text-decoration:none;">{{ $client->name ?? $client->company_name }}</a>
        &rsaquo; Sending Domains
    @else
        <a href="{{ route('settings.index') }}" style="color:var(--text-tertiary);text-decoration:none;">Settings</a>
        &rsaquo; Sending Domains
    @endif
</div>

@if(session('success'))
    <div class="alert alert-success py-2">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="alert alert-danger py-2">{{ session('error') }}</div>
@endif

<div class="card mb-3">
    <div class="card-header">
        <span class="card-header-title">
            <i class="bi bi-globe"></i>
            {{ $client ? 'Add a Marketing Domain for '.($client->name ?? $client->company_name) : 'Add an Agency Domain' }}
        </span>
    </div>
    <div class="card-body">
        <p class="small mb-3" style="color:var(--text-secondary);">
            @if($client)
                Verify a domain this client's recipients will recognise (e.g. <code>news.{{ Str::slug($client->name ?? $client->company_name) }}.com</code>)
                so their marketing emails don't look like they come from a generic system address.
            @else
                This becomes the default "From" domain when a campaign or client doesn't specify its own.
            @endif
        </p>
        <form method="POST" action="{{ $client ? route('clients.sending-domains.store', $client) : route('settings.sending-domains.store') }}">
            @csrf
            <div class="row g-2">
                <div class="col-md-5">
                    <input type="text" name="domain" class="form-control @error('domain') is-invalid @enderror"
                           placeholder="marketing.yourdomain.com" value="{{ old('domain') }}" required>
                    @error('domain')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-5">
                    <input type="text" name="label" class="form-control" placeholder="Friendly label (optional), e.g. Newsletter" value="{{ old('label') }}">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">Add & Verify</button>
                </div>
            </div>
        </form>
    </div>
</div>

@if($domains->isEmpty())
<div class="card">
    <div class="card-body text-center py-5" style="color:var(--text-tertiary);">
        <i class="bi bi-globe" style="font-size:2rem;"></i>
        <p class="mt-2 mb-0">No sending domains configured yet.</p>
    </div>
</div>
@else
@foreach($domains as $domain)
<div class="card mb-3">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span class="card-header-title">
            <i class="bi bi-envelope-at"></i> {{ $domain->domain }}
            @if($domain->label)
                <span style="font-size:12px;color:var(--text-tertiary);font-weight:400;">— {{ $domain->label }}</span>
            @endif
        </span>
        <div class="d-flex align-items-center gap-2">
            @if($domain->isVerified())
                <span class="badge bg-success">Verified</span>
            @elseif($domain->verification_status === 'failed')
                <span class="badge bg-danger">Failed</span>
            @else
                <span class="badge bg-warning text-dark">Pending DNS</span>
            @endif
            @if($domain->dkim_status === 'verified')
                <span class="badge bg-success">DKIM OK</span>
            @endif
        </div>
    </div>
    <div class="card-body">
        @if(!$domain->isVerified())
            <p class="small fw-semibold mb-2" style="color:var(--text-secondary);">Add these DNS records at your domain registrar:</p>
            <div style="overflow-x:auto;">
                <table class="table table-sm table-bordered" style="font-size:12px;">
                    <thead><tr><th>Type</th><th>Host</th><th>Value</th><th>Purpose</th></tr></thead>
                    <tbody>
                    @foreach($domain->dnsRecords() as $record)
                        <tr>
                            <td><code>{{ $record['type'] }}</code></td>
                            <td style="font-family:monospace;">{{ $record['host'] }}</td>
                            <td style="font-family:monospace;word-break:break-all;">{{ $record['value'] }}</td>
                            <td style="color:var(--text-tertiary);">{{ $record['purpose'] }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            <p class="small mb-0" style="color:var(--text-tertiary);">
                DNS changes can take anywhere from a few minutes to 48 hours to propagate. Click "Recheck Status" once you've added the records.
            </p>
        @else
            <p class="small mb-0" style="color:var(--color-success);">
                <i class="bi bi-check-circle me-1"></i>This domain is verified and ready to use as a "From" address
                {{ $domain->dkim_status === 'verified' ? 'with DKIM signing enabled.' : '— DKIM is still propagating.' }}
            </p>
        @endif

        <div class="d-flex gap-2 mt-3">
            <form method="POST" action="{{ route('sending-domains.check', $domain) }}">
                @csrf
                <button type="submit" class="btn btn-outline-primary btn-sm">
                    <i class="bi bi-arrow-repeat me-1"></i>Recheck Status
                </button>
            </form>
            <form method="POST" action="{{ route('sending-domains.destroy', $domain) }}"
                  onsubmit="return confirm('Remove this sending domain? Campaigns using it will fail to send until reconfigured.')">
                @csrf @method('DELETE')
                <button type="submit" class="btn btn-outline-danger btn-sm">
                    <i class="bi bi-trash me-1"></i>Remove
                </button>
            </form>
        </div>

        @if($domain->last_checked_at)
            <p class="small mt-2 mb-0" style="color:var(--text-tertiary);">Last checked {{ $domain->last_checked_at->diffForHumans() }}</p>
        @endif
    </div>
</div>
@endforeach
@endif
@endsection
