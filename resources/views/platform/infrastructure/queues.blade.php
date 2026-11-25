@extends('platform.layout')

@section('title', 'Queues & Failed Jobs')

@section('content')
<header>
    <div>
        <h1>Queues & Background Jobs</h1>
        <div class="muted">Worker status, throughput, and failed job investigation (Annex A 11.4)</div>
    </div>
    <div style="display: flex; gap: 8px;">
        <a href="{{ route('platform.infrastructure.index') }}" class="btn secondary">Back to Infrastructure</a>
        @if($failedJobs->isNotEmpty())
            <form method="POST" action="{{ route('platform.infrastructure.queues.retry', 'all') }}" style="display: inline;" data-motion-confirm="Retry all failed background jobs?">
                @csrf
                <button type="submit" class="btn">Retry All Failed Jobs</button>
            </form>
        @endif
    </div>
</header>

@if(session('notice'))
    <div class="notice" role="alert">{{ session('notice') }}</div>
@endif

<div class="panel" style="margin-bottom: 24px;">
    <h2>Queue Worker Telemetry</h2>
    <p><strong>Connection Driver:</strong> {{ $queueProbe['details']['connection'] ?? config('queue.default') }}</p>
    <p><strong>Failed Jobs Count:</strong> {{ $queueProbe['details']['failed_jobs_count'] ?? 0 }}</p>
    <p><strong>Subsystem Status:</strong> <span class="badge">{{ strtoupper($queueProbe['status']) }}</span></p>
</div>

<div class="panel">
    <h2>Failed Background Jobs</h2>
    <table style="width: 100%; border-collapse: collapse; margin-top: 12px;">
        <thead>
            <tr style="border-bottom: 1px solid #e2e8f0;">
                <th style="text-align: left; padding: 8px;">ID</th>
                <th style="text-align: left; padding: 8px;">Connection / Queue</th>
                <th style="text-align: left; padding: 8px;">Exception Summary</th>
                <th style="text-align: left; padding: 8px;">Failed At</th>
                <th style="text-align: right; padding: 8px;">Action</th>
            </tr>
        </thead>
        <tbody>
            @forelse($failedJobs as $job)
                <tr style="border-bottom: 1px solid #f1f5f9;">
                    <td style="padding: 8px;"><code>{{ $job->uuid ?? $job->id }}</code></td>
                    <td style="padding: 8px;">{{ $job->connection }} / {{ $job->queue }}</td>
                    <td style="padding: 8px; font-size: 13px; color: #dc2626;">{{ Str::limit($job->exception, 120) }}</td>
                    <td style="padding: 8px; font-size: 13px;">{{ $job->failed_at }}</td>
                    <td style="text-align: right; padding: 8px;">
                        <form method="POST" action="{{ route('platform.infrastructure.queues.retry', $job->id) }}" style="display: inline;">
                            @csrf
                            <button type="submit" class="btn secondary" style="padding: 4px 8px; font-size: 12px;">Retry</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" style="text-align: center; padding: 16px;" class="muted">No failed jobs recorded. Queue is running clean!</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div style="margin-top: 16px;">
        {{ $failedJobs->links() }}
    </div>
</div>
@endsection
