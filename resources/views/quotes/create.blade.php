@extends('layouts.app')
@section('title', 'New Quote')
@section('page-title', 'New Quote')

@section('content')
<div class="page-header">
    <div class="d-flex align-items-center gap-3">
        <a href="{{ route('quotes.index') }}" class="btn btn-ghost btn-sm btn-icon"><i class="bi bi-arrow-left"></i></a>
        <div>
            <h1>New Quote</h1>
            <p>Generate a quote from a campaign</p>
        </div>
    </div>
</div>

<div class="row justify-content-center">
    <div class="col-md-7">
        <div class="card">
            <div class="card-header">
                <span class="card-header-title"><i class="bi bi-file-earmark-text"></i> Quote Details</span>
            </div>
            <div class="card-body">
                <form action="{{ route('quotes.store') }}" method="POST">
                    @csrf

                    <div class="mb-3">
                        <label class="form-label" for="client_id">Client</label>
                        <select id="client_id" class="form-select" name="client_filter">
                            <option value="">— All Clients —</option>
                            @foreach($clients as $client)
                                <option value="{{ $client->id }}">{{ $client->company_name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="campaign_id">Campaign <span class="text-danger">*</span></label>
                        <select id="campaign_id" name="campaign_id" class="form-select @error('campaign_id') is-invalid @enderror" required>
                            <option value="">— Select a Campaign —</option>
                            @foreach($campaigns as $campaign)
                                @php
                                    $realCount = $campaign->validRecipients()->count();
                                    $hasReal = $realCount > 0;
                                    $recipientCount = $hasReal ? $realCount : $campaign->estimated_recipients;
                                    $label = $campaign->name . ' (' . number_format($recipientCount) . ' recipients'
                                        . ($hasReal ? '' : ($recipientCount > 0 ? ', estimated' : ', none yet'))
                                        . ')';
                                @endphp
                                <option value="{{ $campaign->id }}"
                                    data-client="{{ $campaign->client_id }}"
                                    data-type="{{ $campaign->campaign_type ?? 'sms' }}"
                                    data-has-real="{{ $hasReal ? '1' : '0' }}"
                                    data-est-sms="{{ $campaign->estimated_recipients }}"
                                    data-est-email="{{ $campaign->estimated_email_recipients }}"
                                    {{ old('campaign_id') == $campaign->id ? 'selected' : '' }}>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                        @error('campaign_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <div style="font-size:12px;color:var(--text-tertiary);margin-top:4px;">
                            Showing draft and recipients-uploaded campaigns.
                        </div>
                    </div>

                    <div id="manual-estimate-box" class="mb-4" style="display:none;background:var(--surface-bg);border:1px solid var(--surface-border);border-radius:8px;padding:16px;">
                        <p class="small fw-semibold mb-2" style="color:var(--text-secondary);">
                            <i class="bi bi-info-circle me-1"></i>This campaign has no recipients yet — enter a pre-sales estimate for this quote.
                        </p>
                        <div class="row g-2">
                            <div class="col-md-6" id="manual-sms-wrap" style="display:none;">
                                <label class="form-label small">Estimated SMS Recipients</label>
                                <input type="number" name="estimated_recipients" id="manual-sms" min="1" class="form-control form-control-sm" value="{{ old('estimated_recipients') }}">
                            </div>
                            <div class="col-md-6" id="manual-email-wrap" style="display:none;">
                                <label class="form-label small">Estimated Email Recipients</label>
                                <input type="number" name="estimated_email_recipients" id="manual-email" min="1" class="form-control form-control-sm" value="{{ old('estimated_email_recipients') }}">
                            </div>
                        </div>
                        <p class="small mb-0 mt-2" style="color:var(--text-tertiary);">
                            This only sets an estimate for quoting — it won't create fake recipients. Once you add real recipients to the campaign, they take over automatically.
                        </p>
                    </div>

                    <div class="d-flex justify-content-end gap-2">
                        <a href="{{ route('quotes.index') }}" class="btn btn-ghost btn-sm">Cancel</a>
                        <button type="submit" class="btn btn-primary btn-sm">
                            <i class="bi bi-file-earmark-plus"></i> Generate Quote
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const clientSelect = document.getElementById('client_id');
    const campaignSelect = document.getElementById('campaign_id');
    const allOptions = Array.from(campaignSelect.options);

    clientSelect.addEventListener('change', function () {
        const clientId = this.value;
        // Remove all campaign options except placeholder
        while (campaignSelect.options.length > 1) {
            campaignSelect.remove(1);
        }
        allOptions.forEach(function (opt) {
            if (opt.value === '') return; // skip placeholder
            if (!clientId || opt.dataset.client === clientId) {
                campaignSelect.appendChild(opt.cloneNode(true));
            }
        });
        campaignSelect.value = '';
        toggleManualEstimate();
    });

    function toggleManualEstimate() {
        const opt = campaignSelect.options[campaignSelect.selectedIndex];
        const box = document.getElementById('manual-estimate-box');
        const smsWrap = document.getElementById('manual-sms-wrap');
        const emailWrap = document.getElementById('manual-email-wrap');

        const hasReal = opt && opt.dataset.hasReal === '1';
        const show = opt && opt.value && !hasReal;

        box.style.display = show ? '' : 'none';
        if (!show) return;

        const type = opt.dataset.type || 'sms';
        smsWrap.style.display = (type === 'sms' || type === 'both') ? '' : 'none';
        emailWrap.style.display = (type === 'email' || type === 'both') ? '' : 'none';

        const smsField = document.getElementById('manual-sms');
        const emailField = document.getElementById('manual-email');
        if (!smsField.value && opt.dataset.estSms > 0) smsField.value = opt.dataset.estSms;
        if (!emailField.value && opt.dataset.estEmail > 0) emailField.value = opt.dataset.estEmail;
    }

    campaignSelect.addEventListener('change', toggleManualEstimate);
    toggleManualEstimate();
});
</script>
@endpush
@endsection
