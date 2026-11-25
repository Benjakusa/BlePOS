@extends('tenant.layout')
@section('title', 'Gym Members')
@section('content')
<div class="page-header">
    <h1 class="page-title">Gym Members</h1>
    <a href="{{ route('tenant.gym.members.create') }}" class="btn btn-primary">Add Member</a>
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
                <th>Date of Birth</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        @forelse($members as $m)
            <tr>
                <td>{{ $m->name }}</td>
                <td>{{ $m->phone }}</td>
                <td>{{ $m->email ?? '—' }}</td>
                <td>{{ $m->dob ? $m->dob->toDateString() : '—' }}</td>
                <td>
                    <a href="{{ route('tenant.gym.members.edit', $m) }}" class="btn btn-sm btn-outline-secondary">Edit</a>
                </td>
            </tr>
        @empty
            <tr><td colspan="5" class="text-center text-muted">No members yet.</td></tr>
        @endforelse
        </tbody>
    </table>
    <div class="card-footer">{{ $members->links() }}</div>
</div>
@endsection
