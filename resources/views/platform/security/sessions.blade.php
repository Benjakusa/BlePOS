@extends('platform.layout')

@section('title', 'Platform sessions')

@section('content')
    <section class="panel" style="overflow-x:auto">
        <table cellpadding="9" cellspacing="0" border="1">
            <thead><tr><th>User</th><th>MFA</th><th>Signed in</th><th>Last activity</th><th>IP</th><th>Approx. location</th><th>Device / browser</th><th>Expires</th><th>Action</th></tr></thead>
            <tbody>@forelse($sessions as $platformSession)
                <tr>
                    <td>{{ $platformSession->user->name }} · {{ $platformSession->user->email }}</td>
                    <td>{{ $platformSession->user->mfa_confirmed_at ? 'Enrolled' : 'Not enrolled' }}</td>
                    <td>{{ $platformSession->logged_in_at->toDayDateTimeString() }}</td>
                    <td>{{ $platformSession->last_activity_at->toDayDateTimeString() }}</td>
                    <td>{{ $platformSession->ip_address ?? 'Unavailable' }}</td>
                    <td>Unavailable</td>
                    <td>{{ $platformSession->user_agent ?? 'Unavailable' }}</td>
                    <td>{{ $platformSession->expires_at->toDayDateTimeString() }}</td>
                    <td><form method="post" action="{{ route('platform.sessions.destroy', $platformSession) }}">@csrf @method('DELETE')<button type="submit">Revoke</button></form></td>
                </tr>
            @empty
                <tr><td colspan="9">No active sessions.</td></tr>
            @endforelse</tbody>
        </table>
        {{ $sessions->links() }}
    </section>
@endsection
