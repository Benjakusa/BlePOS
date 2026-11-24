@extends('platform.layout')

@section('title', 'Cryptographic Key Management')

@section('content')
<header>
    <div>
        <h1>Cryptographic Key Management</h1>
        <div class="muted">Key governance, envelope encryption hierarchy, rotation schedules, and re-encryption progress (Annex A, 12.4)</div>
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

<div class="panel" style="max-width: none; margin-bottom: 24px;">
    <h2 style="font-size: 18px; margin-top: 0;">Envelope Encryption Hierarchy</h2>
    <div class="muted" style="margin-bottom: 16px; font-size: 13px;">
        Per-tenant data keys and column encryption keys are cryptographically wrapped by root Platform Master Keys. Raw secret keys are never exposed in UI or database tables.
    </div>

    <table cellpadding="10" cellspacing="0" border="1" style="width: 100%; border-collapse: collapse; font-size: 13px;">
        <thead>
            <tr style="background: #f8faf9;">
                <th>Key ID</th>
                <th>Name / Purpose</th>
                <th>Type</th>
                <th>Algorithm</th>
                <th>Version</th>
                <th>Wrapping Key</th>
                <th>Fingerprint (SHA-256)</th>
                <th>Rotation Schedule</th>
                <th>Next Due</th>
                <th>Re-encryption</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            @foreach($keys as $key)
                <tr>
                    <td><code>{{ $key->key_id }}</code></td>
                    <td>
                        <strong>{{ $key->name }}</strong>
                        @if($key->metadata)
                            <div class="muted" style="font-size: 11px;">
                                @foreach($key->metadata as $mk => $mv)
                                    <span>{{ $mk }}: {{ $mv }}</span>@if(!$loop->last) · @endif
                                @endforeach
                            </div>
                        @endif
                    </td>
                    <td><code>{{ $key->key_type }}</code></td>
                    <td>{{ $key->algorithm }}</td>
                    <td>
                        <span class="badge" style="background: #e0e7ff; color: #3730a3; padding: 2px 6px; border-radius: 4px;">
                            v{{ $key->version }}
                        </span>
                    </td>
                    <td>
                        @if($key->envelope_wrapped_by_key_id)
                            <code>{{ $key->envelope_wrapped_by_key_id }}</code>
                        @else
                            <span class="muted">Root of Trust</span>
                        @endif
                    </td>
                    <td>
                        <code style="font-size: 11px;" title="{{ $key->fingerprint }}">
                            {{ Str::limit($key->fingerprint, 20) }}
                        </code>
                    </td>
                    <td>Every {{ $key->rotation_schedule_days }} days</td>
                    <td>
                        @if($key->next_rotation_due_at)
                            <span style="color: {{ $key->next_rotation_due_at->isPast() ? '#b91c1c' : '#15803d' }};">
                                {{ $key->next_rotation_due_at->toFormattedDateString() }}
                            </span>
                        @else
                            N/A
                        @endif
                    </td>
                    <td>
                        <div style="font-weight: 600;">{{ $key->reencryption_progress_percent }}%</div>
                        <div style="width: 60px; height: 6px; background: #e2e8f0; border-radius: 3px; overflow: hidden; margin-top: 2px;">
                            <div style="width: {{ $key->reencryption_progress_percent }}%; height: 100%; background: #166534;"></div>
                        </div>
                    </td>
                    <td>
                        <details>
                            <summary style="cursor: pointer; color: #16A34A; font-weight: 600;">Rotate Key</summary>
                            <form method="POST" action="{{ route('platform.security.keys.rotate', $key) }}" style="margin-top: 8px; display: flex; flex-direction: column; gap: 6px;">
                                @csrf
                                <x-input name="reason" label="Rotation justification" required :compact="true" />
                                <button type="submit" class="btn" style="padding: 4px 8px; font-size: 11px;">Confirm Rotation</button>
                            </form>
                        </details>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection
