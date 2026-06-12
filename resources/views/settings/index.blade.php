@extends('layouts.app')
@section('title', 'Settings')
@section('page-title', 'System Settings')

@section('content')
<div class="row">
    <div class="col-md-8">
        <form action="{{ route('settings.update') }}" method="POST">
            @csrf

            <div class="card mb-3">
                <div class="card-header"><i class="bi bi-building me-2"></i>Company Information</div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Company Name</label>
                            <input type="text" name="company_name" class="form-control"
                                value="{{ $settings['company_name'] ?? config('app.name') }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Company Email</label>
                            <input type="email" name="company_email" class="form-control"
                                value="{{ $settings['company_email'] ?? '' }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Company Phone</label>
                            <input type="text" name="company_phone" class="form-control"
                                value="{{ $settings['company_phone'] ?? '' }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">VAT/Tax Number</label>
                            <input type="text" name="company_vat_number" class="form-control"
                                value="{{ $settings['company_vat_number'] ?? '' }}">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Company Address</label>
                            <textarea name="company_address" class="form-control" rows="2">{{ $settings['company_address'] ?? '' }}</textarea>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-header"><i class="bi bi-receipt me-2"></i>Invoice Settings</div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Invoice Prefix</label>
                            <input type="text" name="invoice_prefix" class="form-control" maxlength="10"
                                value="{{ $settings['invoice_prefix'] ?? 'INV' }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">VAT Rate (%)</label>
                            <div class="input-group">
                                <input type="number" name="vat_rate" class="form-control" min="0" max="100" step="0.01"
                                    value="{{ $settings['vat_rate'] ?? '15' }}">
                                <span class="input-group-text">%</span>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">VAT Registered</label>
                            <div class="form-check form-switch mt-2">
                                <input class="form-check-input" type="checkbox" name="vat_registered" id="vatRegistered" value="1"
                                    {{ ($settings['vat_registered'] ?? '0') == '1' ? 'checked' : '' }}>
                                <label class="form-check-label" for="vatRegistered">Business is VAT registered</label>
                            </div>
                            <small class="text-muted">When enabled, quotes &amp; invoices show ex-VAT + VAT breakdown.</small>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-header"><i class="bi bi-cash-coin me-2"></i>SMS Pricing Defaults</div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Default Internal Cost per SMS (R)</label>
                            <div class="input-group">
                                <span class="input-group-text">R</span>
                                <input type="number" name="default_internal_cost" step="0.0001" min="0" class="form-control"
                                    value="{{ $settings['default_internal_cost'] ?? '0.1200' }}">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Default Client Rate per SMS (R)</label>
                            <div class="input-group">
                                <span class="input-group-text">R</span>
                                <input type="number" name="default_client_rate" step="0.0001" min="0" class="form-control"
                                    value="{{ $settings['default_client_rate'] ?? '0.2500' }}">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Default Sender Name</label>
                            <input type="text" name="default_sender_name" class="form-control" maxlength="11"
                                value="{{ $settings['default_sender_name'] ?? '' }}" placeholder="Max 11 chars">
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-header"><i class="bi bi-phone me-2"></i>SMSPortal API Settings</div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">SMSPortal Client ID</label>
                            <input type="text" name="smsportal_client_id" class="form-control"
                                value="{{ $settings['smsportal_client_id'] ?? config('services.smsportal.client_id') }}"
                                placeholder="Your SMSPortal Client ID">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">SMSPortal API Secret</label>
                            <input type="password" name="smsportal_api_secret" class="form-control"
                                value="{{ $settings['smsportal_api_secret'] ?? '' }}"
                                placeholder="Enter to update">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Mode</label>
                            <div class="form-check form-switch mt-2">
                                <input class="form-check-input" type="checkbox" name="smsportal_test_mode" value="1"
                                    {{ ($settings['smsportal_test_mode'] ?? '1') == '1' ? 'checked' : '' }}>
                                <label class="form-check-label text-warning fw-semibold">Test Mode Active</label>
                            </div>
                            <small class="text-muted">Uncheck to go live.</small>
                        </div>
                    </div>
                </div>
            </div>

            <button type="submit" class="btn btn-primary">Save Settings</button>
        </form>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-header"><i class="bi bi-link-45deg me-2"></i>Webhook URL</div>
            <div class="card-body">
                <p class="small text-muted">Configure this URL in your SMSPortal account for delivery receipts:</p>
                <div class="input-group">
                    <input type="text" class="form-control form-control-sm" readonly
                        value="{{ route('webhooks.smsportal') }}" id="webhookUrl">
                    <button class="btn btn-outline-secondary btn-sm" onclick="navigator.clipboard.writeText(document.getElementById('webhookUrl').value)">
                        <i class="bi bi-clipboard"></i>
                    </button>
                </div>
                <hr>
                <p class="small text-muted mb-1"><i class="bi bi-info-circle me-1"></i>SMSPortal Delivery Status Codes:</p>
                <ul class="small mb-0">
                    <li><code>DELIVRD</code> — Delivered</li>
                    <li><code>UNDELIV</code> — Undelivered</li>
                    <li><code>EXPIRED</code> — Expired</li>
                    <li><code>BLIST</code> — Blacklisted</li>
                    <li><code>SUBMITD</code> — Submitted</li>
                    <li><code>STAGED</code> — Staged</li>
                    <li><code>CANCELLED</code> — Cancelled</li>
                    <li><code>NOROUTE</code> — No Route</li>
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection
