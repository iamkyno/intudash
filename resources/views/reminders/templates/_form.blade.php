@php $channel = old('channel', $template->channel ?? 'sms'); @endphp
<div class="row g-3">
    <div class="col-md-6">
        <label class="form-label">Client <span class="text-danger">*</span></label>
        <select name="client_id" class="form-select @error('client_id') is-invalid @enderror" required>
            <option value="">Select Client</option>
            @foreach($clients as $client)
                <option value="{{ $client->id }}" {{ old('client_id', $template->client_id ?? '') == $client->id ? 'selected' : '' }}>
                    {{ $client->company_name }}
                </option>
            @endforeach
        </select>
        @error('client_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-6">
        <label class="form-label">Template Name <span class="text-danger">*</span></label>
        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
            value="{{ old('name', $template->name ?? '') }}" required placeholder="e.g. Appointment 24h Reminder">
        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-6">
        <label class="form-label">Slug <small class="text-muted">(used in API/CSV; auto-generated if blank)</small></label>
        <input type="text" name="slug" class="form-control @error('slug') is-invalid @enderror"
            value="{{ old('slug', $template->slug ?? '') }}" placeholder="e.g. appt-24h">
        @error('slug')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-6">
        <label class="form-label">Channel <span class="text-danger">*</span></label>
        <div class="d-flex gap-3 mt-1">
            @foreach(['sms' => 'SMS', 'email' => 'Email', 'both' => 'Both'] as $val => $lbl)
            <div class="form-check">
                <input class="form-check-input" type="radio" name="channel" id="ch_{{ $val }}" value="{{ $val }}"
                    {{ $channel === $val ? 'checked' : '' }} onchange="rtToggle()">
                <label class="form-check-label" for="ch_{{ $val }}">{{ $lbl }}</label>
            </div>
            @endforeach
        </div>
    </div>

    <div id="rt-sms" class="col-12">
        <div style="background:var(--surface-bg);border:1px solid var(--surface-border);border-radius:8px;padding:16px;">
            <p class="small fw-semibold mb-3" style="color:var(--text-secondary);"><i class="bi bi-chat-dots me-1"></i>SMS</p>
            <div class="row g-3">
                <div class="col-md-9">
                    <label class="form-label">SMS Body</label>
                    <textarea name="sms_body" class="form-control" rows="3"
                        placeholder="Hi @{{name}}, reminder for your booking on @{{date}}.">{{ old('sms_body', $template->sms_body ?? '') }}</textarea>
                    <small class="text-muted">Merge fields: <code>@{{name}}</code>, <code>@{{phone}}</code>, and any custom CSV/API columns e.g. <code>@{{date}}</code>.</small>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Sender Name</label>
                    <input type="text" name="sender_name" class="form-control" maxlength="11"
                        value="{{ old('sender_name', $template->sender_name ?? '') }}">
                </div>
            </div>
        </div>
    </div>

    <div id="rt-email" class="col-12">
        <div style="background:var(--surface-bg);border:1px solid var(--surface-border);border-radius:8px;padding:16px;">
            <p class="small fw-semibold mb-3" style="color:var(--text-secondary);"><i class="bi bi-envelope me-1"></i>Email</p>
            <div class="row g-3">
                <div class="col-12">
                    <label class="form-label">Subject</label>
                    <input type="text" name="email_subject" class="form-control"
                        value="{{ old('email_subject', $template->email_subject ?? '') }}" placeholder="Your appointment reminder">
                </div>
                <div class="col-md-4">
                    <label class="form-label">From Name</label>
                    <input type="text" name="email_from_name" class="form-control"
                        value="{{ old('email_from_name', $template->email_from_name ?? '') }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">From Address</label>
                    <div class="input-group input-group-sm" id="from-picker-group">
                        <input type="text" id="email_from_local" class="form-control" placeholder="reminders" autocomplete="off">
                        <span class="input-group-text">@</span>
                        <select id="email_from_domain" class="form-select"></select>
                    </div>
                    <input type="email" name="email_from_address" id="email_from_address" class="form-control"
                        value="{{ old('email_from_address', $template->email_from_address ?? '') }}"
                        placeholder="verified@domain.com" style="display:none;">
                    <small class="text-muted" id="email_from_hint">Pick a verified domain — keeps reminders looking friendly &amp; trusted.</small>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Reply-To</label>
                    <input type="email" name="email_reply_to" class="form-control"
                        value="{{ old('email_reply_to', $template->email_reply_to ?? '') }}">
                </div>
                <div class="col-12">
                    <label class="form-label">Email Body (HTML)</label>
                    <textarea name="email_body" class="form-control" rows="6"
                        placeholder="Hi @{{name}}, this is a reminder…">{{ old('email_body', $template->email_body ?? '') }}</textarea>
                </div>
            </div>
        </div>
    </div>

    <div class="col-12">
        <div class="form-check form-switch">
            <input class="form-check-input" type="checkbox" name="active" value="1" id="active"
                {{ old('active', $template->active ?? true) ? 'checked' : '' }}>
            <label class="form-check-label" for="active">Active (only active templates can be used by API/CSV)</label>
        </div>
    </div>
</div>

<script>
function rtToggle() {
    const ch = document.querySelector('input[name="channel"]:checked')?.value || 'sms';
    document.getElementById('rt-sms').style.display   = (ch === 'sms' || ch === 'both') ? '' : 'none';
    document.getElementById('rt-email').style.display = (ch === 'email' || ch === 'both') ? '' : 'none';
}
document.addEventListener('DOMContentLoaded', rtToggle);

// ── From-address domain picker ──────────────────────────────────────
const SENDING_DOMAINS = @json($sendingDomains->map(fn($d) => ['domain' => $d->domain, 'client_id' => $d->client_id, 'label' => $d->label]));
const EXISTING_FROM_ADDRESS = @json(old('email_from_address', $template->email_from_address ?? ''));

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
    if (prefill !== undefined) plain.value = prefill;
    if (hint) hint.innerHTML = 'No verified domains match this client yet. <a href="{{ route('settings.sending-domains.index') }}" target="_blank">Verify one</a>, or type any verified sender address manually.';
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
    if (hint) hint.innerHTML = 'Pick a verified domain — keeps reminders looking friendly &amp; trusted.';

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
document.querySelector('select[name="client_id"]')?.addEventListener('change', function() {
    rebuildFromDomainPicker(this.value);
});

document.addEventListener('DOMContentLoaded', function() {
    const clientSelect = document.querySelector('select[name="client_id"]');
    rebuildFromDomainPicker(clientSelect ? clientSelect.value : '');

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
