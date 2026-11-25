@extends('tenant.layout')
@section('title', 'Create Plan')
@section('content')
<div class="page-header">
    <h1 class="page-title">Create Membership Plan</h1>
    <a href="{{ route('tenant.gym.plans') }}" class="btn btn-outline-secondary">Back</a>
</div>

<div class="card">
    <div class="card-body">
        @if($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
            </div>
        @endif
        <form method="POST" action="{{ route('tenant.gym.plans.store') }}">
            @csrf
            <div class="mb-3">
                <label class="form-label">Plan Name <span class="text-danger">*</span></label>
                <input type="text" name="name" class="form-control" value="{{ old('name') }}" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Billing Cycle <span class="text-danger">*</span></label>
                <select name="billing_cycle" class="form-control" required>
                    <option value="">— Select —</option>
                    <option value="monthly" {{ old('billing_cycle') === 'monthly' ? 'selected' : '' }}>Monthly</option>
                    <option value="weekly" {{ old('billing_cycle') === 'weekly' ? 'selected' : '' }}>Weekly</option>
                    <option value="sessions" {{ old('billing_cycle') === 'sessions' ? 'selected' : '' }}>Sessions</option>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label">Price <span class="text-danger">*</span></label>
                <input type="number" name="price" class="form-control" step="0.01" min="0" value="{{ old('price') }}" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Session Credits <small class="text-muted">(only for sessions cycle)</small></label>
                <input type="number" name="session_credits" class="form-control" min="1" value="{{ old('session_credits') }}">
            </div>
            <button type="submit" class="btn btn-primary">Save Plan</button>
        </form>
    </div>
</div>
@endsection
