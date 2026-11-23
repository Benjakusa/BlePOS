<?php

namespace App\Console\Commands;

use App\Services\PlatformAuditVerifier;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('platform:audit-verify')]
#[Description('Verify the append-only platform audit hash chain.')]
class PlatformAuditVerifyCommand extends Command
{
    public function handle(PlatformAuditVerifier $verifier): int
    {
        $result = $verifier->verify();
        if (! $result['intact']) {
            $this->components->error('Audit chain is broken at entry '.$result['broken_entry_id'].'. Checked '.$result['entries_checked'].' entries.');

            return self::FAILURE;
        }

        $this->components->info('Audit chain intact. Checked '.$result['entries_checked'].' entries at '.$result['checked_at'].'.');

        return self::SUCCESS;
    }
}
