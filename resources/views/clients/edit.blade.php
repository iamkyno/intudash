@extends('layouts.app')
@section('title', 'Edit Client')
@section('page-title', 'Edit Client')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">Edit — {{ $client->company_name }}</div>
            <div class="card-body">
                <form action="{{ route('clients.update', $client) }}" method="POST">
                    @csrf @method('PUT')
                    @include('clients._form')
                    <div class="d-flex gap-2 mt-3">
                        <button type="submit" class="btn btn-primary">Update Client</button>
                        <a href="{{ route('clients.show', $client) }}" class="btn btn-outline-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
