@extends('layouts.app')
@section('title', 'New Campaign')
@section('page-title', 'Create Campaign')

@section('content')
<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">Campaign Details</div>
            <div class="card-body">
                <form action="{{ route('campaigns.store') }}" method="POST">
                    @csrf
                    @include('campaigns._form')
                    <div class="d-flex gap-2 mt-3">
                        <button type="submit" class="btn btn-primary">Create Campaign</button>
                        <a href="{{ route('campaigns.index') }}" class="btn btn-outline-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card" id="sms-counter-card">
            <div class="card-header"><i class="bi bi-info-circle me-1"></i>SMS Counter</div>
            <div class="card-body">
                <div id="sms-counter" class="sms-counter">
                    <div class="d-flex justify-content-between">
                        <span>Characters: <strong id="char-count">0</strong></span>
                        <span>Segments: <strong id="segment-count">1</strong></span>
                    </div>
                    <div class="progress mt-2" style="height: 6px;">
                        <div class="progress-bar" id="char-progress" role="progressbar" style="width: 0%"></div>
                    </div>
                    <small class="text-muted mt-1 d-block" id="char-remaining">160 characters remaining</small>
                    <div id="extended-warning" class="alert alert-warning py-1 px-2 mt-2 small d-none">
                        <i class="bi bi-exclamation-triangle me-1"></i>Message contains extended GSM characters (^, {, }, \, [, ~, ], |, €) that count as 2 characters.
                    </div>
                    <div id="multi-segment-warning" class="alert alert-info py-1 px-2 mt-2 small d-none">
                        <i class="bi bi-info-circle me-1"></i>Message exceeds 1 SMS segment. Multi-part SMS uses 153 chars/segment.
                    </div>
                </div>
            </div>
        </div>

        <div class="card mt-3">
            <div class="card-header"><i class="bi bi-calculator me-1"></i>Cost Estimator</div>
            <div class="card-body" id="cost-estimator">
                {{-- Quick recipient count override — updates the form fields too --}}
                <div id="est-quick-sms" class="mb-2">
                    <label class="form-label small mb-1" style="color:var(--text-secondary);">SMS Recipients</label>
                    <input type="number" id="est-quick-sms-input" min="0" placeholder="Enter count…"
                        class="form-control form-control-sm"
                        oninput="document.getElementById('estimated_recipients').value=this.value;updateEstimator()">
                </div>
                <div id="est-quick-email" class="mb-2" style="display:none;">
                    <label class="form-label small mb-1" style="color:var(--text-secondary);">Email Recipients</label>
                    <input type="number" id="est-quick-email-input" min="0" placeholder="Enter count…"
                        class="form-control form-control-sm"
                        oninput="document.getElementById('estimated_email_recipients').value=this.value;updateEstimator()">
                </div>
                <hr class="my-2">
                {{-- SMS rows --}}
                <div id="est-sms-section">
                    <div class="d-flex justify-content-between small mb-1">
                        <span class="text-muted">SMS Recipients:</span>
                        <span id="est-recipients">0</span>
                    </div>
                    <div class="d-flex justify-content-between small mb-1">
                        <span class="text-muted">SMS Segments:</span>
                        <span id="est-segments">1</span>
                    </div>
                    <div class="d-flex justify-content-between small mb-1">
                        <span class="text-muted">Total SMS:</span>
                        <span id="est-total-sms">0</span>
                    </div>
                </div>
                {{-- Email rows --}}
                <div id="est-email-section" style="display:none;">
                    <div class="d-flex justify-content-between small mb-1">
                        <span class="text-muted">Email Recipients:</span>
                        <span id="est-email-recipients">0</span>
                    </div>
                    <div class="d-flex justify-content-between small mb-1">
                        <span class="text-muted">Total Emails:</span>
                        <span id="est-total-emails">0</span>
                    </div>
                </div>
                <div class="d-flex justify-content-between small mb-1" id="est-runs-row" style="display:none!important;">
                    <span class="text-muted">Runs:</span>
                    <span id="est-runs">1</span>
                </div>
                <hr class="my-2">
                {{-- SMS cost rows --}}
                <div id="est-sms-cost-section">
                    <div class="d-flex justify-content-between small mb-1">
                        <span class="text-muted">SMS Internal Cost:</span>
                        <span id="est-cost">R 0.00</span>
                    </div>
                    <div class="d-flex justify-content-between small mb-1">
                        <span class="text-muted">SMS Client Charge:</span>
                        <span id="est-charge">R 0.00</span>
                    </div>
                </div>
                {{-- Email cost rows --}}
                <div id="est-email-cost-section" style="display:none;">
                    <div class="d-flex justify-content-between small mb-1">
                        <span class="text-muted">Email Internal Cost:</span>
                        <span id="est-email-cost">R 0.00</span>
                    </div>
                    <div class="d-flex justify-content-between small mb-1">
                        <span class="text-muted">Email Client Charge:</span>
                        <span id="est-email-charge">R 0.00</span>
                    </div>
                </div>
                <div class="d-flex justify-content-between small fw-semibold mt-1">
                    <span>Total Profit:</span>
                    <span id="est-profit" class="text-success">R 0.00</span>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
const EXTENDED = ['^','{','}','\\','[','~',']','|','€'];

function formatMoney(val) {
    if (val === 0) return '0.00';
    // Show enough decimals so small per-unit rates (email) are visible
    if (val < 0.01) return val.toFixed(6);
    if (val < 1)    return val.toFixed(4);
    return val.toFixed(2);
}

function countSms(msg) {
    let len = 0, hasExtended = false;
    for (const ch of msg) {
        if (EXTENDED.includes(ch)) { len += 2; hasExtended = true; }
        else len++;
    }
    const segments = len <= 160 ? 1 : Math.ceil(len / 153);
    const remaining = len <= 160 ? (160 - len) : (153 - (len % 153));
    return { len, segments, hasExtended, remaining };
}

function getRepeatRuns() {
    const toggle = document.getElementById('repeatToggle');
    if (!toggle || !toggle.checked) return 1;
    return Math.max(1, parseInt(document.getElementById('repeatCount')?.value || 1));
}

function getCampaignType() {
    return document.querySelector('input[name="campaign_type"]:checked')?.value || 'sms';
}

function updateEstimator() {
    const type  = getCampaignType();
    const runs  = getRepeatRuns();
    const showSms   = type === 'sms'   || type === 'both';
    const showEmail = type === 'email' || type === 'both';

    // Toggle section visibility
    document.getElementById('est-sms-section').style.display       = showSms   ? '' : 'none';
    document.getElementById('est-email-section').style.display     = showEmail ? '' : 'none';
    document.getElementById('est-sms-cost-section').style.display  = showSms   ? '' : 'none';
    document.getElementById('est-email-cost-section').style.display= showEmail ? '' : 'none';
    document.getElementById('sms-counter-card').style.display      = showSms   ? '' : 'none';
    document.getElementById('est-quick-sms').style.display         = showSms   ? '' : 'none';
    document.getElementById('est-quick-email').style.display       = showEmail ? '' : 'none';

    // Runs row
    const runsRow = document.getElementById('est-runs-row');
    if (runs > 1) {
        runsRow.style.removeProperty('display');
        document.getElementById('est-runs').textContent = runs + ' runs';
    } else {
        runsRow.style.setProperty('display', 'none', 'important');
    }

    let totalCost = 0, totalCharge = 0;

    // SMS calculations
    if (showSms) {
        const msg         = document.getElementById('message')?.value || '';
        const recipients  = parseInt(document.getElementById('estimated_recipients')?.value || 0);
        const internalCost= parseFloat(document.getElementById('internal_cost_per_sms')?.value || 0);
        const clientRate  = parseFloat(document.getElementById('client_rate_per_sms')?.value || 0);
        const { len, segments, hasExtended, remaining } = countSms(msg);

        document.getElementById('char-count').textContent = len;
        document.getElementById('segment-count').textContent = segments;
        document.getElementById('char-remaining').textContent = remaining + (segments > 1 ? ' chars in current segment' : ' characters remaining');
        const pct = Math.min((len / 160) * 100, 100);
        const bar = document.getElementById('char-progress');
        bar.style.width = pct + '%';
        bar.className = 'progress-bar ' + (len > 160 ? 'bg-danger' : len > 140 ? 'bg-warning' : 'bg-success');
        document.getElementById('extended-warning').classList.toggle('d-none', !hasExtended);
        document.getElementById('multi-segment-warning').classList.toggle('d-none', segments <= 1);

        const totalSms = recipients * segments * runs;
        const smsCost  = totalSms * internalCost;
        const smsCharge= totalSms * clientRate;
        totalCost  += smsCost;
        totalCharge+= smsCharge;

        document.getElementById('est-recipients').textContent  = recipients.toLocaleString();
        document.getElementById('est-segments').textContent    = segments;
        document.getElementById('est-total-sms').textContent   = totalSms.toLocaleString();
        document.getElementById('est-cost').textContent        = 'R ' + formatMoney(smsCost);
        document.getElementById('est-charge').textContent      = 'R ' + formatMoney(smsCharge);
    }

    // Email calculations
    if (showEmail) {
        const emailRecipients  = parseInt(document.getElementById('estimated_email_recipients')?.value || 0);
        const emailInternal    = parseFloat(document.getElementById('internal_cost_per_email')?.value || 0);
        const emailClientRate  = parseFloat(document.getElementById('client_rate_per_email')?.value || 0);
        const totalEmails      = emailRecipients * runs;
        const emailCost        = totalEmails * emailInternal;
        const emailCharge      = totalEmails * emailClientRate;
        totalCost  += emailCost;
        totalCharge+= emailCharge;

        document.getElementById('est-email-recipients').textContent = emailRecipients.toLocaleString();
        document.getElementById('est-total-emails').textContent     = totalEmails.toLocaleString();
        document.getElementById('est-email-cost').textContent       = 'R ' + formatMoney(emailCost);
        document.getElementById('est-email-charge').textContent     = 'R ' + formatMoney(emailCharge);
    }

    const profit = totalCharge - totalCost;
    const profitEl = document.getElementById('est-profit');
    profitEl.textContent = 'R ' + formatMoney(profit);
    profitEl.className = profit >= 0 ? 'text-success' : 'text-danger';
}

const ESTIMATOR_FIELDS = new Set([
    'message','estimated_recipients','internal_cost_per_sms','client_rate_per_sms',
    'estimated_email_recipients','internal_cost_per_email','client_rate_per_email',
]);
document.addEventListener('input', e => {
    const id = e.target.id || e.target.name;
    if (id === 'estimated_recipients') {
        const q = document.getElementById('est-quick-sms-input');
        if (q && document.activeElement !== q) q.value = e.target.value;
    }
    if (id === 'estimated_email_recipients') {
        const q = document.getElementById('est-quick-email-input');
        if (q && document.activeElement !== q) q.value = e.target.value;
    }
    if (ESTIMATOR_FIELDS.has(id)) updateEstimator();
});
document.querySelectorAll('input[name="campaign_type"]').forEach(r => r.addEventListener('change', updateEstimator));
document.getElementById('repeatToggle')?.addEventListener('change', updateEstimator);
document.getElementById('repeatCount')?.addEventListener('input', updateEstimator);
updateEstimator();
</script>
@endpush
