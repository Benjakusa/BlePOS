@extends('tenant.layout')
@section('title', 'Fleet Customers')
@section('content')
<div class="page-header d-flex justify-content-between align-items-center mb-3">
    <h1 class="page-title">Fleet Customers</h1>
    <a href="{{ route('tenant.fleet.customers.create') }}" class="btn btn-primary">Add Customer</a>
</div>
<div class="card">
    <table class="table">
        <thead>
            <tr><th>Name</th><th>Phone</th><th>ID Number</th><th>Driving License</th></tr>
        </thead>
        <tbody>
            @forelse($customers as $c)
            <tr>
                <td>{{ $c->name }}</td>
                <td>{{ $c->phone }}</td>
                <td>{{ $c->id_number ?? '—' }}</td>
                <td>{{ $c->license_number ?? '—' }}</td>
            </tr>
            @empty
            <tr><td colspan="4" class="text-center text-muted">No customers registered.</td></tr>
            @endforelse
        </tbody>
    </table>
    {{ $customers->links() }}
</div>
@endsection
