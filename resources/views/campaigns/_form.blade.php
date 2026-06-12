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
    <div class="col-12">
        <label class="form-label">Message Body <span class="text-danger">*</span></label>
        <textarea name="message" id="message" class="form-control @error('message') is-invalid @enderror"
            rows="5" required placeholder="Type your SMS message here...">{{ old('message', $campaign->message ?? '') }}</textarea>
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
        <label class="form-label">Estimated Recipients</label>
        <input type="number" name="estimated_recipients" id="estimated_recipients" min="0"
            class="form-control" value="{{ old('estimated_recipients', $campaign->estimated_recipients ?? '0') }}">
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

document.querySelector('select[name="client_id"]')?.addEventListener('change', function() {
    const opt = this.options[this.selectedIndex];
    const rate = opt.dataset.rate;
    if (rate) document.getElementById('client_rate_per_sms').value = rate;
    if (typeof updateEstimator === 'function') updateEstimator();
});

// Trigger rate population on page load if client is pre-selected
document.addEventListener('DOMContentLoaded', function() {
    const clientSelect = document.querySelector('select[name="client_id"]');
    if (clientSelect && clientSelect.value) {
        clientSelect.dispatchEvent(new Event('change'));
    }
});
</script>
