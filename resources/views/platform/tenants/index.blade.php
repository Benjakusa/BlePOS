@extends('platform.layout')

@section('title', 'Tenants & Businesses')

@section('content')
<section class="panel" style="max-width: 100%;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <div>
            <h2>Registered Tenants</h2>
            <p class="muted">Active commercial businesses provisioned on the BlePOS SaaS platform.</p>
        </div>
        @if(auth('platform')->user()->hasPermission('platform.tenants.create'))
            <a href="{{ route('platform.tenants.create') }}" style="background: #146b4d; color: #fff; text-decoration: none; padding: 10px 16px; border-radius: 6px; font-weight: 600;">+ Register New Tenant</a>
        @endif
    </div>

    <form method="get" action="{{ route('platform.tenants.index') }}" style="display: flex; gap: 12px; margin-bottom: 20px;">
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Search by name, tenant code, owner email..." style="max-width: 320px;">
        <select name="status" style="border: 1px solid #b9c9bf; border-radius: 5px; padding: 11px 12px; font: inherit;">
            <option value="">All Statuses ({{ $statusCounts['all'] }})</option>
            <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active ({{ $statusCounts['active'] }})</option>
            <option value="trial" {{ request('status') === 'trial' ? 'selected' : '' }}>Trial ({{ $statusCounts['trial'] }})</option>
            <option value="suspended" {{ request('status') === 'suspended' ? 'selected' : '' }}>Suspended ({{ $statusCounts['suspended'] }})</option>
        </select>
        <button type="submit">Filter</button>
    </form>

    <table style="width: 100%; border-collapse: collapse; text-align: left;">
        <thead>
            <tr style="border-bottom: 2px solid #dce5df; color: #63736b;">
                <th style="padding: 10px 12px;">Tenant Code</th>
                <th style="padding: 10px 12px;">Legal Name</th>
                <th style="padding: 10px 12px;">Industry</th>
                <th style="padding: 10px 12px;">Status</th>
                <th style="padding: 10px 12px;">Shops</th>
                <th style="padding: 10px 12px;">Primary Domain</th>
                <th style="padding: 10px 12px;">Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($tenants as $tenant)
                <tr style="border-bottom: 1px solid #eef2ef;">
                    <td style="padding: 12px;"><code>{{ $tenant->code }}</code></td>
                    <td style="padding: 12px; font-weight: 600;">
                        <a href="{{ route('platform.tenants.show', $tenant) }}">{{ $tenant->name }}</a>
                    </td>
                    <td style="padding: 12px;">{{ ucfirst(str_replace('_', ' ', $tenant->industry)) }}</td>
                    <td style="padding: 12px;">
                        <span style="padding: 4px 8px; border-radius: 4px; font-size: 13px; font-weight: 600; {{ $tenant->status === 'active' ? 'background: #e7f4eb; color: #146b4d;' : ($tenant->status === 'suspended' ? 'background: #fde8e8; color: #a32121;' : 'background: #fff8e1; color: #b78103;') }}">
                            {{ ucfirst($tenant->status) }}
                        </span>
                    </td>
                    <td style="padding: 12px;">{{ $tenant->shops_count }}</td>
                    <td style="padding: 12px;"><code>{{ $tenant->primaryDomain()?->domain ?? 'None' }}</code></td>
                    <td style="padding: 12px;">
                        <a href="{{ route('platform.tenants.show', $tenant) }}">Manage</a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" style="padding: 30px; text-align: center; color: #63736b;">No tenants found matching criteria.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div style="margin-top: 20px;">
        {{ $tenants->links() }}
    </div>
</section>
@endsection
