<?php

namespace App\Console\Commands;

use App\Models\PlatformApproval;
use App\Services\PlatformAuditRecorder;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

#[Signature('platform:expire-approvals')]
#[Description('Expire pending platform approvals and record them in the audit chain.')]
class ExpirePlatformApprovals extends Command
{
    public function handle(PlatformAuditRecorder $audit): int
    {
        $expiredCount = 0;
        PlatformApproval::query()
            ->where('status', 'pending')
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now())
            ->orderBy('id')
            ->chunkById(100, function ($approvals) use (&$expiredCount, $audit): void {
                foreach ($approvals as $approval) {
                    DB::transaction(function () use ($approval, &$expiredCount, $audit): void {
                        $lockedApproval = PlatformApproval::query()->whereKey($approval->id)->lockForUpdate()->first();
                        if (! $lockedApproval || $lockedApproval->status !== 'pending' || ! $lockedApproval->expires_at?->isPast()) {
                            return;
                        }

                        $lockedApproval->update(['status' => 'expired']);
                        $audit->record(null, 'platform.approval.expired', PlatformApproval::class, (string) $lockedApproval->id, [
                            'action_type' => $lockedApproval->action_type,
                            'requested_by' => $lockedApproval->requested_by,
                            'reason' => 'Approval request passed its expiry time without a decision.',
                            'expired_at' => $lockedApproval->expires_at->toISOString(),
                        ]);
                        $expiredCount++;
                    });
                }
            });

        $this->info("Expired {$expiredCount} platform approval(s).");

        return self::SUCCESS;
    }
}
