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
</div>

<script>
document.querySelector('select[name="client_id"]')?.addEventListener('change', function() {
    const opt = this.options[this.selectedIndex];
    const rate = opt.dataset.rate;
    if (rate) document.getElementById('client_rate_per_sms').value = rate;
    if (typeof updateEstimator === 'function') updateEstimator();
});
</script>
