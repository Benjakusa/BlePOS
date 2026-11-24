@extends('platform.layout')

@section('title', 'Log Data Protection Request')

@section('content')
<header>
    <div>
        <h1>Log Data Protection Request</h1>
        <div class="muted">Record a Data Subject Access Request (DSAR) under Kenya DPA 2019 or GDPR</div>
    </div>
    <a href="{{ route('platform.compliance.dpa.index') }}" class="btn secondary">Back to Requests</a>
</header>

<div class="panel">
    <form method="POST" action="{{ route('platform.compliance.dpa.store') }}" class="form" style="max-width: 600px; display: flex; flex-direction: column; gap: 14px;">
        @csrf

        <x-input name="subject_name" label="Data Subject Full Name" required placeholder="e.g. John Kamau" />

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px;">
            <x-input type="email" name="subject_email" label="Subject Email Address" required placeholder="e.g. john@example.test" />
            <x-input type="tel" name="subject_phone" label="Subject Phone (Optional)" placeholder="e.g. +254712345678" />
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px;">
            <x-input type="select" name="request_type" label="Request Type" required>
                <option value="access_export" @selected(old('request_type') === 'access_export')>Subject Access / Export</option>
                <option value="rectification" @selected(old('request_type') === 'rectification')>Data Rectification</option>
                <option value="erasure_deletion" @selected(old('request_type') === 'erasure_deletion')>Erasure / Right to be Forgotten</option>
                <option value="objection" @selected(old('request_type') === 'objection')>Objection to Processing</option>
                <option value="consent_withdrawal" @selected(old('request_type') === 'consent_withdrawal')>Consent Withdrawal</option>
            </x-input>

            <x-input name="legal_basis" label="Legal Basis" value="Kenya DPA 2019 Sec 26" required />
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px;">
            <x-input type="select" name="tenant_id" label="Associated Tenant (Optional)">
                <option value="">Platform Global</option>
                @foreach($tenants as $tenant)
                    <option value="{{ $tenant->id }}" @selected(old('tenant_id') == $tenant->id)>
                        {{ $tenant->name }} ({{ $tenant->subdomain }})
                    </option>
                @endforeach
            </x-input>

            <x-input type="select" name="assigned_officer_id" label="Assigned Compliance Officer">
                @foreach($officers as $officer)
                    <option value="{{ $officer->id }}" @selected(old('assigned_officer_id') == $officer->id)>
                        {{ $officer->name }} ({{ $officer->email }})
                    </option>
                @endforeach
            </x-input>
        </div>

        <div style="margin-top: 10px;">
            <button type="submit">Submit Request</button>
        </div>
    </form>
</div>
@endsection
