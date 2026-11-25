@extends('tenant.layout')
@section('title', 'Schedule Class')
@section('content')
<div class="page-header">
    <h1 class="page-title">Schedule a Class</h1>
    <a href="{{ route('tenant.gym.classes') }}" class="btn btn-outline-secondary">Back</a>
</div>

<div class="card">
    <div class="card-body">
        @if($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
            </div>
        @endif
        <form method="POST" action="{{ route('tenant.gym.classes.store') }}">
            @csrf
            <div class="mb-3">
                <label class="form-label">Class Name <span class="text-danger">*</span></label>
                <input type="text" name="name" class="form-control" value="{{ old('name') }}" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Trainer</label>
                <select name="trainer_id" class="form-control">
                    <option value="">— No Trainer —</option>
                    @foreach($trainers as $t)
                        <option value="{{ $t->id }}" {{ old('trainer_id') == $t->id ? 'selected' : '' }}>{{ $t->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label">Scheduled At <span class="text-danger">*</span></label>
                <input type="datetime-local" name="scheduled_at" class="form-control" value="{{ old('scheduled_at') }}" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Duration (minutes) <span class="text-danger">*</span></label>
                <input type="number" name="duration_minutes" class="form-control" min="1" value="{{ old('duration_minutes', 60) }}" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Capacity <span class="text-danger">*</span></label>
                <input type="number" name="capacity" class="form-control" min="1" value="{{ old('capacity', 20) }}" required>
            </div>
            <button type="submit" class="btn btn-primary">Schedule Class</button>
        </form>
    </div>
</div>
@endsection
