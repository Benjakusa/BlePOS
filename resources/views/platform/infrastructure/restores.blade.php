@extends('platform.layout')

@section('title', 'Tenant Restore & Recovery')

@section('content')
<header>
    <div>
        <h1>Tenant Restore & Disaster Recovery</h1>
        <div class="muted">Two-person approval and isolated sandbox restoration workflow (Annex A, Part 31)</div>
    </div>
    <div style="display: flex; gap: 8px;">
        <a href="{{ route('platform.infrastructure.index') }}" class="btn secondary">Back to Infrastructure</a>
        <a href="{{ route('platform.infrastructure.backups') }}" class="btn secondary">Backups</a>
    </div>
</header>

@if(session('notice'))
    <div class="notice" role="alert">{{ session('notice') }}</div>
@endif

@if ($errors->any())
    <div class="notice" style="border-color: #DC2626; background: #EAEEF3; color: var(--clr-red);" role="alert">
        <strong>Please check the errors:</strong>
        <ul style="margin: 8px 0 0; padding-left: 20px;">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="notice" style="background: var(--clr-blue-tint); border-color: #bfdbfe; color: #1e40af; margin-bottom: 24px;">
    <strong>Mandatory Disaster Recovery Invariants (Annex A, Part 31):</strong>
    A live tenant database is never overwritten through a casual admin action. Every restore must first test in an isolated sandbox, undergo secondary administrator approval (Two-Person Rule), and produce an immutable audit log trail.
</div>

<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 20px;">
    <div class="panel">
        <h2>Restore Requests & Execution Ledger</h2>
        <table style="width: 100%; border-collapse: collapse; margin-top: 12px;">
            <thead>
                <tr style="border-bottom: 1px solid #e2e8f0;">
                    <th style="text-align: left; padding: 8px;">ID</th>
                    <th style="text-align: left; padding: 8px;">Tenant</th>
                    <th style="text-align: left; padding: 8px;">Backup File</th>
                    <th style="text-align: left; padding: 8px;">Requester</th>
                    <th style="text-align: center; padding: 8px;">Status</th>
                    <th style="text-align: right; padding: 8px;">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($restores as $restore)
                    <tr style="border-bottom: 1px solid #f1f5f9;">
                        <td style="padding: 8px;">#{{ $restore->id }}</td>
                        <td style="padding: 8px;"><strong>{{ $restore->tenant->name }}</strong></td>
                        <td style="padding: 8px;"><code>{{ $restore->backupRun->filename }}</code></td>
                        <td style="padding: 8px;">{{ $restore->requester->name }}</td>
                        <td style="text-align: center; padding: 8px;">
                            <span class="badge" style="background: #EAEEF3; color: {{ $restore->status === 'test_restored' ? '#16A34A' : '#B45309' }};">
                                {{ strtoupper(str_replace('_', ' ', $restore->status)) }}
                            </span>
                        </td>
                        <td style="text-align: right; padding: 8px;">
                            @if($restore->status === 'requested')
                                @if($restore->requested_by !== auth('platform')->id())
                                    <form method="POST" action="{{ route('platform.infrastructure.restores.approve', $restore) }}" style="display: inline;" onsubmit="return confirm('Verify an isolated restore for tenant {{ $restore->tenant->name }}?');">
                                        @csrf
                                        <button type="submit" class="btn" style="padding: 4px 8px; font-size: 12px;">Approve & Verify Sandbox</button>
                                    </form>
                                @else
                                    <span class="muted" style="font-size: 12px;">Two-person rule: awaits 2nd admin</span>
                                @endif
                            @else
                                <span class="muted" style="font-size: 12px;">Sandbox verified {{ $restore->executed_at?->format('M d, H:i') }}</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" style="text-align: center; padding: 16px;" class="muted">No restore requests recorded.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="panel">
        <h3>Initiate Tenant Restore</h3>
        <form method="POST" action="{{ route('platform.infrastructure.restores.store') }}" class="form" style="margin-top: 12px; display: flex; flex-direction: column; gap: 12px;">
            @csrf
            <x-input type="select" name="tenant_id" label="Target Tenant" required>
                <option value="">Select tenant...</option>
                @foreach($tenants as $t)
                    <option value="{{ $t->id }}">{{ $t->name }} ({{ $t->slug }})</option>
                @endforeach
            </x-input>

            <x-input type="select" name="platform_backup_run_id" label="Restore Snapshot / Point" required>
                <option value="">Select backup run...</option>
                @foreach($backups as $b)
                    <option value="{{ $b->id }}">{{ $b->filename }} ({{ $b->created_at->format('Y-m-d H:i') }})</option>
                @endforeach
            </x-input>

            <x-input type="textarea" name="reason" label="Business Justification / Reason" rows="3" required placeholder="State incident ticket or data corruption cause..." />

            <button type="submit" style="margin-top: 4px;">Submit Restore Request</button>
        </form>
    </div>
</div>
@endsection
