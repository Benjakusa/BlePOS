@extends('tenant.layout')
@section('title', 'Classes')
@section('content')
<div class="page-header">
    <h1 class="page-title">Classes</h1>
    <a href="{{ route('tenant.gym.classes.create') }}" class="btn btn-primary">Schedule Class</a>
</div>

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif
@if($errors->any())
    <div class="alert alert-danger">
        <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
    </div>
@endif

<div class="card">
    <table class="table table-hover mb-0">
        <thead>
            <tr>
                <th>Class</th>
                <th>Trainer</th>
                <th>Scheduled At</th>
                <th>Duration</th>
                <th>Capacity</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        @forelse($classes as $c)
            <tr>
                <td>{{ $c->name }}</td>
                <td>{{ $c->trainer?->name ?? '—' }}</td>
                <td>{{ $c->scheduled_at->format('Y-m-d H:i') }}</td>
                <td>{{ $c->duration_minutes }} min</td>
                <td>{{ $c->capacity }}</td>
                <td>
                    @if($c->is_cancelled)
                        <span class="badge bg-danger">Cancelled</span>
                    @else
                        <span class="badge bg-success">Active</span>
                    @endif
                </td>
                <td>
                    @if(!$c->is_cancelled)
                        <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#bookModal{{ $c->id }}">Book</button>
                    @endif
                </td>
            </tr>
            {{-- Book Modal --}}
            <div class="modal fade" id="bookModal{{ $c->id }}" tabindex="-1">
                <div class="modal-dialog"><div class="modal-content">
                    <form method="POST" action="{{ route('tenant.gym.classes.book') }}">
                        @csrf
                        <input type="hidden" name="class_id" value="{{ $c->id }}">
                        <div class="modal-header"><h5 class="modal-title">Book: {{ $c->name }}</h5></div>
                        <div class="modal-body">
                            <div class="mb-3">
                                <label class="form-label">Member ID</label>
                                <input type="number" name="member_id" class="form-control" required>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">Book</button>
                        </div>
                    </form>
                </div></div>
            </div>
        @empty
            <tr><td colspan="7" class="text-center text-muted">No classes scheduled.</td></tr>
        @endforelse
        </tbody>
    </table>
    <div class="card-footer">{{ $classes->links() }}</div>
</div>
@endsection
