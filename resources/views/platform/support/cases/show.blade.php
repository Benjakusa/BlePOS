@extends('platform.layout')

@section('title', 'Case ' . $case->case_number)

@section('content')
<header>
    <div>
        <h1>Support Case <code>{{ $case->case_number }}</code></h1>
        <div class="muted">{{ $case->subject }} &middot; Opened {{ $case->created_at->format('M d, Y H:i') }}</div>
    </div>
    <div style="display: flex; gap: 8px;">
        <a href="{{ route('platform.support.cases.index') }}" class="btn secondary">Back to Tickets</a>
        <a href="{{ route('platform.support.assist.overview', $case->tenant) }}" class="btn secondary">Tenant Assist Tools</a>
    </div>
</header>

@if(session('notice'))
    <div class="notice" role="alert">{{ session('notice') }}</div>
@endif

@if ($errors->any())
    <div class="notice" style="border-color: #fca5a5; background: #fef2f2; color: #991b1b;" role="alert">
        <strong>Please check the errors:</strong>
        <ul style="margin: 8px 0 0; padding-left: 20px;">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 20px;">
    <div>
        <div class="panel" style="margin-bottom: 20px;">
            <h2>Issue Description</h2>
            <div style="white-space: pre-wrap; margin-top: 10px; line-height: 1.5; color: #1e293b;">{{ $case->description }}</div>

            @if($case->resolution_notes)
                <div style="margin-top: 16px; padding: 12px; background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 6px;">
                    <strong style="color: #166534;">Resolution Notes:</strong>
                    <div style="margin-top: 4px; color: #14532d;">{{ $case->resolution_notes }}</div>
                </div>
            @endif
        </div>

        <div class="panel" style="margin-bottom: 20px;">
            <h2>Activity & Internal Case Notes</h2>

            <div style="display: flex; flex-direction: column; gap: 12px; margin-top: 14px;">
                @forelse($case->notes as $note)
                    <div style="padding: 12px; border-radius: 6px; border: 1px solid {{ $note->is_internal ? '#e2e8f0' : '#bfdbfe' }}; background: {{ $note->is_internal ? '#f8fafc' : '#eff6ff' }};">
                        <div style="display: flex; justify-content: space-between; font-size: 12px; color: #64748b; margin-bottom: 6px;">
                            <span>
                                <strong>{{ $note->author_name }}</strong>
                                @if($note->is_internal)
                                    <span class="badge" style="font-size: 10px; margin-left: 4px;">INTERNAL NOTE</span>
                                @else
                                    <span class="badge" style="font-size: 10px; margin-left: 4px; background: #dbeafe; color: #1e40af;">TENANT VISIBLE</span>
                                @endif
                            </span>
                            <span>{{ $note->created_at->format('M d, Y H:i') }}</span>
                        </div>
                        <div style="font-size: 14px; white-space: pre-wrap; line-height: 1.4;">{{ $note->content }}</div>
                    </div>
                @empty
                    <div class="muted" style="text-align: center; padding: 14px;">No notes recorded yet.</div>
                @endforelse
            </div>

            <form method="POST" action="{{ route('platform.support.cases.notes.store', $case) }}" class="form" style="margin-top: 18px; border-top: 1px solid #e2e8f0; padding-top: 14px;">
                @csrf
                <label>
                    Add Case Note / Reply:
                    <textarea name="content" rows="3" required placeholder="Type notes or updates here..." style="border: 1px solid #b9c9bf; border-radius: 5px; padding: 11px 12px; font: inherit; width: 100%;"></textarea>
                </label>

                <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 8px;">
                    <label style="display: flex; align-items: center; gap: 6px; font-weight: normal; font-size: 13px;">
                        <input type="checkbox" name="is_internal" value="1" checked style="width: auto;">
                        Internal platform note (hidden from tenant)
                    </label>

                    <button type="submit" style="padding: 8px 16px;">Post Note</button>
                </div>
            </form>
        </div>
    </div>

    <div>
        <div class="panel" style="margin-bottom: 20px;">
            <h3>Ticket Metadata</h3>
            <p><strong>Tenant:</strong> <a href="{{ route('platform.tenants.show', $case->tenant) }}">{{ $case->tenant->name }}</a></p>
            <p><strong>Requester:</strong> {{ $case->requester_name }} ({{ $case->requester_email }})</p>
            <p><strong>Category:</strong> <span class="badge">{{ strtoupper($case->category) }}</span></p>
            <p><strong>Priority:</strong> <span class="badge">{{ strtoupper($case->priority) }}</span></p>
            <p><strong>Status:</strong> <span class="badge">{{ strtoupper(str_replace('_', ' ', $case->status)) }}</span></p>
            <p><strong>SLA Target:</strong> {{ $case->sla_due_at?->format('M d, Y H:i') ?? 'N/A' }}</p>
        </div>

        <div class="panel" style="margin-bottom: 20px;">
            <h3>Update Status</h3>
            <form method="POST" action="{{ route('platform.support.cases.status', $case) }}" class="form" style="margin-top: 10px;">
                @csrf
                <label>
                    Status:
                    <select name="status" style="border: 1px solid #b9c9bf; border-radius: 5px; padding: 9px 10px; font: inherit; width: 100%;">
                        <option value="open" @selected($case->status === 'open')>Open</option>
                        <option value="in_progress" @selected($case->status === 'in_progress')>In Progress</option>
                        <option value="waiting_on_tenant" @selected($case->status === 'waiting_on_tenant')>Waiting on Tenant</option>
                        <option value="waiting_on_platform" @selected($case->status === 'waiting_on_platform')>Waiting on Platform</option>
                        <option value="resolved" @selected($case->status === 'resolved')>Resolved</option>
                        <option value="closed" @selected($case->status === 'closed')>Closed</option>
                    </select>
                </label>

                <label>
                    Resolution Notes (optional):
                    <textarea name="resolution_notes" rows="2" placeholder="Summary of resolution if closing..." style="border: 1px solid #b9c9bf; border-radius: 5px; padding: 9px 10px; font: inherit; width: 100%;">{{ $case->resolution_notes }}</textarea>
                </label>

                <button type="submit" style="margin-top: 8px;">Update Status</button>
            </form>
        </div>

        <div class="panel">
            <h3>Assign Agent</h3>
            <form method="POST" action="{{ route('platform.support.cases.assign', $case) }}" class="form" style="margin-top: 10px;">
                @csrf
                <label>
                    Agent:
                    <select name="agent_id" style="border: 1px solid #b9c9bf; border-radius: 5px; padding: 9px 10px; font: inherit; width: 100%;">
                        <option value="">Unassigned</option>
                        @foreach($agents as $agent)
                            <option value="{{ $agent->id }}" @selected($case->assigned_agent_id === $agent->id)>
                                {{ $agent->name }}
                            </option>
                        @endforeach
                    </select>
                </label>

                <button type="submit" style="margin-top: 8px;">Update Assignment</button>
            </form>
        </div>
    </div>
</div>
@endsection
