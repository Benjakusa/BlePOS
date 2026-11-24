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

<div class="panel">
    <form method="POST" action="{{ route('platform.security.incidents.store') }}" class="form" style="max-width: 600px; display: flex; flex-direction: column; gap: 14px;">
        @csrf

        <x-input name="title" label="Incident Title" required />

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px;">
            <x-input type="select" name="category" label="Category" required>
                <option value="brute_force" @selected(old('category') === 'brute_force')>Brute Force</option>
                <option value="mfa_abuse" @selected(old('category') === 'mfa_abuse')>MFA Abuse</option>
                <option value="credential_stuffing" @selected(old('category') === 'credential_stuffing')>Credential Stuffing</option>
                <option value="unauthorized_access" @selected(old('category') === 'unauthorized_access')>Unauthorized Access</option>
                <option value="data_exfiltration" @selected(old('category') === 'data_exfiltration')>Data Exfiltration</option>
                <option value="api_abuse" @selected(old('category') === 'api_abuse')>API Abuse</option>
                <option value="general" @selected(old('category') === 'general')>General Threat</option>
            </x-input>

            <x-input type="select" name="severity" label="Severity" required>
                <option value="low" @selected(old('severity') === 'low')>Low</option>
                <option value="medium" @selected(old('severity') === 'medium')>Medium</option>
                <option value="high" @selected(old('severity') === 'high')>High</option>
                <option value="critical" @selected(old('severity') === 'critical')>Critical</option>
            </x-input>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px;">
            <x-input type="select" name="platform_user_id" label="Assigned Officer (Optional)">
                <option value="">Unassigned</option>
                @foreach($officers as $officer)
                    <option value="{{ $officer->id }}" @selected(old('platform_user_id') == $officer->id)>
                        {{ $officer->name }} ({{ $officer->email }})
                    </option>
                @endforeach
            </x-input>

            <x-input type="select" name="tenant_id" label="Associated Tenant (Optional)">
                <option value="">Platform Wide</option>
                @foreach($tenants as $tenant)
                    <option value="{{ $tenant->id }}" @selected(old('tenant_id') == $tenant->id)>
                        {{ $tenant->name }} ({{ $tenant->subdomain }})
                    </option>
                @endforeach
            </x-input>
        </div>

        <x-input type="textarea" name="description" label="Description & Threat Indicators" rows="5" required />

        <x-input type="textarea" name="containment_steps" label="Containment Steps Taken / Proposed" rows="3" />

        <x-input type="textarea" name="initial_note" label="Initial Timeline Note (Optional)" rows="2" />

        <div style="margin-top: 10px;">
            <button type="submit">Submit Incident</button>
        </div>
    </form>
</div>
@endsection
