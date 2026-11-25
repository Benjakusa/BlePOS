<?php

namespace App\Http\Controllers;

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

    public function read(Request $request, string $notification): RedirectResponse
    {
        $item = $this->user($request)->notifications()->whereKey($notification)->firstOrFail();
        $item->markAsRead();

        return $this->destination($request, $item);
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
