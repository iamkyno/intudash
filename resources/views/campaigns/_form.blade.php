@php $ctype = old('campaign_type', $campaign->campaign_type ?? 'sms'); @endphp

<div class="row g-3">
    <div class="col-md-6">
        <label class="form-label">Campaign Name <span class="text-danger">*</span></label>
        <input type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror"
            value="{{ old('name', $campaign->name ?? '') }}" required>
        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-6">
        <label class="form-label">Client <span class="text-danger">*</span></label>
        <select name="client_id" class="form-select @error('client_id') is-invalid @enderror" required>
            <option value="">Select Client</option>
            @foreach($clients as $client)
                <option value="{{ $client->id }}"
                    data-rate="{{ $client->default_sms_rate }}"
                    {{ old('client_id', $campaign->client_id ?? request('client_id')) == $client->id ? 'selected' : '' }}>
                    {{ $client->company_name }}
                </option>
            @endforeach
        </select>
        @error('client_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    {{-- Recipient source — always a real, selected list. No typed guesses; see Quotes for pre-sales estimates. --}}
    <div class="col-12">
        <label class="form-label">Recipients</label>
        <select id="recipient-source" name="recipient_source" class="form-select">
            <option value="">No recipients selected yet — add them after creating this campaign</option>
        </select>
        <small class="text-muted" id="recipient-source-hint">Pick a saved group or the client's whole active list — recipients are imported the moment you save. Costs are always calculated from actual recipients, never a typed guess.</small>
    </div>

    {{-- Campaign type --}}
    <div class="col-12">
        <label class="form-label">Campaign Type <span class="text-danger">*</span></label>
        <div class="d-flex gap-3">
            @foreach(['sms' => '<i class="bi bi-chat-dots"></i> SMS', 'email' => '<i class="bi bi-envelope"></i> Email', 'both' => '<i class="bi bi-layers"></i> Both'] as $val => $label)
            <div class="form-check">
                <input class="form-check-input" type="radio" name="campaign_type" id="type_{{ $val }}"
                    value="{{ $val }}" {{ $ctype === $val ? 'checked' : '' }} onchange="updateTypeVisibility()">
                <label class="form-check-label" for="type_{{ $val }}">{!! $label !!}</label>
            </div>
            @endforeach
        </div>
    </div>

    {{-- SMS fields --}}
    <div id="sms-fields" class="col-12">
        <div class="row g-3">
            <div class="col-12">
                <label class="form-label">SMS Message <span class="text-danger sms-required">*</span></label>
                <textarea name="message" id="message" class="form-control @error('message') is-invalid @enderror"
                    rows="5" placeholder="Type your SMS message here...">{{ old('message', $campaign->message ?? '') }}</textarea>
                @error('message')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-4">
                <label class="form-label">Sender Name <small class="text-muted">(max 11 chars)</small></label>
                <input type="text" name="sender_name" class="form-control" maxlength="11"
                    value="{{ old('sender_name', $campaign->sender_name ?? '') }}">
            </div>
            <div class="col-md-4">
                <label class="form-label">Internal Cost per SMS (R)</label>
                <div class="input-group">
                    <span class="input-group-text">R</span>
                    <input type="number" name="internal_cost_per_sms" id="internal_cost_per_sms" step="0.0001" min="0"
                        class="form-control" value="{{ old('internal_cost_per_sms', $campaign->internal_cost_per_sms ?? '0.1200') }}">
                </div>
            </div>
            <div class="col-md-4">
                <label class="form-label">Client Rate per SMS (R)</label>
                <div class="input-group">
                    <span class="input-group-text">R</span>
                    <input type="number" name="client_rate_per_sms" id="client_rate_per_sms" step="0.0001" min="0"
                        class="form-control" value="{{ old('client_rate_per_sms', $campaign->client_rate_per_sms ?? '0.2500') }}">
                </div>
            </div>
            <div class="col-md-4">
                <label class="form-label">SMS Recipients <small class="text-muted">(from selection above)</small></label>
                <input type="number" name="estimated_recipients" id="estimated_recipients" min="0" readonly
                    class="form-control" value="{{ old('estimated_recipients', $campaign->estimated_recipients ?? '0') }}"
                    style="background:var(--surface-bg);">
            </div>
        </div>
    </div>

    {{-- Email fields --}}
    <div id="email-fields" class="col-12" style="display:none;">
        <div class="row g-3">
            <div class="col-12">
                <div style="background:var(--surface-bg);border:1px solid var(--surface-border);border-radius:8px;padding:16px;">
                    <p class="small fw-semibold mb-3" style="color:var(--text-secondary);"><i class="bi bi-envelope me-1"></i>Email Settings (Amazon SES)</p>
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">Email Subject <span class="text-danger email-required">*</span></label>
                            <input type="text" name="email_subject" class="form-control"
                                value="{{ old('email_subject', $campaign->email_subject ?? '') }}"
                                placeholder="e.g. Exclusive offer for you">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">From Name</label>
                            <input type="text" name="email_from_name" class="form-control"
                                value="{{ old('email_from_name', $campaign->email_from_name ?? '') }}"
                                placeholder="e.g. Acme Marketing">
                            <small class="text-muted">Leave blank to use settings default</small>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">From Address (alias)</label>
                            <div class="input-group input-group-sm" id="from-picker-group">
                                <input type="text" id="email_from_local" class="form-control" placeholder="promos" autocomplete="off">
                                <span class="input-group-text">@</span>
                                <select id="email_from_domain" class="form-select"></select>
                            </div>
                            <input type="email" name="email_from_address" id="email_from_address" class="form-control"
                                value="{{ old('email_from_address', $campaign->email_from_address ?? '') }}"
                                placeholder="e.g. promos@yourdomain.com" style="display:none;">
                            <small class="text-muted" id="email_from_hint">Pick a verified domain — keeps marketing sends looking friendly &amp; trusted.</small>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Reply-To</label>
                            <input type="email" name="email_reply_to" class="form-control"
                                value="{{ old('email_reply_to', $campaign->email_reply_to ?? '') }}"
                                placeholder="e.g. support@yourdomain.com">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Email Body (HTML) <span class="text-danger email-required">*</span></label>
                            <textarea name="email_body" id="email_body" class="form-control" rows="8"
                                placeholder="HTML email body. Use @{{name}} to personalise.">{{ old('email_body', $campaign->email_body ?? '') }}</textarea>
                            <small class="text-muted">Supports HTML. Use <code>@{{name}}</code> and <code>@{{email}}</code> for personalisation.</small>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <label class="form-label">Internal Cost per Email (R)</label>
                <div class="input-group">
                    <span class="input-group-text">R</span>
                    <input type="number" name="internal_cost_per_email" id="internal_cost_per_email" step="0.000001" min="0"
                        class="form-control" value="{{ old('internal_cost_per_email', $campaign->internal_cost_per_email ?? \App\Services\EmailService::getCostPerEmail()) }}">
                </div>
            </div>
            <div class="col-md-4">
                <label class="form-label">Client Rate per Email (R)</label>
                <div class="input-group">
                    <span class="input-group-text">R</span>
                    <input type="number" name="client_rate_per_email" id="client_rate_per_email" step="0.000001" min="0"
                        class="form-control" value="{{ old('client_rate_per_email', $campaign->client_rate_per_email ?? \App\Services\EmailService::getClientRatePerEmail()) }}">
                </div>
            </div>
            <div class="col-md-4">
                <label class="form-label">Email Recipients <small class="text-muted">(from selection above)</small></label>
                <input type="number" name="estimated_email_recipients" id="estimated_email_recipients" min="0" readonly
                    class="form-control" value="{{ old('estimated_email_recipients', $campaign->estimated_email_recipients ?? '0') }}"
                    style="background:var(--surface-bg);">
            </div>
        </div>
    </div>

    <div class="col-12">
        <label class="form-label">Campaign Notes</label>
        <textarea name="notes" class="form-control" rows="2">{{ old('notes', $campaign->notes ?? '') }}</textarea>
    </div>
    @if(!isset($campaign) || !$campaign->exists)
    <div class="col-12">
        <div class="card" style="background:var(--surface-bg);border:1px solid var(--surface-border);">
            <div class="card-body py-3">
                <div class="d-flex align-items-center gap-3 mb-2">
                    <div class="form-check form-switch mb-0">
                        <input class="form-check-input" type="checkbox" id="repeatToggle" name="repeat_enabled" value="1"
                            {{ old('repeat_enabled') ? 'checked' : '' }} onchange="toggleRepeat(this)">
                        <label class="form-check-label fw-semibold" for="repeatToggle">Repeat this campaign</label>
                    </div>
                </div>
                <div id="repeatSection" style="{{ old('repeat_enabled') ? '' : 'display:none;' }}">
                    <p class="small mb-2" style="color:var(--text-secondary);">Creates multiple copies of this campaign. You can edit the schedule and message for each run after creation.</p>
                    <div class="row g-2 align-items-center">
                        <div class="col-auto">
                            <label class="form-label mb-0">Number of runs</label>
                        </div>
                        <div class="col-auto">
                            <input type="number" name="repeat_count" id="repeatCount" class="form-control form-control-sm"
                                min="2" max="52" value="{{ old('repeat_count', 2) }}" style="width:80px;">
                        </div>
                        <div class="col-auto">
                            <span class="small" style="color:var(--text-secondary);">campaigns will be created (Run 1 of N … Run N of N)</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>

<script>
function toggleRepeat(checkbox) {
    document.getElementById('repeatSection').style.display = checkbox.checked ? '' : 'none';
}

function updateTypeVisibility() {
    const type = document.querySelector('input[name="campaign_type"]:checked')?.value || 'sms';
    const showSms   = type === 'sms'   || type === 'both';
    const showEmail = type === 'email' || type === 'both';

    document.getElementById('sms-fields').style.display   = showSms   ? '' : 'none';
    document.getElementById('email-fields').style.display = showEmail ? '' : 'none';

    // Toggle required attributes
    const msgEl = document.getElementById('message');
    if (msgEl) msgEl.required = showSms;

    if (typeof updateEstimator === 'function') updateEstimator();
}

document.querySelector('select[name="client_id"]')?.addEventListener('change', function() {
    const opt = this.options[this.selectedIndex];
    const rate = opt.dataset.rate;
    if (rate) document.getElementById('client_rate_per_sms').value = rate;
    if (typeof updateEstimator === 'function') updateEstimator();
    rebuildFromDomainPicker(this.value);
    rebuildRecipientSourcePicker(this.value);
});

// ── Recipient source picker (choose a saved group / all recipients instead of estimating) ──
const RECIPIENT_GROUPS = @json($recipientGroups->map(fn($g) => ['id' => $g->id, 'client_id' => $g->client_id, 'name' => $g->name, 'count' => $g->recipients_count]));
const CLIENT_RECIPIENT_COUNTS = @json($clientRecipientCounts);
const EXISTING_RECIPIENT_SOURCE = @json(old('recipient_source', ''));

function rebuildRecipientSourcePicker(clientId) {
    const select = document.getElementById('recipient-source');
    if (!select) return;

    const allCount = CLIENT_RECIPIENT_COUNTS[clientId] || 0;
    const groups = RECIPIENT_GROUPS.filter(g => String(g.client_id) === String(clientId));

    select.innerHTML = '';
    const manual = document.createElement('option');
    manual.value = '';
    manual.textContent = 'Enter estimate manually — upload/import recipients later';
    select.appendChild(manual);

    if (allCount > 0) {
        const all = document.createElement('option');
        all.value = 'all';
        all.dataset.count = allCount;
        all.textContent = `All active recipients (${allCount})`;
        select.appendChild(all);
    }

    groups.forEach(g => {
        const opt = document.createElement('option');
        opt.value = g.id;
        opt.dataset.count = g.count;
        opt.textContent = `Group: ${g.name} (${g.count})`;
        select.appendChild(opt);
    });

    if (allCount === 0 && groups.length === 0) {
        const hint = document.getElementById('recipient-source-hint');
        if (hint) hint.innerHTML = 'This client has no saved recipients yet. <a href="/clients/' + clientId + '/recipients" target="_blank">Add some</a>, or enter an estimate for now.';
    } else {
        const hint = document.getElementById('recipient-source-hint');
        if (hint) hint.innerHTML = 'Pick a saved group or the client\'s whole active list — recipients are imported the moment you save.';
    }

    if (EXISTING_RECIPIENT_SOURCE) {
        const match = Array.from(select.options).find(o => o.value === EXISTING_RECIPIENT_SOURCE);
        if (match) select.value = EXISTING_RECIPIENT_SOURCE;
    }

    applyRecipientSourceCount();
}

function applyRecipientSourceCount() {
    const select = document.getElementById('recipient-source');
    if (!select) return;
    const opt = select.options[select.selectedIndex];
    const estField = document.getElementById('estimated_recipients');
    const estEmailField = document.getElementById('estimated_email_recipients');
    const count = (opt && opt.value) ? (opt.dataset.count || 0) : 0;

    if (estField) estField.value = count;
    if (estEmailField) estEmailField.value = count;

    if (typeof updateEstimator === 'function') updateEstimator();
}

document.getElementById('recipient-source')?.addEventListener('change', applyRecipientSourceCount);

// ── From-address domain picker ──────────────────────────────────────
const SENDING_DOMAINS = @json($sendingDomains->map(fn($d) => ['domain' => $d->domain, 'client_id' => $d->client_id, 'label' => $d->label]));
const EXISTING_FROM_ADDRESS = @json(old('email_from_address', $campaign->email_from_address ?? ''));

function splitEmail(addr) {
    const at = addr.lastIndexOf('@');
    return at === -1 ? [addr, ''] : [addr.substring(0, at), addr.substring(at + 1)];
}

function showPlainFromInput(prefill) {
    const group = document.getElementById('from-picker-group');
    const plain = document.getElementById('email_from_address');
    const hint  = document.getElementById('email_from_hint');
    if (!group || !plain) return;
    group.style.display = 'none';
    plain.style.display = '';
    plain.disabled = false;
    if (prefill !== undefined) plain.value = prefill;
    if (hint) hint.innerHTML = 'No verified domains match this client yet. <a href="{{ route('settings.sending-domains.index') }}" target="_blank">Verify one</a>, or type any SES-verified address manually.';
}

function rebuildFromDomainPicker(clientId) {
    const select = document.getElementById('email_from_domain');
    const group  = document.getElementById('from-picker-group');
    const plain  = document.getElementById('email_from_address');
    const hint   = document.getElementById('email_from_hint');
    if (!select || !group || !plain) return;

    const matches = SENDING_DOMAINS.filter(d => !d.client_id || String(d.client_id) === String(clientId));

    if (matches.length === 0) {
        showPlainFromInput();
        return;
    }

    group.style.display = '';
    plain.style.display = 'none';
    // Stays enabled (just visually hidden) — composeFromAddress() writes the combined
    // value into it, and a disabled field is excluded from form submission.
    if (hint) hint.innerHTML = 'Pick a verified domain — keeps marketing sends looking friendly &amp; trusted.';

    select.innerHTML = '';
    const blank = document.createElement('option');
    blank.value = ''; blank.textContent = '— account default —';
    select.appendChild(blank);
    matches.forEach(d => {
        const opt = document.createElement('option');
        opt.value = d.domain;
        opt.textContent = d.label ? `${d.domain} (${d.label})` : d.domain;
        select.appendChild(opt);
    });
    const custom = document.createElement('option');
    custom.value = '__custom__';
    custom.textContent = 'Custom / other domain…';
    select.appendChild(custom);
}

function composeFromAddress() {
    const select = document.getElementById('email_from_domain');
    const local  = document.getElementById('email_from_local');
    const plain  = document.getElementById('email_from_address');
    if (!select || document.getElementById('from-picker-group').style.display === 'none') return;

    if (select.value === '__custom__') {
        showPlainFromInput('');
        plain.focus();
        return;
    }
    plain.value = select.value ? `${local.value.trim()}@${select.value}` : '';
}

document.getElementById('email_from_domain')?.addEventListener('change', composeFromAddress);
document.getElementById('email_from_local')?.addEventListener('input', composeFromAddress);

document.addEventListener('DOMContentLoaded', function() {
    updateTypeVisibility();
    const clientSelect = document.querySelector('select[name="client_id"]');
    // Dispatching 'change' also runs rebuildFromDomainPicker via the listener above —
    // do this BEFORE prefilling, so the prefill below isn't wiped out afterwards.
    if (clientSelect && clientSelect.value) {
        clientSelect.dispatchEvent(new Event('change'));
    } else {
        rebuildFromDomainPicker('');
        rebuildRecipientSourcePicker('');
    }

    // Prefill from an existing value (edit mode / validation redisplay)
    if (EXISTING_FROM_ADDRESS) {
        const [local, domain] = splitEmail(EXISTING_FROM_ADDRESS);
        const select = document.getElementById('email_from_domain');
        const match = select && Array.from(select.options).find(o => o.value === domain);
        if (match) {
            select.value = domain;
            document.getElementById('email_from_local').value = local;
            composeFromAddress();
        } else {
            showPlainFromInput(EXISTING_FROM_ADDRESS);
        }
    }
});
</script>
