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
                                    value="{{ $settings['default_internal_cost'] ?? config('pricing.sms.internal_cost') }}">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Default Client Rate per SMS (R)</label>
                            <div class="input-group">
                                <span class="input-group-text">R</span>
                                <input type="number" name="default_client_rate" step="0.0001" min="0" class="form-control"
                                    value="{{ $settings['default_client_rate'] ?? config('pricing.sms.client_rate') }}">
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
                <div class="card-header"><i class="bi bi-envelope-at me-2"></i>Email Gateway Settings</div>
                <div class="card-body">
                    <div class="alert alert-light border d-flex justify-content-between align-items-center py-2 px-3 mb-3">
                        <span class="small mb-0">
                            <i class="bi bi-globe me-1"></i>Want to send from a different or more "friendly" domain?
                            Verify it under Sending Domains — agency-wide, or per-client for marketing sends.
                        </span>
                        <a href="{{ route('settings.sending-domains.index') }}" class="btn btn-outline-primary btn-sm text-nowrap ms-2">
                            Manage Sending Domains
                        </a>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Access Key</label>
                            <input type="password" name="aws_key" class="form-control" autocomplete="off"
                                value="" placeholder="{{ !empty($settings['aws_key_set']) ? '•••••••• configured — leave blank to keep' : 'Access key' }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Secret Key</label>
                            <input type="password" name="aws_secret" class="form-control" autocomplete="off"
                                value="" placeholder="{{ !empty($settings['aws_secret_set']) ? '•••••••• configured — leave blank to keep' : 'Enter secret key' }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Region</label>
                            <select name="aws_region" class="form-select">
                                @foreach(['us-east-1','us-west-2','eu-west-1','eu-central-1','ap-southeast-1','ap-southeast-2','sa-east-1'] as $r)
                                    <option value="{{ $r }}" {{ ($settings['aws_region'] ?? 'us-east-1') === $r ? 'selected' : '' }}>{{ $r }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Default From Email</label>
                            <input type="email" name="ses_from_email" class="form-control"
                                value="{{ $settings['ses_from_email'] ?? '' }}" placeholder="noreply@yourdomain.com">
                            <small class="text-muted">Must be a verified sender</small>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Default From Name</label>
                            <input type="text" name="ses_from_name" class="form-control"
                                value="{{ $settings['ses_from_name'] ?? '' }}" placeholder="Company Name">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Internal Cost per Email (R)</label>
                            <div class="input-group">
                                <span class="input-group-text">R</span>
                                <input type="number" name="internal_cost_per_email" step="0.000001" min="0" class="form-control"
                                    value="{{ $settings['internal_cost_per_email'] ?? config('pricing.email.internal_cost') }}">
                            </div>
                            <small class="text-muted">Your true cost to send one email (ZAR). Used for profit calculations.</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Default Client Rate per Email (R)</label>
                            <div class="input-group">
                                <span class="input-group-text">R</span>
                                <input type="number" name="default_client_rate_per_email" step="0.000001" min="0" class="form-control"
                                    value="{{ $settings['default_client_rate_per_email'] ?? config('pricing.email.client_rate') }}">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-header"><i class="bi bi-phone me-2"></i>SMS Gateway Settings</div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Client ID</label>
                            <input type="text" name="smsportal_client_id" class="form-control"
                                value="{{ $settings['smsportal_client_id'] ?? '' }}"
                                placeholder="Your gateway client ID">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">API Secret</label>
                            <input type="password" name="smsportal_api_secret" class="form-control" autocomplete="off"
                                value=""
                                placeholder="{{ !empty($settings['smsportal_api_secret_set']) ? '•••••••• configured — leave blank to keep' : 'Enter to update' }}">
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

            <div class="card mb-3">
                <div class="card-header"><i class="bi bi-shield-lock me-2"></i>Webhook Security</div>
                <div class="card-body">
                    <label class="form-label">Webhook Secret</label>
                    <input type="password" name="webhook_secret" class="form-control" autocomplete="off"
                        value="" placeholder="{{ !empty($settings['webhook_secret_set']) ? '•••••••• configured — leave blank to keep' : 'Set a shared secret' }}">
                    <small class="text-muted">Incoming webhooks must supply this via an <code>X-Webhook-Secret</code> header or <code>?secret=</code> query param. Leave unset to accept unauthenticated callbacks (not recommended).</small>
                </div>
            </div>

            <button type="submit" class="btn btn-primary">Save Settings</button>
        </form>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-header"><i class="bi bi-link-45deg me-2"></i>Webhook URLs</div>
            <div class="card-body">
                <p class="small text-muted mb-1">SMS delivery receipts:</p>
                <div class="input-group mb-2">
                    <input type="text" class="form-control form-control-sm" readonly
                        value="{{ route('webhooks.smsportal') }}" id="webhookUrl">
                    <button type="button" class="btn btn-outline-secondary btn-sm" onclick="navigator.clipboard.writeText(document.getElementById('webhookUrl').value)">
                        <i class="bi bi-clipboard"></i>
                    </button>
                </div>
                <p class="small text-muted mb-1">SMS replies (STOP / opt-outs):</p>
                <div class="input-group mb-2">
                    <input type="text" class="form-control form-control-sm" readonly
                        value="{{ route('webhooks.sms-reply') }}" id="replyWebhookUrl">
                    <button type="button" class="btn btn-outline-secondary btn-sm" onclick="navigator.clipboard.writeText(document.getElementById('replyWebhookUrl').value)">
                        <i class="bi bi-clipboard"></i>
                    </button>
                </div>
                <p class="small text-muted mb-1">Email delivery / bounce / complaint:</p>
                <div class="input-group">
                    <input type="text" class="form-control form-control-sm" readonly
                        value="{{ route('webhooks.ses') }}" id="sesWebhookUrl">
                    <button type="button" class="btn btn-outline-secondary btn-sm" onclick="navigator.clipboard.writeText(document.getElementById('sesWebhookUrl').value)">
                        <i class="bi bi-clipboard"></i>
                    </button>
                </div>
                <small class="text-muted d-block mt-1">Append <code>?secret=YOUR_SECRET</code> if a webhook secret is set.</small>
                <hr>
                <p class="small text-muted mb-0"><i class="bi bi-info-circle me-1"></i>Register these URLs in your SMS and email gateway dashboards so delivery status, replies, and bounces flow back automatically.</p>
            </div>
        </div>
    </div>
</div>
@endsection
