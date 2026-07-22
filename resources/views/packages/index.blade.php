@extends('layouts.app')
@section('title', 'Packages')
@section('page-title', 'Pricing Packages')

@section('content')
@if(session('success'))
    <div class="alert alert-success py-2">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="alert alert-danger py-2">{{ session('error') }}</div>
@endif

<div class="row g-3">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <span class="card-header-title"><i class="bi bi-box-seam"></i> Volume Tiers</span>
            </div>
            @if($packages->isEmpty())
                <div class="card-body text-center py-5" style="color:var(--text-tertiary);">
                    <i class="bi bi-box-seam" style="font-size:2rem;"></i>
                    <p class="mt-2 mb-3">No packages yet. A package is a volume tier — e.g. "500 SMS and up @ R0.25 each" — that fills in the client rate automatically when creating a campaign.</p>
                    <form method="POST" action="{{ route('packages.seed-defaults') }}" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-primary btn-sm">
                            <i class="bi bi-magic me-1"></i>Create Starter SMS Tiers (500 / 1 000 / 5 000 / 10 000)
                        </button>
                    </form>
                </div>
            @else
                <div class="card-body-flush">
                    <table class="table">
                        <thead>
                            <tr><th>Name</th><th>Channel</th><th>From (msgs)</th><th>Rate / msg</th><th>Description</th><th>Status</th><th></th></tr>
                        </thead>
                        <tbody>
                        @foreach($packages as $p)
                            {{-- A <form> can't sit inside a <tr>; each input targets its row's form via the form="" attribute. --}}
                            <tr>
                                <td style="min-width:120px;"><input type="text" name="name" form="pkg-form-{{ $p->id }}" class="form-control form-control-sm" value="{{ $p->name }}"></td>
                                <td>
                                    <select name="channel" form="pkg-form-{{ $p->id }}" class="form-select form-select-sm">
                                        <option value="sms" {{ $p->channel === 'sms' ? 'selected' : '' }}>SMS</option>
                                        <option value="email" {{ $p->channel === 'email' ? 'selected' : '' }}>Email</option>
                                    </select>
                                </td>
                                <td style="max-width:110px;"><input type="number" name="min_units" min="1" form="pkg-form-{{ $p->id }}" class="form-control form-control-sm" value="{{ $p->min_units }}"></td>
                                <td style="max-width:130px;">
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text">R</span>
                                        <input type="number" name="unit_price" step="0.0001" min="0" form="pkg-form-{{ $p->id }}" class="form-control" value="{{ $p->unit_price }}">
                                    </div>
                                </td>
                                <td><input type="text" name="description" form="pkg-form-{{ $p->id }}" class="form-control form-control-sm" value="{{ $p->description }}"></td>
                                <td>
                                    <div class="form-check form-switch mt-1">
                                        <input class="form-check-input" type="checkbox" name="active" value="1" form="pkg-form-{{ $p->id }}" {{ $p->active ? 'checked' : '' }}>
                                    </div>
                                </td>
                                <td class="text-end" style="white-space:nowrap;">
                                    <button type="submit" form="pkg-form-{{ $p->id }}" class="btn btn-ghost btn-sm btn-icon" title="Save"><i class="bi bi-check-lg"></i></button>
                                    <button type="submit" form="pkg-delete-{{ $p->id }}" class="btn btn-ghost btn-sm btn-icon text-danger"
                                            onclick="return confirm('Delete package &quot;{{ addslashes($p->name) }}&quot;?')"><i class="bi bi-trash"></i></button>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
                {{-- Row form targets (must live outside the <table>) --}}
                @foreach($packages as $p)
                    <form id="pkg-form-{{ $p->id }}" method="POST" action="{{ route('packages.update', $p) }}">@csrf @method('PUT')</form>
                    <form id="pkg-delete-{{ $p->id }}" method="POST" action="{{ route('packages.destroy', $p) }}">@csrf @method('DELETE')</form>
                @endforeach
            @endif
        </div>
    </div>

    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <span class="card-header-title"><i class="bi bi-plus-lg"></i> New Package</span>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('packages.store') }}">
                    @csrf
                    <div class="mb-2">
                        <label class="form-label small">Name</label>
                        <input type="text" name="name" class="form-control form-control-sm @error('name') is-invalid @enderror" value="{{ old('name') }}" placeholder="e.g. Growth" required>
                    </div>
                    <div class="row g-2 mb-2">
                        <div class="col-6">
                            <label class="form-label small">Channel</label>
                            <select name="channel" class="form-select form-select-sm">
                                <option value="sms">SMS</option>
                                <option value="email">Email</option>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label small">From (messages)</label>
                            <input type="number" name="min_units" min="1" class="form-control form-control-sm" value="{{ old('min_units', 500) }}" required>
                        </div>
                    </div>
                    <div class="mb-2">
                        <label class="form-label small">Rate per message (R)</label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text">R</span>
                            <input type="number" name="unit_price" step="0.0001" min="0" class="form-control" value="{{ old('unit_price') }}" required>
                        </div>
                    </div>
                    <div class="mb-2">
                        <label class="form-label small">Description (optional)</label>
                        <input type="text" name="description" class="form-control form-control-sm" value="{{ old('description') }}" placeholder="Shown on quotes">
                    </div>
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" name="active" value="1" id="pkg-active" checked>
                        <label class="form-check-label small" for="pkg-active">Active</label>
                    </div>
                    <button type="submit" class="btn btn-primary btn-sm w-100">Add Package</button>
                </form>
            </div>
        </div>

        <div class="card mt-3">
            <div class="card-body">
                <p class="small mb-1 fw-semibold" style="color:var(--text-secondary);"><i class="bi bi-info-circle me-1"></i>How packages work</p>
                <p class="small mb-0" style="color:var(--text-tertiary);">
                    On the campaign form, the Pricing section suggests the best tier for the selected audience size
                    and fills the client rate automatically. You can always override the rate manually.
                    The package name appears on the quote line.
                </p>
            </div>
        </div>
    </div>
</div>
@endsection
