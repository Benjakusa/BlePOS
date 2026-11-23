@extends('platform.layout')

@section('title', $module->name . ' · Module Details')

@section('content')
<header>
    <div>
        <h1>{{ $module->name }}</h1>
        <div class="muted">Code: <code>{{ $module->slug }}</code> | Category: {{ $module->category }} | Version: <code>v{{ $module->version }}</code></div>
    </div>
    <div>
        <a href="{{ route('platform.modules.index') }}" class="btn btn-secondary">Back to Catalogue</a>
    </div>
</header>

<div class="panel" style="max-width: 100%; margin-bottom: 24px;">
    <h2 style="font-size: 16px; margin-top: 0; margin-bottom: 12px;">Module Overview</h2>
    <p>{{ $module->description }}</p>

    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 18px;">
        <div style="border: 1px solid #e2e8f0; padding: 14px; border-radius: 6px;">
            <h3 style="font-size: 14px; margin-top: 0; margin-bottom: 8px;">Prerequisite Dependencies (Required before enabling)</h3>
            @forelse($module->dependencies ?? [] as $dep)
                <div style="padding: 4px 0;"><span class="badge badge-info">{{ $dep }}</span></div>
            @empty
                <div class="muted" style="font-size: 13px;">No dependencies required. Can be enabled independently.</div>
            @endforelse
        </div>

        <div style="border: 1px solid #e2e8f0; padding: 14px; border-radius: 6px;">
            <h3 style="font-size: 14px; margin-top: 0; margin-bottom: 8px;">Dependent Modules (Cannot disable if active)</h3>
            @forelse($dependents as $depMod)
                <div style="padding: 4px 0;">
                    <a href="{{ route('platform.modules.show', $depMod) }}"><strong>{{ $depMod->name }}</strong></a> (<code>{{ $depMod->slug }}</code>)
                </div>
            @empty
                <div class="muted" style="font-size: 13px;">No other modules depend on this module.</div>
            @endforelse
        </div>
    </div>
</div>

<div class="panel" style="max-width: 100%;">
    <h2 style="font-size: 16px; margin-top: 0; margin-bottom: 14px;">Active Tenants with Entitlement ({{ $module->entitlements->count() }})</h2>
    <table>
        <thead>
            <tr>
                <th>Tenant Name</th>
                <th>Business Code</th>
                <th>Industry</th>
                <th>Enabled At</th>
                <th>Status</th>
                <th style="text-align: right;">Action</th>
            </tr>
        </thead>
        <tbody>
            @forelse($module->entitlements as $ent)
                <tr>
                    <td><strong>{{ $ent->tenant->name }}</strong></td>
                    <td><code>{{ $ent->tenant->code }}</code></td>
                    <td>{{ ucfirst($ent->tenant->industry) }}</td>
                    <td>{{ $ent->enabled_at ? $ent->enabled_at->format('Y-m-d H:i') : '—' }}</td>
                    <td><span class="badge badge-success">Active</span></td>
                    <td style="text-align: right;">
                        <a href="{{ route('platform.tenants.show', $ent->tenant) }}" class="btn btn-secondary btn-sm">Inspect Tenant</a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" style="text-align: center; color: #64748b; padding: 20px;">No tenants currently have this module enabled.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
