@extends('platform.layout')

@section('title', 'Platform Control Plane Dashboard')

@section('content')
@if (session('platform_recovery_codes'))
    <section class="panel" style="margin-bottom: 24px; border-left: 4px solid #146b4d;">
        <h2>Save your recovery codes</h2>
        <p>These codes are shown once. Each code works once and resets your authenticator enrollment.</p>
        <ul>@foreach(session('platform_recovery_codes') as $code)<li><code>{{ $code }}</code></li>@endforeach</ul>
    </section>
@endif

<!-- Needs-Attention Alerts & Quick Operational Action Inbox (Row P) -->
@php
    $hasAlerts = ($growth['provisioning_failed'] > 0)
        || ($security['metrics']['active_incidents'] > 0)
        || ($compliance['metrics']['dpa_overdue'] > 0)
        || ($pendingApprovals->isNotEmpty())
        || ($health['overall'] !== 'healthy');
@endphp

@if($hasAlerts)
    <div style="display: grid; gap: 10px; margin-bottom: 24px;">
        @if($growth['provisioning_failed'] > 0)
            <div style="background: #fdf2f2; border: 1px solid #f8b4b4; color: #9b1c1c; padding: 12px 16px; border-radius: 6px; display: flex; justify-content: space-between; align-items: center;">
                <span><strong>Provisioning Failure Alert:</strong> {{ $growth['provisioning_failed'] }} tenant(s) encountered failed provisioning runs requiring administrative recovery.</span>
                <a href="{{ route('platform.provisioning.index') }}" style="color: #9b1c1c; font-weight: bold; text-decoration: underline;">Review Provisioning &rarr;</a>
            </div>
        @endif

        @if($security['metrics']['active_incidents'] > 0)
            <div style="background: #fef8f1; border: 1px solid #fbd5b5; color: #9a3412; padding: 12px 16px; border-radius: 6px; display: flex; justify-content: space-between; align-items: center;">
                <span><strong>Security Incident Alert:</strong> {{ $security['metrics']['active_incidents'] }} open security incident(s) currently being investigated.</span>
                <a href="{{ route('platform.security.index') }}" style="color: #9a3412; font-weight: bold; text-decoration: underline;">Security Centre &rarr;</a>
            </div>
        @endif

        @if($compliance['metrics']['dpa_overdue'] > 0)
            <div style="background: #fdf2f2; border: 1px solid #f8b4b4; color: #9b1c1c; padding: 12px 16px; border-radius: 6px; display: flex; justify-content: space-between; align-items: center;">
                <span><strong>Statutory Deadline Alert:</strong> {{ $compliance['metrics']['dpa_overdue'] }} Data Protection DSAR request(s) have passed statutory response time limits.</span>
                <a href="{{ route('platform.compliance.index') }}" style="color: #9b1c1c; font-weight: bold; text-decoration: underline;">Compliance Console &rarr;</a>
            </div>
        @endif

        @if($pendingApprovals->isNotEmpty())
            <div style="background: #eff6ff; border: 1px solid #bfdbfe; color: #1e40af; padding: 12px 16px; border-radius: 6px; display: flex; justify-content: space-between; align-items: center;">
                <span><strong>Two-Person Rule Signoff Required:</strong> {{ $pendingApprovals->count() }} high-risk administrative action(s) await secondary authorization.</span>
                <a href="{{ route('platform.approvals.index') }}" style="color: #1e40af; font-weight: bold; text-decoration: underline;">Review Approvals &rarr;</a>
            </div>
        @endif

        @if($health['overall'] !== 'healthy')
            <div style="background: #fef8f1; border: 1px solid #fbd5b5; color: #9a3412; padding: 12px 16px; border-radius: 6px; display: flex; justify-content: space-between; align-items: center;">
                <span><strong>Infrastructure Degraded:</strong> One or more subsystem health probes reported degraded or unhealthy responses.</span>
                <a href="{{ route('platform.infrastructure.index') }}" style="color: #9a3412; font-weight: bold; text-decoration: underline;">Infrastructure Probes &rarr;</a>
            </div>
        @endif
    </div>
@endif

<!-- eTIMS Technical Regulatory Status Banner -->
<div style="background: #f0fdf4; border: 1px solid #bbf7d0; color: #166534; padding: 12px 16px; border-radius: 6px; margin-bottom: 24px; display: flex; justify-content: space-between; align-items: center;">
    <div>
        <strong>eTIMS Fiscal Subsystem:</strong> Status is <span style="display: inline-block; background: #fef3c7; color: #92400e; padding: 2px 8px; border-radius: 4px; font-weight: bold; font-size: 12px;">{{ $compliance['metrics']['etims_status'] }}</span>
        <span class="muted" style="margin-left: 8px; font-size: 13px;">(Regulatory compliance placeholder active; certified KRA checklist tracking {{ $compliance['metrics']['cert_passed'] }}/{{ $compliance['metrics']['cert_total'] }} requirements)</span>
    </div>
    <a href="{{ route('platform.compliance.index') }}" style="color: #166534; font-weight: bold; text-decoration: underline;">Checklist &rarr;</a>
</div>

<!-- Row A: Headline Executive KPIs -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 16px; margin-bottom: 24px;">
    <!-- Tenants KPI -->
    <div class="panel" style="padding: 18px;">
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <span class="muted" style="font-size: 12px; text-transform: uppercase; font-weight: 600;">Tenants</span>
            <a href="{{ route('platform.tenants.index') }}" style="font-size: 12px;">View All &rarr;</a>
        </div>
        <div style="font-size: 28px; font-weight: bold; margin-top: 6px; color: #102b22;">
            {{ number_format($growth['total_tenants']) }}
        </div>
        <div style="font-size: 13px; margin-top: 6px; display: flex; gap: 10px; flex-wrap: wrap;">
            <span style="color: #146b4d; font-weight: 600;">{{ $growth['active_tenants'] }} active</span>
            <span style="color: #9a3412;">{{ $growth['suspended_tenants'] }} suspended</span>
            <span class="muted">{{ $growth['retention_rate'] }}% retention</span>
        </div>
    </div>

    <!-- Revenue KPI -->
    <div class="panel" style="padding: 18px;">
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <span class="muted" style="font-size: 12px; text-transform: uppercase; font-weight: 600;">Monthly Recurring Revenue</span>
            <a href="{{ route('platform.analytics.index') }}" style="font-size: 12px;">Analytics &rarr;</a>
        </div>
        <div style="font-size: 28px; font-weight: bold; margin-top: 6px; color: #146b4d;">
            KES {{ number_format((float) $revenue['mrr'], 2) }}
        </div>
        <div style="font-size: 13px; margin-top: 6px; display: flex; gap: 10px; flex-wrap: wrap;">
            <span class="muted">ARR: KES {{ number_format((float) $revenue['arr'], 2) }}</span>
            <span style="color: #146b4d; font-weight: 600;">{{ $revenue['collection_rate'] }}% collected</span>
        </div>
    </div>

    <!-- Infrastructure & Probes KPI -->
    <div class="panel" style="padding: 18px;">
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <span class="muted" style="font-size: 12px; text-transform: uppercase; font-weight: 600;">Subsystems Health</span>
            <a href="{{ route('platform.infrastructure.index') }}" style="font-size: 12px;">Probes &rarr;</a>
        </div>
        <div style="font-size: 28px; font-weight: bold; margin-top: 6px; color: {{ $health['overall'] === 'healthy' ? '#146b4d' : '#dc2626' }};">
            {{ strtoupper($health['overall']) }}
        </div>
        <div style="font-size: 13px; margin-top: 6px; display: flex; gap: 10px; flex-wrap: wrap;">
            <span>DB: {{ $health['probes']['database']['latency_ms'] }}ms</span>
            <span>Cache: {{ $health['probes']['cache']['latency_ms'] }}ms</span>
            <span>Queues: {{ $health['probes']['queues']['latency_ms'] }}ms</span>
        </div>
    </div>

    <!-- Support & Security KPI -->
    <div class="panel" style="padding: 18px;">
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <span class="muted" style="font-size: 12px; text-transform: uppercase; font-weight: 600;">Support & Security</span>
            <a href="{{ route('platform.security.index') }}" style="font-size: 12px;">Security &rarr;</a>
        </div>
        <div style="font-size: 28px; font-weight: bold; margin-top: 6px; color: #102b22;">
            {{ $support['open_cases'] }} <span style="font-size: 16px; font-weight: normal; color: #63736b;">open cases</span>
        </div>
        <div style="font-size: 13px; margin-top: 6px; display: flex; gap: 10px; flex-wrap: wrap;">
            <span style="color: {{ $support['urgent_cases'] > 0 ? '#dc2626' : '#146b4d' }}; font-weight: 600;">{{ $support['urgent_cases'] }} urgent</span>
            <span class="muted">{{ $security['metrics']['blocked_ips'] }} blocked IPs</span>
            <span class="muted">{{ $security['metrics']['active_sessions'] }} active sessions</span>
        </div>
    </div>
</div>

<!-- Row E, H & K: Operational Health, Subsystems & Backups -->
<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 16px; margin-bottom: 24px;">
    <!-- Live Health Probes -->
    <div class="panel" style="max-width: none;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px;">
            <h3 style="margin: 0; font-size: 16px;">Live Subsystem Probes</h3>
            <span class="muted" style="font-size: 12px;">Probed at: {{ now()->format('Y-m-d H:i:s') }}</span>
        </div>
        <table style="width: 100%; border-collapse: collapse; font-size: 13px;">
            <thead>
                <tr style="border-bottom: 1px solid #dce5df; text-align: left;">
                    <th style="padding: 8px 6px;">Subsystem</th>
                    <th style="padding: 8px 6px;">Status</th>
                    <th style="padding: 8px 6px;">Latency</th>
                    <th style="padding: 8px 6px;">Diagnostic Details</th>
                </tr>
            </thead>
            <tbody>
                @foreach($health['probes'] as $probeName => $probe)
                    <tr style="border-bottom: 1px solid #f0f4f2;">
                        <td style="padding: 8px 6px; font-weight: 600; text-transform: capitalize;">{{ $probeName }}</td>
                        <td style="padding: 8px 6px;">
                            <span style="display: inline-block; padding: 2px 6px; border-radius: 4px; font-size: 11px; font-weight: bold; background: {{ $probe['status'] === 'healthy' ? '#e7f4eb' : ($probe['status'] === 'degraded' ? '#fef3c7' : '#fee2e2') }}; color: {{ $probe['status'] === 'healthy' ? '#146b4d' : ($probe['status'] === 'degraded' ? '#92400e' : '#991b1b') }};">
                                {{ strtoupper($probe['status']) }}
                            </span>
                        </td>
                        <td style="padding: 8px 6px;">{{ $probe['latency_ms'] }} ms</td>
                        <td style="padding: 8px 6px; color: #555;">
                            @foreach($probe['details'] as $k => $v)
                                <span>{{ $k }}: <strong>{{ is_scalar($v) ? $v : json_encode($v) }}</strong></span>@if(!$loop->last), @endif
                            @endforeach
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <!-- Backups & Migrations Summary -->
    <div class="panel" style="max-width: none;">
        <h3 style="margin: 0 0 12px 0; font-size: 16px;">Backups &amp; Migrations</h3>
        <div style="font-size: 13px; margin-bottom: 14px;">
            <div style="display: flex; justify-content: space-between; padding: 6px 0; border-bottom: 1px solid #f0f4f2;">
                <span class="muted">Migrations Ran:</span>
                <strong>{{ $migrationStatus['total_ran'] }} (Batch {{ $migrationStatus['last_batch'] }})</strong>
            </div>
            <div style="display: flex; justify-content: space-between; padding: 6px 0; border-bottom: 1px solid #f0f4f2;">
                <span class="muted">Recent Backups:</span>
                <strong>{{ $recentBackups->count() }} run(s) logged</strong>
            </div>
        </div>

        @if($recentBackups->isNotEmpty())
            <div style="font-size: 12px;">
                <span class="muted" style="font-weight: 600;">Latest Encrypted Backup:</span>
                @php $latestBackup = $recentBackups->first(); @endphp
                <div style="margin-top: 4px; padding: 8px; background: #f8faf9; border-radius: 4px; border: 1px solid #e5eae7;">
                    <div>Type: <strong>{{ ucfirst($latestBackup->backup_type ?? 'central') }}</strong> ({{ $latestBackup->status }})</div>
                    <div style="color: #63736b; margin-top: 2px;">{{ $latestBackup->created_at?->diffForHumans() }}</div>
                    @if(!empty($latestBackup->checksum))
                        <div style="margin-top: 4px; font-family: monospace; font-size: 11px; word-break: break-all;">
                            SHA256: {{ substr($latestBackup->checksum, 0, 16) }}...
                        </div>
                    @endif
                </div>
            </div>
        @else
            <div class="muted" style="font-size: 12px;">No automated backups recorded yet.</div>
        @endif
        <div style="margin-top: 14px;">
            <a href="{{ route('platform.infrastructure.index') }}" style="font-size: 12px; font-weight: 600;">Manage Backups & Restore &rarr;</a>
        </div>
    </div>
</div>

<!-- Row C & D: Commercial Subscriptions, Module Adoption & Industry Blueprints -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 16px; margin-bottom: 24px;">
    <!-- Active Subscription Plans -->
    <div class="panel" style="max-width: none;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
            <h3 style="margin: 0; font-size: 16px;">Plan Distribution</h3>
            <a href="{{ route('platform.plans.index') }}" style="font-size: 12px;">Plans &rarr;</a>
        </div>
        <table style="width: 100%; border-collapse: collapse; font-size: 13px;">
            <thead>
                <tr style="border-bottom: 1px solid #dce5df; text-align: left;">
                    <th style="padding: 6px;">Plan</th>
                    <th style="padding: 6px;">Code</th>
                    <th style="padding: 6px; text-align: right;">Active Subscriptions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($plans as $plan)
                    <tr style="border-bottom: 1px solid #f0f4f2;">
                        <td style="padding: 6px; font-weight: 600;">{{ $plan['name'] }}</td>
                        <td style="padding: 6px;"><code style="font-size: 11px;">{{ $plan['code'] }}</code></td>
                        <td style="padding: 6px; text-align: right; font-weight: bold; color: #146b4d;">{{ $plan['active_subscribers'] }}</td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="muted" style="padding: 10px 0;">No commercial plans configured.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Module Catalogue Adoption -->
    <div class="panel" style="max-width: none;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
            <h3 style="margin: 0; font-size: 16px;">Top Adopted Modules</h3>
            <a href="{{ route('platform.modules.index') }}" style="font-size: 12px;">Catalogue &rarr;</a>
        </div>
        <table style="width: 100%; border-collapse: collapse; font-size: 13px;">
            <thead>
                <tr style="border-bottom: 1px solid #dce5df; text-align: left;">
                    <th style="padding: 6px;">Module</th>
                    <th style="padding: 6px; text-align: right;">Tenants</th>
                    <th style="padding: 6px; text-align: right;">Adoption Rate</th>
                </tr>
            </thead>
            <tbody>
                @forelse($topModules as $mod)
                    <tr style="border-bottom: 1px solid #f0f4f2;">
                        <td style="padding: 6px; font-weight: 600;">{{ $mod['name'] }}</td>
                        <td style="padding: 6px; text-align: right;">{{ $mod['enabled_tenants_count'] }}</td>
                        <td style="padding: 6px; text-align: right; font-weight: bold; color: #146b4d;">{{ $mod['adoption_rate'] }}%</td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="muted" style="padding: 10px 0;">No module entitlements recorded.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Industry Blueprint Distribution -->
    <div class="panel" style="max-width: none;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
            <h3 style="margin: 0; font-size: 16px;">Industry Blueprints</h3>
            <a href="{{ route('platform.templates.index') }}" style="font-size: 12px;">Templates &rarr;</a>
        </div>
        <table style="width: 100%; border-collapse: collapse; font-size: 13px;">
            <thead>
                <tr style="border-bottom: 1px solid #dce5df; text-align: left;">
                    <th style="padding: 6px;">Industry</th>
                    <th style="padding: 6px; text-align: right;">Tenants</th>
                </tr>
            </thead>
            <tbody>
                @forelse($industries as $ind => $cnt)
                    <tr style="border-bottom: 1px solid #f0f4f2;">
                        <td style="padding: 6px; font-weight: 600; text-transform: capitalize;">{{ str_replace('-', ' ', $ind) }}</td>
                        <td style="padding: 6px; text-align: right; color: #146b4d; font-weight: 600;">{{ $cnt }}</td>
                    </tr>
                @empty
                    <tr><td colspan="2" class="muted" style="padding: 10px 0;">No industry assignments recorded.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- Row N & L: Security Centre, Threat Monitoring & Regulatory Compliance -->
<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 24px;">
    <!-- Security Centre Overview -->
    <div class="panel" style="max-width: none;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
            <h3 style="margin: 0; font-size: 16px;">Security Centre &amp; Key Governance</h3>
            <a href="{{ route('platform.security.index') }}" style="font-size: 12px;">Console &rarr;</a>
        </div>
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px; margin-bottom: 14px; font-size: 13px;">
            <div style="background: #f8faf9; padding: 8px 12px; border-radius: 4px; border: 1px solid #e5eae7;">
                <span class="muted" style="font-size: 11px;">Active Incidents:</span>
                <div style="font-size: 18px; font-weight: bold; color: {{ $security['metrics']['active_incidents'] > 0 ? '#dc2626' : '#146b4d' }};">
                    {{ $security['metrics']['active_incidents'] }}
                </div>
            </div>
            <div style="background: #f8faf9; padding: 8px 12px; border-radius: 4px; border: 1px solid #e5eae7;">
                <span class="muted" style="font-size: 11px;">Critical Events (24h):</span>
                <div style="font-size: 18px; font-weight: bold; color: {{ $security['metrics']['critical_events_24h'] > 0 ? '#dc2626' : '#146b4d' }};">
                    {{ $security['metrics']['critical_events_24h'] }}
                </div>
            </div>
            <div style="background: #f8faf9; padding: 8px 12px; border-radius: 4px; border: 1px solid #e5eae7;">
                <span class="muted" style="font-size: 11px;">Blocked Denylist IPs:</span>
                <div style="font-size: 18px; font-weight: bold; color: #102b22;">
                    {{ $security['metrics']['blocked_ips'] }}
                </div>
            </div>
            <div style="background: #f8faf9; padding: 8px 12px; border-radius: 4px; border: 1px solid #e5eae7;">
                <span class="muted" style="font-size: 11px;">Active Sessions:</span>
                <div style="font-size: 18px; font-weight: bold; color: #102b22;">
                    {{ $security['metrics']['active_sessions'] }}
                </div>
            </div>
        </div>

        <div style="font-size: 12px;">
            <span class="muted" style="font-weight: 600;">Envelope Encryption Keys:</span>
            <div style="margin-top: 6px; display: flex; flex-direction: column; gap: 4px;">
                @foreach($security['keys'] as $key)
                    <div style="display: flex; justify-content: space-between; font-family: monospace; font-size: 11px; padding: 4px 6px; background: #fff; border: 1px solid #e5eae7; border-radius: 4px;">
                        <span>Key {{ $key->key_id }} (v{{ $key->version }})</span>
                        <span style="color: {{ $key->status === 'active' ? '#146b4d' : '#63736b' }}; font-weight: bold;">{{ strtoupper($key->status) }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <!-- Compliance & eTIMS Console Overview -->
    <div class="panel" style="max-width: none;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
            <h3 style="margin: 0; font-size: 16px;">Compliance &amp; eTIMS Console</h3>
            <a href="{{ route('platform.compliance.index') }}" style="font-size: 12px;">Console &rarr;</a>
        </div>
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px; margin-bottom: 14px; font-size: 13px;">
            <div style="background: #f8faf9; padding: 8px 12px; border-radius: 4px; border: 1px solid #e5eae7;">
                <span class="muted" style="font-size: 11px;">eTIMS Checklist:</span>
                <div style="font-size: 18px; font-weight: bold; color: #146b4d;">
                    {{ $compliance['metrics']['cert_passed'] }} / {{ $compliance['metrics']['cert_total'] }}
                </div>
            </div>
            <div style="background: #f8faf9; padding: 8px 12px; border-radius: 4px; border: 1px solid #e5eae7;">
                <span class="muted" style="font-size: 11px;">Pending DPA Requests:</span>
                <div style="font-size: 18px; font-weight: bold; color: {{ $compliance['metrics']['dpa_pending'] > 0 ? '#9a3412' : '#102b22' }};">
                    {{ $compliance['metrics']['dpa_pending'] }}
                </div>
            </div>
            <div style="background: #f8faf9; padding: 8px 12px; border-radius: 4px; border: 1px solid #e5eae7;">
                <span class="muted" style="font-size: 11px;">Overdue DPA Requests:</span>
                <div style="font-size: 18px; font-weight: bold; color: {{ $compliance['metrics']['dpa_overdue'] > 0 ? '#dc2626' : '#146b4d' }};">
                    {{ $compliance['metrics']['dpa_overdue'] }}
                </div>
            </div>
            <div style="background: #f8faf9; padding: 8px 12px; border-radius: 4px; border: 1px solid #e5eae7;">
                <span class="muted" style="font-size: 11px;">Published Legal Docs:</span>
                <div style="font-size: 18px; font-weight: bold; color: #102b22;">
                    {{ $compliance['legal_documents']->count() }}
                </div>
            </div>
        </div>

        <div style="font-size: 12px;">
            <span class="muted" style="font-weight: 600;">Governing Legal Documents:</span>
            <div style="margin-top: 6px; display: flex; flex-direction: column; gap: 4px;">
                @foreach($compliance['legal_documents']->take(3) as $doc)
                    <div style="display: flex; justify-content: space-between; padding: 4px 6px; background: #fff; border: 1px solid #e5eae7; border-radius: 4px;">
                        <span>{{ $doc->title }} (v{{ $doc->version }})</span>
                        <span class="muted">{{ $doc->published_at?->format('Y-m-d') }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>

<!-- Two-Person Rule Signoff & Pending Approvals (Row P) -->
<div class="panel" style="max-width: none; margin-bottom: 24px;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
        <h3 style="margin: 0; font-size: 16px;">Pending Two-Person Administrative Approvals</h3>
        <a href="{{ route('platform.approvals.index') }}" style="font-size: 12px;">Approvals Console &rarr;</a>
    </div>
    @if($pendingApprovals->isNotEmpty())
        <table style="width: 100%; border-collapse: collapse; font-size: 13px;">
            <thead>
                <tr style="border-bottom: 1px solid #dce5df; text-align: left;">
                    <th style="padding: 8px 6px;">Action Type</th>
                    <th style="padding: 8px 6px;">Target</th>
                    <th style="padding: 8px 6px;">Requested By</th>
                    <th style="padding: 8px 6px;">Requested At</th>
                    <th style="padding: 8px 6px; text-align: right;">Action</th>
                </tr>
            </thead>
            <tbody>
                @foreach($pendingApprovals as $approval)
                    <tr style="border-bottom: 1px solid #f0f4f2;">
                        <td style="padding: 8px 6px; font-weight: 600;">{{ $approval->action_type }}</td>
                        <td style="padding: 8px 6px;">{{ $approval->target_type }} #{{ $approval->target_id }}</td>
                        <td style="padding: 8px 6px;">{{ $approval->requester?->name ?? 'System' }}</td>
                        <td style="padding: 8px 6px;" class="muted">{{ $approval->created_at?->format('Y-m-d H:i') }}</td>
                        <td style="padding: 8px 6px; text-align: right;">
                            <a href="{{ route('platform.approvals.index') }}" style="font-weight: 600; text-decoration: underline;">Review</a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <div class="muted" style="font-size: 13px; padding: 8px 0;">No pending two-person approvals requiring secondary sign-off.</div>
    @endif
</div>

<!-- Quick Navigation / Administrative Shortcuts -->
<div class="panel" style="max-width: none;">
    <h3 style="margin: 0 0 12px 0; font-size: 16px;">Platform Control Plane Quick Links</h3>
    <div style="display: flex; gap: 10px; flex-wrap: wrap; font-size: 13px;">
        <a href="{{ route('platform.tenants.index') }}" style="padding: 8px 12px; background: #eaf4ef; border-radius: 4px; text-decoration: none; font-weight: 600;">Tenants Console</a>
        <a href="{{ route('platform.provisioning.index') }}" style="padding: 8px 12px; background: #eaf4ef; border-radius: 4px; text-decoration: none; font-weight: 600;">Provisioning Engine</a>
        <a href="{{ route('platform.plans.index') }}" style="padding: 8px 12px; background: #eaf4ef; border-radius: 4px; text-decoration: none; font-weight: 600;">Plans & Subscriptions</a>
        <a href="{{ route('platform.invoices.index') }}" style="padding: 8px 12px; background: #eaf4ef; border-radius: 4px; text-decoration: none; font-weight: 600;">Invoices & Billing</a>
        <a href="{{ route('platform.modules.index') }}" style="padding: 8px 12px; background: #eaf4ef; border-radius: 4px; text-decoration: none; font-weight: 600;">Module Catalogue</a>
        <a href="{{ route('platform.infrastructure.index') }}" style="padding: 8px 12px; background: #eaf4ef; border-radius: 4px; text-decoration: none; font-weight: 600;">Infrastructure Health</a>
        <a href="{{ route('platform.security.index') }}" style="padding: 8px 12px; background: #eaf4ef; border-radius: 4px; text-decoration: none; font-weight: 600;">Security Centre</a>
        <a href="{{ route('platform.compliance.index') }}" style="padding: 8px 12px; background: #eaf4ef; border-radius: 4px; text-decoration: none; font-weight: 600;">Compliance & eTIMS</a>
        <a href="{{ route('platform.support.cases.index') }}" style="padding: 8px 12px; background: #eaf4ef; border-radius: 4px; text-decoration: none; font-weight: 600;">Support Cases</a>
        <a href="{{ route('platform.analytics.index') }}" style="padding: 8px 12px; background: #eaf4ef; border-radius: 4px; text-decoration: none; font-weight: 600;">Analytics & Reports</a>
        <a href="{{ route('platform.audit.index') }}" style="padding: 8px 12px; background: #eaf4ef; border-radius: 4px; text-decoration: none; font-weight: 600;">Audit Log</a>
    </div>
</div>
@endsection
