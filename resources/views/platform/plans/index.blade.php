@extends('platform.layout')

@section('title', 'Plans Catalogue')

@section('content')
<header>
    <div>
        <h1>Subscription Plans</h1>
        <div class="muted">Manage tiers, feature entitlements, pricing, and branch quotas</div>
    </div>
    <div>
        @if(auth('platform')->user()->hasPermission('platform.billing.plans.manage'))
            <a href="{{ route('platform.plans.create') }}" class="btn">New Plan</a>
        @endif
    </div>
</header>

<div class="panel" style="max-width: 100%;">
    <table>
        <thead>
            <tr>
                <th>Code</th>
                <th>Plan Name</th>
                <th style="text-align: right;">Monthly Price</th>
                <th style="text-align: right;">Annual Price</th>
                <th style="text-align: center;">Trial</th>
                <th>Quotas (Shops / Users / Tills)</th>
                <th style="text-align: center;">Subscribers</th>
                <th>Status</th>
                <th style="text-align: right;">Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($plans as $plan)
                <tr>
                    <td><code>{{ $plan->code }}</code></td>
                    <td><strong>{{ $plan->name }}</strong></td>
                    <td style="text-align: right;">{{ $plan->currency }} {{ number_format((float) $plan->monthly_price, 2) }}</td>
                    <td style="text-align: right;">{{ $plan->currency }} {{ number_format((float) $plan->annual_price, 2) }}</td>
                    <td style="text-align: center;">{{ $plan->trial_days }} days</td>
                    <td>
                        <small class="muted">
                            Max Shops: <strong>{{ $plan->max_shops }}</strong> |
                            Users: <strong>{{ $plan->max_users }}</strong> |
                            Registers: <strong>{{ $plan->max_registers }}</strong>
                        </small>
                    </td>
                    <td style="text-align: center;">
                        <span class="badge badge-info">{{ $plan->active_subscribers_count }}</span>
                    </td>
                    <td>
                        @if($plan->is_active)
                            <span class="badge badge-success">Active</span>
                        @else
                            <span class="badge badge-secondary">Inactive</span>
                        @endif
                    </td>
                    <td style="text-align: right;">
                        <div class="actions" style="justify-content: flex-end;">
                            @if(auth('platform')->user()->hasPermission('platform.billing.plans.manage'))
                                <a href="{{ route('platform.plans.edit', $plan) }}" class="btn btn-secondary btn-sm">Edit</a>
                                <form action="{{ route('platform.plans.toggle', $plan) }}" method="POST" style="display: inline;">
                                    @csrf
                                    <button type="submit" class="btn btn-secondary btn-sm">
                                        {{ $plan->is_active ? 'Deactivate' : 'Activate' }}
                                    </button>
                                </form>
                            @endif
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" style="text-align: center; color: #64748b; padding: 24px;">No subscription plans created yet.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div style="margin-top: 16px;">
        {{ $plans->links() }}
    </div>
</div>
@endsection
