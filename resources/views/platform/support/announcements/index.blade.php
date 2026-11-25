@extends('platform.layout')

@section('title', 'Platform Announcements')

@section('content')
<header>
    <div>
        <h1>Platform Announcements & Bulletins</h1>
        <div class="muted">System broadcasts, maintenance notices, and targeted platform alerts (Annex A 13.4)</div>
    </div>
    <div style="display: flex; gap: 8px;">
        <a href="{{ route('platform.support.cases.index') }}" class="btn secondary">Back to Support</a>
        <a href="{{ route('platform.announcements.create') }}" class="btn">New Announcement</a>
    </div>
</header>

@if(session('notice'))
    <div class="notice" role="alert">{{ session('notice') }}</div>
@endif

<div class="panel" style="max-width: 100%;">
    <table style="width: 100%; border-collapse: collapse;">
        <thead>
            <tr style="border-bottom: 2px solid #e2e8f0;">
                <th style="text-align: left; padding: 10px 8px;">Title</th>
                <th style="text-align: center; padding: 10px 8px;">Priority</th>
                <th style="text-align: center; padding: 10px 8px;">Targeting</th>
                <th style="text-align: center; padding: 10px 8px;">Status</th>
                <th style="text-align: center; padding: 10px 8px;">Published</th>
                <th style="text-align: center; padding: 10px 8px;">Tenant Reads</th>
                <th style="text-align: right; padding: 10px 8px;">Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($announcements as $announcement)
                <tr style="border-bottom: 1px solid #f1f5f9;">
                    <td style="padding: 10px 8px;">
                        <strong>{{ $announcement->title }}</strong>
                        <div class="muted" style="font-size: 13px; margin-top: 2px;">{{ Str::limit($announcement->content, 90) }}</div>
                    </td>
                    <td style="text-align: center; padding: 10px 8px;">
                        <span class="badge" style="background: {{ $announcement->priority === 'critical' ? '#EAEEF3' : ($announcement->priority === 'warning' ? 'var(--clr-grey-tint)' : 'var(--clr-blue-tint)') }}; color: {{ $announcement->priority === 'critical' ? 'var(--clr-red)' : ($announcement->priority === 'warning' ? 'var(--clr-muted)' : '#0369a1') }};">
                            {{ strtoupper($announcement->priority) }}
                        </span>
                    </td>
                    <td style="text-align: center; padding: 10px 8px;">
                        <span class="badge">{{ strtoupper($announcement->target_type) }} {{ $announcement->target_value ? "({$announcement->target_value})" : '' }}</span>
                    </td>
                    <td style="text-align: center; padding: 10px 8px;">
                        <span class="badge">{{ strtoupper($announcement->status) }}</span>
                    </td>
                    <td style="text-align: center; padding: 10px 8px; font-size: 13px;">
                        {{ $announcement->starts_at?->format('M d, Y H:i') ?? '—' }}
                    </td>
                    <td style="text-align: center; padding: 10px 8px; font-weight: bold;">
                        {{ $announcement->reads_count }}
                    </td>
                    <td style="text-align: right; padding: 10px 8px;">
                        @if($announcement->status === 'published')
                            <form method="POST" action="{{ route('platform.announcements.archive', $announcement) }}" style="display: inline;" data-motion-confirm="Archive this announcement?">
                                @csrf
                                <button type="submit" class="btn secondary" style="padding: 4px 8px; font-size: 12px; color: #64748b;">Archive</button>
                            </form>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" style="text-align: center; padding: 24px;" class="muted">No platform announcements created yet.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div style="margin-top: 16px;">
        {{ $announcements->links() }}
    </div>
</div>
@endsection
