<?php

namespace App\Console\Commands;

use App\Services\InfrastructureService;
use Illuminate\Console\Command;

class PrunePlatformBackups extends Command
{
    protected $signature = 'platform:backup-prune';

    protected $description = 'Expire old platform backup artifacts using the configured retention policy';

    public function handle(InfrastructureService $infrastructure): int
    {
        $result = $infrastructure->pruneExpiredBackups();
        $this->info("Expired {$result['expired']} backup artifact(s); {$result['failed']} failed.");

        return $result['failed'] > 0 ? self::FAILURE : self::SUCCESS;
    }
}
