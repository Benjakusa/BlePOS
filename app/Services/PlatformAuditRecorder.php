<?php

namespace App\Services;

use App\Models\PlatformAuditLog;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class PlatformAuditRecorder
{
    /** @param array<string, mixed> $details */
    public function record(?int $actorId, string $action, ?string $targetType = null, ?string $targetId = null, array $details = []): PlatformAuditLog
    {
        return DB::transaction(function () use ($actorId, $action, $targetType, $targetId, $details): PlatformAuditLog {
            $chainLock = DB::table('platform_audit_chain_locks')->where('id', 1)->lockForUpdate()->first();
            if (! $chainLock) {
                throw new RuntimeException('The platform audit chain lock row is missing.');
            }

            $latest = PlatformAuditLog::query()->orderByDesc('id')->lockForUpdate()->first();
            $occurredAt = now();
            $previousHash = $latest?->entry_hash;
            $canonical = json_encode([
                'actor_id' => $actorId,
                'action' => $action,
                'target_type' => $targetType,
                'target_id' => $targetId,
                'details' => $details,
                'occurred_at' => $occurredAt->format('Y-m-d H:i:s'),
            ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
            $entryHash = hash('sha256', ($previousHash ?? '').$canonical);

            return PlatformAuditLog::query()->forceCreate([
                'actor_id' => $actorId,
                'action' => $action,
                'target_type' => $targetType,
                'target_id' => $targetId,
                'details' => $details,
                'previous_hash' => $previousHash,
                'entry_hash' => $entryHash,
                'occurred_at' => $occurredAt,
            ]);
        });
    }
}
