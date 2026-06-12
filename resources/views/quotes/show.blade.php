@extends('layouts.app')
@section('title', $quote->quote_number)
@section('page-title', 'Quote ' . $quote->quote_number)

@push('styles')
<style>
    .quote-paper {
        background: #ffffff;
        color: #111111;
        border-radius: 8px;
        padding: 40px 44px;
        border: 1px solid rgba(0,0,0,0.08);
    }
    .quote-paper .q-heading { font-size: 28px; font-weight: 700; color: var(--color-brand); letter-spacing: -0.03em; margin: 0 0 2px; }
    .quote-paper .q-number  { font-size: 15px; font-weight: 500; color: #333; margin: 0; }
    .quote-paper .q-meta    { font-size: 13px; color: #666; margin: 2px 0 0; }
    .quote-paper .q-label   { font-size: 10.5px; text-transform: uppercase; letter-spacing: 0.08em; font-weight: 600; color: #999; margin-bottom: 6px; }
    .quote-paper .q-company { font-size: 15px; font-weight: 600; color: #111; margin: 0 0 3px; }
    .quote-paper .q-detail  { font-size: 13px; color: #555; margin: 0; line-height: 1.6; }
    .quote-paper .q-divider { border: none; border-top: 1px solid #eee; margin: 24px 0; }
    .quote-paper .q-table   { width: 100%; border-collapse: collapse; font-size: 13.5px; color: #222; }
    .quote-paper .q-table thead th { font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.07em; color: #888; padding: 8px 12px; border-bottom: 2px solid #f0f0f0; background: transparent; }
    .quote-paper .q-table tbody td { padding: 11px 12px; border-bottom: 1px solid #f5f5f5; color: #333; vertical-align: middle; }
    .quote-paper .q-table tfoot td { padding: 9px 12px; color: #555; font-size: 13px; }
    .quote-paper .q-table tfoot tr.total-row td { padding-top: 14px; border-top: 2px solid #eee; font-size: 16px; font-weight: 700; color: #111; }
    .quote-paper .q-table .text-right { text-align: right; }
    .quote-paper .q-footer { font-size: 12px; color: #aaa; text-align: center; margin-top: 32px; padding-top: 16px; border-top: 1px solid #eee; }
</style>
@endpush

@section('content')
<div class="page-header">
    <div class="d-flex align-items-center gap-3">
        <a href="{{ route('quotes.index') }}" class="btn btn-ghost btn-sm btn-icon"><i class="bi bi-arrow-left"></i></a>
        <div>
            <h1>{{ $quote->quote_number }}</h1>
            <p>{{ $quote->client->company_name }}</p>
        </div>
        @php $badgeMap = ['draft'=>'badge-neutral','sent'=>'badge-info','accepted'=>'badge-success','declined'=>'badge-danger','expired'=>'badge-neutral']; @endphp
        <span class="badge {{ $badgeMap[$quote->status] ?? 'badge-neutral' }} badge-dot" style="font-size:12px;padding:5px 10px;">
            {{ ucfirst($quote->status) }}
        </span>
    </div>
    <a href="{{ route('quotes.pdf', $quote) }}" class="btn btn-ghost btn-sm">
        <i class="bi bi-file-pdf"></i> Download PDF
    </a>
</div>

<div class="row g-3">
    {{-- Quote paper --}}
    <div class="col-md-8">
        <div class="quote-paper">
            <div class="d-flex justify-content-between align-items-flex-start mb-4">
                <div>
                    <div class="q-heading">QUOTE</div>
                    <p class="q-number">{{ $quote->quote_number }}</p>
                </div>
                <div style="text-align:right;">
                    <div style="font-size:20px;font-weight:700;color:var(--color-brand);letter-spacing:-0.02em;">IntuDash</div>
                    <div style="font-size:12px;color:#888;margin-top:2px;">SMS Campaign Services</div>
                    <div class="mt-2">
                        <p class="q-meta">Issued: {{ $quote->created_at->format('d F Y') }}</p>
                        @if($quote->valid_until)
                            <p class="q-meta">Valid Until: <strong style="color:{{ $quote->isExpired() ? '#E8694A' : '#333' }}">{{ $quote->valid_until->format('d F Y') }}</strong></p>
                        @endif
                    </div>
                </div>
            </div>

            <hr class="q-divider">

            <div class="row mb-4">
                <div class="col-6">
                    <div class="q-label">Quote For</div>
                    <div class="q-company">{{ $quote->client->company_name }}</div>
                    <p class="q-detail">{{ $quote->client->contact_person }}</p>
                    <p class="q-detail">{{ $quote->client->email }}</p>
                    @if($quote->client->billing_address)
                        <p class="q-detail">{{ $quote->client->billing_address }}</p>
                    @endif
                    @if($quote->client->vat_number)
                        <p class="q-detail" style="margin-top:4px;">VAT No. {{ $quote->client->vat_number }}</p>
                    @endif
                </div>
                <div class="col-6">
                    <div class="q-label">Campaign</div>
                    <div class="q-company">{{ $quote->campaign?->name ?? '—' }}</div>
                    @if($quote->campaign)
                        <p class="q-detail">{{ $quote->campaign->sms_segments }} SMS segment{{ $quote->campaign->sms_segments > 1 ? 's' : '' }} per recipient</p>
                    @endif
                </div>
            </div>

            <table class="q-table">
                <thead>
                    <tr>
                        <th>Description</th>
                        <th class="text-right">Qty</th>
                        <th class="text-right">Unit Price</th>
                        <th class="text-right">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($quote->items as $item)
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
                        <td class="text-right">R {{ number_format($quote->subtotal, 2) }}</td>
                    </tr>
                    @if($quote->vat_enabled)
                    <tr>
                        <td colspan="3" class="text-right" style="color:#888;">VAT ({{ $quote->vat_rate }}%)</td>
                        <td class="text-right">R {{ number_format($quote->vat_amount, 2) }}</td>
                    </tr>
                    @endif
                    <tr class="total-row">
                        <td colspan="3" class="text-right">Total</td>
                        <td class="text-right" style="color:var(--color-brand);">R {{ number_format($quote->total, 2) }}</td>
                    </tr>
                </tfoot>
            </table>

            @if($quote->notes)
                <hr class="q-divider">
                <div class="q-label">Notes</div>
                <p class="q-detail">{{ $quote->notes }}</p>
            @endif

            <div class="q-footer">
                This quote is valid for 30 days from issue date — IntuDash SMS Campaign Manager
            </div>
        </div>
    </div>

    {{-- Sidebar --}}
    <div class="col-md-4 d-flex flex-column gap-3">
        {{-- Actions --}}
        @if(in_array($quote->status, ['draft','sent']))
        <div class="card">
            <div class="card-header">
                <span class="card-header-title"><i class="bi bi-lightning"></i> Actions</span>
            </div>
            <div class="card-body d-flex flex-column gap-2">
                <form action="{{ route('quotes.accept', $quote) }}" method="POST">
                    @csrf
                    <button type="submit" class="btn btn-primary btn-sm w-100">
                        <i class="bi bi-check-circle"></i> Accept Quote → Generate Invoice
                    </button>
                </form>
                <form action="{{ route('quotes.decline', $quote) }}" method="POST">
                    @csrf
                    <button type="submit" class="btn btn-danger-ghost btn-sm w-100" onclick="return confirm('Decline this quote?')">
                        <i class="bi bi-x-circle"></i> Decline Quote
                    </button>
                </form>
            </div>
        </div>
        @endif

        @if($quote->status === 'accepted' && $quote->invoice)
        <div class="card">
            <div class="card-header">
                <span class="card-header-title"><i class="bi bi-receipt"></i> Invoice</span>
            </div>
            <div class="card-body">
                <a href="{{ route('invoices.show', $quote->invoice) }}" class="btn btn-ghost btn-sm w-100">
                    <i class="bi bi-receipt"></i> {{ $quote->invoice->invoice_number }}
                    <span class="badge badge-success ms-auto">Accepted</span>
                </a>
                @if($quote->accepted_at)
                    <div style="font-size:12px;color:var(--text-tertiary);margin-top:8px;">Accepted {{ $quote->accepted_at->format('d M Y') }}</div>
                @endif
            </div>
        </div>
        @endif

        {{-- Update Status --}}
        <div class="card">
            <div class="card-header">
                <span class="card-header-title"><i class="bi bi-pencil-square"></i> Update Status</span>
            </div>
            <div class="card-body">
                <form action="{{ route('quotes.update-status', $quote) }}" method="POST">
                    @csrf @method('PATCH')
                    <select name="status" class="form-select mb-3" style="font-size:13.5px;">
                        @foreach(['draft','sent','accepted','declined','expired'] as $s)
                            <option value="{{ $s }}" {{ $quote->status === $s ? 'selected' : '' }}>{{ ucfirst($s) }}</option>
                        @endforeach
                    </select>
                    <button type="submit" class="btn btn-primary btn-sm w-100">Save Status</button>
                </form>
            </div>
        </div>

        {{-- Summary --}}
        <div class="card">
            <div class="card-header">
                <span class="card-header-title"><i class="bi bi-calculator"></i> Summary</span>
            </div>
            <div class="card-body">
                @foreach([
                    ['SMS Quantity', number_format($quote->sms_quantity)],
                    ['Rate / SMS', 'R '.number_format($quote->sms_rate, 4)],
                    ['Subtotal', 'R '.number_format($quote->subtotal, 2)],
                    ['VAT ('.$quote->vat_rate.'%)', $quote->vat_enabled ? 'R '.number_format($quote->vat_amount, 2) : 'Excluded'],
                ] as [$label, $val])
                <div class="d-flex justify-content-between mb-2" style="font-size:13px;">
                    <span style="color:var(--text-secondary);">{{ $label }}</span>
                    <span style="color:var(--text-primary);">{{ $val }}</span>
                </div>
                @endforeach
                <hr class="divider">
                <div class="d-flex justify-content-between" style="font-size:15px;font-weight:600;">
                    <span style="color:var(--text-secondary);">Total</span>
                    <span style="color:var(--color-brand);">R {{ number_format($quote->total, 2) }}</span>
                </div>
            </div>
        </div>

        {{-- Links --}}
        <div class="card">
            <div class="card-header">
                <span class="card-header-title"><i class="bi bi-link-45deg"></i> Links</span>
            </div>
            <div class="card-body d-flex flex-column gap-2">
                <a href="{{ route('quotes.pdf', $quote) }}" class="btn btn-ghost btn-sm">
                    <i class="bi bi-file-pdf"></i> Download PDF
                </a>
                @if($quote->campaign)
                    <a href="{{ route('campaigns.show', $quote->campaign) }}" class="btn btn-ghost btn-sm">
                        <i class="bi bi-megaphone"></i> View Campaign
                    </a>
                @endif
                <a href="{{ route('clients.show', $quote->client) }}" class="btn btn-ghost btn-sm">
                    <i class="bi bi-person"></i> View Client
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
