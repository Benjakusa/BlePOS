<?php

namespace App\Services;

use App\Models\TenantFinancialAuditEntry;
use Illuminate\Support\Facades\DB;

class TenantFinancialAuditRecorder
{
    /** @param array<string, mixed>|null $before @param array<string, mixed> $after */
    public function record(int $tenantId, ?int $shopId, ?int $actorId, string $entity, string|int $entityId, string $action, ?array $before, array $after): TenantFinancialAuditEntry
    {
        return DB::transaction(function () use ($tenantId, $shopId, $actorId, $entity, $entityId, $action, $before, $after): TenantFinancialAuditEntry {
            $tenantExists = DB::table('tenants')->where('id', $tenantId)->lockForUpdate()->exists();
            if (! $tenantExists) {
                throw new \RuntimeException('Cannot append tenant audit entry without a tenant row.');
            }
            $latest = TenantFinancialAuditEntry::query()->where('tenant_id', $tenantId)->orderByDesc('id')->lockForUpdate()->first();
            $beforeHash = $before === null ? null : hash('sha256', json_encode($before, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));
            $afterHash = hash('sha256', json_encode($after, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));
            $createdAt = now();
            $canonical = json_encode([$tenantId, $shopId, $actorId, $entity, (string) $entityId, $action, $beforeHash, $afterHash, $latest?->hash, $createdAt->format('Y-m-d H:i:s')], JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);

            return TenantFinancialAuditEntry::query()->create([
                'tenant_id' => $tenantId, 'shop_id' => $shopId, 'actor_user_id' => $actorId,
                'entity' => $entity, 'entity_id' => (string) $entityId, 'action' => $action,
                'before_hash' => $beforeHash, 'after_hash' => $afterHash, 'prev_hash' => $latest?->hash,
                'hash' => hash('sha256', ($latest?->hash ?? '').$canonical), 'created_at' => $createdAt,
            ]);
        });
    }
}
