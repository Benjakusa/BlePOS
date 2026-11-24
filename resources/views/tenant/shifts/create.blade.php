@extends('tenant.layout')

@section('title', 'Open Shift')

@section('content')
<header>
    <div>
        <h1>Open Work Shift</h1>
        <div class="muted">Record opening cash float in register till to start processing sales</div>
    </div>
    <div>
        <a href="{{ route('tenant.shifts.index') }}" class="btn btn-secondary">Shift History</a>
    </div>
</header>

<div class="panel">
    <form action="{{ route('tenant.shifts.store') }}" method="POST" class="form" style="max-width: 600px; display: flex; flex-direction: column; gap: 14px;">
        @csrf
        <div style="background: var(--clr-grey-tint); padding: 12px; border-radius: var(--radius-sm); margin-bottom: 4px;">
            <p style="margin: 0;"><strong>Active Branch:</strong> {{ $shop->name }}</p>
            <p style="margin: 4px 0 0;"><strong>Cashier:</strong> {{ auth()->user()->name }}</p>
        </div>

        <x-input type="number" step="0.01" min="0" name="opening_float" label="Opening Cash Float (KES)" value="0.00" required autofocus helper="Count the physical notes and coins in the drawer before starting." />

        <x-input type="textarea" name="opening_notes" label="Opening Notes" rows="2" placeholder="e.g. Received float envelope #42 from safe" />

        <div style="margin-top: 8px;">
            <button type="submit" class="btn">Open Shift & Begin Sales</button>
        </div>
    </form>
</div>
@endsection
