<div>
    <!-- Simplicity is the consequence of refined emotions. - Jean D'Alembert -->
</div>
@extends('platform.layout')

@section('title', 'Security events')

@section('content')
    <section class="panel" style="max-width:none; margin-bottom:18px">
        <form method="get" action="{{ route('platform.security.events.index') }}" style="display:flex; gap:12px; align-items:end; flex-wrap:wrap">
            <label style="min-width:220px">Event, account or IP
                <input name="search" value="{{ $filters['search'] ?? '' }}" maxlength="120">
            </label>
            <label>Severity
                <select name="severity" style="border:1px solid #b9c9bf; border-radius:5px; padding:11px 12px; font:inherit">
                    <option value="">All severities</option>
                    @foreach(['info' => 'Info', 'warning' => 'Warning', 'critical' => 'Critical'] as $value => $label)
                        <option value="{{ $value }}" @selected(($filters['severity'] ?? '') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </label>
            <button type="submit">Filter</button>
            @if(($filters['search'] ?? null) || ($filters['severity'] ?? null))
                <a href="{{ route('platform.security.events.index') }}">Clear filters</a>
            @endif
        </form>
    </section>

    <section class="panel" style="max-width:none; overflow-x:auto">
        <table cellpadding="9" cellspacing="0" border="1" style="width:100%; border-collapse:collapse">
            <thead><tr><th>Occurred</th><th>Severity</th><th>Event</th><th>Account</th><th>IP</th><th>Device / browser</th><th>Metadata</th></tr></thead>
            <tbody>
            @forelse($events as $event)
                <tr>
                    <td>{{ $event->occurred_at->toDayDateTimeString() }}</td>
                    <td>{{ ucfirst($event->severity) }}</td>
                    <td>{{ $event->event_type }}</td>
                    <td>{{ $event->user?->name ?? 'Unknown account' }}@if($event->user) <span class="muted">· {{ $event->user->email }}</span>@endif</td>
                    <td>{{ $event->ip_address ?? 'Unavailable' }}</td>
                    <td>{{ $event->user_agent ?? 'Unavailable' }}</td>
                    <td><details><summary>Details</summary><pre style="white-space:pre-wrap">{{ $event->metadata ? json_encode($event->metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) : 'No metadata' }}</pre></details></td>
                </tr>
            @empty
                <tr><td colspan="7">No security events match these filters.</td></tr>
            @endforelse
            </tbody>
        </table>
        {{ $events->links() }}
    </section>
@endsection
