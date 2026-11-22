@extends('platform.layout')

@section('title', 'Platform audit log')

@section('content')
    <section class="panel" style="margin-bottom:16px">
        @if($integrity['intact'])
            <p class="notice">Chain intact as of {{ $integrity['checked_at'] }} · {{ $integrity['entries_checked'] }} entries verified.</p>
        @else
            <p class="error">Audit chain verification failed at entry {{ $integrity['broken_entry_id'] }}.</p>
        @endif
        <form method="get" class="form">
            <label>Actor ID<input name="actor_id" type="number" value="{{ $filters['actor_id'] ?? '' }}"></label>
            <label>Action<input name="action" value="{{ $filters['action'] ?? '' }}"></label>
            <label>Target type<input name="target_type" value="{{ $filters['target_type'] ?? '' }}"></label>
            <label>Target ID<input name="target_id" value="{{ $filters['target_id'] ?? '' }}"></label>
            <label>From<input name="from" type="date" value="{{ $filters['from'] ?? '' }}"></label>
            <label>To<input name="to" type="date" value="{{ $filters['to'] ?? '' }}"></label>
            <button type="submit">Filter audit entries</button>
            @if(auth('platform')->user()->hasPermission('platform.audit.export'))
                <a href="{{ route('platform.audit.export', request()->query()) }}">Export matching entries as CSV</a>
            @endif
        </form>
    </section>
    <section class="panel" style="overflow-x:auto">
        <table cellpadding="9" cellspacing="0" border="1">
            <thead><tr><th>Time</th><th>Actor</th><th>Action</th><th>Target</th><th>Details</th><th>Chain hash</th></tr></thead>
            <tbody>@forelse($entries as $entry)
                <tr>
                    <td>{{ $entry->occurred_at->toDayDateTimeString() }}</td>
                    <td>{{ $entry->actor?->email ?? 'System / recovery' }}</td>
                    <td>{{ $entry->action }}</td>
                    <td>{{ $entry->target_type }} {{ $entry->target_id }}</td>
                    <td><pre>{{ json_encode($entry->details, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre></td>
                    <td><code>{{ $entry->entry_hash }}</code></td>
                </tr>
            @empty
                <tr><td colspan="6">No audit entries.</td></tr>
            @endforelse</tbody>
        </table>
        {{ $entries->links() }}
    </section>
@endsection
