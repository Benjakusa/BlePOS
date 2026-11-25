<?php

namespace App\Console\Commands;

use App\Services\InfrastructureService;
use Illuminate\Console\Command;

class CreatePlatformBackup extends Command
{
    protected $signature = 'platform:backup';

    protected $description = 'Create and verify an encrypted platform database backup';

    public function handle(InfrastructureService $infrastructure): int
    {
        try {
            $backup = $infrastructure->triggerBackup('database');
            $this->info("Verified backup created: {$backup->filename}");

            return self::SUCCESS;
        } catch (\Throwable $exception) {
            report($exception);
            $this->error('Platform backup failed: '.$exception->getMessage());

            return self::FAILURE;
        }
    }
}
