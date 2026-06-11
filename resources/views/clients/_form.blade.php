<div class="row g-3">
    <div class="col-md-6">
        <label class="form-label">Company Name <span class="text-danger">*</span></label>
        <input type="text" name="company_name" class="form-control @error('company_name') is-invalid @enderror"
            value="{{ old('company_name', $client->company_name ?? '') }}" required>
        @error('company_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-6">
        <label class="form-label">Contact Person <span class="text-danger">*</span></label>
        <input type="text" name="contact_person" class="form-control @error('contact_person') is-invalid @enderror"
            value="{{ old('contact_person', $client->contact_person ?? '') }}" required>
        @error('contact_person')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-6">
        <label class="form-label">Email <span class="text-danger">*</span></label>
        <input type="email" name="email" class="form-control @error('email') is-invalid @enderror"
            value="{{ old('email', $client->email ?? '') }}" required>
        @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-6">
        <label class="form-label">Phone <span class="text-danger">*</span></label>
        <input type="text" name="phone" class="form-control @error('phone') is-invalid @enderror"
            value="{{ old('phone', $client->phone ?? '') }}" required>
        @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-6">
        <label class="form-label">VAT/Tax Number</label>
        <input type="text" name="vat_number" class="form-control"
            value="{{ old('vat_number', $client->vat_number ?? '') }}">
    </div>
    <div class="col-md-6">
        <label class="form-label">Default SMS Rate (R) <span class="text-danger">*</span></label>
        <div class="input-group">
            <span class="input-group-text">R</span>
            <input type="number" name="default_sms_rate" step="0.0001" min="0"
                class="form-control @error('default_sms_rate') is-invalid @enderror"
                value="{{ old('default_sms_rate', $client->default_sms_rate ?? '0.2500') }}" required>
        </div>
        @error('default_sms_rate')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-12">
        <label class="form-label">Billing Address</label>
        <textarea name="billing_address" class="form-control" rows="3">{{ old('billing_address', $client->billing_address ?? '') }}</textarea>
    </div>
    <div class="col-md-4">
        <label class="form-label">Status</label>
        <select name="status" class="form-select">
            <option value="active" {{ old('status', $client->status ?? 'active') === 'active' ? 'selected' : '' }}>Active</option>
            <option value="inactive" {{ old('status', $client->status ?? '') === 'inactive' ? 'selected' : '' }}>Inactive</option>
        </select>
    </div>
    <div class="col-12">
        <label class="form-label">Notes</label>
        <textarea name="notes" class="form-control" rows="2">{{ old('notes', $client->notes ?? '') }}</textarea>
    </div>
</div>
