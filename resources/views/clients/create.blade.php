@extends('layouts.app')
@section('title', 'Add Client')
@section('page-title', 'Add Client')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">Client Details</div>
            <div class="card-body">
                <form action="{{ route('clients.store') }}" method="POST">
                    @csrf
                    @include('clients._form')
                    <div class="d-flex gap-2 mt-3">
                        <button type="submit" class="btn btn-primary">Save Client</button>
                        <a href="{{ route('clients.index') }}" class="btn btn-outline-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
