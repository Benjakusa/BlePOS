@extends('tenant.layout')

@section('title', 'Stock Movement Audit Ledger')

@section('content')
<header>
    <div>
        <h1>Stock Movement Audit Ledger</h1>
        <div class="muted">Immutable cryptographic record of every inventory change across all branches</div>
    </div>
    <div style="display: flex; gap: 8px;">
        <a href="{{ route('tenant.inventory.index') }}" class="btn btn-secondary">Stock Levels</a>
        <a href="{{ route('tenant.inventory.adjust') }}" class="btn btn-secondary">New Adjustment</a>
    </div>
</header>

<div class="panel" style="margin-bottom: 20px;">
    <form action="{{ route('tenant.inventory.movements') }}" method="GET" style="display: flex; gap: 12px; flex-wrap: wrap; align-items: flex-end;">
        <div style="min-width: 180px;">
            <x-input type="select" name="shop_id" label="Shop" :selected="request('shop_id')">
                <option value="">All Shops</option>
                @foreach($shops as $s)
                    <option value="{{ $s->id }}" {{ request('shop_id') == $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
                @endforeach
            </x-input>
        </div>
        <div style="min-width: 180px;">
            <x-input type="select" name="type" label="Movement Type" :selected="request('type')">
                <option value="">All Types</option>
                <option value="sale" {{ request('type') === 'sale' ? 'selected' : '' }}>Sale</option>
                <option value="purchase" {{ request('type') === 'purchase' ? 'selected' : '' }}>Purchase / Receive</option>
                <option value="adjustment" {{ request('type') === 'adjustment' ? 'selected' : '' }}>Adjustment</option>
                <option value="damage" {{ request('type') === 'damage' ? 'selected' : '' }}>Damage</option>
                <option value="waste" {{ request('type') === 'waste' ? 'selected' : '' }}>Waste</option>
                <option value="return" {{ request('type') === 'return' ? 'selected' : '' }}>Return</option>
                <option value="void_return" {{ request('type') === 'void_return' ? 'selected' : '' }}>Void Reversal</option>
            </x-input>
        </div>
        <div>
            <button type="submit" class="btn btn-secondary">Filter</button>
            @if(request()->hasAny(['shop_id', 'type']))
                <a href="{{ route('tenant.inventory.movements') }}" class="btn btn-secondary" style="margin-left: 4px;">Reset</a>
            @endif
        </div>
    </form>
</div>

<div class="table-container">
    <table>
        <thead>
            <tr>
                <th>Date / Time</th>
                <th>Shop</th>
                <th>Product</th>
                <th>Type</th>
                <th style="text-align: right;">Before</th>
                <th style="text-align: right;">Change</th>
                <th style="text-align: right;">After</th>
                <th>User</th>
                <th>Reason / Reference</th>
            </tr>
        </thead>
        <tbody>
            @forelse($movements as $m)
                <tr>
                    <td style="font-size: 13px; color: #64748b;">{{ $m->created_at->format('Y-m-d H:i:s') }}</td>
                    <td>{{ $m->shop->name }}</td>
                    <td><strong>{{ $m->product->name }}</strong><br><small class="muted">{{ $m->product->sku }}</small></td>
                    <td>
                        @if($m->type === 'sale')
                            <span class="badge badge-secondary">Sale</span>
                        @elseif($m->type === 'purchase')
                            <span class="badge badge-success">Receive</span>
                        @elseif($m->type === 'void_return')
                            <span class="badge badge-info">Void Return</span>
                        @elseif(in_array($m->type, ['damage', 'waste']))
                            <span class="badge badge-danger">{{ ucfirst($m->type) }}</span>
                        @else
                            <span class="badge badge-warning">{{ ucfirst($m->type) }}</span>
                        @endif
                    </td>
                    <td style="text-align: right;">{{ number_format((float) $m->balance_before, 2) }}</td>
                    <td style="text-align: right; font-weight: 700; color: {{ (float) $m->quantity >= 0 ? 'var(--clr-green)' : '#dc2626' }};">
                        {{ (float) $m->quantity >= 0 ? '+' : '' }}{{ number_format((float) $m->quantity, 2) }}
                    </td>
                    <td style="text-align: right; font-weight: 650;">{{ number_format((float) $m->balance_after, 2) }}</td>
                    <td>{{ $m->user->name ?? 'System' }}</td>
                    <td>
                        {{ $m->reason ?: '—' }}
                        @if($m->reference_type)
                            <br><small class="muted">{{ $m->reference_type }} #{{ $m->reference_id }}</small>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" style="text-align: center; color: #64748b; padding: 24px;">No stock movements recorded.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div style="margin-top: 16px;">
    {{ $movements->links() }}
</div>
@endsection
