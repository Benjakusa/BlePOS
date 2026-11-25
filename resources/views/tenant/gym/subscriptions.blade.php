@extends('tenant.layout')
@section('title', 'Subscriptions')
@section('content')
<div class="page-header">
    <h1 class="page-title">Subscriptions</h1>
    <a href="{{ route('tenant.gym.subscriptions.create') }}" class="btn btn-primary">New Subscription</a>
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
                <th>Member</th>
                <th>Plan</th>
                <th>Status</th>
                <th>Start</th>
                <th>End</th>
                <th>Credits Left</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        @forelse($subscriptions as $s)
            <tr>
                <td>{{ $s->member?->name ?? '—' }}</td>
                <td>{{ $s->plan?->name ?? '—' }}</td>
                <td>
                    @php
                        $badge = match($s->status) {
                            'active' => 'success', 'frozen' => 'info',
                            'expired' => 'secondary', 'cancelled' => 'danger', default => 'secondary'
                        };
                    @endphp
                    <span class="badge bg-{{ $badge }} text-capitalize">{{ $s->status }}</span>
                </td>
                <td>{{ $s->start_date->toDateString() }}</td>
                <td>{{ $s->end_date->toDateString() }}</td>
                <td>{{ $s->session_credits_remaining ?? '—' }}</td>
                <td>
                    @if($s->status === 'active')
                        <button class="btn btn-sm btn-outline-info" data-bs-toggle="modal" data-bs-target="#freezeModal{{ $s->id }}">Freeze</button>
                    @endif
                    <button class="btn btn-sm btn-outline-success" data-bs-toggle="modal" data-bs-target="#renewModal{{ $s->id }}">Renew</button>
                </td>
            </tr>
            {{-- Freeze Modal --}}
            <div class="modal fade" id="freezeModal{{ $s->id }}" tabindex="-1">
                <div class="modal-dialog"><div class="modal-content">
                    <form method="POST" action="{{ route('tenant.gym.subscriptions.freeze', $s) }}">
                        @csrf @method('PATCH')
                        <div class="modal-header"><h5 class="modal-title">Freeze Subscription</h5></div>
                        <div class="modal-body">
                            <div class="mb-3"><label class="form-label">Freeze From</label><input type="date" name="freeze_start" class="form-control" required></div>
                            <div class="mb-3"><label class="form-label">Freeze Until</label><input type="date" name="freeze_end" class="form-control" required></div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-info">Freeze</button>
                        </div>
                    </form>
                </div></div>
            </div>
            {{-- Renew Modal --}}
            <div class="modal fade" id="renewModal{{ $s->id }}" tabindex="-1">
                <div class="modal-dialog"><div class="modal-content">
                    <form method="POST" action="{{ route('tenant.gym.subscriptions.renew', $s) }}">
                        @csrf @method('PATCH')
                        <div class="modal-header"><h5 class="modal-title">Renew Subscription</h5></div>
                        <div class="modal-body">
                            <div class="mb-3">
                                <label class="form-label">Amount</label>
                                <input type="number" name="amount" class="form-control" step="0.01" value="{{ $s->plan?->price }}" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Payment Method</label>
                                <select name="method" class="form-control" required>
                                    <option value="cash">Cash</option>
                                    <option value="mpesa">M-Pesa</option>
                                </select>
                            </div>
                            <div class="mb-3"><label class="form-label">Reference</label><input type="text" name="reference" class="form-control"></div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-success">Renew</button>
                        </div>
                    </form>
                </div></div>
            </div>
        @empty
            <tr><td colspan="7" class="text-center text-muted">No subscriptions yet.</td></tr>
        @endforelse
        </tbody>
    </table>
    <div class="card-footer">{{ $subscriptions->links() }}</div>
</div>
@endsection
