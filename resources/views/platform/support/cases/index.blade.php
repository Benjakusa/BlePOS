@extends('platform.layout')

@section('title', 'Support Centre')

@section('content')
<header>
    <div>
        <h1>Support Centre</h1>
        <div class="muted">Tenant support tickets, SLA tracking, non-impersonating assist tools, and announcements</div>
    </div>
    <div style="display: flex; gap: 8px;">
        <a href="{{ route('platform.announcements.index') }}" class="btn secondary">Announcements</a>
        <a href="{{ route('platform.support.cases.create') }}" class="btn">Open New Ticket</a>
    </div>
</header>

@if(session('notice'))
    <div class="notice" role="alert">{{ session('notice') }}</div>
@endif

<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 16px; margin-bottom: 24px;">
    <div class="panel" style="padding: 16px;">
        <div class="muted" style="font-size: 13px;">Open Tickets</div>
        <div style="font-size: 22px; font-weight: bold; margin-top: 4px;">{{ $summary['open_cases'] }}</div>
    </div>
    <div class="panel" style="padding: 16px;">
        <div class="muted" style="font-size: 13px;">Urgent Priority</div>
        <div style="font-size: 22px; font-weight: bold; margin-top: 4px; color: #dc2626;">{{ $summary['urgent_cases'] }}</div>
    </div>
    <div class="panel" style="padding: 16px;">
        <div class="muted" style="font-size: 13px;">Unassigned Tickets</div>
        <div style="font-size: 22px; font-weight: bold; margin-top: 4px; color: #b45309;">{{ $summary['unassigned_cases'] }}</div>
    </div>
    <div class="panel" style="padding: 16px;">
        <div class="muted" style="font-size: 13px;">SLA Overdue</div>
        <div style="font-size: 22px; font-weight: bold; margin-top: 4px; color: #dc2626;">{{ $summary['overdue_cases'] }}</div>
    </div>
    <div class="panel" style="padding: 16px;">
        <div class="muted" style="font-size: 13px;">Resolved Tickets</div>
        <div style="font-size: 22px; font-weight: bold; margin-top: 4px; color: #146b4d;">{{ $summary['resolved_cases'] }}</div>
    </div>
</div>

<div class="panel" style="max-width: 100%;">
    <table style="width: 100%; border-collapse: collapse;">
        <thead>
            <tr style="border-bottom: 2px solid #e2e8f0;">
                <th style="text-align: left; padding: 10px 8px;">Case #</th>
                <th style="text-align: left; padding: 10px 8px;">Tenant</th>
                <th style="text-align: left; padding: 10px 8px;">Subject</th>
                <th style="text-align: center; padding: 10px 8px;">Category</th>
                <th style="text-align: center; padding: 10px 8px;">Priority</th>
                <th style="text-align: center; padding: 10px 8px;">Status</th>
                <th style="text-align: left; padding: 10px 8px;">Assigned Agent</th>
                <th style="text-align: center; padding: 10px 8px;">SLA Due</th>
                <th style="text-align: right; padding: 10px 8px;">Action</th>
            </tr>
        </thead>
        <tbody>
            @forelse($cases as $case)
                <tr style="border-bottom: 1px solid #f1f5f9;">
                    <td style="padding: 10px 8px;"><code>{{ $case->case_number }}</code></td>
                    <td style="padding: 10px 8px;">
                        <a href="{{ route('platform.tenants.show', $case->tenant) }}"><strong>{{ $case->tenant->name }}</strong></a>
                    </td>
                    <td style="padding: 10px 8px;">{{ $case->subject }}</td>
                    <td style="text-align: center; padding: 10px 8px;"><span class="badge">{{ strtoupper($case->category) }}</span></td>
                    <td style="text-align: center; padding: 10px 8px;">
                        <span class="badge" style="background: {{ $case->priority === 'urgent' ? '#fee2e2' : ($case->priority === 'high' ? '#ffedd5' : '#f1f5f9') }}; color: {{ $case->priority === 'urgent' ? '#991b1b' : ($case->priority === 'high' ? '#9a3412' : '#334155') }};">
                            {{ strtoupper($case->priority) }}
                        </span>
                    </td>
                    <td style="text-align: center; padding: 10px 8px;">
                        <span class="badge">{{ strtoupper(str_replace('_', ' ', $case->status)) }}</span>
                    </td>
                    <td style="padding: 10px 8px;">{{ $case->assignedAgent?->name ?? 'Unassigned' }}</td>
                    <td style="text-align: center; padding: 10px 8px; font-size: 13px; color: {{ $case->isOverdue() ? '#dc2626' : '#64748b' }}; font-weight: {{ $case->isOverdue() ? 'bold' : 'normal' }};">
                        {{ $case->sla_due_at?->format('M d, H:i') ?? '—' }}
                        @if($case->isOverdue()) (Overdue) @endif
                    </td>
                    <td style="text-align: right; padding: 10px 8px;">
                        <a href="{{ route('platform.support.cases.show', $case) }}">Inspect</a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" style="text-align: center; padding: 24px;" class="muted">No support tickets found.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div style="margin-top: 16px;">
        {{ $cases->links() }}
    </div>
</div>
@endsection
