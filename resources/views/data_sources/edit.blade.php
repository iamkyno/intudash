@extends('layouts.app')
@section('title', 'Edit Data Source — '.$source->name)
@section('page-title', 'Edit Data Source')

@section('content')
<div class="mb-3" style="font-size:13px;color:var(--text-tertiary);">
    <a href="{{ route('clients.show', $client) }}" style="color:var(--text-tertiary);text-decoration:none;">{{ $client->name }}</a>
    &rsaquo; <a href="{{ route('clients.data-sources.index', $client) }}" style="color:var(--text-tertiary);text-decoration:none;">Data Sources</a>
    &rsaquo; {{ $source->name }}
</div>

@if(session('success'))
    <div class="alert alert-success py-2">{{ session('success') }}</div>
@endif

<div class="row g-3">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">Connection Details</div>
            <div class="card-body">
                <form method="POST" action="{{ route('clients.data-sources.update', [$client, $source]) }}">
                    @csrf @method('PUT')
                    @include('data_sources._form')
                    <div class="d-flex gap-2 mt-4">
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                        <a href="{{ route('clients.data-sources.index', $client) }}" class="btn btn-outline-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card">
            <div class="card-header">Test Connection</div>
            <div class="card-body">
                <p class="small" style="color:var(--text-secondary);">
                    Run a quick preview to verify the connection and column mappings before importing.
                </p>
                <button id="preview-btn" class="btn btn-outline-primary btn-sm w-100">
                    <i class="bi bi-play-circle me-1"></i>Preview (5 rows)
                </button>
                <div id="preview-result" class="mt-3" style="display:none;"></div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.getElementById('preview-btn')?.addEventListener('click', async () => {
    const btn = document.getElementById('preview-btn');
    const out = document.getElementById('preview-result');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Testing…';
    out.style.display = 'none';

    try {
        const res = await fetch('{{ route('clients.data-sources.preview', [$client, $source]) }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json',
            },
        });
        const json = await res.json();

        if (json.success && json.rows?.length) {
            const cols = Object.keys(json.rows[0]);
            let html = `<p class="small text-success mb-2"><i class="bi bi-check-circle me-1"></i>Connected — ${json.rows.length} sample row(s)</p>`;
            html += '<div style="overflow-x:auto;"><table class="table table-sm table-bordered" style="font-size:11px;">';
            html += '<thead><tr>' + cols.map(c => `<th>${c}</th>`).join('') + '</tr></thead><tbody>';
            for (const row of json.rows) {
                html += '<tr>' + cols.map(c => `<td>${row[c] ?? ''}</td>`).join('') + '</tr>';
            }
            html += '</tbody></table></div>';
            out.innerHTML = html;
        } else {
            out.innerHTML = `<p class="small text-danger mb-0"><i class="bi bi-x-circle me-1"></i>${json.error ?? 'No rows returned.'}</p>`;
        }
    } catch (e) {
        out.innerHTML = `<p class="small text-danger mb-0"><i class="bi bi-x-circle me-1"></i>${e.message}</p>`;
    }

    out.style.display = '';
    btn.disabled = false;
    btn.innerHTML = '<i class="bi bi-play-circle me-1"></i>Preview (5 rows)';
});
</script>
@endpush
@endsection
