<?php

namespace Tests\Feature;

use App\Support\ResilientVite;
use Illuminate\Foundation\Vite;
use Tests\TestCase;

class ResilientViteTest extends TestCase
{
    public function test_stale_hot_file_falls_back_to_compiled_asset_manifest(): void
    {
        $hotFile = tempnam(sys_get_temp_dir(), 'blepos-vite-hot-');
        file_put_contents($hotFile, 'http://127.0.0.1:1');

        try {
            $vite = new ResilientVite;
            $vite->useHotFile($hotFile);

            $this->assertFalse($vite->isRunningHot());
        } finally {
            @unlink($hotFile);
        }
    }

    public function test_application_uses_the_resilient_vite_resolver(): void
    {
        $this->assertInstanceOf(ResilientVite::class, app(Vite::class));
    }
}
