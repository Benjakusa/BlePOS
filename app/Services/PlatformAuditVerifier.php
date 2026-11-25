<?php

namespace App\Services;

use App\Models\PlatformAuditLog;
use App\Models\TenantFinancialAuditEntry;

class PlatformAuditVerifier
{
    /** @return array{intact: bool, entries_checked: int, broken_entry_id: int|null, checked_at: string} */
    public function verify(): array
    {
        $previousHash = null;
        $entriesChecked = 0;

        foreach (PlatformAuditLog::query()->orderBy('id')->cursor() as $entry) {
            $canonical = json_encode([
                'actor_id' => $entry->actor_id,
                'action' => $entry->action,
                'target_type' => $entry->target_type,
                'target_id' => $entry->target_id,
                'details' => $entry->details,
                'occurred_at' => $entry->occurred_at->format('Y-m-d H:i:s'),
            ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
            $expectedHash = hash('sha256', ($previousHash ?? '').$canonical);

            if ($entry->previous_hash !== $previousHash || ! hash_equals($expectedHash, $entry->entry_hash)) {
                return [
                    'intact' => false,
                    'entries_checked' => $entriesChecked + 1,
                    'broken_entry_id' => $entry->id,
                    'checked_at' => now()->toISOString(),
                ];
            }

            $previousHash = $entry->entry_hash;
            $entriesChecked++;
        }

        $tenantResult = $this->verifyTenantChains();

        return [
            'intact' => $tenantResult['intact'],
            'entries_checked' => $entriesChecked + $tenantResult['entries_checked'],
            'broken_entry_id' => $tenantResult['broken_entry_id'],
            'checked_at' => now()->toISOString(),
        ];
    }

    /** @return array{intact: bool, entries_checked: int, broken_entry_id: int|null} */
    private function verifyTenantChains(): array
    {
        $checked = 0;
        $previous = [];
        foreach (TenantFinancialAuditEntry::query()->orderBy('tenant_id')->orderBy('id')->cursor() as $entry) {
            $prior = $previous[$entry->tenant_id] ?? null;
            $canonical = json_encode([
                (int) $entry->tenant_id, $entry->shop_id === null ? null : (int) $entry->shop_id,
                $entry->actor_user_id === null ? null : (int) $entry->actor_user_id,
                $entry->entity, (string) $entry->entity_id, $entry->action,
                $entry->before_hash, $entry->after_hash, $prior,
                $entry->created_at->format('Y-m-d H:i:s'),
            ], JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
            if ($entry->prev_hash !== $prior || ! hash_equals(hash('sha256', ($prior ?? '').$canonical), $entry->hash)) {
                return ['intact' => false, 'entries_checked' => $checked + 1, 'broken_entry_id' => $entry->id];
            }
            $previous[$entry->tenant_id] = $entry->hash;
            $checked++;
        }

        return ['intact' => true, 'entries_checked' => $checked, 'broken_entry_id' => null];
    }
}
