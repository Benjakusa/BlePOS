@extends('platform.layout')

@section('title', 'Platform audit log')

@section('content')
    <section class="panel" style="margin-bottom:16px">
        @if($integrity['intact'])
            <p class="notice">Chain intact as of {{ $integrity['checked_at'] }} · {{ $integrity['entries_checked'] }} entries verified.</p>
        @else
            <p class="error">Audit chain verification failed at entry {{ $integrity['broken_entry_id'] }}.</p>
        @endif
        <form method="get" class="form" style="display:grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 12px; align-items: end;">
            <x-input type="number" name="actor_id" label="Actor ID" :value="$filters['actor_id'] ?? ''" />
            <x-input name="action" label="Action" :value="$filters['action'] ?? ''" />
            <x-input name="target_type" label="Target type" :value="$filters['target_type'] ?? ''" />
            <x-input name="target_id" label="Target ID" :value="$filters['target_id'] ?? ''" />
            <x-input type="date" name="from" label="From" :value="$filters['from'] ?? ''" />
            <x-input type="date" name="to" label="To" :value="$filters['to'] ?? ''" />
            <div style="grid-column: 1 / -1; display:flex; gap: 12px; align-items:center;">
                <button type="submit">Filter audit entries</button>
                @if(auth('platform')->user()->hasPermission('platform.audit.export'))
                    <a href="{{ route('platform.audit.export', request()->query()) }}">Export matching entries as CSV</a>
                @endif
            </div>
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
