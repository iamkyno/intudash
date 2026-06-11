@extends('layouts.app')
@section('title', 'Invoices')
@section('page-title', 'Invoices')

@section('content')
<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr><th>Invoice #</th><th>Client</th><th>Campaign</th><th>Amount</th><th>Status</th><th>Date</th><th></th></tr>
                </thead>
                <tbody>
                    @forelse($invoices as $invoice)
                    <tr>
                        <td><a href="{{ route('invoices.show', $invoice) }}" class="fw-semibold text-decoration-none">{{ $invoice->invoice_number }}</a></td>
                        <td>{{ $invoice->client->company_name }}</td>
                        <td>{{ $invoice->campaign?->name ?? '—' }}</td>
                        <td>R {{ number_format($invoice->total, 2) }}</td>
                        <td><span class="badge bg-{{ $invoice->status_color }}">{{ ucfirst($invoice->status) }}</span></td>
                        <td class="text-muted small">{{ $invoice->created_at->format('d M Y') }}</td>
                        <td>
                            <div class="d-flex gap-1">
                                <a href="{{ route('invoices.show', $invoice) }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></a>
                                <a href="{{ route('invoices.pdf', $invoice) }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-file-pdf"></i></a>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="7" class="text-center text-muted py-4">No invoices yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
<div class="mt-3">{{ $invoices->links() }}</div>
@endsection
