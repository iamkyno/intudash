@extends('layouts.app')
@section('title', $invoice->invoice_number)
@section('page-title', 'Invoice ' . $invoice->invoice_number)

@push('styles')
<style>
    /* Invoice paper — white light surface */
    .invoice-paper {
        background: #ffffff;
        color: #111111;
        border-radius: 8px;
        padding: 40px 44px;
        border: 1px solid rgba(0,0,0,0.08);
    }
    .invoice-paper .inv-heading {
        font-size: 28px;
        font-weight: 700;
        color: var(--color-brand);
        letter-spacing: -0.03em;
        margin: 0 0 2px;
    }
    .invoice-paper .inv-number {
        font-size: 15px;
        font-weight: 500;
        color: #333;
        margin: 0;
    }
    .invoice-paper .inv-meta {
        font-size: 13px;
        color: #666;
        margin: 2px 0 0;
    }
    .invoice-paper .inv-label {
        font-size: 10.5px;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        font-weight: 600;
        color: #999;
        margin-bottom: 6px;
    }
    .invoice-paper .inv-company {
        font-size: 15px;
        font-weight: 600;
        color: #111;
        margin: 0 0 3px;
    }
    .invoice-paper .inv-detail {
        font-size: 13px;
        color: #555;
        margin: 0;
        line-height: 1.6;
    }
    .invoice-paper .inv-divider {
        border: none;
        border-top: 1px solid #eee;
        margin: 24px 0;
    }
    .invoice-paper .inv-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 13.5px;
        color: #222;
    }
    .invoice-paper .inv-table thead th {
        font-size: 11px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.07em;
        color: #888;
        padding: 8px 12px;
        border-bottom: 2px solid #f0f0f0;
        background: transparent;
    }
    .invoice-paper .inv-table tbody td {
        padding: 11px 12px;
        border-bottom: 1px solid #f5f5f5;
        color: #333;
        vertical-align: middle;
    }
    .invoice-paper .inv-table tfoot td {
        padding: 9px 12px;
        color: #555;
        font-size: 13px;
    }
    .invoice-paper .inv-table tfoot tr.total-row td {
        padding-top: 14px;
        border-top: 2px solid #eee;
        font-size: 16px;
        font-weight: 700;
        color: #111;
    }
    .invoice-paper .inv-table .text-right { text-align: right; }
    .invoice-paper .inv-footer {
        font-size: 12px;
        color: #aaa;
        text-align: center;
        margin-top: 32px;
        padding-top: 16px;
        border-top: 1px solid #eee;
    }
</style>
@endpush

@section('content')
<div class="page-header">
    <div class="d-flex align-items-center gap-3">
        <a href="{{ route('invoices.index') }}" class="btn btn-ghost btn-sm btn-icon"><i class="bi bi-arrow-left"></i></a>
        <div>
            <h1>{{ $invoice->invoice_number }}</h1>
            <p>{{ $invoice->client->company_name }}</p>
        </div>
        @php $badgeMap = ['draft'=>'badge-neutral','sent'=>'badge-info','paid'=>'badge-success','overdue'=>'badge-danger','cancelled'=>'badge-neutral']; @endphp
        <span class="badge {{ $badgeMap[$invoice->status] ?? 'badge-neutral' }} badge-dot" style="font-size:12px;padding:5px 10px;">
            {{ ucfirst($invoice->status) }}
        </span>
    </div>
    <a href="{{ route('invoices.pdf', $invoice) }}" class="btn btn-ghost btn-sm">
        <i class="bi bi-file-pdf"></i> Download PDF
    </a>
</div>

<div class="row g-3">
    {{-- Invoice paper --}}
    <div class="col-md-8">
        <div class="invoice-paper">
            {{-- Header --}}
            <div class="d-flex justify-content-between align-items-flex-start mb-4">
                <div>
                    <div class="inv-heading">INVOICE</div>
                    <p class="inv-number">{{ $invoice->invoice_number }}</p>
                </div>
                <div style="text-align:right;">
                    <div style="font-size:20px;font-weight:700;color:var(--color-brand);letter-spacing:-0.02em;">IntuDash</div>
                    <div style="font-size:12px;color:#888;margin-top:2px;">SMS Campaign Services</div>
                    <div class="mt-2">
                        <p class="inv-meta">Issued: {{ $invoice->created_at->format('d F Y') }}</p>
                        @if($invoice->due_date)
                            <p class="inv-meta">Due: <strong style="color:{{ $invoice->status === 'overdue' ? '#E8694A' : '#333' }}">{{ $invoice->due_date->format('d F Y') }}</strong></p>
                        @endif
                    </div>
                </div>
            </div>

            <hr class="inv-divider">

            {{-- Bill to / Campaign --}}
            <div class="row mb-4">
                <div class="col-6">
                    <div class="inv-label">Bill To</div>
                    <div class="inv-company">{{ $invoice->client->company_name }}</div>
                    <p class="inv-detail">{{ $invoice->client->contact_person }}</p>
                    <p class="inv-detail">{{ $invoice->client->email }}</p>
                    @if($invoice->client->billing_address)
                        <p class="inv-detail">{{ $invoice->client->billing_address }}</p>
                    @endif
                    @if($invoice->client->vat_number)
                        <p class="inv-detail" style="margin-top:4px;">VAT No. {{ $invoice->client->vat_number }}</p>
                    @endif
                </div>
                <div class="col-6">
                    <div class="inv-label">Campaign</div>
                    <div class="inv-company">{{ $invoice->campaign?->name ?? '—' }}</div>
                    @if($invoice->campaign)
                        <p class="inv-detail">{{ $invoice->campaign->sms_segments }} SMS segment{{ $invoice->campaign->sms_segments > 1 ? 's' : '' }} per recipient</p>
                    @endif
                </div>
            </div>

            {{-- Line items table --}}
            <table class="inv-table">
                <thead>
                    <tr>
                        <th>Description</th>
                        <th class="text-right">Qty</th>
                        <th class="text-right">Unit Price</th>
                        <th class="text-right">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($invoice->items as $item)
                    <tr>
                        <td>{{ $item->description }}</td>
                        <td class="text-right">{{ number_format($item->quantity) }}</td>
                        <td class="text-right">R {{ number_format($item->unit_price, 4) }}</td>
                        <td class="text-right">R {{ number_format($item->total, 2) }}</td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="3" class="text-right" style="color:#888;">Subtotal</td>
                        <td class="text-right">R {{ number_format($invoice->subtotal, 2) }}</td>
                    </tr>
                    @if($invoice->vat_enabled)
                    <tr>
                        <td colspan="3" class="text-right" style="color:#888;">VAT ({{ $invoice->vat_rate }}%)</td>
                        <td class="text-right">R {{ number_format($invoice->vat_amount, 2) }}</td>
                    </tr>
                    @endif
                    <tr class="total-row">
                        <td colspan="3" class="text-right">Total Due</td>
                        <td class="text-right" style="color:var(--color-brand);">R {{ number_format($invoice->total, 2) }}</td>
                    </tr>
                </tfoot>
            </table>

            @if($invoice->notes)
                <hr class="inv-divider">
                <div class="inv-label">Notes</div>
                <p class="inv-detail">{{ $invoice->notes }}</p>
            @endif

            <div class="inv-footer">
                Thank you for your business — IntuDash SMS Campaign Manager
            </div>
        </div>
    </div>

    {{-- Sidebar actions --}}
    <div class="col-md-4 d-flex flex-column gap-3">
        {{-- Update status --}}
        <div class="card">
            <div class="card-header">
                <span class="card-header-title"><i class="bi bi-pencil-square"></i> Update Status</span>
            </div>
            <div class="card-body">
                <form action="{{ route('invoices.update-status', $invoice) }}" method="POST">
                    @csrf @method('PATCH')
                    <select name="status" class="form-select mb-3" style="font-size:13.5px;">
                        @foreach(['draft','sent','paid','overdue','cancelled'] as $s)
                            <option value="{{ $s }}" {{ $invoice->status === $s ? 'selected' : '' }}>{{ ucfirst($s) }}</option>
                        @endforeach
                    </select>
                    <button type="submit" class="btn btn-primary btn-sm w-100">Save Status</button>
                </form>
                @if($invoice->status === 'paid' && $invoice->paid_at)
                    <div class="d-flex align-items-center gap-2 mt-3" style="font-size:13px;color:var(--color-success);">
                        <i class="bi bi-check-circle-fill"></i>
                        Paid on {{ $invoice->paid_at->format('d M Y') }}
                    </div>
                @endif
            </div>
        </div>

        {{-- Summary --}}
        <div class="card">
            <div class="card-header">
                <span class="card-header-title"><i class="bi bi-calculator"></i> Summary</span>
            </div>
            <div class="card-body">
                @foreach([
                    ['SMS Quantity', number_format($invoice->sms_quantity)],
                    ['Rate / SMS', 'R '.number_format($invoice->sms_rate, 4)],
                    ['Subtotal', 'R '.number_format($invoice->subtotal, 2)],
                    ['VAT ('.$invoice->vat_rate.'%)', $invoice->vat_enabled ? 'R '.number_format($invoice->vat_amount, 2) : 'Excluded'],
                ] as [$label, $val])
                <div class="d-flex justify-content-between mb-2" style="font-size:13px;">
                    <span style="color:var(--text-secondary);">{{ $label }}</span>
                    <span style="color:var(--text-primary);">{{ $val }}</span>
                </div>
                @endforeach
                <hr class="divider">
                <div class="d-flex justify-content-between" style="font-size:15px;font-weight:600;">
                    <span style="color:var(--text-secondary);">Total</span>
                    <span style="color:var(--color-brand);">R {{ number_format($invoice->total, 2) }}</span>
                </div>
            </div>
        </div>

        {{-- Links --}}
        <div class="card">
            <div class="card-header">
                <span class="card-header-title"><i class="bi bi-link-45deg"></i> Links</span>
            </div>
            <div class="card-body d-flex flex-column gap-2">
                <a href="{{ route('invoices.pdf', $invoice) }}" class="btn btn-ghost btn-sm">
                    <i class="bi bi-file-pdf"></i> Download PDF
                </a>
                @if($invoice->campaign)
                    <a href="{{ route('campaigns.show', $invoice->campaign) }}" class="btn btn-ghost btn-sm">
                        <i class="bi bi-megaphone"></i> View Campaign
                    </a>
                @endif
                <a href="{{ route('clients.show', $invoice->client) }}" class="btn btn-ghost btn-sm">
                    <i class="bi bi-person"></i> View Client
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
