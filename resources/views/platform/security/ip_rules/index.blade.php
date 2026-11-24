@extends('platform.layout')

@section('title', 'IP & Abuse Rules')

@section('content')
<header>
    <div>
        <h1>IP & Network Abuse Rules</h1>
        <div class="muted">Dynamic allowlist, denylist, CIDR ranges, and temporary threat block policies (Annex A, 12.5)</div>
    </div>
    <div style="display: flex; gap: 8px;">
        <a href="{{ route('platform.security.index') }}" class="btn secondary">Security Centre</a>
    </div>
</header>

@if(session('notice'))
    <div class="notice" role="alert">{{ session('notice') }}</div>
@endif

@if($errors->any())
    <div class="error" style="margin-bottom: 16px;">
        <ul>
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 20px;">
    <div class="panel" style="max-width: none;">
        <h2 style="font-size: 18px; margin-top: 0;">Active & Configured IP Rules</h2>
        <table cellpadding="8" cellspacing="0" border="1" style="width: 100%; border-collapse: collapse; font-size: 13px;">
            <thead>
                <tr style="background: #f8faf9;">
                    <th>IP / CIDR</th>
                    <th>Type</th>
                    <th>Scope</th>
                    <th>Reason</th>
                    <th>Expires</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($rules as $rule)
                    <tr>
                        <td><code>{{ $rule->ip_or_cidr }}</code></td>
                        <td>
                            <span class="badge" style="background: {{ $rule->rule_type === 'denylist' ? '#fee2e2' : '#dcfce7' }}; color: {{ $rule->rule_type === 'denylist' ? '#991b1b' : '#166534' }}; padding: 2px 6px; border-radius: 4px; font-weight: 600;">
                                {{ strtoupper($rule->rule_type) }}
                            </span>
                        </td>
                        <td><code>{{ $rule->scope }}</code></td>
                        <td>{{ $rule->reason }}</td>
                        <td>{{ $rule->expires_at ? $rule->expires_at->toDayDateTimeString() : 'Permanent' }}</td>
                        <td>
                            @if($rule->isExpired())
                                <span class="muted">[Expired]</span>
                            @else
                                <span style="color: {{ $rule->is_active ? '#15803d' : '#9ca3af' }}; font-weight: 600;">
                                    {{ $rule->is_active ? 'Active' : 'Disabled' }}
                                </span>
                            @endif
                        </td>
                        <td>
                            <div style="display: flex; gap: 6px;">
                                <form method="POST" action="{{ route('platform.security.ip-rules.toggle', $rule) }}" style="display: inline;">
                                    @csrf
                                    <button type="submit" class="btn secondary" style="padding: 2px 6px; font-size: 12px; color: #146b4d;">
                                        {{ $rule->is_active ? 'Disable' : 'Enable' }}
                                    </button>
                                </form>
                                <form method="POST" action="{{ route('platform.security.ip-rules.destroy', $rule) }}" style="display: inline;">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn secondary" style="padding: 2px 6px; font-size: 12px; color: #b91c1c;">
                                        Delete
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="muted" style="text-align: center; padding: 20px;">No dynamic IP rules registered.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        {{ $rules->links() }}
    </div>

    <div>
        <div class="panel" style="max-width: none;">
            <h3 style="font-size: 16px; margin-top: 0;">Add Dynamic IP Rule</h3>
            <form method="POST" action="{{ route('platform.security.ip-rules.store') }}" style="display: grid; gap: 12px;">
                @csrf
                <label style="font-size: 13px;">IP or CIDR Network
                    <input name="ip_or_cidr" required placeholder="e.g. 198.51.100.5 or 10.0.0.0/8" value="{{ old('ip_or_cidr') }}">
                </label>
                <label style="font-size: 13px;">Rule Type
                    <select name="rule_type" required style="border: 1px solid #b9c9bf; border-radius: 5px; padding: 8px 10px; font: inherit;">
                        <option value="denylist" @selected(old('rule_type') === 'denylist')>Denylist (Block traffic)</option>
                        <option value="allowlist" @selected(old('rule_type') === 'allowlist')>Allowlist (Permit access)</option>
                    </select>
                </label>
                <label style="font-size: 13px;">Enforcement Scope
                    <select name="scope" required style="border: 1px solid #b9c9bf; border-radius: 5px; padding: 8px 10px; font: inherit;">
                        <option value="global" @selected(old('scope') === 'global')>Global (Platform & API)</option>
                        <option value="platform_console" @selected(old('scope') === 'platform_console')>Platform Console Only</option>
                        <option value="tenant_api" @selected(old('scope') === 'tenant_api')>Tenant APIs Only</option>
                    </select>
                </label>
                <label style="font-size: 13px;">Duration (Minutes)
                    <input type="number" name="duration_minutes" placeholder="Leave empty for permanent rule" value="{{ old('duration_minutes') }}">
                </label>
                <label style="font-size: 13px;">Justification / Reason
                    <textarea name="reason" rows="2" required style="border: 1px solid #b9c9bf; border-radius: 5px; padding: 8px 10px; font: inherit;" placeholder="Reason for rule...">{{ old('reason') }}</textarea>
                </label>
                <div>
                    <button type="submit" style="width: 100%;">Create Rule</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
