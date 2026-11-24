@extends('platform.layout')

@section('title', 'Platform users')

@section('content')
    @if(auth('platform')->user()->hasPermission('platform.security.users.invite'))
    <section class="panel">
        <h2>Invite platform staff</h2>
        <form class="form" method="post" action="{{ route('platform.users.invite') }}">
            @csrf
            <x-input type="email" name="email" label="Email address" required />
            <x-input type="select" name="platform_role_id" label="Role" required>
                @foreach($roles->where('slug', '!=', 'super-administrator') as $role)
                    <option value="{{ $role->id }}">{{ $role->name }}</option>
                @endforeach
            </x-input>
            <x-input name="reason" label="Reason" minlength="8" maxlength="1000" required />
            <button type="submit">Send invitation</button>
        </form>
    </section>
    @endif

    <section class="panel" style="margin-top:18px">
        <h2>Platform staff</h2>
        <div style="overflow-x:auto"><table cellpadding="9" cellspacing="0" border="1">
            <thead><tr><th>Name</th><th>Email</th><th>Status</th><th>MFA</th><th>Roles</th><th>Last login</th><th>Controls</th></tr></thead>
            <tbody>@foreach($users as $platformUser)
                <tr>
                    <td>{{ $platformUser->name }}</td><td>{{ $platformUser->email }}</td><td>{{ $platformUser->status }}</td>
                    <td>{{ $platformUser->mfa_confirmed_at ? 'Enrolled' : 'Required' }}</td>
                    <td>{{ $platformUser->roles->pluck('name')->join(', ') ?: 'None' }}</td>
                    <td>{{ $platformUser->last_login_at?->toDayDateTimeString() ?? 'Never' }}</td>
                    <td>
                        @if(!$platformUser->is(auth('platform')->user()))
                            @if(auth('platform')->user()->hasPermission('platform.security.users.status'))
                                <form method="post" action="{{ route('platform.users.status', $platformUser) }}" style="display:flex;gap:6px;align-items:center;margin-bottom:4px">
                                    @csrf @method('PATCH')
                                    <x-input type="select" name="status" :compact="true">
                                        <option value="active">Active</option>
                                        <option value="locked">Lock</option>
                                        <option value="disabled">Deactivate</option>
                                    </x-input>
                                    <x-input name="reason" label="Reason" minlength="8" required :compact="true" />
                                    <button type="submit">Save status</button>
                                </form>
                            @endif
                            @if(auth('platform')->user()->hasPermission('platform.security.users.mfa-reset.request'))
                                <form method="post" action="{{ route('platform.users.mfa-reset', $platformUser) }}" style="display:flex;gap:6px;align-items:center;margin-bottom:4px">
                                    @csrf
                                    <x-input name="reason" label="MFA reset reason" minlength="8" required :compact="true" />
                                    <button type="submit">Request MFA reset</button>
                                </form>
                            @endif
                            @if(auth('platform')->user()->hasPermission('platform.security.users.password-reset'))
                                <form method="post" action="{{ route('platform.users.password-reset', $platformUser) }}" style="display:flex;gap:6px;align-items:center;margin-bottom:4px">
                                    @csrf
                                    <x-input name="reason" label="Password reset reason" minlength="8" required :compact="true" />
                                    <button type="submit">Require password reset</button>
                                </form>
                            @endif
                            @if(auth('platform')->user()->hasPermission('platform.security.sessions.revoke'))
                                <form method="post" action="{{ route('platform.users.sessions.revoke-all', $platformUser) }}">
                                    @csrf @method('DELETE')
                                    <button type="submit">Revoke all sessions</button>
                                </form>
                            @endif
                        @endif
                        @if(!$platformUser->is(auth('platform')->user()) && auth('platform')->user()->hasPermission('platform.security.users.roles.manage'))
                            @foreach($platformUser->roles as $assignedRole)
                                <form method="post" action="{{ route('platform.users.roles.remove', [$platformUser, $assignedRole]) }}" style="display:flex;gap:6px;align-items:center;margin-bottom:4px">
                                    @csrf @method('DELETE')
                                    <x-input name="reason" label="Reason to remove {{ $assignedRole->name }}" minlength="8" required :compact="true" />
                                    <button type="submit">Remove {{ $assignedRole->name }}</button>
                                </form>
                            @endforeach
                        @endif
                        @if(!$platformUser->is(auth('platform')->user()) && auth('platform')->user()->hasPermission('platform.security.users.roles.manage'))
                            @foreach($roles as $role)
                                @if(!$platformUser->roles->contains('id', $role->id))
                                    <form method="post" action="{{ route('platform.users.roles.assign', $platformUser) }}" style="display:flex;gap:6px;align-items:center;margin-bottom:4px">
                                        @csrf <input type="hidden" name="platform_role_id" value="{{ $role->id }}">
                                        <x-input name="reason" label="Reason to grant {{ $role->name }}" minlength="8" required :compact="true" />
                                        <button type="submit">Grant {{ $role->name }}</button>
                                    </form>
                                @endif
                            @endforeach
                        @endif
                    </td>
                </tr>
            @endforeach</tbody>
        </table></div>
        {{ $users->links() }}
    </section>

    <section class="panel" style="margin-top:18px">
        <h2>Pending invitations</h2>
        @forelse($invitations as $invitation)
            <div style="border-top:1px solid #dce5df;padding:12px 0">
                <p>{{ $invitation->email }} · {{ $invitation->role->name }} · expires {{ $invitation->expires_at->toDayDateTimeString() }}</p>
                @if(auth('platform')->user()->hasPermission('platform.security.users.invite'))
                    <form method="post" action="{{ route('platform.users.invitations.resend', $invitation) }}" style="display:flex;gap:8px;margin-bottom:8px">
                        @csrf
                        <x-input name="reason" label="Reason to resend" minlength="8" required :compact="true" />
                        <button type="submit">Resend</button>
                    </form>
                    <form method="post" action="{{ route('platform.users.invitations.cancel', $invitation) }}" style="display:flex;gap:8px">
                        @csrf @method('DELETE')
                        <x-input name="reason" label="Reason to cancel" minlength="8" required :compact="true" />
                        <button type="submit">Cancel</button>
                    </form>
                @endif
            </div>
        @empty
            <p class="muted">No pending invitations.</p>
        @endforelse
    </section>
@endsection
