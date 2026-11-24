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
    <form method="POST" action="{{ route('platform.compliance.dpa.store') }}" class="form" style="max-width: 600px;">
        @csrf

        <label>Data Subject Full Name
            <input name="subject_name" value="{{ old('subject_name') }}" required placeholder="e.g. John Kamau">
        </label>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px;">
            <label>Subject Email Address
                <input type="email" name="subject_email" value="{{ old('subject_email') }}" required placeholder="e.g. john@example.test">
            </label>

            <label>Subject Phone (Optional)
                <input name="subject_phone" value="{{ old('subject_phone') }}" placeholder="e.g. +254712345678">
            </label>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px;">
            <label>Request Type
                <select name="request_type" required style="border: 1px solid #b9c9bf; border-radius: 5px; padding: 11px 12px; font: inherit;">
                    <option value="access_export" @selected(old('request_type') === 'access_export')>Subject Access / Export</option>
                    <option value="rectification" @selected(old('request_type') === 'rectification')>Data Rectification</option>
                    <option value="erasure_deletion" @selected(old('request_type') === 'erasure_deletion')>Erasure / Right to be Forgotten</option>
                    <option value="objection" @selected(old('request_type') === 'objection')>Objection to Processing</option>
                    <option value="consent_withdrawal" @selected(old('request_type') === 'consent_withdrawal')>Consent Withdrawal</option>
                </select>
            </label>

            <label>Legal Basis
                <input name="legal_basis" value="{{ old('legal_basis', 'Kenya DPA 2019 Sec 26') }}" required>
            </label>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px;">
            <label>Associated Tenant (Optional)
                <select name="tenant_id" style="border: 1px solid #b9c9bf; border-radius: 5px; padding: 11px 12px; font: inherit;">
                    <option value="">Platform Global</option>
                    @foreach($tenants as $tenant)
                        <option value="{{ $tenant->id }}" @selected(old('tenant_id') == $tenant->id)>
                            {{ $tenant->name }} ({{ $tenant->subdomain }})
                        </option>
                    @endforeach
                </select>
            </label>

            <label>Assigned Compliance Officer
                <select name="assigned_officer_id" style="border: 1px solid #b9c9bf; border-radius: 5px; padding: 11px 12px; font: inherit;">
                    @foreach($officers as $officer)
                        <option value="{{ $officer->id }}" @selected(old('assigned_officer_id') == $officer->id)>
                            {{ $officer->name }} ({{ $officer->email }})
                        </option>
                    @endforeach
                </select>
            </label>
        </div>

        <div style="margin-top: 10px;">
            <button type="submit">Submit Request</button>
        </div>
    </form>
</div>
@endsection
