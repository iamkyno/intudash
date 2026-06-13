@extends('layouts.app')
@section('title', 'Edit Reminder Template')
@section('page-title', 'Edit Reminder Template')

@section('content')
<div class="page-header">
    <div class="d-flex align-items-center gap-3">
        <a href="{{ route('reminder-templates.index') }}" class="btn btn-ghost btn-sm btn-icon"><i class="bi bi-arrow-left"></i></a>
        <h1>Edit Template</h1>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <form action="{{ route('reminder-templates.update', $template) }}" method="POST">
            @csrf @method('PUT')
            @include('reminders.templates._form')
            <div class="d-flex gap-2 mt-3">
                <button type="submit" class="btn btn-primary">Save Changes</button>
                <a href="{{ route('reminder-templates.index') }}" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
