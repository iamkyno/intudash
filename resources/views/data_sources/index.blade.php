@extends('layouts.app')
@section('title', 'Data Sources — '.$client->name)
@section('page-title', 'Data Sources')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <span style="font-size:13px;color:var(--text-tertiary);">
            <a href="{{ route('clients.show', $client) }}" style="color:var(--text-tertiary);text-decoration:none;">{{ $client->name }}</a>
            &rsaquo; Data Sources
        </span>
    </div>
    <a href="{{ route('clients.data-sources.create', $client) }}" class="btn btn-primary btn-sm">
        <i class="bi bi-plus-lg me-1"></i>Add Data Source
    </a>
</div>

@if(session('success'))
    <div class="alert alert-success py-2">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="alert alert-danger py-2">{{ session('error') }}</div>
@endif

@if($sources->isEmpty())
<div class="card">
    <div class="card-body text-center py-5" style="color:var(--text-tertiary);">
        <i class="bi bi-database" style="font-size:2rem;"></i>
        <p class="mt-2 mb-0">No data sources configured yet.</p>
        <a href="{{ route('clients.data-sources.create', $client) }}" class="btn btn-primary btn-sm mt-3">Connect a Database</a>
    </div>
</div>
@else
<div class="card">
    <table class="table table-hover mb-0">
        <thead>
            <tr>
                <th>Name</th>
                <th>Driver</th>
                <th>Host / Database</th>
                <th>Source</th>
                <th>Status</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
        @foreach($sources as $source)
        <tr>
            <td>{{ $source->name }}</td>
            <td><span class="badge bg-secondary">{{ strtoupper($source->driver) }}</span></td>
            <td style="font-size:13px;">{{ $source->host }}:{{ $source->port }} / {{ $source->database }}</td>
            <td style="font-size:13px;">{{ $source->table_or_view ?: ($source->custom_query ? 'custom query' : '—') }}</td>
            <td>
                @if($source->active)
                    <span class="badge bg-success">Active</span>
                @else
                    <span class="badge bg-secondary">Inactive</span>
                @endif
            </td>
            <td class="text-end">
                <a href="{{ route('clients.data-sources.edit', [$client, $source]) }}" class="btn btn-ghost btn-sm">
                    <i class="bi bi-pencil"></i>
                </a>
                <form method="POST" action="{{ route('clients.data-sources.destroy', [$client, $source]) }}" class="d-inline"
                      onsubmit="return confirm('Delete this data source?')">
                    @csrf @method('DELETE')
                    <button class="btn btn-ghost btn-sm text-danger"><i class="bi bi-trash"></i></button>
                </form>
            </td>
        </tr>
        @endforeach
        </tbody>
    </table>
</div>
@endif
@endsection
