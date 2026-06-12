@extends('layouts.app')
@section('title', 'Quotes')
@section('page-title', 'Quotes')

@section('content')
<div class="page-header">
    <div>
        <h1>Quotes</h1>
        <p>{{ $quotes->total() }} total</p>
    </div>
    <a href="{{ route('quotes.create') }}" class="btn btn-primary btn-sm">
        <i class="bi bi-plus-circle"></i> New Quote
    </a>
</div>

<div class="card">
    <div class="card-body-flush">
        <table class="table">
            <thead>
                <tr><th>Quote #</th><th>Client</th><th>Campaign</th><th>Amount</th><th>Status</th><th>Valid Until</th><th>Date</th><th></th></tr>
            </thead>
            <tbody>
                @php
                $badgeMap = ['draft'=>'badge-neutral','sent'=>'badge-info','accepted'=>'badge-success','declined'=>'badge-danger','expired'=>'badge-neutral'];
                @endphp
                @forelse($quotes as $quote)
                <tr>
                    <td><a href="{{ route('quotes.show', $quote) }}" class="table-link">{{ $quote->quote_number }}</a></td>
                    <td>{{ $quote->client->company_name }}</td>
                    <td class="table-muted">{{ $quote->campaign?->name ?? '—' }}</td>
                    <td style="font-size:13px;font-weight:600;color:var(--text-primary);">R {{ number_format($quote->total, 2) }}</td>
                    <td><span class="badge {{ $badgeMap[$quote->status] ?? 'badge-neutral' }} badge-dot">{{ ucfirst($quote->status) }}</span></td>
                    <td class="table-muted">{{ $quote->valid_until?->format('d M Y') ?? '—' }}</td>
                    <td class="table-muted">{{ $quote->created_at->format('d M Y') }}</td>
                    <td>
                        <div class="d-flex gap-1">
                            <a href="{{ route('quotes.show', $quote) }}" class="btn btn-ghost btn-sm btn-icon"><i class="bi bi-eye"></i></a>
                            <a href="{{ route('quotes.pdf', $quote) }}" class="btn btn-ghost btn-sm btn-icon"><i class="bi bi-file-pdf"></i></a>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" style="text-align:center;padding:40px;color:var(--text-tertiary);">No quotes yet.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3">{{ $quotes->links() }}</div>
@endsection
