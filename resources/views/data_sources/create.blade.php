@extends('layouts.app')
@section('title', 'Add Data Source')
@section('page-title', 'Add Data Source')

@section('content')
<div class="mb-3" style="font-size:13px;color:var(--text-tertiary);">
    <a href="{{ route('clients.show', $client) }}" style="color:var(--text-tertiary);text-decoration:none;">{{ $client->name }}</a>
    &rsaquo; <a href="{{ route('clients.data-sources.index', $client) }}" style="color:var(--text-tertiary);text-decoration:none;">Data Sources</a>
    &rsaquo; Add
</div>

<div class="card">
    <div class="card-header">Connect External Database</div>
    <div class="card-body">
        <form method="POST" action="{{ route('clients.data-sources.store', $client) }}">
            @csrf
            @include('data_sources._form')
            <div class="d-flex gap-2 mt-4">
                <button type="submit" class="btn btn-primary">Save Data Source</button>
                <a href="{{ route('clients.data-sources.index', $client) }}" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
