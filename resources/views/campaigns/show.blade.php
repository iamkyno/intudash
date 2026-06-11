@extends('layouts.app')
@section('title', $campaign->name)
@section('page-title', $campaign->name)

@section('content')
<div class="row g-3 mb-3">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>Campaign Details</span>
                <div class="d-flex gap-2 align-items-center">
                    <span class="badge bg-{{ $campaign->status_color }} fs-6">{{ $campaign->status_label }}</span>
                    @if($campaign->status === 'draft')
                        <a href="{{ route('campaigns.edit', $campaign) }}" class="btn btn-sm btn-outline-secondary">
                            <i class="bi bi-pencil"></i> Edit
                        </a>
                    @endif
                    @if(!in_array($campaign->status, ['sending','completed','cancelled']))
                        <form action="{{ route('campaigns.cancel', $campaign) }}" method="POST" class="d-inline">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Cancel this campaign?')">
                                Cancel
                            </button>
                        </form>
                    @endif
                </div>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <p class="text-muted small mb-1">Client</p>
                        <p><a href="{{ route('clients.show', $campaign->client) }}" class="text-decoration-none fw-semibold">{{ $campaign->client->company_name }}</a></p>
                    </div>
                    <div class="col-md-6">
                        <p class="text-muted small mb-1">Provider</p>
                        <p><span class="badge bg-light text-dark text-uppercase">{{ $campaign->provider }}</span>
                        @if(config('services.smsportal.test_mode'))
                            <span class="badge bg-warning text-dark ms-1">Test Mode</span>
                        @endif
                        </p>
                    </div>
                    <div class="col-12">
                        <p class="text-muted small mb-1">Message</p>
                        <div class="bg-light rounded p-3">{{ $campaign->message }}</div>
                    </div>
                    @if($campaign->notes)
                    <div class="col-12">
                        <p class="text-muted small mb-1">Notes</p>
                        <p>{{ $campaign->notes }}</p>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card mb-3">
            <div class="card-header">Financial Summary</div>
            <div class="card-body">
                <dl class="row mb-0 small">
                    <dt class="col-7 text-muted">Segments/SMS</dt>
                    <dd class="col-5">{{ $campaign->sms_segments }}</dd>
                    <dt class="col-7 text-muted">Recipients (est.)</dt>
                    <dd class="col-5">{{ number_format($campaign->estimated_recipients) }}</dd>
                    <dt class="col-7 text-muted">Recipients (actual)</dt>
                    <dd class="col-5">{{ number_format($campaign->actual_recipients) }}</dd>
                    <dt class="col-7 text-muted">Internal Cost/SMS</dt>
                    <dd class="col-5">R {{ $campaign->internal_cost_per_sms }}</dd>
                    <dt class="col-7 text-muted">Client Rate/SMS</dt>
                    <dd class="col-5">R {{ $campaign->client_rate_per_sms }}</dd>
                    <dt class="col-7 text-muted">Est. Cost</dt>
                    <dd class="col-5">R {{ number_format($campaign->estimated_cost, 2) }}</dd>
                    <dt class="col-7 text-muted">Est. Charge</dt>
                    <dd class="col-5">R {{ number_format($campaign->estimated_charge, 2) }}</dd>
                    <dt class="col-7 text-muted fw-semibold">Est. Profit</dt>
                    <dd class="col-5 fw-semibold text-success">R {{ number_format($campaign->estimated_profit, 2) }}</dd>
                    @if($campaign->actual_charge > 0)
                    <dt class="col-7 text-muted">Actual Charge</dt>
                    <dd class="col-5">R {{ number_format($campaign->actual_charge, 2) }}</dd>
                    <dt class="col-7 text-muted fw-semibold">Actual Profit</dt>
                    <dd class="col-5 fw-semibold text-success">R {{ number_format($campaign->actual_profit, 2) }}</dd>
                    @endif
                </dl>
            </div>
        </div>

        <!-- Workflow Actions -->
        <div class="card">
            <div class="card-header">Actions</div>
            <div class="card-body d-grid gap-2">
                @if(in_array($campaign->status, ['draft','recipients_uploaded']))
                    <a href="{{ route('campaigns.recipients', $campaign) }}" class="btn btn-outline-primary btn-sm">
                        <i class="bi bi-people me-1"></i>Manage Recipients ({{ $campaign->recipients()->where('status','valid')->count() }} valid)
                    </a>
                @endif

                @if(in_array($campaign->status, ['recipients_uploaded']) && $campaign->validRecipients()->count() > 0)
                    <form action="{{ route('invoices.generate', $campaign) }}" method="POST">
                        @csrf
                        <button type="submit" class="btn btn-primary btn-sm w-100">
                            <i class="bi bi-receipt me-1"></i>Generate Invoice
                        </button>
                    </form>
                @endif

                @if($campaign->status === 'invoice_generated')
                    @foreach($campaign->invoices as $invoice)
                        <a href="{{ route('invoices.show', $invoice) }}" class="btn btn-outline-warning btn-sm">
                            <i class="bi bi-receipt me-1"></i>View Invoice {{ $invoice->invoice_number }}
                        </a>
                    @endforeach
                @endif

                @if($campaign->canBeScheduled())
                    <button class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#scheduleModal">
                        <i class="bi bi-calendar-check me-1"></i>Schedule / Send Campaign
                    </button>
                @endif

                @if(in_array($campaign->status, ['completed','partially_completed','sending']))
                    <a href="{{ route('campaigns.report', $campaign) }}" class="btn btn-outline-info btn-sm">
                        <i class="bi bi-bar-chart-fill me-1"></i>View Report
                    </a>
                    <a href="{{ route('campaigns.export-report', $campaign) }}" class="btn btn-outline-secondary btn-sm">
                        <i class="bi bi-download me-1"></i>Export CSV Report
                    </a>
                @endif
            </div>
        </div>
    </div>
</div>

@if($deliveryStats['total'] > 0)
<div class="card mb-3">
    <div class="card-header"><i class="bi bi-bar-chart me-2 text-primary"></i>Delivery Stats</div>
    <div class="card-body">
        <div class="row g-3">
            @foreach(['submitted'=>'primary','delivered'=>'success','undelivered'=>'danger','expired'=>'warning','blacklisted'=>'danger','no_route'=>'secondary','failed'=>'danger','pending'=>'secondary'] as $key => $color)
            <div class="col-6 col-md-3">
                <div class="text-center p-2 rounded border">
                    <div class="fw-bold fs-5 text-{{ $color }}">{{ $deliveryStats[$key] }}</div>
                    <div class="text-muted small">{{ ucfirst(str_replace('_', ' ', $key)) }}</div>
                </div>
            </div>
            @endforeach
        </div>
        <div class="row mt-3">
            <div class="col-md-6">
                <div class="d-flex justify-content-between small mb-1">
                    <span>Delivery Rate</span><span class="text-success fw-semibold">{{ $deliveryStats['delivery_pct'] }}%</span>
                </div>
                <div class="progress" style="height:8px">
                    <div class="progress-bar bg-success" style="width:{{ $deliveryStats['delivery_pct'] }}%"></div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="d-flex justify-content-between small mb-1">
                    <span>Failure Rate</span><span class="text-danger fw-semibold">{{ $deliveryStats['failure_pct'] }}%</span>
                </div>
                <div class="progress" style="height:8px">
                    <div class="progress-bar bg-danger" style="width:{{ $deliveryStats['failure_pct'] }}%"></div>
                </div>
            </div>
        </div>
    </div>
</div>
@endif

<!-- Schedule Modal -->
<div class="modal fade" id="scheduleModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Schedule Campaign</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('campaigns.schedule', $campaign) }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Send Option</label>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="send_type" id="sendImmediate" value="immediate" checked>
                            <label class="form-check-label" for="sendImmediate">
                                <strong>Send Immediately</strong> — starts sending now
                            </label>
                        </div>
                        <div class="form-check mt-2">
                            <input class="form-check-input" type="radio" name="send_type" id="sendScheduled" value="scheduled">
                            <label class="form-check-label" for="sendScheduled">
                                <strong>Schedule for Later</strong>
                            </label>
                        </div>
                    </div>
                    <div id="scheduleFields" class="d-none">
                        <label class="form-label">Date & Time (SAST)</label>
                        <input type="datetime-local" name="scheduled_at" class="form-control"
                            min="{{ now()->addMinutes(5)->format('Y-m-d\TH:i') }}">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Confirm</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.querySelectorAll('input[name="send_type"]').forEach(r => {
    r.addEventListener('change', () => {
        document.getElementById('scheduleFields').classList.toggle('d-none', r.value !== 'scheduled' || !r.checked);
    });
});
document.getElementById('sendScheduled')?.addEventListener('change', function() {
    document.getElementById('scheduleFields').classList.toggle('d-none', !this.checked);
});
</script>
@endpush
@endsection
