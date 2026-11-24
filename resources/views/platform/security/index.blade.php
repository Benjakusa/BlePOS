@extends('platform.layout')

@section('title', 'Security Centre')

@section('content')
<header>
    <div>
        <h1>Security Centre</h1>
        <div class="muted">Incident management, threat monitoring, dynamic IP filtering, and cryptographic key governance (Annex A, Part 12)</div>
    </div>
    <div style="display: flex; gap: 8px;">
        <a href="{{ route('platform.security.incidents.index') }}" class="btn">Incidents</a>
        <a href="{{ route('platform.security.events.index') }}" class="btn secondary">Security Events</a>
        <a href="{{ route('platform.security.ip-rules.index') }}" class="btn secondary">IP Rules</a>
        <a href="{{ route('platform.security.keys.index') }}" class="btn secondary">Cryptographic Keys</a>
    </div>
</header>

@if(session('notice'))
    <div class="notice" role="alert">{{ session('notice') }}</div>
@endif

<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px; margin-bottom: 24px;">
    <div class="panel" style="padding: 16px;">
        <span class="muted" style="font-size: 13px; text-transform: uppercase;">Active Incidents</span>
        <div style="font-size: 26px; font-weight: bold; margin-top: 4px; color: {{ $metrics['active_incidents'] > 0 ? '#b91c1c' : '#15803d' }};">
            {{ $metrics['active_incidents'] }}
        </div>
        <div class="muted" style="font-size: 12px; margin-top: 4px;">Open, investigating or escalated</div>
    </div>
    <div class="panel" style="padding: 16px;">
        <span class="muted" style="font-size: 13px; text-transform: uppercase;">Critical Events (24h)</span>
        <div style="font-size: 26px; font-weight: bold; margin-top: 4px; color: {{ $metrics['critical_events_24h'] > 0 ? '#b45309' : '#15803d' }};">
            {{ $metrics['critical_events_24h'] }}
        </div>
        <div class="muted" style="font-size: 12px; margin-top: 4px;">Warnings and critical threats</div>
    </div>
    <div class="panel" style="padding: 16px;">
        <span class="muted" style="font-size: 13px; text-transform: uppercase;">Blocked IPs / Subnets</span>
        <div style="font-size: 26px; font-weight: bold; margin-top: 4px;">
            {{ $metrics['blocked_ips'] }}
        </div>
        <div class="muted" style="font-size: 12px; margin-top: 4px;">Active denylist rules</div>
    </div>
    <div class="panel" style="padding: 16px;">
        <span class="muted" style="font-size: 13px; text-transform: uppercase;">Active Platform Sessions</span>
        <div style="font-size: 26px; font-weight: bold; margin-top: 4px;">
            {{ $metrics['active_sessions'] }}
        </div>
        <div class="muted" style="font-size: 12px; margin-top: 4px;">Active in past 30 minutes</div>
    </div>
</div>

<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
    <div class="panel" style="max-width: none;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
            <h2 style="font-size: 18px; margin: 0;">Recent Security Incidents</h2>
            <a href="{{ route('platform.security.incidents.create') }}" class="btn" style="padding: 6px 10px; font-size: 13px;">+ New Incident</a>
        </div>
        <table cellpadding="8" cellspacing="0" border="1" style="width: 100%; border-collapse: collapse; font-size: 13px;">
            <thead>
                <tr style="background: #f8faf9;">
                    <th>ID</th>
                    <th>Title</th>
                    <th>Severity</th>
                    <th>Status</th>
                    <th>Assigned</th>
                </tr>
            </thead>
            <tbody>
                @forelse($recentIncidents as $inc)
                    <tr>
                        <td>
                            <a href="{{ route('platform.security.incidents.show', $inc) }}">
                                <strong>{{ $inc->incident_number }}</strong>
                            </a>
                        </td>
                        <td>{{ Str::limit($inc->title, 35) }}</td>
                        <td>
                            <span class="badge" style="background: {{ $inc->severity === 'critical' ? '#fee2e2' : ($inc->severity === 'high' ? '#ffedd5' : '#fef9c3') }}; color: {{ $inc->severity === 'critical' ? '#991b1b' : ($inc->severity === 'high' ? '#9a3412' : '#854d0e') }}; padding: 2px 6px; border-radius: 4px;">
                                {{ strtoupper($inc->severity) }}
                            </span>
                        </td>
                        <td>{{ ucfirst($inc->status) }}</td>
                        <td>{{ $inc->assignedOfficer?->name ?? 'Unassigned' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="muted" style="text-align: center; padding: 16px;">No security incidents recorded.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="panel" style="max-width: none;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
            <h2 style="font-size: 18px; margin: 0;">Recent Security Events</h2>
            <a href="{{ route('platform.security.events.index') }}" style="font-size: 13px;">View All</a>
        </div>
        <table cellpadding="8" cellspacing="0" border="1" style="width: 100%; border-collapse: collapse; font-size: 13px;">
            <thead>
                <tr style="background: #f8faf9;">
                    <th>Occurred</th>
                    <th>Severity</th>
                    <th>Event Type</th>
                    <th>IP</th>
                </tr>
            </thead>
            <tbody>
                @forelse($recentEvents as $event)
                    <tr>
                        <td>{{ $event->occurred_at->format('M d H:i') }}</td>
                        <td>
                            <span class="badge" style="background: {{ $event->severity === 'critical' ? '#fee2e2' : ($event->severity === 'warning' ? '#fef3c7' : '#f0fdf4') }}; color: {{ $event->severity === 'critical' ? '#991b1b' : ($event->severity === 'warning' ? '#92400e' : '#166534') }}; padding: 2px 6px; border-radius: 4px;">
                                {{ strtoupper($event->severity) }}
                            </span>
                        </td>
                        <td>{{ $event->event_type }}</td>
                        <td>{{ $event->ip_address ?? '—' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="muted" style="text-align: center; padding: 16px;">No recent security events.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="panel" style="max-width: none; margin-top: 20px;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
        <h2 style="font-size: 18px; margin: 0;">Cryptographic Key Inventory & Rotation Status</h2>
        <a href="{{ route('platform.security.keys.index') }}" style="font-size: 13px;">Manage Keys</a>
    </div>
    <table cellpadding="8" cellspacing="0" border="1" style="width: 100%; border-collapse: collapse; font-size: 13px;">
        <thead>
            <tr style="background: #f8faf9;">
                <th>Key ID</th>
                <th>Name</th>
                <th>Algorithm</th>
                <th>Version</th>
                <th>Status</th>
                <th>Fingerprint (SHA-256)</th>
                <th>Next Rotation Due</th>
            </tr>
        </thead>
        <tbody>
            @foreach($keys as $k)
                <tr>
                    <td><code>{{ $k->key_id }}</code></td>
                    <td>{{ $k->name }}</td>
                    <td>{{ $k->algorithm }}</td>
                    <td>v{{ $k->version }}</td>
                    <td>
                        <span class="badge" style="background: #dcfce7; color: #166534; padding: 2px 6px; border-radius: 4px;">
                            {{ strtoupper($k->status) }}
                        </span>
                    </td>
                    <td><code style="font-size: 11px;">{{ Str::limit($k->fingerprint, 28) }}</code></td>
                    <td>{{ $k->next_rotation_due_at?->toFormattedDateString() ?? 'N/A' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection
