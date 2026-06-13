@extends('layouts.app')
@section('title', 'New Reminder')
@section('page-title', 'New Reminder')

@section('content')
<div class="page-header">
    <div class="d-flex align-items-center gap-3">
        <a href="{{ route('reminders.index') }}" class="btn btn-ghost btn-sm btn-icon"><i class="bi bi-arrow-left"></i></a>
        <h1>New Reminder</h1>
    </div>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-body">
                <form action="{{ route('reminders.store') }}" method="POST">
                    @csrf
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Client <span class="text-danger">*</span></label>
                            <select name="client_id" id="clientSelect" class="form-select @error('client_id') is-invalid @enderror" required onchange="filterTemplates()">
                                <option value="">Select Client</option>
                                @foreach($clients as $client)
                                    <option value="{{ $client->id }}" {{ old('client_id') == $client->id ? 'selected' : '' }}>{{ $client->company_name }}</option>
                                @endforeach
                            </select>
                            @error('client_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Template <span class="text-danger">*</span></label>
                            <select name="template" id="templateSelect" class="form-select @error('template') is-invalid @enderror" required>
                                <option value="">Select a client first</option>
                            </select>
                            @error('template')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Recipient Name</label>
                            <input type="text" name="name" class="form-control" value="{{ old('name') }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Send At <span class="text-danger">*</span></label>
                            <input type="datetime-local" name="send_at" class="form-control @error('send_at') is-invalid @enderror" value="{{ old('send_at') }}" required>
                            @error('send_at')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Phone</label>
                            <input type="text" name="phone" class="form-control @error('phone') is-invalid @enderror" value="{{ old('phone') }}" placeholder="0821234567">
                            @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email') }}">
                            @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                    <div class="d-flex gap-2 mt-3">
                        <button type="submit" class="btn btn-primary">Schedule Reminder</button>
                        <a href="{{ route('reminders.index') }}" class="btn btn-outline-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-header"><i class="bi bi-info-circle me-1"></i>How it works</div>
            <div class="card-body small" style="color:var(--text-secondary);">
                <p>The reminder is sent automatically at the <strong>Send At</strong> time via the template's channel (SMS, email, or both).</p>
                <p class="mb-0">Provide a phone for SMS templates, an email for email templates, or both for combined templates.</p>
            </div>
        </div>
    </div>
</div>

<script>
const TEMPLATES = {
    @foreach($clients as $client)
    "{{ $client->id }}": [
        @foreach($client->reminderTemplates->where('active', true) as $t)
        { slug: "{{ $t->slug }}", name: "{{ $t->name }} ({{ $t->channel }})" },
        @endforeach
    ],
    @endforeach
};
function filterTemplates() {
    const clientId = document.getElementById('clientSelect').value;
    const sel = document.getElementById('templateSelect');
    sel.innerHTML = '';
    const list = TEMPLATES[clientId] || [];
    if (!list.length) {
        sel.innerHTML = '<option value="">No active templates for this client</option>';
        return;
    }
    sel.innerHTML = '<option value="">Select template</option>';
    list.forEach(t => {
        const o = document.createElement('option');
        o.value = t.slug; o.textContent = t.name;
        sel.appendChild(o);
    });
}
document.addEventListener('DOMContentLoaded', function() {
    if (document.getElementById('clientSelect').value) filterTemplates();
});
</script>
@endsection
