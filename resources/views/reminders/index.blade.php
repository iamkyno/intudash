@extends('layouts.app')
@section('title', 'Reminders')
@section('page-title', 'Reminders')

@section('content')
<div class="page-header">
    <div>
        <h1>Reminders</h1>
        <p>{{ $reminders->total() }} total</p>
    </div>
    <div class="d-flex gap-2">
        <button type="button" class="btn btn-ghost btn-sm" data-bs-toggle="modal" data-bs-target="#uploadModal">
            <i class="bi bi-upload"></i> Import CSV
        </button>
        <a href="{{ route('reminders.create') }}" class="btn btn-primary btn-sm">
            <i class="bi bi-plus-circle"></i> New Reminder
        </a>
    </div>
</div>

@if(session('import_errors'))
<div class="alert alert-warning">
    <strong>Some rows were skipped:</strong>
    <ul class="mb-0 mt-1" style="font-size:13px;">
        @foreach(session('import_errors') as $err)<li>{{ $err }}</li>@endforeach
    </ul>
</div>
@endif

<div class="card mb-3">
    <div class="card-body">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-4">
                <select name="client_id" class="form-select form-select-sm">
                    <option value="">All clients</option>
                    @foreach($clients as $id => $name)
                        <option value="{{ $id }}" {{ request('client_id') == $id ? 'selected' : '' }}>{{ $name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <select name="status" class="form-select form-select-sm">
                    <option value="">All statuses</option>
                    @foreach(['pending','sent','partially_sent','failed','cancelled'] as $s)
                        <option value="{{ $s }}" {{ request('status') === $s ? 'selected' : '' }}>{{ ucwords(str_replace('_',' ',$s)) }}</option>
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
                    <th>Recipient</th>
                    <th>Client</th>
                    <th>Template</th>
                    <th>Channel</th>
                    <th>Send At</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @php
                $badge = ['pending'=>'badge-info','sent'=>'badge-success','partially_sent'=>'badge-warning','failed'=>'badge-danger','cancelled'=>'badge-neutral'];
                @endphp
                @forelse($reminders as $reminder)
                <tr>
                    <td style="color:var(--text-primary);">
                        {{ $reminder->recipient_name ?: '—' }}
                        <div style="font-size:12px;color:var(--text-tertiary);">{{ $reminder->phone ?: $reminder->email }}</div>
                    </td>
                    <td class="table-muted">{{ $reminder->client->company_name ?? '—' }}</td>
                    <td><code style="font-size:12px;">{{ $reminder->template_slug }}</code></td>
                    <td><span class="badge badge-neutral">{{ strtoupper($reminder->channel) }}</span></td>
                    <td class="table-muted">{{ $reminder->send_at->format('d M Y H:i') }}</td>
                    <td>
                        <span class="badge {{ $badge[$reminder->status] ?? 'badge-neutral' }} badge-dot">{{ ucwords(str_replace('_',' ',$reminder->status)) }}</span>
                        @if($reminder->failure_reason)
                            <i class="bi bi-info-circle ms-1" title="{{ $reminder->failure_reason }}" style="color:var(--color-danger);"></i>
                        @endif
                    </td>
                    <td>
                        <div class="d-flex gap-1">
                            @if($reminder->status === 'pending')
                            <form action="{{ route('reminders.cancel', $reminder) }}" method="POST">
                                @csrf
                                <button class="btn btn-ghost btn-sm btn-icon" title="Cancel"><i class="bi bi-x-circle"></i></button>
                            </form>
                            @endif
                            <form action="{{ route('reminders.destroy', $reminder) }}" method="POST" onsubmit="return confirm('Delete this reminder?')">
                                @csrf @method('DELETE')
                                <button class="btn btn-ghost btn-sm btn-icon text-danger"><i class="bi bi-trash"></i></button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="7" style="text-align:center;padding:40px;color:var(--text-tertiary);">
                    No reminders yet. <a href="{{ route('reminders.create') }}" class="text-brand">Add one</a> or import a CSV.
                </td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3">{{ $reminders->links() }}</div>

{{-- CSV upload modal --}}
<div class="modal fade" id="uploadModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('reminders.upload') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Import Reminders CSV</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Client <span class="text-danger">*</span></label>
                        <select name="client_id" class="form-select" required>
                            <option value="">Select Client</option>
                            @foreach($clients as $id => $name)
                                <option value="{{ $id }}">{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">CSV File <span class="text-danger">*</span></label>
                        <input type="file" name="csv_file" class="form-control" accept=".csv,.txt" required>
                    </div>
                    <div style="background:var(--surface-bg);border:1px solid var(--surface-border);border-radius:6px;padding:12px;">
                        <p class="small fw-semibold mb-1">Required columns:</p>
                        <ul class="small mb-1">
                            <li><code>template</code> — the template slug for this client</li>
                            <li><code>send_at</code> — when to send (e.g. <code>2026-06-20 09:00</code>)</li>
                            <li><code>phone</code> and/or <code>email</code> — per the template channel</li>
                        </ul>
                        <p class="small mb-0"><code>name</code> is optional. Any extra columns (e.g. <code>date</code>, <code>time</code>) become merge fields usable as <code>@{{column}}</code> in the template.</p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-sm">Import</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
