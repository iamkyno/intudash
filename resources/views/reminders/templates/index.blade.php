@extends('layouts.app')
@section('title', 'Reminder Templates')
@section('page-title', 'Reminder Templates')

@section('content')
<div class="page-header">
    <div>
        <h1>Reminder Templates</h1>
        <p>{{ $templates->total() }} total</p>
    </div>
    <a href="{{ route('reminder-templates.create') }}" class="btn btn-primary btn-sm">
        <i class="bi bi-plus-circle"></i> New Template
    </a>
</div>

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
                    <th>Name</th>
                    <th>Client</th>
                    <th>Slug</th>
                    <th>Channel</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($templates as $template)
                <tr>
                    <td><a href="{{ route('reminder-templates.edit', $template) }}" class="table-link">{{ $template->name }}</a></td>
                    <td class="table-muted">{{ $template->client->company_name ?? '—' }}</td>
                    <td><code style="font-size:12px;">{{ $template->slug }}</code></td>
                    <td><span class="badge badge-info">{{ ['sms'=>'SMS','email'=>'Email','both'=>'SMS + Email'][$template->channel] }}</span></td>
                    <td>
                        @if($template->active)
                            <span class="badge badge-success badge-dot">Active</span>
                        @else
                            <span class="badge badge-neutral badge-dot">Inactive</span>
                        @endif
                    </td>
                    <td>
                        <div class="d-flex gap-1">
                            <a href="{{ route('reminder-templates.edit', $template) }}" class="btn btn-ghost btn-sm btn-icon"><i class="bi bi-pencil"></i></a>
                            <form action="{{ route('reminder-templates.destroy', $template) }}" method="POST" onsubmit="return confirm('Delete this template?')">
                                @csrf @method('DELETE')
                                <button class="btn btn-ghost btn-sm btn-icon text-danger"><i class="bi bi-trash"></i></button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="6" style="text-align:center;padding:40px;color:var(--text-tertiary);">
                    No templates yet. <a href="{{ route('reminder-templates.create') }}" class="text-brand">Create your first</a>
                </td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3">{{ $templates->links() }}</div>
@endsection
