<?php

namespace App\Console\Commands;

use App\Services\ProductionConfiguration;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('platform:production-check')]
#[Description('Check production configuration requirements before deployment.')]
class PlatformProductionCheckCommand extends Command
{
    public function handle(ProductionConfiguration $configuration): int
    {
        $problems = $configuration->violations();
        if ($problems !== []) {
            foreach ($problems as $problem) {
                $this->components->error($problem);
            }

            return self::FAILURE;
        }
        $this->components->info('Production configuration assertions passed.');

        return self::SUCCESS;
    }
}
