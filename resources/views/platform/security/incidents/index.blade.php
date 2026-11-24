@extends('platform.layout')

@section('title', 'Security Incidents')

@section('content')
<header>
    <div>
        <h1>Security Incidents</h1>
        <div class="muted">Incident lifecycle, containment tracking, and threat response workflow (Annex A, 12.3)</div>
    </div>
    <div style="display: flex; gap: 8px;">
        <a href="{{ route('platform.security.index') }}" class="btn secondary">Security Centre</a>
        <a href="{{ route('platform.security.incidents.create') }}" class="btn">+ New Incident</a>
    </div>
</header>

@if(session('notice'))
    <div class="notice" role="alert">{{ session('notice') }}</div>
@endif

<section class="panel" style="max-width: none; margin-bottom: 18px;">
    <form method="get" action="{{ route('platform.security.incidents.index') }}" style="display: flex; gap: 12px; align-items: end; flex-wrap: wrap;">
        <label>Status
            <select name="status" style="border: 1px solid #b9c9bf; border-radius: 5px; padding: 10px 12px; font: inherit;">
                <option value="">All statuses</option>
                @foreach(['new' => 'New', 'acknowledged' => 'Acknowledged', 'investigating' => 'Investigating', 'escalated' => 'Escalated', 'resolved' => 'Resolved', 'closed' => 'Closed'] as $val => $lbl)
                    <option value="{{ $val }}" @selected(($filters['status'] ?? '') === $val)>{{ $lbl }}</option>
                @endforeach
            </select>
        </label>
        <label>Severity
            <select name="severity" style="border: 1px solid #b9c9bf; border-radius: 5px; padding: 10px 12px; font: inherit;">
                <option value="">All severities</option>
                @foreach(['low' => 'Low', 'medium' => 'Medium', 'high' => 'High', 'critical' => 'Critical'] as $val => $lbl)
                    <option value="{{ $val }}" @selected(($filters['severity'] ?? '') === $val)>{{ $lbl }}</option>
                @endforeach
            </select>
        </label>
        <label>Category
            <select name="category" style="border: 1px solid #b9c9bf; border-radius: 5px; padding: 10px 12px; font: inherit;">
                <option value="">All categories</option>
                @foreach(['brute_force' => 'Brute Force', 'mfa_abuse' => 'MFA Abuse', 'credential_stuffing' => 'Credential Stuffing', 'unauthorized_access' => 'Unauthorized Access', 'data_exfiltration' => 'Data Exfiltration', 'api_abuse' => 'API Abuse', 'general' => 'General Threat'] as $val => $lbl)
                    <option value="{{ $val }}" @selected(($filters['category'] ?? '') === $val)>{{ $lbl }}</option>
                @endforeach
            </select>
        </label>
        <button type="submit">Filter</button>
        @if(!empty(array_filter($filters)))
            <a href="{{ route('platform.security.incidents.index') }}" style="align-self: center;">Clear</a>
        @endif
    </form>
</section>

<section class="panel" style="max-width: none; overflow-x: auto;">
    <table cellpadding="10" cellspacing="0" border="1" style="width: 100%; border-collapse: collapse;">
        <thead>
            <tr style="background: #f8faf9;">
                <th>Incident #</th>
                <th>Title</th>
                <th>Category</th>
                <th>Severity</th>
                <th>Status</th>
                <th>Assigned Officer</th>
                <th>Tenant</th>
                <th>Created</th>
            </tr>
        </thead>
        <tbody>
            @forelse($incidents as $incident)
                <tr>
                    <td>
                        <a href="{{ route('platform.security.incidents.show', $incident) }}">
                            <strong>{{ $incident->incident_number }}</strong>
                        </a>
                    </td>
                    <td>{{ $incident->title }}</td>
                    <td><code>{{ $incident->category }}</code></td>
                    <td>
                        <span class="badge" style="background: {{ $incident->severity === 'critical' ? '#fee2e2' : ($incident->severity === 'high' ? '#ffedd5' : ($incident->severity === 'medium' ? '#fef9c3' : '#f0fdf4')) }}; color: {{ $incident->severity === 'critical' ? '#991b1b' : ($incident->severity === 'high' ? '#9a3412' : ($incident->severity === 'medium' ? '#854d0e' : '#166534')) }}; padding: 3px 8px; border-radius: 4px; font-weight: 600;">
                            {{ strtoupper($incident->severity) }}
                        </span>
                    </td>
                    <td>
                        <span class="badge" style="background: {{ in_array($incident->status, ['resolved', 'closed']) ? '#dcfce7' : '#e0e7ff' }}; color: {{ in_array($incident->status, ['resolved', 'closed']) ? '#166534' : '#3730a3' }}; padding: 3px 8px; border-radius: 4px;">
                            {{ strtoupper($incident->status) }}
                        </span>
                    </td>
                    <td>{{ $incident->assignedOfficer?->name ?? 'Unassigned' }}</td>
                    <td>{{ $incident->tenant?->name ?? 'Platform Wide' }}</td>
                    <td>{{ $incident->created_at->format('Y-m-d H:i') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="muted" style="text-align: center; padding: 24px;">No security incidents found.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
    {{ $incidents->links() }}
</section>
@endsection
