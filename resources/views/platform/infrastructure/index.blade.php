@extends('platform.layout')

@section('title', 'Infrastructure & System Health')

@section('content')
<header>
    <div>
        <h1>Infrastructure & Health Console</h1>
        <div class="muted">Real subsystem probes, database servers, queues, scheduler, storage, and recovery controls (Annex A, Part 11)</div>
    </div>
    <div style="display: flex; gap: 8px;">
        <form method="POST" action="{{ route('platform.infrastructure.probes') }}" style="display: inline;">
            @csrf
            <button type="submit" class="btn">Refresh Probes</button>
        </form>
        <a href="{{ route('platform.infrastructure.queues') }}" class="btn secondary">Queues</a>
        <a href="{{ route('platform.infrastructure.migrations') }}" class="btn secondary">Migrations</a>
        <a href="{{ route('platform.infrastructure.backups') }}" class="btn secondary">Backups</a>
        <a href="{{ route('platform.infrastructure.restores') }}" class="btn secondary">Restore Workflow</a>
    </div>
</header>

@if(session('notice'))
    <div class="notice" role="alert">{{ session('notice') }}</div>
@endif

<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px; margin-bottom: 24px;">
    @foreach($health['probes'] as $name => $probe)
        <div class="panel" style="padding: 16px;">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <span class="muted" style="font-size: 13px; text-transform: uppercase;">{{ $name }}</span>
                <span class="badge" style="background: {{ $probe['status'] === 'healthy' ? '#EAEEF3' : ($probe['status'] === 'degraded' ? 'var(--clr-grey-tint)' : '#EAEEF3') }}; color: {{ $probe['status'] === 'healthy' ? 'var(--clr-green)' : ($probe['status'] === 'degraded' ? 'var(--clr-muted)' : 'var(--clr-red)') }};">
                    {{ strtoupper($probe['status']) }}
                </span>
            </div>
            <div style="font-size: 20px; font-weight: bold; margin-top: 8px;">
                {{ $probe['latency_ms'] }} ms
            </div>
            <div class="muted" style="font-size: 12px; margin-top: 4px;">
                @foreach($probe['details'] as $k => $v)
                    <div>{{ $k }}: <strong>{{ is_array($v) ? json_encode($v) : (is_bool($v) ? ($v ? 'true' : 'false') : $v) }}</strong></div>
                @endforeach
            </div>
        </div>
    @endforeach
</div>

<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 20px;">
    <div class="panel">
        <h2>Recent Backup Runs & Verification</h2>
        <table style="width: 100%; border-collapse: collapse; margin-top: 12px;">
            <thead>
                <tr style="border-bottom: 1px solid #e2e8f0;">
                    <th style="text-align: left; padding: 8px;">Filename</th>
                    <th style="text-align: center; padding: 8px;">Type</th>
                    <th style="text-align: right; padding: 8px;">Size</th>
                    <th style="text-align: center; padding: 8px;">Encrypted</th>
                    <th style="text-align: center; padding: 8px;">Status</th>
                    <th style="text-align: left; padding: 8px;">Completed</th>
                </tr>
            </thead>
            <tbody>
                @forelse($recentBackups as $backup)
                    <tr style="border-bottom: 1px solid #f1f5f9;">
                        <td style="padding: 8px;"><code>{{ $backup->filename }}</code></td>
                        <td style="text-align: center; padding: 8px;"><span class="badge">{{ strtoupper($backup->backup_type) }}</span></td>
                        <td style="text-align: right; padding: 8px;">{{ number_format($backup->size_bytes / 1048576, 2) }} MB</td>
                        <td style="text-align: center; padding: 8px;">
                            <span class="badge" style="background: var(--clr-blue-tint); color: #0369a1;">AES-256</span>
                        </td>
                        <td style="text-align: center; padding: 8px;">
                            <span class="badge" style="background: #EAEEF3; color: var(--clr-green);">{{ strtoupper($backup->status) }}</span>
                        </td>
                        <td style="padding: 8px; font-size: 13px;">{{ $backup->completed_at?->format('Y-m-d H:i') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" style="text-align: center; padding: 16px;" class="muted">No backup runs recorded yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="panel">
        <h3>Server Environment</h3>
        <p><strong>PHP Version:</strong> {{ $phpVersion }}</p>
        <p><strong>Laravel Framework:</strong> v{{ $laravelVersion }}</p>
        <p><strong>Database Connection:</strong> {{ config('database.default') }}</p>
        <p><strong>Cache Driver:</strong> {{ config('cache.default') }}</p>
        <p><strong>Queue Connection:</strong> {{ config('queue.default') }}</p>
        <p><strong>Applied Migrations:</strong> {{ $migrationStatus['total_ran'] }} (Batch {{ $migrationStatus['last_batch'] }})</p>
        <p><strong>Pending Migrations:</strong> {{ count($migrationStatus['pending_migrations']) }}</p>
    </div>
</div>
@endsection
