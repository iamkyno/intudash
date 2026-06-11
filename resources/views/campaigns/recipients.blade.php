@extends('layouts.app')
@section('title', 'Recipients')
@section('page-title', 'Recipients — ' . $campaign->name)

@section('content')
<div class="row g-3 mb-3">
    <div class="col-6 col-md-3">
        <div class="stat-card text-center">
            <div class="stat-value">{{ number_format($stats['total']) }}</div>
            <div class="stat-label">Total Uploaded</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card text-center">
            <div class="stat-value text-success">{{ number_format($stats['valid']) }}</div>
            <div class="stat-label">Valid</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card text-center">
            <div class="stat-value text-warning">{{ number_format($stats['duplicate']) }}</div>
            <div class="stat-label">Duplicates</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card text-center">
            <div class="stat-value text-danger">{{ number_format($stats['invalid']) }}</div>
            <div class="stat-label">Invalid</div>
        </div>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header"><i class="bi bi-upload me-2"></i>Upload CSV</div>
            <div class="card-body">
                <form action="{{ route('campaigns.recipients.upload', $campaign) }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">CSV File</label>
                        <input type="file" name="csv_file" class="form-control" accept=".csv,.txt" required>
                        <small class="text-muted">Required columns: <code>phone</code>. Optional: <code>name</code>, <code>email</code></small>
                    </div>
                    <button type="submit" class="btn btn-primary btn-sm">Upload Recipients</button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card">
            <div class="card-header"><i class="bi bi-person-plus me-2"></i>Add Single Recipient</div>
            <div class="card-body">
                <form action="{{ route('campaigns.recipients.store', $campaign) }}" method="POST">
                    @csrf
                    <div class="row g-2">
                        <div class="col-6">
                            <input type="text" name="name" class="form-control form-control-sm" placeholder="Name">
                        </div>
                        <div class="col-6">
                            <input type="text" name="phone" class="form-control form-control-sm" placeholder="Phone *" required>
                        </div>
                        <div class="col-8">
                            <input type="email" name="email" class="form-control form-control-sm" placeholder="Email (optional)">
                        </div>
                        <div class="col-4">
                            <button type="submit" class="btn btn-sm btn-primary w-100">Add</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="d-flex justify-content-between align-items-center mb-2">
    <div class="d-flex gap-2">
        <a href="{{ route('campaigns.recipients.export-invalid', $campaign) }}" class="btn btn-sm btn-outline-warning">
            <i class="bi bi-download me-1"></i>Export Invalid/Duplicates
        </a>
        <a href="{{ route('campaigns.recipients.export-valid', $campaign) }}" class="btn btn-sm btn-outline-success">
            <i class="bi bi-download me-1"></i>Export Clean List
        </a>
    </div>
    <a href="{{ route('campaigns.show', $campaign) }}" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i>Back to Campaign
    </a>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm table-hover mb-0">
                <thead>
                    <tr><th>Name</th><th>Phone</th><th>Normalized</th><th>Email</th><th>Status</th><th>Reason</th><th></th></tr>
                </thead>
                <tbody>
                    @forelse($recipients as $r)
                    <tr>
                        <td>{{ $r->name ?? '—' }}</td>
                        <td>{{ $r->phone }}</td>
                        <td><code>{{ $r->phone_normalized }}</code></td>
                        <td>{{ $r->email ?? '—' }}</td>
                        <td>
                            <span class="badge bg-{{ $r->status === 'valid' ? 'success' : ($r->status === 'duplicate' ? 'warning' : 'danger') }}">
                                {{ ucfirst($r->status) }}
                            </span>
                        </td>
                        <td class="text-muted small">{{ $r->invalid_reason ?? '' }}</td>
                        <td>
                            <form action="{{ route('campaigns.recipients.destroy', [$campaign, $r]) }}" method="POST">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="7" class="text-center text-muted py-3">No recipients yet. Upload a CSV or add manually.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
<div class="mt-3">{{ $recipients->links() }}</div>
@endsection
