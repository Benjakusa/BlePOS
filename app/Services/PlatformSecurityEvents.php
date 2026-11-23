<?php

namespace App\Services;

use App\Models\PlatformSecurityEvent;
use Illuminate\Http\Request;

class PlatformSecurityEvents
{
    /** @param array<string, mixed> $metadata */
    public function record(Request $request, string $eventType, string $severity = 'info', ?int $userId = null, array $metadata = []): PlatformSecurityEvent
    {
        return PlatformSecurityEvent::query()->create([
            'platform_user_id' => $userId,
            'event_type' => $eventType,
            'severity' => $severity,
            'ip_address' => $request->ip(),
            'user_agent' => mb_substr((string) $request->userAgent(), 0, 1000),
            'metadata' => $metadata,
            'occurred_at' => now(),
        ]);
    }
}
