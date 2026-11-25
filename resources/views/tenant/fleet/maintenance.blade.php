@extends('tenant.layout')
@section('title', 'Fleet Maintenance')
@section('content')
<div class="page-header d-flex justify-content-between align-items-center mb-3">
    <h1 class="page-title">Maintenance & Service Log</h1>
</div>

<div class="card p-3 mb-4">
    <h4>Log New Maintenance</h4>
    <form method="POST" action="{{ route('tenant.fleet.maintenance.store') }}" class="d-flex gap-2">
        @csrf
        <select name="vehicle_id" class="form-control" required>
            @foreach(\App\Models\FleetVehicle::all() as $v)
            <option value="{{ $v->id }}">{{ $v->plate }} ({{ $v->make }} {{ $v->model }})</option>
            @endforeach
        </select>
        <input type="text" name="description" placeholder="Description of service/repair" class="form-control" required>
        <input type="number" step="0.01" name="cost" placeholder="Cost (KES)" class="form-control" value="0" required>
        <button type="submit" class="btn btn-warning">Log Maintenance</button>
    </form>
</div>

<div class="card">
    <table class="table">
        <thead>
            <tr><th>Vehicle</th><th>Started</th><th>Description</th><th>Cost</th><th>Status</th><th>Action</th></tr>
        </thead>
        <tbody>
            @forelse($records as $m)
            <tr>
                <td><strong>{{ $m->vehicle?->plate }}</strong></td>
                <td>{{ $m->started_at }}</td>
                <td>{{ $m->description }}</td>
                <td>KES {{ number_format($m->cost, 2) }}</td>
                <td><span class="badge {{ $m->is_active ? 'badge-danger' : 'badge-success' }}">{{ $m->is_active ? 'Under Maintenance' : 'Completed' }}</span></td>
                <td>
                    @if($m->is_active)
                    <form method="POST" action="{{ route('tenant.fleet.maintenance.complete', $m) }}">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-success">Complete</button>
                    </form>
                    @else
                    <span class="text-muted">Done on {{ $m->completed_at }}</span>
                    @endif
                </td>
            </tr>
            @empty
            <tr><td colspan="6" class="text-center text-muted">No maintenance records logged.</td></tr>
            @endforelse
        </tbody>
    </table>
    {{ $records->links() }}
</div>
@endsection
