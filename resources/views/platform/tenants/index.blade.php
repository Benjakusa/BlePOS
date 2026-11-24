@extends('platform.layout')

@section('title', 'Tenants & Businesses')

@section('content')
<div class="card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 16px;">
        <div>
            <h2 class="section-title" style="font-size: 18px;">Registered Tenants</h2>
            <p class="muted" style="margin-top: 2px;">Active commercial businesses provisioned on the BlePOS SaaS platform.</p>
        </div>
        @if(auth('platform')->user()->hasPermission('platform.tenants.create'))
            <a href="{{ route('platform.tenants.create') }}" class="btn btn-primary" aria-label="Register New Tenant">
                <i class="bi bi-plus-lg"></i>
                <span>Register New Tenant</span>
            </a>
        @endif
    </div>

    <form method="get" action="{{ route('platform.tenants.index') }}" style="display: flex; gap: 12px; margin-bottom: 24px; flex-wrap: wrap; align-items: flex-start;">
        <div style="flex: 1; min-width: 260px; max-width: 400px;">
            <x-input
                type="search"
                name="search"
                label="Search by name, tenant code, owner email"
                :value="request('search')"
                wrapperClass="mb-0"
            />
        </div>
        <div style="min-width: 200px;">
            <x-input type="select" name="status" label="Status" wrapperClass="mb-0">
                <option value="">All Statuses ({{ $statusCounts['all'] }})</option>
                <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active ({{ $statusCounts['active'] }})</option>
                <option value="trial" {{ request('status') === 'trial' ? 'selected' : '' }}>Trial ({{ $statusCounts['trial'] }})</option>
                <option value="suspended" {{ request('status') === 'suspended' ? 'selected' : '' }}>Suspended ({{ $statusCounts['suspended'] }})</option>
            </x-input>
        </div>
        <button type="submit" class="btn btn-secondary" style="height: 40px;">
            <i class="bi bi-funnel"></i>
            <span>Filter</span>
        </button>
        @if(request('search') || request('status'))
            <a href="{{ route('platform.tenants.index') }}" class="btn btn-ghost" aria-label="Clear filters" style="height: 40px;">
                <i class="bi bi-x-circle"></i>
                <span>Clear</span>
            </a>
        @endif
    </form>

    <div class="table-container">
        <div class="table-scroll">
            <table>
                <thead>
                    <tr>
                        <th>Tenant Code</th>
                        <th>Legal Name</th>
                        <th>Industry</th>
                        <th>Status</th>
                        <th>Shops</th>
                        <th>Primary Domain</th>
                        <th style="text-align: right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($tenants as $tenant)
                        <tr>
                            <td title="{{ $tenant->code }}">
                                <code style="background: var(--clr-grey-tint); padding: 3px 6px; border-radius: var(--radius-sm); font-size: 12px;">{{ $tenant->code }}</code>
                            </td>
                            <td title="{{ $tenant->name }}" style="font-weight: 600;">
                                <a href="{{ route('platform.tenants.show', $tenant) }}" class="text-blue">{{ $tenant->name }}</a>
                            </td>
                            <td title="{{ ucfirst(str_replace('_', ' ', $tenant->industry)) }}">
                                {{ ucfirst(str_replace('_', ' ', $tenant->industry)) }}
                            </td>
                            <td>
                                <span class="badge {{ $tenant->status === 'active' ? 'badge-success' : ($tenant->status === 'suspended' ? 'badge-danger' : 'badge-warning') }}">
                                    <i class="bi bi-{{ $tenant->status === 'active' ? 'check-circle' : ($tenant->status === 'suspended' ? 'x-circle' : 'clock') }}"></i>
                                    {{ ucfirst($tenant->status) }}
                                </span>
                            </td>
                            <td title="{{ $tenant->shops_count }} shop(s)">{{ $tenant->shops_count }}</td>
                            <td title="{{ $tenant->primaryDomain()?->domain ?? 'None' }}">
                                <code>{{ $tenant->primaryDomain()?->domain ?? 'None' }}</code>
                            </td>
                            <td style="text-align: right;">
                                <div class="table-actions" style="justify-content: flex-end;">
                                    <a href="{{ route('platform.tenants.show', $tenant) }}" class="btn-ghost" data-tooltip="Manage Tenant" aria-label="Manage Tenant">
                                        <i class="bi bi-sliders"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" style="padding: 32px; text-align: center;" class="muted">
                                <i class="bi bi-inbox" style="font-size: 28px; display: block; margin-bottom: 8px;"></i>
                                No tenants found matching criteria.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div style="margin-top: 20px;">
        {{ $tenants->links() }}
    </div>
</div>
@endsection
