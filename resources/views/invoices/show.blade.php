@extends('layouts.app')
@section('title', $invoice->invoice_number)
@section('page-title', 'Invoice ' . $invoice->invoice_number)

@section('content')
<div class="row">
    <div class="col-md-8">
        <div class="card mb-3">
            <div class="card-body">
                <div class="d-flex justify-content-between mb-4">
                    <div>
                        <h4 class="fw-bold text-primary">INVOICE</h4>
                        <h5>{{ $invoice->invoice_number }}</h5>
                    </div>
                    <div class="text-end">
                        <span class="badge bg-{{ $invoice->status_color }} fs-6">{{ ucfirst($invoice->status) }}</span>
                        <p class="text-muted small mt-1">Date: {{ $invoice->created_at->format('d F Y') }}</p>
                        @if($invoice->due_date)
                        <p class="text-muted small">Due: {{ $invoice->due_date->format('d F Y') }}</p>
                        @endif
                    </div>
                </div>

                <div class="row mb-4">
                    <div class="col-6">
                        <p class="text-muted small mb-1">Bill To:</p>
                        <strong>{{ $invoice->client->company_name }}</strong>
                        <p class="small mb-0">{{ $invoice->client->contact_person }}</p>
                        <p class="small mb-0">{{ $invoice->client->email }}</p>
                        @if($invoice->client->billing_address)
                        <p class="small mb-0">{{ $invoice->client->billing_address }}</p>
                        @endif
                        @if($invoice->client->vat_number)
                        <p class="small mb-0">VAT: {{ $invoice->client->vat_number }}</p>
                        @endif
                    </div>
                    <div class="col-6">
                        <p class="text-muted small mb-1">Campaign:</p>
                        <strong>{{ $invoice->campaign?->name ?? '—' }}</strong>
                    </div>
                </div>

                <table class="table table-bordered">
                    <thead class="table-light">
                        <tr><th>Description</th><th class="text-end">Qty</th><th class="text-end">Rate</th><th class="text-end">Total</th></tr>
                    </thead>
                    <tbody>
                        @foreach($invoice->items as $item)
                        <tr>
                            <td>{{ $item->description }}</td>
                            <td class="text-end">{{ number_format($item->quantity) }}</td>
                            <td class="text-end">R {{ number_format($item->unit_price, 4) }}</td>
                            <td class="text-end">R {{ number_format($item->total, 2) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="3" class="text-end text-muted">Subtotal</td>
                            <td class="text-end">R {{ number_format($invoice->subtotal, 2) }}</td>
                        </tr>
                        @if($invoice->vat_enabled)
                        <tr>
                            <td colspan="3" class="text-end text-muted">VAT ({{ $invoice->vat_rate }}%)</td>
                            <td class="text-end">R {{ number_format($invoice->vat_amount, 2) }}</td>
                        </tr>
                        @endif
                        <tr class="fw-bold">
                            <td colspan="3" class="text-end">Total</td>
                            <td class="text-end fs-5">R {{ number_format($invoice->total, 2) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card mb-3">
            <div class="card-header">Update Status</div>
            <div class="card-body">
                <form action="{{ route('invoices.update-status', $invoice) }}" method="POST">
                    @csrf @method('PATCH')
                    <select name="status" class="form-select mb-2">
                        @foreach(['draft','sent','paid','overdue','cancelled'] as $s)
                            <option value="{{ $s }}" {{ $invoice->status === $s ? 'selected' : '' }}>{{ ucfirst($s) }}</option>
                        @endforeach
                    </select>
                    <button type="submit" class="btn btn-primary btn-sm w-100">Update Status</button>
                </form>
                @if($invoice->status === 'paid' && $invoice->paid_at)
                    <p class="text-success small mt-2 mb-0"><i class="bi bi-check-circle me-1"></i>Paid on {{ $invoice->paid_at->format('d M Y') }}</p>
                @endif
            </div>
        </div>

        <div class="card">
            <div class="card-header">Downloads</div>
            <div class="card-body d-grid gap-2">
                <a href="{{ route('invoices.pdf', $invoice) }}" class="btn btn-outline-danger btn-sm">
                    <i class="bi bi-file-pdf me-1"></i>Download PDF
                </a>
                @if($invoice->campaign)
                <a href="{{ route('campaigns.show', $invoice->campaign) }}" class="btn btn-outline-primary btn-sm">
                    <i class="bi bi-megaphone me-1"></i>View Campaign
                </a>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
