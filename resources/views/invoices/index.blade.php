@extends('layouts.app')
@section('title', 'Invoices')
@section('page-title', 'Invoices')

@section('content')
<div class="page-header">
    <div>
        <h1>Invoices</h1>
        <p>{{ $invoices->total() }} total</p>
    </div>
</div>

<div class="card">
    <div class="card-body-flush">
        <table class="table">
            <thead>
                <tr><th>Invoice</th><th>Client</th><th>Campaign</th><th>Amount</th><th>Status</th><th>Date</th><th></th></tr>
            </thead>
            <tbody>
                @php
                $badgeMap = ['draft'=>'badge-neutral','sent'=>'badge-info','paid'=>'badge-success','overdue'=>'badge-danger','cancelled'=>'badge-neutral'];
                @endphp
                @forelse($invoices as $invoice)
                <tr>
                    <td><a href="{{ route('invoices.show', $invoice) }}" class="table-link">{{ $invoice->invoice_number }}</a></td>
                    <td>{{ $invoice->client->company_name }}</td>
                    <td class="table-muted">{{ $invoice->campaign?->name ?? '—' }}</td>
                    <td style="font-size:13px;font-weight:600;color:var(--text-primary);">R {{ number_format($invoice->total, 2) }}</td>
                    <td><span class="badge {{ $badgeMap[$invoice->status] ?? 'badge-neutral' }} badge-dot">{{ ucfirst($invoice->status) }}</span></td>
                    <td class="table-muted">{{ $invoice->created_at->format('d M Y') }}</td>
                    <td>
                        <div class="d-flex gap-1">
                            <a href="{{ route('invoices.show', $invoice) }}" class="btn btn-ghost btn-sm btn-icon"><i class="bi bi-eye"></i></a>
                            <a href="{{ route('invoices.pdf', $invoice) }}" class="btn btn-ghost btn-sm btn-icon"><i class="bi bi-file-pdf"></i></a>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" style="text-align:center;padding:40px;color:var(--text-tertiary);">No invoices yet.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3">{{ $invoices->links() }}</div>
@endsection
