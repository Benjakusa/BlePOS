@extends('tenant.layout')

@section('title', 'Sales History')

@section('content')
<div class="card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 16px;">
        <div>
            <h2 class="section-title" style="font-size: 18px;">Sales History</h2>
            <p class="muted" style="margin-top: 2px;">All sales transactions, receipts, and order statuses</p>
        </div>
        <div>
            <a href="{{ route('tenant.pos.checkout') }}" class="btn btn-primary" aria-label="New POS Sale">
                <i class="bi bi-cart-plus"></i>
                <span>New POS Sale</span>
            </a>
        </div>
    </div>

    <form action="{{ route('tenant.pos.sales') }}" method="GET" style="display: flex; gap: 12px; flex-wrap: wrap; align-items: end; margin-bottom: 24px;">
        <div style="min-width: 160px;">
            <x-input type="select" name="status" label="Status" :selected="request('status')">
                <option value="">All Statuses</option>
                <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Completed</option>
                <option value="held" {{ request('status') === 'held' ? 'selected' : '' }}>Held</option>
                <option value="voided" {{ request('status') === 'voided' ? 'selected' : '' }}>Voided</option>
            </x-input>
        </div>
        <div style="min-width: 160px;">
            <x-input type="date" name="date_from" label="From Date" :value="request('date_from')" />
        </div>
        <div style="min-width: 160px;">
            <x-input type="date" name="date_to" label="To Date" :value="request('date_to')" />
        </div>
        <button type="submit" class="btn btn-secondary">
            <i class="bi bi-funnel"></i>
            <span>Filter</span>
        </button>
        @if(request()->hasAny(['status', 'date_from', 'date_to']))
            <a href="{{ route('tenant.pos.sales') }}" class="btn btn-ghost" aria-label="Reset filters">
                <i class="bi bi-x-circle"></i>
                <span>Reset</span>
            </a>
        @endif
    </form>

    <div class="table-container">
        <div class="table-scroll">
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
                            <td title="{{ $sale->sale_number }}">
                                <strong>{{ $sale->sale_number }}</strong>
                            </td>
                            <td title="{{ $sale->created_at->format('Y-m-d H:i') }}" class="muted">
                                {{ $sale->created_at->format('Y-m-d H:i') }}
                            </td>
                            <td title="{{ $sale->register?->name ?? 'Default' }}">
                                {{ $sale->register?->name ?? 'Default' }}
                            </td>
                            <td title="{{ $sale->user?->name ?? 'Staff' }}">
                                {{ $sale->user?->name ?? 'Staff' }}
                            </td>
                            <td style="text-align: right; font-weight: 600;" class="{{ $sale->status === 'voided' ? 'text-red' : 'text-green' }}" title="KES {{ number_format((float) $sale->grand_total, 2) }}">
                                KES {{ number_format((float) $sale->grand_total, 2) }}
                            </td>
                            <td>
                                @if($sale->status === 'completed')
                                    <span class="badge badge-success">
                                        <i class="bi bi-check-circle"></i>
                                        Completed
                                    </span>
                                @elseif($sale->status === 'voided')
                                    <span class="badge badge-danger">
                                        <i class="bi bi-x-circle"></i>
                                        Voided
                                    </span>
                                @else
                                    <span class="badge badge-warning">
                                        <i class="bi bi-clock"></i>
                                        {{ ucfirst($sale->status) }}
                                    </span>
                                @endif
                            </td>
                            <td style="text-align: right;">
                                <div class="table-actions" style="justify-content: flex-end;">
                                    <a href="{{ route('tenant.pos.receipt', $sale) }}" class="btn-ghost" target="_blank" data-tooltip="Print Receipt" aria-label="Print Receipt">
                                        <i class="bi bi-receipt"></i>
                                    </a>
                                    @if($sale->status === 'completed')
                                        <form action="{{ route('tenant.pos.void', $sale) }}" method="POST" onsubmit="return prompt('Enter void reason:') ? true : false" style="margin: 0;">
                                            @csrf
                                            <input type="hidden" name="void_reason" value="Void requested by supervisor">
                                            <button type="submit" class="btn-ghost danger" data-tooltip="Void Sale" aria-label="Void Sale">
                                                <i class="bi bi-slash-circle"></i>
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" style="text-align: center; padding: 32px;" class="muted">
                                <i class="bi bi-receipt" style="font-size: 20px; display: block; margin-bottom: 8px;"></i>
                                No sales recorded yet.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div style="margin-top: 20px;">
        {{ $sales->links() }}
    </div>
</div>
@endsection
