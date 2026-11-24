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

<div class="panel" style="max-width: 680px;">
    <form method="POST" action="{{ route('platform.support.cases.store') }}" class="form" style="display: flex; flex-direction: column; gap: 14px;">
        @csrf

        <x-input type="select" name="tenant_id" label="Tenant Organization" required>
            <option value="">Select tenant...</option>
            @foreach($tenants as $tenant)
                <option value="{{ $tenant->id }}" @selected(old('tenant_id', $selectedTenantId) == $tenant->id)>
                    {{ $tenant->name }} ({{ $tenant->slug }})
                </option>
            @endforeach
        </x-input>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px;">
            <x-input name="requester_name" label="Requester Name" required placeholder="e.g. Mary Wanjiku" />
            <x-input type="email" name="requester_email" label="Requester Email" required placeholder="e.g. mary@example.test" />
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px;">
            <x-input type="select" name="category" label="Category" required>
                <option value="pos_hardware">POS Hardware / Printing</option>
                <option value="billing">Billing & Subscriptions</option>
                <option value="etims_sync">eTIMS Sync / KRA Fiscalisation</option>
                <option value="account_access">Account Access / MFA</option>
                <option value="general">General Support</option>
            </x-input>

            <x-input type="select" name="priority" label="Priority" required>
                <option value="low">Low</option>
                <option value="normal" selected>Normal</option>
                <option value="high">High</option>
                <option value="critical">Critical / Outage</option>
            </x-input>
        </div>

        <x-input name="subject" label="Subject / Problem Summary" required placeholder="Brief description of the issue" />

        <x-input type="textarea" name="description" label="Detailed Description / Steps to Reproduce" rows="5" required />

        <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 10px;">
            <a href="{{ route('platform.support.cases.index') }}" class="btn secondary">Cancel</a>
            <button type="submit">Create Support Ticket</button>
        </div>
    </form>
</div>
@endsection
