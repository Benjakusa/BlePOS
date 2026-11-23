<?php

namespace App\Services;

use App\Models\PlatformAuditLog;

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

        return [
            'intact' => true,
            'entries_checked' => $entriesChecked,
            'broken_entry_id' => null,
            'checked_at' => now()->toISOString(),
        ];
    }
}
