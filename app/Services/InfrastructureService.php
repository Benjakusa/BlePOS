<?php

namespace App\Services;

use App\Models\PlatformBackupRun;
use App\Models\PlatformHealthProbe;
use App\Models\PlatformRestoreRequest;
use App\Models\PlatformUser;
use App\Models\Tenant;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\Process\Process;

class InfrastructureService
{
    /**
     * Run real-time probe against the active database.
     *
     * @return array{subsystem: string, status: string, latency_ms: float, details: array<string, mixed>}
     */
    public function probeDatabase(): array
    {
        $start = microtime(true);
        $status = 'healthy';
        $details = [];

        try {
            DB::select('SELECT 1');
            $latency = round((microtime(true) - $start) * 1000, 2);

            $connection = DB::getDefaultConnection();
            $driver = config("database.connections.{$connection}.driver");
            $tableCount = match ($driver) {
                'sqlite' => (int) DB::scalar("SELECT COUNT(*) FROM sqlite_master WHERE type = 'table' AND name NOT LIKE 'sqlite_%'"),
                'mysql' => (int) DB::scalar('SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE()'),
                'pgsql' => (int) DB::scalar("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = current_schema() AND table_type = 'BASE TABLE'"),
                default => null,
            };

            $details = [
                'connection' => $connection,
                'driver' => $driver,
                'tables_count' => $tableCount,
                'ping' => 'OK',
            ];

            if ($latency > 250) {
                $status = 'degraded';
            }
        } catch (\Throwable $e) {
            $latency = round((microtime(true) - $start) * 1000, 2);
            $status = 'unhealthy';
            $details = [
                'error' => $e->getMessage(),
            ];
        }

        return [
            'subsystem' => 'database',
            'status' => $status,
            'latency_ms' => $latency,
            'details' => $details,
        ];
    }

    /**
     * Run real-time probe against the application cache.
     *
     * @return array{subsystem: string, status: string, latency_ms: float, details: array<string, mixed>}
     */
    public function probeCache(): array
    {
        $start = microtime(true);
        $status = 'healthy';
        $details = [];

        try {
            $probeKey = 'platform_probe_'.Str::random(10);
            Cache::put($probeKey, 'alive', 10);
            $val = Cache::get($probeKey);
            Cache::forget($probeKey);

            $latency = round((microtime(true) - $start) * 1000, 2);

            $details = [
                'store' => config('cache.default'),
                'read_write' => $val === 'alive' ? 'OK' : 'FAIL',
            ];

            if ($val !== 'alive' || $latency > 200) {
                $status = 'degraded';
            }
        } catch (\Throwable $e) {
            $latency = round((microtime(true) - $start) * 1000, 2);
            $status = 'unhealthy';
            $details = [
                'error' => $e->getMessage(),
            ];
        }

        return [
            'subsystem' => 'cache',
            'status' => $status,
            'latency_ms' => $latency,
            'details' => $details,
        ];
    }

    /**
     * Run real-time probe on storage disk filesystem.
     *
     * @return array{subsystem: string, status: string, latency_ms: float, details: array<string, mixed>}
     */
    public function probeStorage(): array
    {
        $start = microtime(true);
        $status = 'healthy';
        $details = [];

        try {
            $storagePath = storage_path('framework');
            $isWritable = is_writable($storagePath);
            $freeSpaceBytes = @disk_free_space($storagePath) ?: 0;
            $totalSpaceBytes = @disk_total_space($storagePath) ?: 0;

            $latency = round((microtime(true) - $start) * 1000, 2);

            $freeSpaceGb = round($freeSpaceBytes / 1073741824, 2);
            $totalSpaceGb = round($totalSpaceBytes / 1073741824, 2);

            $details = [
                'path' => $storagePath,
                'writable' => $isWritable,
                'free_gb' => $freeSpaceGb,
                'total_gb' => $totalSpaceGb,
            ];

            if (! $isWritable || ($totalSpaceBytes > 0 && ($freeSpaceBytes / $totalSpaceBytes) < 0.05)) {
                $status = 'degraded';
            }
        } catch (\Throwable $e) {
            $latency = round((microtime(true) - $start) * 1000, 2);
            $status = 'unhealthy';
            $details = [
                'error' => $e->getMessage(),
            ];
        }

        return [
            'subsystem' => 'storage',
            'status' => $status,
            'latency_ms' => $latency,
            'details' => $details,
        ];
    }

    /**
     * Run real-time probe on queue subsystem.
     *
     * @return array{subsystem: string, status: string, latency_ms: float, details: array<string, mixed>}
     */
    public function probeQueues(): array
    {
        $start = microtime(true);
        $status = 'healthy';
        $details = [];

        try {
            $failedCount = DB::table('failed_jobs')->count();
            $pendingCount = Queue::size();
            $connection = config('queue.default');

            $latency = round((microtime(true) - $start) * 1000, 2);

            $details = [
                'connection' => $connection,
                'failed_jobs_count' => $failedCount,
                'pending_jobs_count' => $pendingCount,
            ];

            if ($failedCount > 25 || $pendingCount > 1000) {
                $status = 'degraded';
            }
        } catch (\Throwable $e) {
            $latency = round((microtime(true) - $start) * 1000, 2);
            $status = 'unhealthy';
            $details = [
                'error' => $e->getMessage(),
            ];
        }

        return [
            'subsystem' => 'queues',
            'status' => $status,
            'latency_ms' => $latency,
            'details' => $details,
        ];
    }

    /** Alert when production backup artifacts or sandbox restore drills are stale. */
    public function probeRecoveryReadiness(): array
    {
        $start = microtime(true);
        $latestBackup = PlatformBackupRun::query()->where('status', 'verified')->latest('completed_at')->first();
        $latestRestore = PlatformRestoreRequest::query()->where('status', 'test_restored')->latest('executed_at')->first();
        $backupAgeHours = $latestBackup?->completed_at?->diffInHours(now());
        $restoreAgeDays = $latestRestore?->executed_at?->diffInDays(now());
        $status = 'healthy';
        $warnings = [];

        if ($backupAgeHours === null || $backupAgeHours > 24) {
            $status = 'degraded';
            $warnings[] = $latestBackup ? 'latest_backup_is_older_than_24_hours' : 'no_verified_backup_exists';
        }
        if ($restoreAgeDays === null || $restoreAgeDays > 30) {
            $status = 'degraded';
            $warnings[] = $latestRestore ? 'restore_drill_is_older_than_30_days' : 'no_successful_restore_drill_exists';
        }

        return [
            'subsystem' => 'backup_recovery',
            'status' => $status,
            'latency_ms' => round((microtime(true) - $start) * 1000, 2),
            'details' => [
                'latest_backup_at' => $latestBackup?->completed_at?->toIso8601String(),
                'backup_age_hours' => $backupAgeHours,
                'latest_restore_drill_at' => $latestRestore?->executed_at?->toIso8601String(),
                'restore_drill_age_days' => $restoreAgeDays,
                'warnings' => $warnings,
            ],
        ];
    }

    /**
     * Run all subsystem probes, persist health records, and return overall summary.
     *
     * @return array{overall: string, probes: array<string, array{subsystem: string, status: string, latency_ms: float, details: array<string, mixed>}>}
     */
    public function runAllProbes(): array
    {
        $probes = [
            'database' => $this->probeDatabase(),
            'cache' => $this->probeCache(),
            'storage' => $this->probeStorage(),
            'queues' => $this->probeQueues(),
            'backup_recovery' => $this->probeRecoveryReadiness(),
        ];

        $overall = 'healthy';
        $now = now();

        foreach ($probes as $probe) {
            if ($probe['status'] === 'unhealthy') {
                $overall = 'unhealthy';
            } elseif ($probe['status'] === 'degraded' && $overall !== 'unhealthy') {
                $overall = 'degraded';
            }

            PlatformHealthProbe::query()->create([
                'subsystem' => $probe['subsystem'],
                'status' => $probe['status'],
                'latency_ms' => $probe['latency_ms'],
                'details' => $probe['details'],
                'probed_at' => $now,
            ]);
        }

        return [
            'overall' => $overall,
            'probes' => $probes,
        ];
    }

    /**
     * Inspect migration status across the application.
     *
     * @return array{total_ran: int, last_batch: int, ran_migrations: array<int, string>, pending_migrations: array<int, string>}
     */
    public function getMigrationStatus(): array
    {
        $ran = DB::table('migrations')->orderBy('batch')->pluck('migration')->all();
        $lastBatch = (int) (DB::table('migrations')->max('batch') ?? 0);

        $migrationFiles = glob(database_path('migrations/*.php')) ?: [];
        $allFiles = array_map(fn ($path) => basename($path, '.php'), $migrationFiles);

        $pending = array_values(array_diff($allFiles, $ran));

        return [
            'total_ran' => count($ran),
            'last_batch' => $lastBatch,
            'ran_migrations' => $ran,
            'pending_migrations' => $pending,
        ];
    }

    /**
     * Trigger a platform or tenant backup.
     */
    public function triggerBackup(string $type, ?Tenant $tenant = null, ?PlatformUser $actor = null): PlatformBackupRun
    {
        $startedAt = now();
        if ($type !== 'database' || $tenant !== null) {
            throw ValidationException::withMessages(['backup_type' => 'Only full database snapshots are currently supported; tenant-only and file backups are not available.']);
        }
        $filename = 'backups/backup_platform_'.$startedAt->format('Ymd_His').'_'.Str::random(8).'.enc';
        $disk = (string) config('platform.backup_disk', 'local');
        $connection = DB::connection();
        $driver = $connection->getDriverName();
        if ($driver === 'sqlite') {
            $source = $connection->getDatabaseName();
            if ($source === ':memory:' || ! is_file($source)) {
                throw ValidationException::withMessages(['backup' => 'A file-backed SQLite database is required to create a durable backup.']);
            }
            $snapshotPath = storage_path('app/private/'.Str::uuid().'.sqlite');
            if (! is_dir(dirname($snapshotPath))) {
                mkdir(dirname($snapshotPath), 0700, true);
            }
            $sqlite = new \PDO('sqlite:'.$source);
            $quotedSnapshotPath = str_replace("'", "''", $snapshotPath);
            $sqlite->exec("VACUUM INTO '{$quotedSnapshotPath}'");
            $plain = file_get_contents($snapshotPath);
            @unlink($snapshotPath);
        } elseif ($driver === 'mysql') {
            $config = $connection->getConfig();
            $process = new Process([
                'mysqldump', '--single-transaction', '--routines', '--triggers', '--databases',
                (string) $connection->getDatabaseName(), '-h', (string) $config['host'], '-P', (string) ($config['port'] ?? 3306), '-u', (string) $config['username'],
            ], null, ['MYSQL_PWD' => (string) ($config['password'] ?? '')]);
            $process->run();
            if (! $process->isSuccessful()) {
                throw ValidationException::withMessages(['backup' => 'mysqldump failed: '.Str::limit($process->getErrorOutput(), 500)]);
            }
            $plain = $process->getOutput();
        } elseif ($driver === 'pgsql') {
            $config = $connection->getConfig();
            $process = new Process([
                'pg_dump', '--format=plain', '--no-owner', '--host', (string) $config['host'], '--port', (string) ($config['port'] ?? 5432), '--username', (string) $config['username'], (string) $connection->getDatabaseName(),
            ], null, ['PGPASSWORD' => (string) ($config['password'] ?? '')]);
            $process->run();
            if (! $process->isSuccessful()) {
                throw ValidationException::withMessages(['backup' => 'pg_dump failed: '.Str::limit($process->getErrorOutput(), 500)]);
            }
            $plain = $process->getOutput();
        } else {
            throw ValidationException::withMessages(['backup' => "Database driver {$driver} does not have a configured snapshot implementation."]);
        }
        if ($plain === false || $plain === '') {
            throw ValidationException::withMessages(['backup' => 'Database snapshot was empty.']);
        }
        $artifact = Crypt::encryptString(base64_encode($plain));
        if (! Storage::disk($disk)->put($filename, $artifact)) {
            throw ValidationException::withMessages(['backup' => 'The backup artifact could not be written to the configured backup disk.']);
        }
        $stored = Storage::disk($disk)->get($filename);
        $checksum = hash('sha256', $artifact);
        if (! is_string($stored) || ! hash_equals($checksum, hash('sha256', $stored))) {
            Storage::disk($disk)->delete($filename);
            throw ValidationException::withMessages(['backup' => 'Backup artifact checksum verification failed.']);
        }

        return PlatformBackupRun::query()->create([
            'backup_type' => $type,
            'tenant_id' => null,
            'status' => 'verified',
            'filename' => $filename,
            'destination' => $disk,
            'size_bytes' => strlen($stored),
            'duration_seconds' => now()->diffInSeconds($startedAt),
            'checksum' => $checksum,
            'is_encrypted' => true,
            'verified_at' => now(),
            'started_at' => $startedAt,
            'completed_at' => now(),
        ]);
    }

    /** Remove expired backup bytes while keeping the run metadata for audit history. */
    public function pruneExpiredBackups(?int $retentionDays = null): array
    {
        $retentionDays = max(1, $retentionDays ?? (int) config('platform.backup_retention_days', 30));
        $cutoff = now()->subDays($retentionDays);
        $expired = 0;
        $failed = 0;

        PlatformBackupRun::query()
            ->where('status', 'verified')
            ->whereNotNull('completed_at')
            ->where('completed_at', '<', $cutoff)
            ->orderBy('id')
            ->chunkById(100, function ($backups) use (&$expired, &$failed, $retentionDays): void {
                foreach ($backups as $backup) {
                    try {
                        $disk = Storage::disk($backup->destination);
                        if ($disk->exists($backup->filename) && ! $disk->delete($backup->filename)) {
                            $failed++;

                            continue;
                        }

                        $backup->forceFill([
                            'status' => 'expired',
                            'error_message' => "Backup artifact expired under the {$retentionDays}-day retention policy.",
                        ])->save();
                        $expired++;
                    } catch (\Throwable $exception) {
                        report($exception);
                        $failed++;
                    }
                }
            });

        return ['expired' => $expired, 'failed' => $failed, 'retention_days' => $retentionDays];
    }

    /**
     * Request a tenant restore into an isolated sandbox environment.
     */
    public function requestTenantRestore(
        Tenant $tenant,
        PlatformBackupRun $backup,
        string $reason,
        PlatformUser $requester
    ): PlatformRestoreRequest {
        return PlatformRestoreRequest::query()->create([
            'tenant_id' => $tenant->id,
            'platform_backup_run_id' => $backup->id,
            'requested_by' => $requester->id,
            'status' => 'requested',
            'target_environment' => 'isolated_sandbox',
            'reason' => $reason,
        ]);
    }

    /**
     * Approve and execute production recovery for a tenant restore.
     * Enforces the Two-Person Rule (Annex A 31): approver must be different from requester.
     */
    public function approveAndExecuteRestore(
        PlatformRestoreRequest $restore,
        PlatformUser $approver,
        ?string $verificationNotes = null
    ): PlatformRestoreRequest {
        if ($restore->requested_by === $approver->id) {
            throw ValidationException::withMessages([
                'approver' => 'Two-Person Rule violation: The requesting administrator cannot approve their own tenant restore request.',
            ]);
        }

        $backup = $restore->backupRun;
        if (! $backup || ! Storage::disk($backup->destination)->exists($backup->filename)) {
            throw ValidationException::withMessages(['backup' => 'The referenced backup artifact is missing.']);
        }
        $artifact = Storage::disk($backup->destination)->get($backup->filename);
        if (! hash_equals((string) $backup->checksum, hash('sha256', $artifact))) {
            throw ValidationException::withMessages(['backup' => 'Backup checksum does not match the stored bytes.']);
        }
        try {
            $plain = base64_decode(Crypt::decryptString($artifact), true);
        } catch (\Throwable $exception) {
            throw ValidationException::withMessages(['backup' => 'Backup decryption failed.']);
        }
        if ($plain === false) {
            throw ValidationException::withMessages(['backup' => 'Backup payload is invalid.']);
        }
        if (DB::connection()->getDriverName() !== 'sqlite' || substr($plain, 0, 15) !== 'SQLite format 3') {
            throw ValidationException::withMessages(['backup' => 'Backup is not a valid SQLite database snapshot.']);
        }
        $sandbox = storage_path('app/private/restore-sandboxes/tenant_'.$restore->tenant_id.'_request_'.$restore->id.'.sqlite');
        if (! is_dir(dirname($sandbox))) {
            mkdir(dirname($sandbox), 0700, true);
        }
        file_put_contents($sandbox, $plain, LOCK_EX);
        chmod($sandbox, 0600);
        $pdo = new \PDO('sqlite:'.$sandbox);
        $integrity = $pdo->query('PRAGMA integrity_check')->fetchColumn();
        if ($integrity !== 'ok') {
            throw ValidationException::withMessages(['backup' => 'The isolated SQLite restore failed its integrity check.']);
        }

        $restore->update([
            'approved_by' => $approver->id,
            'status' => 'test_restored',
            'target_environment' => 'isolated_sandbox',
            'verification_notes' => $verificationNotes ?? 'Encrypted backup was restored into an isolated SQLite sandbox and passed integrity_check. Production data was not changed.',
            'executed_at' => now(),
        ]);

        return $restore;
    }
}
