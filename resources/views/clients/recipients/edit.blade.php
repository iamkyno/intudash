@extends('layouts.app')
@section('title', 'Edit Recipient')
@section('page-title', 'Edit Recipient')

@section('content')
<div class="mb-3" style="font-size:13px;color:var(--text-tertiary);">
    <a href="{{ route('clients.show', $client) }}" style="color:var(--text-tertiary);text-decoration:none;">{{ $client->name ?? $client->company_name }}</a>
    &rsaquo; <a href="{{ route('clients.recipients.index', $client) }}" style="color:var(--text-tertiary);text-decoration:none;">Recipients</a>
    &rsaquo; {{ $recipient->name ?: $recipient->phone ?: $recipient->email }}
</div>

<div class="card" style="max-width:640px;">
    <div class="card-header">Edit Recipient</div>
    <div class="card-body">
        <form method="POST" action="{{ route('clients.recipients.update', [$client, $recipient]) }}">
            @csrf @method('PUT')

            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label class="form-label">Name</label>
                    <input type="text" name="name" class="form-control" value="{{ old('name', $recipient->name) }}">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        @foreach(['active' => 'Active', 'invalid' => 'Invalid', 'unsubscribed' => 'Unsubscribed'] as $val => $label)
                            <option value="{{ $val }}" {{ old('status', $recipient->status) === $val ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Phone</label>
                    <input type="text" name="phone" class="form-control" value="{{ old('phone', $recipient->phone) }}">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control" value="{{ old('email', $recipient->email) }}">
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label">Groups</label>
                @if($groups->isEmpty())
                    <p class="small mb-0" style="color:var(--text-tertiary);">No groups created yet for this client.</p>
                @else
                    @php $currentGroupIds = $recipient->groups->pluck('id')->all(); @endphp
                    <div class="d-flex flex-wrap gap-3">
                        @foreach($groups as $g)
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="group_ids[]" value="{{ $g->id }}"
                                id="grp{{ $g->id }}" {{ in_array($g->id, old('group_ids', $currentGroupIds)) ? 'checked' : '' }}>
                            <label class="form-check-label" for="grp{{ $g->id }}">{{ $g->name }}</label>
                        </div>
                        @endforeach
                    </div>
                @endif
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">Save Changes</button>
                <a href="{{ route('clients.recipients.index', $client) }}" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
