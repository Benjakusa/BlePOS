<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Illuminate\Session\Middleware\StartSession;
use Symfony\Component\HttpFoundation\Response;

class StartBleposSession extends StartSession
{
    /** @param \Closure(Request): (Response) $next */
    public function handle($request, \Closure $next)
    {
        if (! $this->isPlatformRequest($request)) {
            return parent::handle($request, $next);
        }

        $platformSettings = config('session.platform');
        $sessionConfigKeys = ['cookie', 'lifetime', 'expire_on_close', 'path', 'domain', 'secure', 'http_only', 'same_site', 'partitioned'];
        $originalSettings = [];

        foreach ($sessionConfigKeys as $key) {
            $originalSettings[$key] = config("session.{$key}");
            config(["session.{$key}" => $platformSettings[$key]]);
        }

        try {
            return parent::handle($request, $next);
        } finally {
            foreach ($originalSettings as $key => $value) {
                config(["session.{$key}" => $value]);
            }
        }
    }

    public function getSession(Request $request)
    {
        return tap($this->manager->driver(), function ($session) use ($request): void {
            $cookieName = $this->isPlatformRequest($request)
                ? config('session.platform.cookie')
                : config('session.cookie');

            $session->setName($cookieName);
            $session->setId($request->cookies->get($cookieName));
        });
    }

    private function isPlatformRequest(Request $request): bool
    {
        return $request->is('platform-console') || $request->is('platform-console/*');
    }
}
