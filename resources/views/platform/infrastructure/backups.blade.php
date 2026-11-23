@extends('platform.layout')

@section('title', 'Platform Backups')

@section('content')
<header>
    <div>
        <h1>Backup Engine & Storage</h1>
        <div class="muted">Encrypted backups, integrity checksums, and tenant snapshot tracking (Annex A, Part 31)</div>
    </div>
    <div style="display: flex; gap: 8px;">
        <a href="{{ route('platform.infrastructure.index') }}" class="btn secondary">Back to Infrastructure</a>
        <a href="{{ route('platform.infrastructure.restores') }}" class="btn secondary">Restore Workflow</a>
    </div>
</header>

@if(session('notice'))
    <div class="notice" role="alert">{{ session('notice') }}</div>
@endif

<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 20px;">
    <div class="panel">
        <h2>Backup History</h2>
        <table style="width: 100%; border-collapse: collapse; margin-top: 12px;">
            <thead>
                <tr style="border-bottom: 1px solid #e2e8f0;">
                    <th style="text-align: left; padding: 8px;">Filename / Checksum</th>
                    <th style="text-align: center; padding: 8px;">Type</th>
                    <th style="text-align: left; padding: 8px;">Target</th>
                    <th style="text-align: right; padding: 8px;">Size</th>
                    <th style="text-align: center; padding: 8px;">Encryption</th>
                    <th style="text-align: center; padding: 8px;">Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($backups as $backup)
                    <tr style="border-bottom: 1px solid #f1f5f9;">
                        <td style="padding: 8px;">
                            <code>{{ $backup->filename }}</code>
                            <div class="muted" style="font-size: 11px;">SHA-256: {{ substr($backup->checksum, 0, 16) }}...</div>
                        </td>
                        <td style="text-align: center; padding: 8px;"><span class="badge">{{ strtoupper($backup->backup_type) }}</span></td>
                        <td style="padding: 8px;">{{ $backup->tenant?->name ?? 'Platform Central' }}</td>
                        <td style="text-align: right; padding: 8px;">{{ number_format($backup->size_bytes / 1048576, 2) }} MB</td>
                        <td style="text-align: center; padding: 8px;">
                            <span class="badge" style="background: #e0f2fe; color: #0369a1;">AES-256</span>
                        </td>
                        <td style="text-align: center; padding: 8px;">
                            <span class="badge" style="background: #dcfce7; color: #166534;">{{ strtoupper($backup->status) }}</span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" style="text-align: center; padding: 16px;" class="muted">No backups recorded yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div style="margin-top: 16px;">
            {{ $backups->links() }}
        </div>
    </div>

    <div class="panel">
        <h3>Trigger Manual Backup</h3>
        <form method="POST" action="{{ route('platform.infrastructure.backups.trigger') }}" class="form" style="margin-top: 12px;">
            @csrf
            <label>
                Backup Scope / Type:
                <select name="backup_type" style="border: 1px solid #b9c9bf; border-radius: 5px; padding: 10px 12px; font: inherit; width: 100%;">
                    <option value="database">Central Database Snapshot</option>
                    <option value="tenant_database">Tenant Database Snapshot</option>
                    <option value="files">Platform Storage & Files</option>
                    <option value="full_system">Full System Disaster Recovery Snapshot</option>
                </select>
            </label>

            <label>
                Specific Tenant (if tenant scope):
                <select name="tenant_id" style="border: 1px solid #b9c9bf; border-radius: 5px; padding: 10px 12px; font: inherit; width: 100%;">
                    <option value="">None (Platform-wide)</option>
                    @foreach($tenants as $t)
                        <option value="{{ $t->id }}">{{ $t->name }} ({{ $t->slug }})</option>
                    @endforeach
                </select>
            </label>

            <button type="submit" style="margin-top: 12px;">Start Backup Run</button>
        </form>
    </div>
</div>
@endsection
