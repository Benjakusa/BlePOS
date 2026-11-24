@extends('tenant.layout')

@section('title', 'Sales History')

@section('content')
<header>
    <div>
        <h1>Sales History</h1>
        <div class="muted">All sales transactions, receipts, and order statuses</div>
    </div>
    <div>
        <a href="{{ route('tenant.pos.checkout') }}" class="btn">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="14" x="2" y="3" rx="2"/><line x1="8" x2="16" y1="21" y2="21"/><line x1="12" x2="12" y1="17" y2="21"/></svg>
            <span>New POS Sale</span>
        </a>
    </div>
</header>

<div class="panel" style="margin-bottom: 20px;">
    <form action="{{ route('tenant.pos.sales') }}" method="GET" style="display: flex; gap: 12px; flex-wrap: wrap; align-items: flex-end;">
        <div>
            <label for="status" style="font-size: 12px;">Status</label>
            <select id="status" name="status">
                <option value="">All Statuses</option>
                <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Completed</option>
                <option value="held" {{ request('status') === 'held' ? 'selected' : '' }}>Held</option>
                <option value="voided" {{ request('status') === 'voided' ? 'selected' : '' }}>Voided</option>
            </select>
        </div>
        <div>
            <label for="date_from" style="font-size: 12px;">From Date</label>
            <input type="date" id="date_from" name="date_from" value="{{ request('date_from') }}">
        </div>
        <div>
            <label for="date_to" style="font-size: 12px;">To Date</label>
            <input type="date" id="date_to" name="date_to" value="{{ request('date_to') }}">
        </div>
        <div>
            <button type="submit" class="btn">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/></svg>
                <span>Filter</span>
            </button>
            @if(request()->hasAny(['status', 'date_from', 'date_to']))
                <a href="{{ route('tenant.pos.sales') }}" class="btn btn-secondary" style="margin-left: 4px;">Reset</a>
            @endif
        </div>
    </form>
</div>

<div class="table-container">
    <table>
        <thead>
            <tr>
                <th>Sale #</th>
                <th>Date / Time</th>
                <th>Register</th>
                <th>Cashier</th>
                <th style="text-align: right;">Grand Total</th>
                <th>Status</th>
                <th style="text-align: right;">Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($sales as $sale)
                <tr>
                    <td><strong>{{ $sale->sale_number }}</strong></td>
                    <td>{{ $sale->created_at->format('Y-m-d H:i') }}</td>
                    <td>{{ $sale->register?->name ?? 'Default' }}</td>
                    <td>{{ $sale->user?->name ?? 'Staff' }}</td>
                    <td style="text-align: right; font-weight: 700; color: #166534;">
                        KES {{ number_format((float) $sale->grand_total, 2) }}
                    </td>
                    <td>
                        @if($sale->status === 'completed')
                            <span class="badge badge-success">Completed</span>
                        @elseif($sale->status === 'voided')
                            <span class="badge badge-danger">Voided</span>
                        @else
                            <span class="badge badge-warning">{{ ucfirst($sale->status) }}</span>
                        @endif
                    </td>
                    <td style="text-align: right;">
                        <div class="actions" style="justify-content: flex-end;">
                            <a href="{{ route('tenant.pos.receipt', $sale) }}" class="btn btn-secondary btn-sm" target="_blank">Receipt</a>
                            @if($sale->status === 'completed')
                                <form action="{{ route('tenant.pos.void', $sale) }}" method="POST" onsubmit="return prompt('Enter void reason:') ? true : false">
                                    @csrf
                                    <input type="hidden" name="void_reason" value="Void requested by supervisor">
                                    <button type="submit" class="btn btn-danger btn-sm">Void</button>
                                </form>
                            @endif
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" style="text-align: center; color: #64748b; padding: 24px;">No sales recorded yet.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div style="margin-top: 16px;">
    {{ $sales->links() }}
</div>
@endsection
