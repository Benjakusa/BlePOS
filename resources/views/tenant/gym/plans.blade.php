@extends('tenant.layout')
@section('title', 'Membership Plans')
@section('content')
<div class="page-header">
    <h1 class="page-title">Membership Plans</h1>
    <a href="{{ route('tenant.gym.plans.create') }}" class="btn btn-primary">Add Plan</a>
</div>

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

<div class="card">
    <table class="table table-hover mb-0">
        <thead>
            <tr>
                <th>Name</th>
                <th>Billing Cycle</th>
                <th>Price</th>
                <th>Session Credits</th>
                <th>Active</th>
            </tr>
        </thead>
        <tbody>
        @forelse($plans as $p)
            <tr>
                <td>{{ $p->name }}</td>
                <td><span class="badge bg-secondary text-capitalize">{{ $p->billing_cycle }}</span></td>
                <td>{{ number_format($p->price, 2) }}</td>
                <td>{{ $p->session_credits ?? '—' }}</td>
                <td>
                    @if($p->is_active)
                        <span class="badge bg-success">Yes</span>
                    @else
                        <span class="badge bg-danger">No</span>
                    @endif
                </td>
            </tr>
        @empty
            <tr><td colspan="5" class="text-center text-muted">No plans yet.</td></tr>
        @endforelse
        </tbody>
    </table>
    <div class="card-footer">{{ $plans->links() }}</div>
</div>
@endsection
