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

                    <div class="mb-4">
                        <label class="form-label" for="campaign_id">Campaign <span class="text-danger">*</span></label>
                        <select id="campaign_id" name="campaign_id" class="form-select @error('campaign_id') is-invalid @enderror" required>
                            <option value="">— Select a Campaign —</option>
                            @foreach($campaigns as $campaign)
                                @php
                                    $recipientCount = $campaign->estimated_recipients > 0
                                        ? $campaign->estimated_recipients
                                        : $campaign->validRecipients()->count();
                                    $label = $campaign->name . ' (' . number_format($recipientCount) . ' recipients)';
                                @endphp
                                <option value="{{ $campaign->id }}"
                                    data-client="{{ $campaign->client_id }}"
                                    {{ old('campaign_id') == $campaign->id ? 'selected' : '' }}>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                        @error('campaign_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <div style="font-size:12px;color:var(--text-tertiary);margin-top:4px;">
                            Showing draft and recipients-uploaded campaigns with estimated or valid recipients.
                        </div>
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
    });
});
</script>
@endpush
@endsection
