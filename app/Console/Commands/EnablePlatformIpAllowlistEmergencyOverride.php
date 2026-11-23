<?php

namespace App\Console\Commands;

use App\Models\PlatformSecurityEvent;
use App\Services\PlatformAuditRecorder;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

#[Signature('platform:ip-allowlist-emergency-override {--minutes=15 : Override duration (1 to 60 minutes)} {--reason= : Required incident/change reason}')]
#[Description('Temporarily bypass the platform IP allowlist from the server CLI and record the override.')]
class EnablePlatformIpAllowlistEmergencyOverride extends Command
{
    public function handle(PlatformAuditRecorder $audit): int
    {
        if (! config('platform.security.ip_allowlist_enabled')) {
            $this->components->error('The platform IP allowlist is not enabled. No override was created.');

            return self::FAILURE;
        }

        $validated = Validator::make([
            'minutes' => $this->option('minutes'),
            'reason' => $this->option('reason'),
        ], [
            'minutes' => ['required', 'integer', 'min:1', 'max:'.config('platform.security.ip_allowlist_override_max_minutes')],
            'reason' => ['required', 'string', 'min:8', 'max:1000'],
        ]);

        if ($validated->fails()) {
            foreach ($validated->errors()->all() as $error) {
                $this->components->error($error);
            }

            return self::FAILURE;
        }

        $minutes = (int) $this->option('minutes');
        $expiresAt = now()->addMinutes($minutes);
        $reason = (string) $this->option('reason');
        $metadata = [
            'minutes' => $minutes,
            'reason' => $reason,
            'expires_at' => $expiresAt->toISOString(),
            'source' => 'server_cli',
        ];

        DB::transaction(function () use ($audit, $metadata): void {
            PlatformSecurityEvent::query()->create([
                'event_type' => 'platform.network.ip_allowlist_emergency_override',
                'severity' => 'critical',
                'metadata' => $metadata,
                'occurred_at' => now(),
            ]);
            $audit->record(null, 'platform.network.ip_allowlist_emergency_override', null, null, $metadata);
        });

        Cache::put(config('platform.security.ip_allowlist_override_cache_key'), true, $expiresAt);

        $this->components->warn("The IP allowlist override is active for {$minutes} minutes and expires at {$expiresAt->toIso8601String()}.");
        $this->components->info('The override is recorded in platform security events and the append-only audit log.');

        return self::SUCCESS;
    }
}
