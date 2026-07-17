@extends('layouts.app')
@section('title', 'Recipients — '.($client->name ?? $client->company_name))
@section('page-title', 'Recipients')

@section('content')
<div class="mb-3" style="font-size:13px;color:var(--text-tertiary);">
    <a href="{{ route('clients.show', $client) }}" style="color:var(--text-tertiary);text-decoration:none;">{{ $client->name ?? $client->company_name }}</a>
    &rsaquo; Recipients
</div>

@if(session('success'))
    <div class="alert alert-success py-2">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="alert alert-danger py-2">{{ session('error') }}</div>
@endif

<div class="row g-3 mb-3">
    <div class="col-6 col-md-3">
        <div class="stat-card text-center">
            <div class="stat-value">{{ number_format($stats['total']) }}</div>
            <div class="stat-label">Total</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card text-center">
            <div class="stat-value text-success">{{ number_format($stats['active']) }}</div>
            <div class="stat-label">Active</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card text-center">
            <div class="stat-value text-danger">{{ number_format($stats['invalid']) }}</div>
            <div class="stat-label">Invalid</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card text-center">
            <div class="stat-value text-warning">{{ number_format($stats['unsubscribed']) }}</div>
            <div class="stat-label">Unsubscribed</div>
        </div>
    </div>
</div>

<div class="row g-3">
    {{-- Groups sidebar --}}
    <div class="col-md-3">
        <div class="card mb-3">
            <div class="card-header"><i class="bi bi-collection me-2"></i>Groups</div>
            <div class="card-body-flush">
                <a href="{{ route('clients.recipients.index', $client) }}"
                   class="d-flex justify-content-between align-items-center px-3 py-2"
                   style="text-decoration:none;font-size:13px;{{ !request('group_id') ? 'background:var(--surface-bg);font-weight:600;' : '' }}color:var(--text-primary);">
                    All recipients
                    <span class="badge bg-secondary">{{ $stats['total'] }}</span>
                </a>
                @foreach($groups as $g)
                <a href="{{ route('clients.recipients.index', $client) }}?group_id={{ $g->id }}"
                   class="d-flex justify-content-between align-items-center px-3 py-2"
                   style="text-decoration:none;font-size:13px;{{ request('group_id') == $g->id ? 'background:var(--surface-bg);font-weight:600;' : '' }}color:var(--text-primary);">
                    {{ $g->name }}
                    <span class="badge bg-secondary">{{ $g->recipients_count }}</span>
                </a>
                @endforeach
                @if($groups->isEmpty())
                    <p class="small px-3 py-2 mb-0" style="color:var(--text-tertiary);">No groups yet.</p>
                @endif
            </div>
        </div>

        <div class="card">
            <div class="card-header"><i class="bi bi-plus-lg me-2"></i>New Group</div>
            <div class="card-body">
                <form method="POST" action="{{ route('clients.recipient-groups.store', $client) }}">
                    @csrf
                    <input type="text" name="name" class="form-control form-control-sm mb-2" placeholder="Group name" required>
                    <input type="text" name="description" class="form-control form-control-sm mb-2" placeholder="Description (optional)">
                    <button type="submit" class="btn btn-primary btn-sm w-100">Create Group</button>
                </form>
            </div>
        </div>

        @if($groups->isNotEmpty())
        <div class="card mt-3">
            <div class="card-header"><i class="bi bi-pencil me-2"></i>Manage Groups</div>
            <div class="card-body-flush">
                @foreach($groups as $g)
                <div class="d-flex justify-content-between align-items-center px-3 py-2" style="font-size:12px;border-bottom:1px solid var(--surface-border);">
                    <span>{{ $g->name }}</span>
                    <form method="POST" action="{{ route('clients.recipient-groups.destroy', [$client, $g]) }}"
                          onsubmit="return confirm('Delete group &quot;{{ addslashes($g->name) }}&quot;? Recipients stay, just ungrouped.')">
                        @csrf @method('DELETE')
                        <button class="btn btn-ghost btn-sm text-danger p-0" style="line-height:1;"><i class="bi bi-trash"></i></button>
                    </form>
                </div>
                @endforeach
            </div>
        </div>
        @endif
    </div>

    {{-- Main content --}}
    <div class="col-md-9">
        <div class="row g-3 mb-3">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header"><i class="bi bi-upload me-2"></i>Upload CSV</div>
                    <div class="card-body">
                        <form action="{{ route('clients.recipients.upload', $client) }}" method="POST" enctype="multipart/form-data">
                            @csrf
                            <div class="mb-2">
                                <input type="file" name="csv_file" class="form-control form-control-sm" accept=".csv,.txt" required>
                                <small class="text-muted">Columns: <code>name</code>, <code>phone</code>, <code>email</code></small>
                            </div>
                            @if($groups->isNotEmpty())
                            <div class="mb-2">
                                <select name="group_id" class="form-select form-select-sm">
                                    <option value="">— No group —</option>
                                    @foreach($groups as $g)
                                        <option value="{{ $g->id }}" {{ request('group_id') == $g->id ? 'selected' : '' }}>{{ $g->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            @endif
                            <button type="submit" class="btn btn-primary btn-sm">Upload</button>
                        </form>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header"><i class="bi bi-person-plus me-2"></i>Add Single Recipient</div>
                    <div class="card-body">
                        <form action="{{ route('clients.recipients.store', $client) }}" method="POST">
                            @csrf
                            <div class="row g-2 mb-2">
                                <div class="col-6"><input type="text" name="name" class="form-control form-control-sm" placeholder="Name"></div>
                                <div class="col-6"><input type="text" name="phone" class="form-control form-control-sm" placeholder="Phone"></div>
                            </div>
                            <div class="row g-2">
                                <div class="col-{{ $groups->isNotEmpty() ? '6' : '8' }}">
                                    <input type="email" name="email" class="form-control form-control-sm" placeholder="Email">
                                </div>
                                @if($groups->isNotEmpty())
                                <div class="col-6">
                                    <select name="group_id" class="form-select form-select-sm">
                                        <option value="">— No group —</option>
                                        @foreach($groups as $g)
                                            <option value="{{ $g->id }}">{{ $g->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                @endif
                                <div class="col-2">
                                    <button type="submit" class="btn btn-sm btn-primary w-100">Add</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        {{-- Bulk actions --}}
        @if($groups->isNotEmpty())
        <div class="card mb-2">
            <div class="card-body py-2 d-flex align-items-center gap-2">
                <span class="small" style="color:var(--text-secondary);">Bulk action:</span>
                <select id="bulk-group" class="form-select form-select-sm" style="max-width:220px;">
                    @foreach($groups as $g)
                        <option value="{{ $g->id }}">{{ $g->name }}</option>
                    @endforeach
                </select>
                <button type="button" class="btn btn-outline-primary btn-sm" onclick="submitBulk('add')">Add selected to group</button>
                <button type="button" class="btn btn-outline-danger btn-sm" onclick="submitBulk('remove')">Remove selected from group</button>
                <span id="bulk-count" class="small ms-auto" style="color:var(--text-tertiary);">0 selected</span>
            </div>
        </div>
        @endif

        <div class="card">
            <div class="card-body-flush">
                <table class="table">
                    <thead>
                        <tr>
                            <th style="width:32px;"><input type="checkbox" id="check-all"></th>
                            <th>Name</th>
                            <th>Phone</th>
                            <th>Email</th>
                            <th>Groups</th>
                            <th>Status</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recipients as $r)
                        <tr>
                            <td><input type="checkbox" class="recipient-check" value="{{ $r->id }}"></td>
                            <td>{{ $r->name ?: '—' }}</td>
                            <td class="table-muted">{{ $r->phone ?: '—' }}</td>
                            <td class="table-muted">{{ $r->email ?: '—' }}</td>
                            <td>
                                @forelse($r->groups as $g)
                                    <span class="badge bg-secondary me-1">{{ $g->name }}</span>
                                @empty
                                    <span style="color:var(--text-tertiary);font-size:12px;">—</span>
                                @endforelse
                            </td>
                            <td>
                                <span class="badge {{ $r->status === 'active' ? 'bg-success' : ($r->status === 'unsubscribed' ? 'bg-warning text-dark' : 'bg-danger') }}">
                                    {{ ucfirst($r->status) }}
                                </span>
                            </td>
                            <td class="text-end">
                                <a href="{{ route('clients.recipients.edit', [$client, $r]) }}" class="btn btn-ghost btn-sm btn-icon"><i class="bi bi-pencil"></i></a>
                                <form action="{{ route('clients.recipients.destroy', [$client, $r]) }}" method="POST" class="d-inline"
                                      onsubmit="return confirm('Remove this recipient?')">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-ghost btn-sm btn-icon text-danger"><i class="bi bi-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" style="text-align:center;padding:40px;color:var(--text-tertiary);">
                                No recipients yet. Upload a CSV or add one above.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="mt-3">{{ $recipients->links() }}</div>
    </div>
</div>

{{-- Hidden form used to submit bulk group actions --}}
<form id="bulk-form" method="POST" action="{{ route('clients.recipients.bulk-assign', $client) }}" style="display:none;">
    @csrf
    <input type="hidden" name="group_id" id="bulk-form-group">
    <input type="hidden" name="action" id="bulk-form-action">
    <div id="bulk-form-ids"></div>
</form>

@push('scripts')
<script>
const checkAll = document.getElementById('check-all');
const checks = () => document.querySelectorAll('.recipient-check');

checkAll?.addEventListener('change', function() {
    checks().forEach(c => c.checked = this.checked);
    updateCount();
});

document.addEventListener('change', function(e) {
    if (e.target.classList.contains('recipient-check')) updateCount();
});

function updateCount() {
    const n = document.querySelectorAll('.recipient-check:checked').length;
    const el = document.getElementById('bulk-count');
    if (el) el.textContent = n + ' selected';
}

function submitBulk(action) {
    const selected = Array.from(document.querySelectorAll('.recipient-check:checked')).map(c => c.value);
    if (selected.length === 0) {
        alert('Select at least one recipient first.');
        return;
    }
    const groupSelect = document.getElementById('bulk-group');
    document.getElementById('bulk-form-group').value = groupSelect.value;
    document.getElementById('bulk-form-action').value = action;

    const idsContainer = document.getElementById('bulk-form-ids');
    idsContainer.innerHTML = '';
    selected.forEach(id => {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'recipient_ids[]';
        input.value = id;
        idsContainer.appendChild(input);
    });

    document.getElementById('bulk-form').submit();
}
</script>
@endpush
@endsection
