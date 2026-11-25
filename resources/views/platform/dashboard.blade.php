@extends('platform.layout')

@section('title', 'Platform Control Plane Dashboard')

@section('content')

{{-- Top Header --}}
<div class="page-header" style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 24px; flex-wrap: wrap; gap: 16px;">
    <div>
        <div style="display: flex; align-items: center; gap: 12px;">
            <h1 class="page-title">Platform Control Plane</h1>
            <span class="badge badge-success">
                <i class="bi bi-shield-check"></i>
                <span>Super Admin</span>
            </span>
        </div>
        <p class="muted" style="margin-top: 4px;">Executive oversight, real-time subsystem diagnostics, and multi-tenant operational status</p>
    </div>

    <div style="display: flex; gap: 10px; align-items: center;">
        <a href="{{ route('platform.tenants.create') }}" class="btn btn-primary" aria-label="Register Tenant">
            <i class="bi bi-plus-lg"></i>
            <span>Register Tenant</span>
        </a>
        <a href="{{ route('platform.analytics.index') }}" class="btn btn-secondary" aria-label="View Analytics">
            <i class="bi bi-bar-chart-line"></i>
            <span>Analytics</span>
        </a>
    </div>
</div>

@if (session('platform_recovery_codes'))
    <div class="alert alert-warning" style="margin-bottom: 24px;">
        <i class="bi bi-key-fill" style="font-size: 20px;"></i>
        <div>
            <strong>Save your recovery codes:</strong> These codes are shown once. Each code works once and resets your authenticator enrollment.
            <div style="margin-top: 8px; display: flex; flex-wrap: wrap; gap: 8px;">
                @foreach(session('platform_recovery_codes') as $code)
                    <code style="background: var(--clr-surface); padding: 4px 8px; border-radius: 4px; font-weight: 600;">{{ $code }}</code>
                @endforeach
            </div>
        </div>
    </div>
@endif

<!-- Needs-Attention Alerts (Row P) — Strict: NO pale red or pale green backgrounds -->
@php
    $hasAlerts = ($growth['provisioning_failed'] > 0)
        || ($security['metrics']['active_incidents'] > 0)
        || ($compliance['metrics']['dpa_overdue'] > 0)
        || ($pendingApprovals->isNotEmpty())
        || ($health['overall'] !== 'healthy');
@endphp

@if($hasAlerts)
    <div style="display: grid; gap: 12px; margin-bottom: 24px;">
        @if($growth['provisioning_failed'] > 0)
            <div class="alert alert-danger" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0;">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <i class="bi bi-exclamation-octagon" style="font-size: 18px;"></i>
                    <span><strong>Provisioning Failure Alert:</strong> {{ $growth['provisioning_failed'] }} tenant(s) encountered failed provisioning runs requiring administrative recovery.</span>
                </div>
                <a href="{{ route('platform.provisioning.index') }}" class="btn btn-sm btn-danger">
                    <span>Review Provisioning &rarr;</span>
                </a>
            </div>
        @endif

        @if($security['metrics']['active_incidents'] > 0)
            <div class="alert alert-danger" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0;">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <i class="bi bi-shield-slash" style="font-size: 18px;"></i>
                    <span><strong>Security Incident Alert:</strong> {{ $security['metrics']['active_incidents'] }} open security incident(s) currently being investigated.</span>
                </div>
                <a href="{{ route('platform.security.index') }}" class="btn btn-sm btn-danger">
                    <span>Security Centre &rarr;</span>
                </a>
            </div>
        @endif

        @if($compliance['metrics']['dpa_overdue'] > 0)
            <div class="alert alert-danger" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0;">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <i class="bi bi-clock-history" style="font-size: 18px;"></i>
                    <span><strong>Statutory Deadline Alert:</strong> {{ $compliance['metrics']['dpa_overdue'] }} Data Protection DSAR request(s) have passed statutory response time limits.</span>
                </div>
                <a href="{{ route('platform.compliance.index') }}" class="btn btn-sm btn-danger">
                    <span>Compliance Console &rarr;</span>
                </a>
            </div>
        @endif

        @if($pendingApprovals->isNotEmpty())
            <div class="alert alert-info" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0;">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <i class="bi bi-person-check" style="font-size: 18px;"></i>
                    <span><strong>Two-Person Rule Signoff Required:</strong> {{ $pendingApprovals->count() }} high-risk administrative action(s) await secondary authorization.</span>
                </div>
                <a href="{{ route('platform.approvals.index') }}" class="btn btn-sm btn-secondary">
                    <span>Review Approvals &rarr;</span>
                </a>
            </div>
        @endif

        @if($health['overall'] !== 'healthy')
            <div class="alert alert-warning" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0;">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <i class="bi bi-activity" style="font-size: 18px;"></i>
                    <span><strong>Infrastructure Degraded:</strong> One or more subsystem health probes reported degraded or unhealthy responses.</span>
                </div>
                <a href="{{ route('platform.infrastructure.index') }}" class="btn btn-sm btn-secondary">
                    <span>Infrastructure Probes &rarr;</span>
                </a>
            </div>
        @endif
    </div>
@endif

<!-- eTIMS Technical Regulatory Status Banner -->
<div class="alert alert-info" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
    <div style="display: flex; align-items: center; gap: 10px;">
        <i class="bi bi-receipt" style="font-size: 18px;"></i>
        <div>
            <strong>eTIMS Fiscal Subsystem:</strong> Status is <span class="badge badge-warning">{{ $compliance['metrics']['etims_status'] }}</span>
            <span class="muted" style="margin-left: 8px;">(Regulatory compliance certified checklist tracking {{ $compliance['metrics']['cert_passed'] }}/{{ $compliance['metrics']['cert_total'] }} requirements)</span>
        </div>
    </div>
    <a href="{{ route('platform.compliance.index') }}" class="btn btn-sm btn-secondary">
        <span>Checklist &rarr;</span>
    </a>
</div>

<!-- Row A: Headline Executive KPIs (Section 6) -->
<div class="grid-cards">
    <!-- Tenants KPI -->
    <div class="stat-card">
        <div class="stat-icon green">
            <i class="bi bi-buildings"></i>
        </div>
        <div class="stat-body">
            <div class="stat-label">Tenants</div>
            <div class="stat-value" data-count-up="{{ $growth['total_tenants'] }}">{{ number_format($growth['total_tenants']) }}</div>
            <div class="stat-trend up">
                <i class="bi bi-check-circle"></i>
                <span class="text-green">{{ $growth['active_tenants'] }} active</span> &bull; <span class="{{ $growth['suspended_tenants'] > 0 ? 'text-red' : 'muted' }}">{{ $growth['suspended_tenants'] }} suspended</span>
            </div>
        </div>
    </div>

    <!-- Revenue KPI -->
    <div class="stat-card">
        <div class="stat-icon green">
            <i class="bi bi-currency-dollar"></i>
        </div>
        <div class="stat-body">
            <div class="stat-label">Monthly Recurring Revenue</div>
            <div class="stat-value text-green">KES {{ number_format((float) $revenue['mrr'], 2) }}</div>
            <div class="caption">ARR: KES {{ number_format((float) $revenue['arr'], 2) }} &bull; {{ $revenue['collection_rate'] }}% collected</div>
        </div>
    </div>

    <!-- Infrastructure & Probes KPI -->
    <div class="stat-card">
        <div class="stat-icon {{ $health['overall'] === 'healthy' ? 'green' : 'red' }}">
            <i class="bi bi-heart-pulse"></i>
        </div>
        <div class="stat-body">
            <div class="stat-label">Subsystems Health</div>
            <div class="stat-value {{ $health['overall'] === 'healthy' ? 'text-green' : 'text-red' }}">
                {{ strtoupper($health['overall']) }}
            </div>
            <div class="caption">DB: {{ $health['probes']['database']['latency_ms'] }}ms &bull; Cache: {{ $health['probes']['cache']['latency_ms'] }}ms</div>
        </div>
    </div>

    <!-- Support & Security KPI -->
    <div class="stat-card">
        <div class="stat-icon {{ $support['urgent_cases'] > 0 ? 'red' : 'blue' }}">
            <i class="bi bi-headset"></i>
        </div>
        <div class="stat-body">
            <div class="stat-label">Support Cases</div>
            <div class="stat-value">{{ $support['open_cases'] }} <span style="font-size: 14px; font-weight: normal; color: var(--clr-muted);">open</span></div>
            <div class="stat-trend {{ $support['urgent_cases'] > 0 ? 'down' : 'up' }}">
                <span class="{{ $support['urgent_cases'] > 0 ? 'text-red' : 'text-green' }}">{{ $support['urgent_cases'] }} urgent</span> &bull; {{ $security['metrics']['blocked_ips'] }} blocked IPs
            </div>
        </div>
    </div>
</div>

<!-- Row E, H & K: Operational Health, Subsystems & Backups -->
<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 20px; margin-bottom: 24px;">
    <!-- Live Health Probes -->
    <div class="card">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
            <h3 class="section-title">Live Subsystem Probes</h3>
            <span class="caption">Updated: {{ now()->format('H:i:s') }}</span>
        </div>
        <div class="table-container">
            <div class="table-scroll">
                <table>
                    <thead>
                        <tr>
                            <th>Subsystem</th>
                            <th>Status</th>
                            <th>Latency</th>
                            <th>Diagnostic Details</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($health['probes'] as $probeName => $probe)
                            <tr>
                                <td title="{{ ucfirst($probeName) }}"><strong>{{ ucfirst($probeName) }}</strong></td>
                                <td>
                                    <span class="badge {{ $probe['status'] === 'healthy' ? 'badge-success' : ($probe['status'] === 'degraded' ? 'badge-warning' : 'badge-danger') }}">
                                        <i class="bi bi-{{ $probe['status'] === 'healthy' ? 'check-circle' : 'exclamation-circle' }}"></i>
                                        {{ strtoupper($probe['status']) }}
                                    </span>
                                </td>
                                <td>{{ $probe['latency_ms'] }} ms</td>
                                <td title="@foreach($probe['details'] as $k => $v){{ $k }}: {{ is_scalar($v) ? $v : json_encode($v) }} @endforeach" class="muted">
                                    @foreach($probe['details'] as $k => $v)
                                        <span>{{ $k }}: <strong>{{ is_scalar($v) ? $v : json_encode($v) }}</strong></span>@if(!$loop->last), @endif
                                    @endforeach
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Backups & Migrations Summary -->
    <div class="card">
        <h3 class="section-title" style="margin-bottom: 16px;">Backups &amp; Migrations</h3>
        <div style="margin-bottom: 16px;">
            <div style="display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid var(--clr-header-tint);">
                <span class="muted">Migrations Ran:</span>
                <strong>{{ $migrationStatus['total_ran'] }} (Batch {{ $migrationStatus['last_batch'] }})</strong>
            </div>
            <div style="display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid var(--clr-header-tint);">
                <span class="muted">Recent Backups:</span>
                <strong>{{ $recentBackups->count() }} run(s) logged</strong>
            </div>
        </div>

        @if($recentBackups->isNotEmpty())
            @php $latestBackup = $recentBackups->first(); @endphp
            <div style="font-size: 12px; margin-bottom: 16px;">
                <span class="muted" style="font-weight: 600;">Latest Encrypted Backup:</span>
                <div style="margin-top: 6px; padding: 10px; background: var(--clr-grey-tint); border-radius: var(--radius-md);">
                    <div>Type: <strong>{{ ucfirst($latestBackup->backup_type ?? 'central') }}</strong> ({{ $latestBackup->status }})</div>
                    <div class="muted" style="margin-top: 2px;">{{ $latestBackup->created_at?->diffForHumans() }}</div>
                    @if(!empty($latestBackup->checksum))
                        <div style="margin-top: 4px; font-family: Helvetica, Arial, sans-serif; font-size: 11px;">
                            SHA256: {{ substr($latestBackup->checksum, 0, 16) }}...
                        </div>
                    @endif
                </div>
            </div>
        @else
            <div class="muted" style="font-size: 12px; margin-bottom: 16px;">No automated backups recorded yet.</div>
        @endif

        <div style="margin-top: auto;">
            <a href="{{ route('platform.infrastructure.index') }}" class="btn btn-secondary w-full" style="width: 100%;">
                <i class="bi bi-hdd-network"></i>
                <span>Manage Backups &amp; Restore</span>
            </a>
        </div>
    </div>
</div>

<!-- Row C & D: Commercial Subscriptions, Module Adoption & Industry Blueprints -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; margin-bottom: 24px;">
    <!-- Active Subscription Plans -->
    <div class="card">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
            <h3 class="section-title">Plan Distribution</h3>
            <a href="{{ route('platform.plans.index') }}" style="font-size: 13px;">Plans &rarr;</a>
        </div>
        <div class="table-container">
            <div class="table-scroll">
                <table>
                    <thead>
                        <tr>
                            <th>Plan</th>
                            <th>Code</th>
                            <th style="text-align: right;">Subscribers</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($plans as $plan)
                            <tr>
                                <td title="{{ $plan['name'] }}"><strong>{{ $plan['name'] }}</strong></td>
                                <td><code>{{ $plan['code'] }}</code></td>
                                <td style="text-align: right; font-weight: 600;" class="text-green">{{ $plan['active_subscribers'] }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="muted" style="padding: 14px;">No commercial plans configured.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Module Catalogue Adoption -->
    <div class="card">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
            <h3 class="section-title">Top Adopted Modules</h3>
            <a href="{{ route('platform.modules.index') }}" style="font-size: 13px;">Catalogue &rarr;</a>
        </div>
        <div class="table-container">
            <div class="table-scroll">
                <table>
                    <thead>
                        <tr>
                            <th>Module</th>
                            <th style="text-align: right;">Tenants</th>
                            <th style="text-align: right;">Adoption Rate</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($topModules as $mod)
                            <tr>
                                <td title="{{ $mod['name'] }}"><strong>{{ $mod['name'] }}</strong></td>
                                <td style="text-align: right;">{{ $mod['enabled_tenants_count'] }}</td>
                                <td style="text-align: right; font-weight: 600;" class="text-green">{{ $mod['adoption_rate'] }}%</td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="muted" style="padding: 14px;">No module entitlements recorded.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Industry Blueprint Distribution -->
    <div class="card">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
            <h3 class="section-title">Industry Blueprints</h3>
            <a href="{{ route('platform.templates.index') }}" style="font-size: 13px;">Templates &rarr;</a>
        </div>
        <div class="table-container">
            <div class="table-scroll">
                <table>
                    <thead>
                        <tr>
                            <th>Industry</th>
                            <th style="text-align: right;">Tenants</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($industries as $ind => $cnt)
                            <tr>
                                <td title="{{ str_replace('-', ' ', $ind) }}" style="text-transform: capitalize;"><strong>{{ str_replace('-', ' ', $ind) }}</strong></td>
                                <td style="text-align: right; font-weight: 600;" class="text-green">{{ $cnt }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="2" class="muted" style="padding: 14px;">No industry assignments recorded.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Row N & L: Security Centre, Threat Monitoring & Regulatory Compliance -->
<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 24px;">
    <!-- Security Centre Overview -->
    <div class="card">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
            <h3 class="section-title">Security Centre &amp; Key Governance</h3>
            <a href="{{ route('platform.security.index') }}" style="font-size: 13px;">Console &rarr;</a>
        </div>
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 16px;">
            <div class="stat-card" style="padding: 14px;">
                <div class="stat-body">
                    <div class="stat-label">Active Incidents</div>
                    <div class="stat-value {{ $security['metrics']['active_incidents'] > 0 ? 'text-red' : 'text-green' }}" style="font-size: 20px;">
                        {{ $security['metrics']['active_incidents'] }}
                    </div>
                </div>
            </div>
            <div class="stat-card" style="padding: 14px;">
                <div class="stat-body">
                    <div class="stat-label">Critical Events (24h)</div>
                    <div class="stat-value {{ $security['metrics']['critical_events_24h'] > 0 ? 'text-red' : 'text-green' }}" style="font-size: 20px;">
                        {{ $security['metrics']['critical_events_24h'] }}
                    </div>
                </div>
            </div>
            <div class="stat-card" style="padding: 14px;">
                <div class="stat-body">
                    <div class="stat-label">Blocked Denylist IPs</div>
                    <div class="stat-value" style="font-size: 20px;">{{ $security['metrics']['blocked_ips'] }}</div>
                </div>
            </div>
            <div class="stat-card" style="padding: 14px;">
                <div class="stat-body">
                    <div class="stat-label">Active Sessions</div>
                    <div class="stat-value" style="font-size: 20px;">{{ $security['metrics']['active_sessions'] }}</div>
                </div>
            </div>
        </div>

        <div style="font-size: 12px;">
            <span class="muted" style="font-weight: 600;">Envelope Encryption Keys:</span>
            <div style="margin-top: 6px; display: flex; flex-direction: column; gap: 6px;">
                @foreach($security['keys'] as $key)
                    <div style="display: flex; justify-content: space-between; font-family: Helvetica, Arial, sans-serif; font-size: 11px; padding: 6px 10px; background: var(--clr-grey-tint); border-radius: var(--radius-sm);">
                        <span>Key {{ $key->key_id }} (v{{ $key->version }})</span>
                        <span class="{{ $key->status === 'active' ? 'text-green' : 'muted' }}" style="font-weight: 600;">{{ strtoupper($key->status) }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <!-- Compliance & eTIMS Console Overview -->
    <div class="card">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
            <h3 class="section-title">Compliance &amp; eTIMS Console</h3>
            <a href="{{ route('platform.compliance.index') }}" style="font-size: 13px;">Console &rarr;</a>
        </div>
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 16px;">
            <div class="stat-card" style="padding: 14px;">
                <div class="stat-body">
                    <div class="stat-label">eTIMS Checklist</div>
                    <div class="stat-value text-green" style="font-size: 20px;">{{ $compliance['metrics']['cert_passed'] }} / {{ $compliance['metrics']['cert_total'] }}</div>
                </div>
            </div>
            <div class="stat-card" style="padding: 14px;">
                <div class="stat-body">
                    <div class="stat-label">Pending DPA Requests</div>
                    <div class="stat-value {{ $compliance['metrics']['dpa_pending'] > 0 ? 'text-warning' : 'text-green' }}" style="font-size: 20px;">
                        {{ $compliance['metrics']['dpa_pending'] }}
                    </div>
                </div>
            </div>
            <div class="stat-card" style="padding: 14px;">
                <div class="stat-body">
                    <div class="stat-label">Overdue DPA Requests</div>
                    <div class="stat-value {{ $compliance['metrics']['dpa_overdue'] > 0 ? 'text-red' : 'text-green' }}" style="font-size: 20px;">
                        {{ $compliance['metrics']['dpa_overdue'] }}
                    </div>
                </div>
            </div>
            <div class="stat-card" style="padding: 14px;">
                <div class="stat-body">
                    <div class="stat-label">Published Legal Docs</div>
                    <div class="stat-value" style="font-size: 20px;">{{ $compliance['legal_documents']->count() }}</div>
                </div>
            </div>
        </div>

        <div style="font-size: 12px;">
            <span class="muted" style="font-weight: 600;">Governing Legal Documents:</span>
            <div style="margin-top: 6px; display: flex; flex-direction: column; gap: 6px;">
                @foreach($compliance['legal_documents']->take(3) as $doc)
                    <div style="display: flex; justify-content: space-between; padding: 6px 10px; background: var(--clr-grey-tint); border-radius: var(--radius-sm);">
                        <span>{{ $doc->title }} (v{{ $doc->version }})</span>
                        <span class="muted">{{ $doc->published_at?->format('Y-m-d') }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>

<!-- Two-Person Rule Signoff & Pending Approvals -->
<div class="card" style="margin-bottom: 24px;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
        <h3 class="section-title">Pending Two-Person Administrative Approvals</h3>
        <a href="{{ route('platform.approvals.index') }}" style="font-size: 13px;">Approvals Console &rarr;</a>
    </div>
    @if($pendingApprovals->isNotEmpty())
        <div class="table-container">
            <div class="table-scroll">
                <table>
                    <thead>
                        <tr>
                            <th>Action Type</th>
                            <th>Target</th>
                            <th>Requested By</th>
                            <th>Requested At</th>
                            <th style="text-align: right;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($pendingApprovals as $approval)
                            <tr>
                                <td title="{{ $approval->action_type }}"><strong>{{ $approval->action_type }}</strong></td>
                                <td>{{ $approval->target_type }} #{{ $approval->target_id }}</td>
                                <td>{{ $approval->requester?->name ?? 'System' }}</td>
                                <td class="muted">{{ $approval->created_at?->format('Y-m-d H:i') }}</td>
                                <td style="text-align: right;">
                                    <a href="{{ route('platform.approvals.index') }}" class="btn-ghost" data-tooltip="Review Approval">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @else
        <div class="muted" style="padding: 8px 0;">No pending two-person approvals requiring secondary sign-off.</div>
    @endif
</div>

<!-- Quick Navigation / Administrative Shortcuts -->
<div class="card">
    <h3 class="section-title" style="margin-bottom: 16px;">Platform Control Plane Quick Links</h3>
    <div style="display: flex; gap: 10px; flex-wrap: wrap;">
        <a href="{{ route('platform.tenants.index') }}" class="btn btn-secondary">
            <i class="bi bi-buildings"></i>
            <span>Tenants Console</span>
        </a>
        <a href="{{ route('platform.provisioning.index') }}" class="btn btn-secondary">
            <i class="bi bi-gear-wide-connected"></i>
            <span>Provisioning Engine</span>
        </a>
        <a href="{{ route('platform.plans.index') }}" class="btn btn-secondary">
            <i class="bi bi-credit-card"></i>
            <span>Plans &amp; Subscriptions</span>
        </a>
        <a href="{{ route('platform.invoices.index') }}" class="btn btn-secondary">
            <i class="bi bi-receipt"></i>
            <span>Invoices &amp; Billing</span>
        </a>
        <a href="{{ route('platform.modules.index') }}" class="btn btn-secondary">
            <i class="bi bi-box-seam"></i>
            <span>Module Catalogue</span>
        </a>
        <a href="{{ route('platform.infrastructure.index') }}" class="btn btn-secondary">
            <i class="bi bi-server"></i>
            <span>Infrastructure Health</span>
        </a>
        <a href="{{ route('platform.security.index') }}" class="btn btn-secondary">
            <i class="bi bi-shield-lock"></i>
            <span>Security Centre</span>
        </a>
        <a href="{{ route('platform.compliance.index') }}" class="btn btn-secondary">
            <i class="bi bi-patch-check"></i>
            <span>Compliance &amp; eTIMS</span>
        </a>
        <a href="{{ route('platform.support.cases.index') }}" class="btn btn-secondary">
            <i class="bi bi-headset"></i>
            <span>Support Cases</span>
        </a>
        <a href="{{ route('platform.analytics.index') }}" class="btn btn-secondary">
            <i class="bi bi-bar-chart-line"></i>
            <span>Analytics &amp; Reports</span>
        </a>
        <a href="{{ route('platform.audit.index') }}" class="btn btn-secondary">
            <i class="bi bi-journal-text"></i>
            <span>Audit Log</span>
        </a>
    </div>
</div>

@endsection
