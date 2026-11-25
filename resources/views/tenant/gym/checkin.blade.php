@extends('tenant.layout')
@section('title', 'Member Check-in')
@section('content')
<div class="page-header">
    <h1 class="page-title">Member Check-in</h1>
</div>

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif
@if($errors->any())
    <div class="alert alert-danger">
        <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
    </div>
@endif

<div class="card mb-4">
    <div class="card-body">
        <h5 class="card-title">Record Check-in</h5>
        <form method="POST" action="{{ route('tenant.gym.checkin.process') }}">
            @csrf
            <div class="mb-3">
                <label class="form-label">Member ID <span class="text-danger">*</span></label>
                <input type="number" name="member_id" class="form-control" value="{{ old('member_id') }}" placeholder="Enter member ID" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Method <span class="text-danger">*</span></label>
                <select name="method" class="form-control" required>
                    <option value="manual" {{ old('method') === 'manual' ? 'selected' : '' }}>Manual</option>
                    <option value="barcode" {{ old('method') === 'barcode' ? 'selected' : '' }}>Barcode</option>
                    <option value="qr" {{ old('method') === 'qr' ? 'selected' : '' }}>QR Code</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary">Check In</button>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header">Recent Check-ins</div>
    <table class="table table-hover mb-0">
        <thead>
            <tr>
                <th>Member</th>
                <th>Checked In At</th>
                <th>Method</th>
            </tr>
        </thead>
        <tbody>
        @forelse($recentCheckins as $c)
            <tr>
                <td>{{ $c->member?->name ?? '—' }}</td>
                <td>{{ $c->checked_in_at->format('Y-m-d H:i') }}</td>
                <td><span class="badge bg-secondary text-capitalize">{{ $c->method }}</span></td>
            </tr>
        @empty
            <tr><td colspan="3" class="text-center text-muted">No check-ins today.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
@endsection
