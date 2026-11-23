<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\PlatformBackupRun;
use App\Models\PlatformRestoreRequest;
use App\Models\Tenant;
use App\Services\InfrastructureService;
use App\Services\PlatformAuditRecorder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PlatformInfrastructureController extends Controller
{
    public function __construct(
        protected InfrastructureService $infra,
        protected PlatformAuditRecorder $audit
    ) {}

    public function index(Request $request): View
    {
        abort_unless($request->user('platform')?->hasPermission('platform.infrastructure.view'), 403);

        $health = $this->infra->runAllProbes();
        $migrationStatus = $this->infra->getMigrationStatus();
        $recentBackups = PlatformBackupRun::query()->latest()->take(5)->get();

        return view('platform.infrastructure.index', [
            'health' => $health,
            'migrationStatus' => $migrationStatus,
            'recentBackups' => $recentBackups,
            'phpVersion' => PHP_VERSION,
            'laravelVersion' => app()->version(),
        ]);
    }

    public function runProbes(Request $request): RedirectResponse
    {
        abort_unless($request->user('platform')?->hasPermission('platform.infrastructure.view'), 403);

        $result = $this->infra->runAllProbes();

        $this->audit->record(
            actorId: $request->user('platform')?->id,
            action: 'infrastructure.probes.run',
            targetType: null,
            targetId: null,
            details: [
                'overall' => $result['overall'],
            ],
        );

        return redirect()->route('platform.infrastructure.index')
            ->with('notice', "Infrastructure probes refreshed: Overall status is {$result['overall']}.");
    }

    public function queues(Request $request): View
    {
        abort_unless($request->user('platform')?->hasPermission('platform.infrastructure.view'), 403);

        $failedJobs = DB::table('failed_jobs')->latest('failed_at')->paginate(20);
        $queueProbe = $this->infra->probeQueues();

        return view('platform.infrastructure.queues', [
            'failedJobs' => $failedJobs,
            'queueProbe' => $queueProbe,
        ]);
    }

    public function retryFailedJob(Request $request, string $id): RedirectResponse
    {
        abort_unless($request->user('platform')?->hasPermission('platform.infrastructure.view'), 403);

        if ($id === 'all') {
            Artisan::call('queue:retry', ['id' => ['all']]);
            $action = 'infrastructure.queues.retry_all';
        } else {
            Artisan::call('queue:retry', ['id' => [$id]]);
            $action = 'infrastructure.queues.retry';
        }

        $this->audit->record(
            actorId: $request->user('platform')?->id,
            action: $action,
            targetType: null,
            targetId: $id,
            details: [
                'target' => $id,
            ],
        );

        return redirect()->route('platform.infrastructure.queues')
            ->with('notice', "Queue retry command dispatched for job {$id}.");
    }

    public function migrations(Request $request): View
    {
        abort_unless($request->user('platform')?->hasPermission('platform.infrastructure.view'), 403);

        $migrationStatus = $this->infra->getMigrationStatus();

        return view('platform.infrastructure.migrations', [
            'migrationStatus' => $migrationStatus,
        ]);
    }

    public function backups(Request $request): View
    {
        abort_unless($request->user('platform')?->hasPermission('platform.infrastructure.view'), 403);

        $query = PlatformBackupRun::query()->with('tenant')->latest();

        if ($request->filled('type')) {
            $query->where('backup_type', $request->query('type'));
        }

        if ($request->filled('tenant_id')) {
            $query->where('tenant_id', $request->query('tenant_id'));
        }

        $backups = $query->paginate(20)->withQueryString();
        $tenants = Tenant::query()->orderBy('name')->get();

        return view('platform.infrastructure.backups', [
            'backups' => $backups,
            'tenants' => $tenants,
        ]);
    }

    public function triggerBackup(Request $request): RedirectResponse
    {
        abort_unless($request->user('platform')?->hasPermission('platform.infrastructure.view'), 403);

        $validated = $request->validate([
            'backup_type' => ['required', 'string', 'in:database,tenant_database,files,full_system'],
            'tenant_id' => ['nullable', 'integer', 'exists:tenants,id'],
        ]);

        $tenant = ! empty($validated['tenant_id']) ? Tenant::query()->find($validated['tenant_id']) : null;

        $backup = $this->infra->triggerBackup(
            type: $validated['backup_type'],
            tenant: $tenant,
            actor: $request->user('platform'),
        );

        $this->audit->record(
            actorId: $request->user('platform')?->id,
            action: 'infrastructure.backup.trigger',
            targetType: PlatformBackupRun::class,
            targetId: (string) $backup->id,
            details: [
                'filename' => $backup->filename,
                'type' => $backup->backup_type,
                'checksum' => $backup->checksum,
            ],
        );

        return redirect()->route('platform.infrastructure.backups')
            ->with('notice', "Encrypted backup run {$backup->filename} completed and verified.");
    }

    public function restoreRequests(Request $request): View
    {
        abort_unless($request->user('platform')?->hasPermission('platform.infrastructure.view'), 403);

        $restores = PlatformRestoreRequest::query()
            ->with(['tenant', 'backupRun', 'requester', 'approver'])
            ->latest()
            ->paginate(20);

        $tenants = Tenant::query()->orderBy('name')->get();
        $backups = PlatformBackupRun::query()->latest()->take(20)->get();

        return view('platform.infrastructure.restores', [
            'restores' => $restores,
            'tenants' => $tenants,
            'backups' => $backups,
        ]);
    }

    public function storeRestoreRequest(Request $request): RedirectResponse
    {
        abort_unless($request->user('platform')?->hasPermission('platform.infrastructure.view'), 403);

        $validated = $request->validate([
            'tenant_id' => ['required', 'integer', 'exists:tenants,id'],
            'platform_backup_run_id' => ['required', 'integer', 'exists:platform_backup_runs,id'],
            'reason' => ['required', 'string', 'max:1000'],
        ]);

        $tenant = Tenant::query()->findOrFail($validated['tenant_id']);
        $backup = PlatformBackupRun::query()->findOrFail($validated['platform_backup_run_id']);

        $restore = $this->infra->requestTenantRestore(
            tenant: $tenant,
            backup: $backup,
            reason: $validated['reason'],
            requester: $request->user('platform'),
        );

        $this->audit->record(
            actorId: $request->user('platform')?->id,
            action: 'infrastructure.restore.request',
            targetType: PlatformRestoreRequest::class,
            targetId: (string) $restore->id,
            details: [
                'tenant_id' => $tenant->id,
                'backup_id' => $backup->id,
                'reason' => $validated['reason'],
            ],
        );

        return redirect()->route('platform.infrastructure.restores')
            ->with('notice', "Tenant restore request #{$restore->id} created in isolated sandbox. Awaiting second-person approval.");
    }

    public function approveRestore(Request $request, PlatformRestoreRequest $restore): RedirectResponse
    {
        abort_unless($request->user('platform')?->hasPermission('platform.infrastructure.view'), 403);

        $validated = $request->validate([
            'verification_notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $this->infra->approveAndExecuteRestore(
            restore: $restore,
            approver: $request->user('platform'),
            verificationNotes: $validated['verification_notes'] ?? null,
        );

        $this->audit->record(
            actorId: $request->user('platform')?->id,
            action: 'infrastructure.restore.approve',
            targetType: PlatformRestoreRequest::class,
            targetId: (string) $restore->id,
            details: [
                'tenant_id' => $restore->tenant_id,
                'approver_id' => $request->user('platform')->id,
            ],
        );

        return redirect()->route('platform.infrastructure.restores')
            ->with('notice', "Restore request #{$restore->id} approved and production recovery executed.");
    }
}
