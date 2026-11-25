@extends($platform ? 'platform.layout' : 'tenant.layout')

@section('title', 'Notifications')

@section('content')
<div class="page-header" style="display:flex;justify-content:space-between;align-items:center;gap:12px;margin-bottom:20px"><div><h1>Notifications</h1><p class="muted">Support updates and billing notices for your account.</p></div>
    @if(auth($platform ? 'platform' : null)->user()->unreadNotifications()->exists())
        <form method="post" action="{{ route($platform ? 'platform.notifications.read-all' : 'tenant.notifications.read-all') }}">@csrf<button class="btn btn-secondary" type="submit">Mark all as read</button></form>
    @endif
</div>
<div class="panel">
    @forelse($notifications as $notification)
        @php($data = $notification->data)
        <div style="display:flex;justify-content:space-between;align-items:center;gap:16px;padding:14px 0;border-bottom:1px solid #e2e8f0;{{ $notification->read_at ? 'opacity:.72' : '' }}">
            <div><strong>{{ $data['title'] ?? 'Notification' }}</strong><p style="margin:4px 0">{{ $data['message'] ?? '' }}</p><small class="muted">{{ $notification->created_at->diffForHumans() }}</small></div>
            @if(! $notification->read_at)
                <form method="post" action="{{ route($platform ? 'platform.notifications.read' : 'tenant.notifications.read', $notification->id) }}">@csrf<button class="btn btn-primary btn-sm" type="submit">Open</button></form>
            @elseif(! empty($data['url']) && parse_url($data['url'], PHP_URL_HOST) === request()->getHost())
                <a class="btn btn-secondary btn-sm" href="{{ parse_url($data['url'], PHP_URL_PATH) }}">View</a>
            @endif
        </div>
    @empty<p class="muted">You’re all caught up. No notifications yet.</p>@endforelse
    {{ $notifications->links() }}
</div>
@endsection
