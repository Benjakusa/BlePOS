@extends('tenant.layout')
@section('title', 'Edit Member')
@section('content')
<div class="page-header">
    <h1 class="page-title">Edit Member</h1>
    <a href="{{ route('tenant.gym.members') }}" class="btn btn-outline-secondary">Back</a>
</div>

<div class="card">
    <div class="card-body">
        @if($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
            </div>
        @endif
        <form method="POST" action="{{ route('tenant.gym.members.update', $member) }}">
            @csrf
            @method('PUT')
            <div class="mb-3">
                <label class="form-label">Name <span class="text-danger">*</span></label>
                <input type="text" name="name" class="form-control" value="{{ old('name', $member->name) }}" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Phone <span class="text-danger">*</span></label>
                <input type="text" name="phone" class="form-control" value="{{ old('phone', $member->phone) }}" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Email</label>
                <input type="email" name="email" class="form-control" value="{{ old('email', $member->email) }}">
            </div>
            <div class="mb-3">
                <label class="form-label">Date of Birth</label>
                <input type="date" name="dob" class="form-control" value="{{ old('dob', $member->dob?->toDateString()) }}">
            </div>
            <div class="mb-3">
                <label class="form-label">Notes</label>
                <textarea name="notes" class="form-control" rows="3">{{ old('notes', $member->notes) }}</textarea>
            </div>
            <button type="submit" class="btn btn-primary">Update Member</button>
        </form>
    </div>
</div>
@endsection
