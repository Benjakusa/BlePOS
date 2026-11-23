@extends('tenant.layout')

@section('title', 'Cashier Shifts')

@section('content')
<header>
    <div>
        <h1>Cashier Shifts</h1>
        <div class="muted">Track register sessions, cash floats, sales by tender, and variance</div>
    </div>
    <div>
        @if(!$currentOpenShift)
            <a href="{{ route('tenant.shifts.create') }}" class="btn">Open New Shift</a>
        @else
            <a href="{{ route('tenant.shifts.show', $currentOpenShift) }}" class="btn" style="background: #0284c7;">Active Shift in Progress</a>
        @endif
    </div>
</header>

@if($currentOpenShift)
    <div class="panel" style="background: #f0fdf4; border-color: #bbf7d0; margin-bottom: 24px;">
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <div>
                <span class="badge badge-success">Your Open Shift</span>
                <h3 style="margin: 8px 0 4px; font-size: 18px;">{{ $currentOpenShift->shift_number }}</h3>
                <div class="muted">Shop: {{ $currentOpenShift->shop->name }} | Register: {{ $currentOpenShift->register->name }} | Float: KES {{ number_format((float) $currentOpenShift->opening_float, 2) }}</div>
            </div>
            <div style="display: flex; gap: 8px;">
                <a href="{{ route('tenant.pos.checkout') }}" class="btn">Go to POS</a>
                <a href="{{ route('tenant.shifts.show', $currentOpenShift) }}" class="btn btn-secondary">Close / Manage Shift</a>
            </div>
        </div>
    </div>
@endif

<div class="panel" style="margin-bottom: 20px;">
    <form action="{{ route('tenant.shifts.index') }}" method="GET" style="display: flex; gap: 12px; flex-wrap: wrap; align-items: flex-end;">
        <div>
            <label for="status" style="font-size: 12px;">Status</label>
            <select id="status" name="status">
                <option value="">All Shifts</option>
                <option value="open" {{ request('status') === 'open' ? 'selected' : '' }}>Open</option>
                <option value="closed" {{ request('status') === 'closed' ? 'selected' : '' }}>Closed</option>
            </select>
        </div>
        <div>
            <label for="shop_id" style="font-size: 12px;">Shop</label>
            <select id="shop_id" name="shop_id">
                <option value="">All Shops</option>
                @foreach($shops as $s)
                    <option value="{{ $s->id }}" {{ request('shop_id') == $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <button type="submit" class="btn btn-secondary">Filter</button>
            @if(request()->hasAny(['status', 'shop_id']))
                <a href="{{ route('tenant.shifts.index') }}" class="btn btn-secondary" style="margin-left: 4px;">Reset</a>
            @endif
        </div>
    </form>
</div>

<div class="table-container">
    <table>
        <thead>
            <tr>
                <th>Shift #</th>
                <th>Shop</th>
                <th>Register</th>
                <th>Cashier</th>
                <th style="text-align: right;">Opening Float</th>
                <th style="text-align: right;">Total Sales</th>
                <th>Status</th>
                <th>Opened At</th>
                <th style="text-align: right;">Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($shifts as $shift)
                @php
                    $totalSales = bcadd(bcadd((string) $shift->cash_sales, (string) $shift->mpesa_sales, 2), (string) $shift->card_sales, 2);
                @endphp
                <tr>
                    <td><strong>{{ $shift->shift_number }}</strong></td>
                    <td>{{ $shift->shop->name }}</td>
                    <td>{{ $shift->register->name }}</td>
                    <td>{{ $shift->user->name ?? 'Staff' }}</td>
                    <td style="text-align: right;">KES {{ number_format((float) $shift->opening_float, 2) }}</td>
                    <td style="text-align: right; font-weight: 650; color: #166534;">KES {{ number_format((float) $totalSales, 2) }}</td>
                    <td>
                        @if($shift->status === 'open')
                            <span class="badge badge-success">Open</span>
                        @else
                            <span class="badge badge-secondary">Closed</span>
                        @endif
                    </td>
                    <td>{{ $shift->opened_at->format('Y-m-d H:i') }}</td>
                    <td style="text-align: right;">
                        <a href="{{ route('tenant.shifts.show', $shift) }}" class="btn btn-secondary btn-sm">Details</a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" style="text-align: center; color: #64748b; padding: 24px;">No shifts found.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div style="margin-top: 16px;">
    {{ $shifts->links() }}
</div>
@endsection
