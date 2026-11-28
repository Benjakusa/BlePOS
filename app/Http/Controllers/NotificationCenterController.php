<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\View\View;

class NotificationCenterController extends Controller
{
    public function index(Request $request): View
    {
        $user = $this->user($request);

        return view('notifications.index', [
            'notifications' => $user->notifications()->latest()->paginate(30),
            'platform' => $request->routeIs('platform.*'),
        ]);
    }

    public function poll(Request $request): JsonResponse
    {
        $user = $this->user($request);
        $unreadCount = $user->unreadNotifications()->count();

        $popups = $user->unreadNotifications()
            ->latest()
            ->limit(15)
            ->get()
            ->filter(fn (DatabaseNotification $item): bool => in_array($item->data['kind'] ?? null, ['support', 'announcement'], true))
            ->take(5)
            ->map(function (DatabaseNotification $item) use ($request): array {
                $prefix = $request->routeIs('platform.*') ? 'platform' : 'tenant';

                return [
                    'id' => $item->id,
                    'kind' => $item->data['kind'] ?? 'general',
                    'title' => $item->data['title'] ?? 'Notification',
                    'message' => $item->data['message'] ?? '',
                    'time' => $item->created_at?->diffForHumans() ?? 'Just now',
                    'read_url' => route("{$prefix}.notifications.read", $item->id),
                    'dismiss_url' => route("{$prefix}.notifications.dismiss", $item->id),
                ];
            })
            ->values();

        return response()->json([
            'unread_count' => $unreadCount,
            'popups' => $popups,
        ]);
    }

    public function read(Request $request, string $notification): RedirectResponse
    {
        $item = $this->user($request)->notifications()->whereKey($notification)->firstOrFail();
        $item->markAsRead();

        return $this->destination($request, $item);
    }

    public function dismiss(Request $request, string $notification): JsonResponse
    {
        $user = $this->user($request);
        $item = $user->notifications()->whereKey($notification)->firstOrFail();
        $item->markAsRead();

        return response()->json([
            'ok' => true,
            'unread_count' => $user->unreadNotifications()->count(),
        ]);
    }

    public function readAll(Request $request): RedirectResponse
    {
        $this->user($request)->unreadNotifications()->update(['read_at' => now()]);

        return redirect()->route($request->routeIs('platform.*') ? 'platform.notifications.index' : 'tenant.notifications.index');
    }

    private function user(Request $request): object
    {
        $user = $request->routeIs('platform.*') ? $request->user('platform') : $request->user();
        abort_unless($user, 403);

        return $user;
    }

    private function destination(Request $request, DatabaseNotification $notification): RedirectResponse
    {
        $url = $notification->data['url'] ?? null;
        $fallback = $request->routeIs('platform.*') ? 'platform.notifications.index' : 'tenant.notifications.index';

        if (is_string($url)) {
            $host = parse_url($url, PHP_URL_HOST);
            $path = parse_url($url, PHP_URL_PATH);
            if (is_string($path) && ($host === null || $host === $request->getHost())) {
                return redirect($path.(parse_url($url, PHP_URL_QUERY) ? '?'.parse_url($url, PHP_URL_QUERY) : ''));
            }
        }

        return redirect()->route($fallback);
    }
}
