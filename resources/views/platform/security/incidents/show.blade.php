@extends('platform.layout')

@section('title', 'Incident ' . $incident->incident_number)

@section('content')
<header>
    <div>
        <div style="display: flex; align-items: center; gap: 10px;">
            <h1>{{ $incident->incident_number }}</h1>
            <span class="badge" style="background: {{ $incident->severity === 'critical' ? '#fee2e2' : ($incident->severity === 'high' ? '#ffedd5' : '#fef9c3') }}; color: {{ $incident->severity === 'critical' ? '#991b1b' : ($incident->severity === 'high' ? '#9a3412' : '#854d0e') }}; padding: 4px 8px; border-radius: 4px; font-weight: 700;">
                {{ strtoupper($incident->severity) }}
            </span>
            <span class="badge" style="background: {{ in_array($incident->status, ['resolved', 'closed']) ? '#dcfce7' : '#e0e7ff' }}; color: {{ in_array($incident->status, ['resolved', 'closed']) ? '#166534' : '#3730a3' }}; padding: 4px 8px; border-radius: 4px;">
                {{ strtoupper($incident->status) }}
            </span>
        </div>
        <div class="muted" style="margin-top: 4px;">{{ $incident->title }}</div>
    </div>
    <div style="display: flex; gap: 8px;">
        <a href="{{ route('platform.security.incidents.index') }}" class="btn secondary">All Incidents</a>
    </div>
</header>

@if(session('notice'))
    <div class="notice" role="alert">{{ session('notice') }}</div>
@endif

<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 20px;">
    <div>
        <div class="panel" style="max-width: none; margin-bottom: 20px;">
            <h2 style="font-size: 18px; margin-top: 0;">Threat Overview & Description</h2>
            <div style="white-space: pre-wrap; margin-bottom: 16px; line-height: 1.5;">{{ $incident->description }}</div>

            @if($incident->containment_steps)
                <h3 style="font-size: 15px; margin-bottom: 6px;">Containment Measures</h3>
                <div style="background: #f8faf9; border: 1px solid #e2e8f0; border-radius: 6px; padding: 12px; margin-bottom: 16px; white-space: pre-wrap;">{{ $incident->containment_steps }}</div>
            @endif

            @if($incident->resolution_notes)
                <h3 style="font-size: 15px; margin-bottom: 6px; color: #166534;">Resolution Notes</h3>
                <div style="background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 6px; padding: 12px; white-space: pre-wrap;">{{ $incident->resolution_notes }}</div>
            @endif
        </div>

        <div class="panel" style="max-width: none;">
            <h2 style="font-size: 18px; margin-top: 0;">Incident Timeline & Response Actions</h2>

            <div style="display: flex; flex-direction: column; gap: 12px; margin-bottom: 24px;">
                @forelse($incident->notes as $note)
                    <div style="border-left: 3px solid {{ $note->is_containment_action ? '#b91c1c' : '#146b4d' }}; padding-left: 12px;">
                        <div style="display: flex; justify-content: space-between; font-size: 12px;" class="muted">
                            <span>
                                <strong>{{ $note->user->name }}</strong>
                                @if($note->is_containment_action)
                                    <span style="color: #b91c1c; font-weight: 600;">[Containment Action]</span>
                                @endif
                            </span>
                            <span>{{ $note->created_at->format('M d, Y H:i:s') }}</span>
                        </div>
                        <div style="margin-top: 4px; white-space: pre-wrap; font-size: 14px;">{{ $note->note }}</div>
                    </div>
                @empty
                    <div class="muted">No timeline notes recorded yet.</div>
                @endforelse
            </div>

            <form method="POST" action="{{ route('platform.security.incidents.notes', $incident) }}" style="display: grid; gap: 10px; border-top: 1px solid #e2e8f0; padding-top: 16px;">
                @csrf
                <label style="font-weight: 600; font-size: 14px;">Append Timeline Note / Action
                    <textarea name="note" rows="3" required style="border: 1px solid #b9c9bf; border-radius: 5px; padding: 10px 12px; font: inherit;" placeholder="Record investigation findings, mitigation steps, or updates..."></textarea>
                </label>
                <label style="display: flex; align-items: center; gap: 8px; font-weight: normal; font-size: 13px;">
                    <input type="checkbox" name="is_containment_action" value="1" style="width: auto;">
                    Mark as active containment action
                </label>
                <div>
                    <button type="submit" style="padding: 8px 14px; font-size: 13px;">Add Note</button>
                </div>
            </form>
        </div>
    </div>

    <div>
        <div class="panel" style="max-width: none; margin-bottom: 20px;">
            <h3 style="font-size: 16px; margin-top: 0;">Incident Metadata</h3>
            <table cellpadding="6" cellspacing="0" style="width: 100%; font-size: 13px;">
                <tr>
                    <td class="muted">Category:</td>
                    <td><code>{{ $incident->category }}</code></td>
                </tr>
                <tr>
                    <td class="muted">Severity:</td>
                    <td><strong>{{ strtoupper($incident->severity) }}</strong></td>
                </tr>
                <tr>
                    <td class="muted">Status:</td>
                    <td><strong>{{ ucfirst($incident->status) }}</strong></td>
                </tr>
                <tr>
                    <td class="muted">Assigned:</td>
                    <td>{{ $incident->assignedOfficer?->name ?? 'Unassigned' }}</td>
                </tr>
                <tr>
                    <td class="muted">Reporter:</td>
                    <td>{{ $incident->reporter?->name ?? 'System' }}</td>
                </tr>
                <tr>
                    <td class="muted">Tenant Scope:</td>
                    <td>{{ $incident->tenant?->name ?? 'Platform Global' }}</td>
                </tr>
                <tr>
                    <td class="muted">Created:</td>
                    <td>{{ $incident->created_at->format('M d, Y H:i') }}</td>
                </tr>
                @if($incident->acknowledged_at)
                    <tr>
                        <td class="muted">Acknowledged:</td>
                        <td>{{ $incident->acknowledged_at->format('M d, Y H:i') }}</td>
                    </tr>
                @endif
                @if($incident->resolved_at)
                    <tr>
                        <td class="muted">Resolved:</td>
                        <td>{{ $incident->resolved_at->format('M d, Y H:i') }}</td>
                    </tr>
                @endif
            </table>
        </div>

        <div class="panel" style="max-width: none;">
            <h3 style="font-size: 16px; margin-top: 0;">Update Status & Progression</h3>
            <form method="POST" action="{{ route('platform.security.incidents.status', $incident) }}" style="display: grid; gap: 12px;">
                @csrf
                <label style="font-size: 13px;">Transition Status
                    <select name="status" style="border: 1px solid #b9c9bf; border-radius: 5px; padding: 8px 10px; font: inherit; width: 100%;">
                        @foreach(['new' => 'New', 'acknowledged' => 'Acknowledged', 'investigating' => 'Investigating', 'escalated' => 'Escalated', 'resolved' => 'Resolved', 'closed' => 'Closed'] as $st => $lbl)
                            <option value="{{ $st }}" @selected($incident->status === $st)>{{ $lbl }}</option>
                        @endforeach
                    </select>
                </label>
                <label style="font-size: 13px;">Status Transition Notes
                    <textarea name="notes" rows="2" style="border: 1px solid #b9c9bf; border-radius: 5px; padding: 8px 10px; font: inherit; width: 100%;" placeholder="Summary or resolution reason..."></textarea>
                </label>
                <div>
                    <button type="submit" style="width: 100%;">Update Status</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
