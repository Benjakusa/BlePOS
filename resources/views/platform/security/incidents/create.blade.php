@extends('platform.layout')

@section('title', 'Register Security Incident')

@section('content')
<header>
    <div>
        <h1>Register Security Incident</h1>
        <div class="muted">Log and categorize a security incident, breach report or anomalous event</div>
    </div>
    <a href="{{ route('platform.security.incidents.index') }}" class="btn secondary">Back to Incidents</a>
</header>

@if($errors->any())
    <div class="error" style="margin-bottom: 16px;">
        <ul>
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="panel">
    <form method="POST" action="{{ route('platform.security.incidents.store') }}" class="form" style="max-width: 600px;">
        @csrf

        <label>Incident Title
            <input name="title" value="{{ old('title') }}" required placeholder="e.g. Distributed brute-force attack on tenant login">
        </label>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px;">
            <label>Category
                <select name="category" required style="border: 1px solid #b9c9bf; border-radius: 5px; padding: 11px 12px; font: inherit;">
                    <option value="brute_force" @selected(old('category') === 'brute_force')>Brute Force</option>
                    <option value="mfa_abuse" @selected(old('category') === 'mfa_abuse')>MFA Abuse</option>
                    <option value="credential_stuffing" @selected(old('category') === 'credential_stuffing')>Credential Stuffing</option>
                    <option value="unauthorized_access" @selected(old('category') === 'unauthorized_access')>Unauthorized Access</option>
                    <option value="data_exfiltration" @selected(old('category') === 'data_exfiltration')>Data Exfiltration</option>
                    <option value="api_abuse" @selected(old('category') === 'api_abuse')>API Abuse</option>
                    <option value="general" @selected(old('category') === 'general')>General Threat</option>
                </select>
            </label>

            <label>Severity
                <select name="severity" required style="border: 1px solid #b9c9bf; border-radius: 5px; padding: 11px 12px; font: inherit;">
                    <option value="low" @selected(old('severity') === 'low')>Low</option>
                    <option value="medium" @selected(old('severity') === 'medium')>Medium</option>
                    <option value="high" @selected(old('severity') === 'high')>High</option>
                    <option value="critical" @selected(old('severity') === 'critical')>Critical</option>
                </select>
            </label>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px;">
            <label>Assigned Officer (Optional)
                <select name="platform_user_id" style="border: 1px solid #b9c9bf; border-radius: 5px; padding: 11px 12px; font: inherit;">
                    <option value="">Unassigned</option>
                    @foreach($officers as $officer)
                        <option value="{{ $officer->id }}" @selected(old('platform_user_id') == $officer->id)>
                            {{ $officer->name }} ({{ $officer->email }})
                        </option>
                    @endforeach
                </select>
            </label>

            <label>Associated Tenant (Optional)
                <select name="tenant_id" style="border: 1px solid #b9c9bf; border-radius: 5px; padding: 11px 12px; font: inherit;">
                    <option value="">Platform Wide</option>
                    @foreach($tenants as $tenant)
                        <option value="{{ $tenant->id }}" @selected(old('tenant_id') == $tenant->id)>
                            {{ $tenant->name }} ({{ $tenant->subdomain }})
                        </option>
                    @endforeach
                </select>
            </label>
        </div>

        <label>Description & Threat Indicators
            <textarea name="description" rows="5" required style="border: 1px solid #b9c9bf; border-radius: 5px; padding: 11px 12px; font: inherit;" placeholder="Detailed description of the incident, source IPs, affected accounts or endpoints...">{{ old('description') }}</textarea>
        </label>

        <label>Containment Steps Taken / Proposed
            <textarea name="containment_steps" rows="3" style="border: 1px solid #b9c9bf; border-radius: 5px; padding: 11px 12px; font: inherit;" placeholder="Immediate containment actions, e.g. IP blocked, session revoked, rate limiting enabled...">{{ old('containment_steps') }}</textarea>
        </label>

        <label>Initial Timeline Note (Optional)
            <textarea name="initial_note" rows="2" style="border: 1px solid #b9c9bf; border-radius: 5px; padding: 11px 12px; font: inherit;" placeholder="Note to record immediately in incident timeline...">{{ old('initial_note') }}</textarea>
        </label>

        <div style="margin-top: 10px;">
            <button type="submit">Submit Incident</button>
        </div>
    </form>
</div>
@endsection
