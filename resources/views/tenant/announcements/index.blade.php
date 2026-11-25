@extends('tenant.layout')

@section('title', 'Platform Announcements')

@section('content')
<div class="page-header" style="margin-bottom:20px"><h1>Platform Announcements</h1><p class="muted">Updates and notices from the BlePOS platform team.</p></div>
<div class="panel">
    @forelse($announcements as $announcement)
        <article style="padding:14px 0;border-bottom:1px solid #e2e8f0"><div style="display:flex;justify-content:space-between;gap:12px"><strong><a href="{{ route('tenant.announcements.show', $announcement) }}">{{ $announcement->title }}</a></strong><span class="badge {{ $announcement->priority === 'critical' ? 'badge-danger' : ($announcement->priority === 'warning' ? 'badge-warning' : 'badge-success') }}">{{ ucfirst($announcement->priority) }}</span></div><p>{{ \Illuminate\Support\Str::limit($announcement->content, 240) }}</p><small class="muted">{{ $announcement->created_at->format('d M Y, H:i') }}</small></article>
    @empty<p class="muted">There are no current announcements.</p>@endforelse
    {{ $announcements->links() }}
</div>
@endsection
