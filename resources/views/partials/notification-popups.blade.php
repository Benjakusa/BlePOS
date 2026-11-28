@php
    $popupPrefix = $isPlatform ?? false ? 'platform' : 'tenant';
    $popupUser = ($isPlatform ?? false) ? auth('platform')->user() : auth()->user();
    $initialPopups = collect();

    if ($popupUser) {
        $initialPopups = $popupUser->unreadNotifications()
            ->latest()
            ->limit(15)
            ->get()
            ->filter(fn ($item): bool => in_array($item->data['kind'] ?? null, ['support', 'announcement'], true))
            ->take(5)
            ->map(fn ($item): array => [
                'id' => $item->id,
                'kind' => $item->data['kind'] ?? 'general',
                'title' => $item->data['title'] ?? 'Notification',
                'message' => $item->data['message'] ?? '',
                'time' => $item->created_at?->diffForHumans() ?? 'Just now',
                'read_url' => route("{$popupPrefix}.notifications.read", $item->id),
                'dismiss_url' => route("{$popupPrefix}.notifications.dismiss", $item->id),
            ])
            ->values();
    }
@endphp

@if($popupUser)
<div id="ble-notif-popup-backdrop" class="ble-popup-backdrop" role="dialog" aria-modal="true" aria-label="Important notifications" @if($initialPopups->isEmpty()) hidden @endif>
    <div id="ble-notif-popup-dialog" class="ble-popup-dialog">
        @foreach($initialPopups as $popup)
            @php
                $isAnnouncement = ($popup['kind'] ?? '') === 'announcement';
            @endphp
            <div class="ble-popup-card {{ $isAnnouncement ? 'kind-announcement' : 'kind-support' }}" data-popup-id="{{ $popup['id'] }}">
                <div class="ble-popup-header">
                    <span class="ble-popup-badge {{ $isAnnouncement ? 'announcement' : 'support' }}">
                        <i class="bi {{ $isAnnouncement ? 'bi-megaphone-fill' : 'bi-chat-dots-fill' }}"></i>
                        {{ $isAnnouncement ? 'Platform Announcement' : 'Support Ticket Feedback' }}
                    </span>
                    <button type="button"
                            class="ble-popup-close"
                            data-dismiss-url="{{ $popup['dismiss_url'] }}"
                            data-popup-dismiss
                            aria-label="Dismiss notification">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>
                <div class="ble-popup-body">
                    <h3 class="ble-popup-title">{{ $popup['title'] }}</h3>
                    <p class="ble-popup-message">{{ $popup['message'] }}</p>
                    <div class="ble-popup-time">{{ $popup['time'] }}</div>
                </div>
                <div class="ble-popup-footer">
                    <button type="button"
                            class="btn btn-secondary btn-sm"
                            data-dismiss-url="{{ $popup['dismiss_url'] }}"
                            data-popup-dismiss>
                        Dismiss
                    </button>
                    <form method="post" action="{{ $popup['read_url'] }}" style="margin:0;">
                        @csrf
                        <button type="submit" class="btn btn-primary btn-sm">
                            <i class="bi bi-box-arrow-up-right"></i>
                            {{ $isAnnouncement ? 'Read Announcement' : 'View Ticket' }}
                        </button>
                    </form>
                </div>
            </div>
        @endforeach
    </div>
</div>

<script nonce="{{ request()->attributes->get('csp_nonce') }}">
(function () {
    'use strict';

    const backdrop = document.getElementById('ble-notif-popup-backdrop');
    const dialog = document.getElementById('ble-notif-popup-dialog');
    const pollUrl = @json(route("{$popupPrefix}.notifications.poll"));
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    const seenIds = new Set(
        Array.from(document.querySelectorAll('[data-popup-id]')).map((el) => el.getAttribute('data-popup-id'))
    );

    function syncBellState(unreadCount) {
        document.querySelectorAll('[data-notif-bell]').forEach((bell) => {
            const icon = bell.querySelector('.notif-bell-icon');
            let badge = bell.querySelector('.notif-count-badge');

            if (unreadCount > 0) {
                bell.classList.add('has-unread');
                if (icon) {
                    icon.classList.remove('bi-bell');
                    icon.classList.add('bi-bell-fill');
                }
                if (!badge) {
                    badge = document.createElement('span');
                    badge.className = 'notif-count-badge';
                    badge.setAttribute('aria-label', 'Unread notifications');
                    bell.appendChild(badge);
                }
                badge.textContent = unreadCount > 99 ? '99+' : String(unreadCount);
            } else {
                bell.classList.remove('has-unread');
                if (icon) {
                    icon.classList.remove('bi-bell-fill');
                    icon.classList.add('bi-bell');
                }
                if (badge) {
                    badge.remove();
                }
            }
        });
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = String(text ?? '');
        return div.innerHTML;
    }

    function checkBackdropVisibility() {
        if (!backdrop || !dialog) return;
        backdrop.hidden = dialog.querySelectorAll('.ble-popup-card').length === 0;
    }

    async function dismissCard(card, dismissUrl) {
        if (card) {
            card.remove();
            checkBackdropVisibility();
        }
        if (!dismissUrl) return;
        try {
            const res = await fetch(dismissUrl, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });
            if (res.ok) {
                const payload = await res.json();
                if (typeof payload.unread_count === 'number') {
                    syncBellState(payload.unread_count);
                }
            }
        } catch (e) {
            // Keep UI responsive even if network is temporarily unavailable
        }
    }

    if (dialog) {
        dialog.addEventListener('click', function (event) {
            const btn = event.target.closest('[data-popup-dismiss]');
            if (!btn) return;
            const card = btn.closest('.ble-popup-card');
            dismissCard(card, btn.getAttribute('data-dismiss-url'));
        });
    }

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && backdrop && !backdrop.hidden) {
            const firstDismiss = dialog?.querySelector('[data-popup-dismiss]');
            if (firstDismiss) {
                firstDismiss.click();
            }
        }
    });

    function appendPopupCard(item) {
        if (!dialog || !item || seenIds.has(item.id)) return;
        seenIds.add(item.id);
        const isAnnouncement = item.kind === 'announcement';
        const card = document.createElement('div');
        card.className = 'ble-popup-card ' + (isAnnouncement ? 'kind-announcement' : 'kind-support');
        card.setAttribute('data-popup-id', item.id);
        card.innerHTML =
            '<div class="ble-popup-header">' +
                '<span class="ble-popup-badge ' + (isAnnouncement ? 'announcement' : 'support') + '">' +
                    '<i class="bi ' + (isAnnouncement ? 'bi-megaphone-fill' : 'bi-chat-dots-fill') + '"></i> ' +
                    (isAnnouncement ? 'Platform Announcement' : 'Support Ticket Feedback') +
                '</span>' +
                '<button type="button" class="ble-popup-close" data-dismiss-url="' + escapeHtml(item.dismiss_url) + '" data-popup-dismiss aria-label="Dismiss notification">' +
                    '<i class="bi bi-x-lg"></i>' +
                '</button>' +
            '</div>' +
            '<div class="ble-popup-body">' +
                '<h3 class="ble-popup-title">' + escapeHtml(item.title) + '</h3>' +
                '<p class="ble-popup-message">' + escapeHtml(item.message) + '</p>' +
                '<div class="ble-popup-time">' + escapeHtml(item.time) + '</div>' +
            '</div>' +
            '<div class="ble-popup-footer">' +
                '<button type="button" class="btn btn-secondary btn-sm" data-dismiss-url="' + escapeHtml(item.dismiss_url) + '" data-popup-dismiss>Dismiss</button>' +
                '<form method="post" action="' + escapeHtml(item.read_url) + '" style="margin:0;">' +
                    '<input type="hidden" name="_token" value="' + escapeHtml(csrfToken) + '">' +
                    '<button type="submit" class="btn btn-primary btn-sm">' +
                        '<i class="bi bi-box-arrow-up-right"></i> ' +
                        (isAnnouncement ? 'Read Announcement' : 'View Ticket') +
                    '</button>' +
                '</form>' +
            '</div>';
        dialog.appendChild(card);
        checkBackdropVisibility();
    }

    async function pollNotifications() {
        if (document.visibilityState === 'hidden') return;
        try {
            const res = await fetch(pollUrl, {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });
            if (!res.ok) return;
            const data = await res.json();
            if (typeof data.unread_count === 'number') {
                syncBellState(data.unread_count);
            }
            if (Array.isArray(data.popups)) {
                data.popups.forEach(appendPopupCard);
            }
        } catch (e) {
            // Silent retry on next interval
        }
    }

    setInterval(pollNotifications, 25000);
})();
</script>
@endif
