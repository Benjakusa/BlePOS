@extends('platform.layout')

@section('title', 'Open Support Ticket')

@section('content')
<header>
    <div>
        <h1>Open Support Ticket</h1>
        <div class="muted">Record an incoming customer issue, bug, or service request</div>
    </div>
    <div>
        <a href="{{ route('platform.support.cases.index') }}" class="btn secondary">Back to Support Tickets</a>
    </div>
</header>

@if ($errors->any())
    <div class="notice" style="border-color: #fca5a5; background: #fef2f2; color: #991b1b;" role="alert">
        <strong>Please check the errors:</strong>
        <ul style="margin: 8px 0 0; padding-left: 20px;">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="panel" style="max-width: 680px;">
    <form method="POST" action="{{ route('platform.support.cases.store') }}" class="form">
        @csrf

        <label>
            Tenant Organization:
            <select name="tenant_id" required style="border: 1px solid #b9c9bf; border-radius: 5px; padding: 11px 12px; font: inherit; width: 100%;">
                <option value="">Select tenant...</option>
                @foreach($tenants as $tenant)
                    <option value="{{ $tenant->id }}" @selected(old('tenant_id', $selectedTenantId) == $tenant->id)>
                        {{ $tenant->name }} ({{ $tenant->slug }})
                    </option>
                @endforeach
            </select>
        </label>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px;">
            <label>
                Requester Name:
                <input type="text" name="requester_name" value="{{ old('requester_name') }}" required>
            </label>
            <label>
                Requester Email:
                <input type="email" name="requester_email" value="{{ old('requester_email') }}" required>
            </label>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px;">
            <label>
                Category:
                <select name="category" style="border: 1px solid #b9c9bf; border-radius: 5px; padding: 11px 12px; font: inherit; width: 100%;">
                    <option value="general">General Inquiry</option>
                    <option value="billing">Billing & Subscriptions</option>
                    <option value="hardware_pos">POS Hardware & Printers</option>
                    <option value="inventory">Inventory & Stock Adjustments</option>
                    <option value="user_access">User Access & Passwords</option>
                    <option value="etims_tax">KRA eTIMS Tax Compliance</option>
                    <option value="system_bug">System Bug / Error</option>
                </select>
            </label>

            <label>
                Priority (determines SLA):
                <select name="priority" style="border: 1px solid #b9c9bf; border-radius: 5px; padding: 11px 12px; font: inherit; width: 100%;">
                    <option value="low">Low (72h SLA)</option>
                    <option value="medium" selected>Medium (24h SLA)</option>
                    <option value="high">High (12h SLA)</option>
                    <option value="urgent">Urgent (4h SLA)</option>
                </select>
            </label>
        </div>

        <label>
            Assign Agent (optional):
            <select name="assigned_agent_id" style="border: 1px solid #b9c9bf; border-radius: 5px; padding: 11px 12px; font: inherit; width: 100%;">
                <option value="">Leave unassigned</option>
                @foreach($agents as $agent)
                    <option value="{{ $agent->id }}" @selected(old('assigned_agent_id') == $agent->id)>
                        {{ $agent->name }} ({{ $agent->email }})
                    </option>
                @endforeach
            </select>
        </label>

        <label>
            Subject:
            <input type="text" name="subject" value="{{ old('subject') }}" required placeholder="e.g. Receipt printer failing to cut paper on POS checkout">
        </label>

        <label>
            Issue Description:
            <textarea name="description" rows="5" required style="border: 1px solid #b9c9bf; border-radius: 5px; padding: 11px 12px; font: inherit; width: 100%;">{{ old('description') }}</textarea>
        </label>

        <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 16px;">
            <a href="{{ route('platform.support.cases.index') }}" class="btn secondary">Cancel</a>
            <button type="submit">Open Ticket</button>
        </div>
    </form>
</div>
@endsection
