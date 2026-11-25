@extends('tenant.layout')
@section('title', 'Add Trainer')
@section('content')
<div class="page-header">
    <h1 class="page-title">Add Trainer</h1>
    <a href="{{ route('tenant.gym.trainers') }}" class="btn btn-outline-secondary">Back</a>
</div>

<div class="card">
    <div class="card-body">
        @if($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
            </div>
        @endif
        <form method="POST" action="{{ route('tenant.gym.trainers.store') }}">
            @csrf
            <div class="mb-3">
                <label class="form-label">Name <span class="text-danger">*</span></label>
                <input type="text" name="name" class="form-control" value="{{ old('name') }}" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Phone <span class="text-danger">*</span></label>
                <input type="text" name="phone" class="form-control" value="{{ old('phone') }}" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Email</label>
                <input type="email" name="email" class="form-control" value="{{ old('email') }}">
            </div>
            <div class="mb-3">
                <label class="form-label">Commission Rate (%) <span class="text-danger">*</span></label>
                <input type="number" name="commission_rate" class="form-control" step="0.01" min="0" max="100" value="{{ old('commission_rate', 0) }}" required>
            </div>
            <button type="submit" class="btn btn-primary">Save Trainer</button>
        </form>
    </div>
</div>
@endsection
