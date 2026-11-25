@extends('tenant.layout')
@section('title', 'Mechanics')
@section('content')
<div class="page-header d-flex justify-content-between align-items-center mb-3">
    <h1 class="page-title">Mechanics & Techs</h1>
    <a href="{{ route('tenant.garage.mechanics.create') }}" class="btn btn-primary">Add Mechanic</a>
</div>
<div class="card">
    <table class="table">
        <thead>
            <tr><th>Name</th><th>Phone</th><th>Commission %</th><th>Status</th></tr>
        </thead>
        <tbody>
            @forelse($mechanics as $m)
            <tr>
                <td>{{ $m->name }}</td>
                <td>{{ $m->phone }}</td>
                <td>{{ $m->commission_rate }}%</td>
                <td><span class="badge {{ $m->is_active ? 'badge-success' : 'badge-secondary' }}">{{ $m->is_active ? 'Active' : 'Inactive' }}</span></td>
            </tr>
            @empty
            <tr><td colspan="4" class="text-center text-muted">No mechanics recorded.</td></tr>
            @endforelse
        </tbody>
    </table>
    {{ $mechanics->links() }}
</div>
@endsection
