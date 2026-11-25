@extends('platform.layout')

@section('title', 'Tenant Assist · ' . $tenant->name)

@section('content')
<header>
    <div>
        <h1>Tenant Assist Diagnostics: {{ $tenant->name }}</h1>
        <div class="muted">Technical metadata, system health counters, and non-impersonating diagnostic tools (Annex A 13.2 & 13.3)</div>
    </div>
    <div style="display: flex; gap: 8px;">
        <a href="{{ route('platform.support.cases.index') }}" class="btn secondary">Back to Support</a>
        <a href="{{ route('platform.tenants.show', $tenant) }}" class="btn secondary">Tenant Details</a>
    </div>
</header>

@if(session('notice'))
    <div class="notice" role="alert">{{ session('notice') }}</div>
@endif

<div class="notice" style="background: #EAEEF3; border-color: #CBD5E1; color: var(--clr-green); margin-bottom: 24px;">
    <strong>No-Impersonation Security Policy (Annex A 13.3):</strong>
    BlePOS strictly forbids "Login as Tenant" or administrative impersonation. All support assistance is performed through audited telemetry, metadata counters, and diagnostic health checks.
</div>

<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 20px;">
    <div>
        <div class="panel" style="margin-bottom: 20px;">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <h2>Data Integrity & Consistency Check</h2>
                <form method="POST" action="{{ route('platform.support.assist.integrity', $tenant) }}" style="display: inline;">
                    @csrf
                    <button type="submit" class="btn" style="padding: 6px 12px; font-size: 13px;">Run Integrity Check</button>
                </form>
            </div>

            <div style="display: flex; gap: 16px; margin: 16px 0;">
                <div style="background: #EAEEF3; border: 1px solid #CBD5E1; border-radius: 6px; padding: 12px 20px; text-align: center;">
                    <div style="font-size: 20px; font-weight: bold; color: var(--clr-green);">{{ $integrity['pass_count'] }}</div>
                    <div style="font-size: 12px; color: var(--clr-green-hover);">Checks Passed</div>
                </div>
                <div style="background: {{ $integrity['fail_count'] > 0 ? '#EAEEF3' : 'var(--clr-grey-tint)' }}; border: 1px solid {{ $integrity['fail_count'] > 0 ? '#DC2626' : '#e2e8f0' }}; border-radius: 6px; padding: 12px 20px; text-align: center;">
                    <div style="font-size: 20px; font-weight: bold; color: {{ $integrity['fail_count'] > 0 ? 'var(--clr-red)' : '#64748b' }};">{{ $integrity['fail_count'] }}</div>
                    <div style="font-size: 12px; color: {{ $integrity['fail_count'] > 0 ? '#b91c1c' : '#64748b' }};">Checks Failed</div>
                </div>
            </div>

            <table style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="border-bottom: 1px solid #e2e8f0;">
                        <th style="text-align: left; padding: 8px;">Integrity Test Area</th>
                        <th style="text-align: center; padding: 8px;">Result</th>
                        <th style="text-align: left; padding: 8px;">Diagnostic Message</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($integrity['checks'] as $area => $check)
                        <tr style="border-bottom: 1px solid #f1f5f9;">
                            <td style="padding: 10px 8px;"><strong>{{ strtoupper($area) }}</strong></td>
                            <td style="text-align: center; padding: 10px 8px;">
                                <span class="badge" style="background: {{ $check['status'] === 'PASS' ? '#EAEEF3' : '#EAEEF3' }}; color: {{ $check['status'] === 'PASS' ? 'var(--clr-green)' : 'var(--clr-red)' }};">
                                    {{ $check['status'] }}
                                </span>
                            </td>
                            <td style="padding: 10px 8px; color: #475569;">{{ $check['message'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="panel">
            <h2>Shops & Registers Telemetry</h2>
            <ul style="padding-left: 20px; margin-top: 10px;">
                @forelse($tenant->shops as $shop)
                    <li style="margin-bottom: 8px;">
                        <strong>{{ $shop->name }}</strong> (Code: <code>{{ $shop->code }}</code>)
                        &mdash; {{ $shop->registers->count() }} POS Till(s) configured.
                    </li>
                @empty
                    <li class="muted">No shop branches registered.</li>
                @endforelse
            </ul>
        </div>
    </div>

    <div>
        <div class="panel" style="margin-bottom: 20px;">
            <h3>Tenant Core Metadata</h3>
            <p><strong>Organization:</strong> {{ $tenant->name }}</p>
            <p><strong>Slug:</strong> <code>{{ $tenant->slug }}</code></p>
            <p><strong>Status:</strong> <span class="badge">{{ strtoupper($tenant->status) }}</span></p>
            <p><strong>Provisioned At:</strong> {{ $tenant->provisioned_at?->format('Y-m-d H:i') ?? 'Pending' }}</p>
            <p><strong>Registered Domain:</strong> {{ $tenant->primaryDomain()?->domain ?? 'None' }}</p>
            @php
                $activeSub = $tenant->subscriptions->whereIn('status', ['active', 'trial'])->first();
            @endphp
            <p><strong>Active Plan:</strong> {{ $activeSub ? $activeSub->plan->name : 'No Active Subscription' }}</p>
            <p><strong>Enabled Modules:</strong> {{ $tenant->entitlements->where('status', 'enabled')->count() }} active</p>
        </div>

        <div class="panel">
            <h3>Quick Actions</h3>
            <div style="display: flex; flex-direction: column; gap: 8px; margin-top: 10px;">
                <a href="{{ route('platform.support.cases.create', ['tenant_id' => $tenant->id]) }}" class="btn secondary" style="text-align: center;">Open Ticket for Tenant</a>
                <a href="{{ route('platform.invoices.create', ['tenant_id' => $tenant->id]) }}" class="btn secondary" style="text-align: center;">Issue In-Flight Invoice</a>
            </div>
        </div>
    </div>
</div>
@endsection
