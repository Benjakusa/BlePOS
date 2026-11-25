@extends('tenant.layout')
@section('title', 'Trainers')
@section('content')
<div class="page-header">
    <h1 class="page-title">Trainers</h1>
    <a href="{{ route('tenant.gym.trainers.create') }}" class="btn btn-primary">Add Trainer</a>
</div>

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

<div class="card">
    <table class="table table-hover mb-0">
        <thead>
            <tr>
                <th>Name</th>
                <th>Phone</th>
                <th>Email</th>
                <th>Commission Rate</th>
                <th>Active</th>
            </tr>
        </thead>
        <tbody>
        @forelse($trainers as $t)
            <tr>
                <td>{{ $t->name }}</td>
                <td>{{ $t->phone }}</td>
                <td>{{ $t->email ?? '—' }}</td>
                <td>{{ $t->commission_rate }}%</td>
                <td>
                    @if($t->is_active)
                        <span class="badge bg-success">Yes</span>
                    @else
                        <span class="badge bg-danger">No</span>
                    @endif
                </td>
            </tr>
        @empty
            <tr><td colspan="5" class="text-center text-muted">No trainers yet.</td></tr>
        @endforelse
        </tbody>
    </table>
    <div class="card-footer">{{ $trainers->links() }}</div>
</div>
@endsection
