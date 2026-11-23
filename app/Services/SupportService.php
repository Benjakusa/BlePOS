<?php

namespace App\Services;

use App\Models\PlatformAnnouncement;
use App\Models\PlatformUser;
use App\Models\SupportCase;
use App\Models\SupportCaseNote;
use App\Models\Tenant;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class SupportService
{
    /**
     * Create a new support case for a tenant.
     *
     * @param  array{subject: string, description: string, requester_name: string, requester_email: string, category?: string, priority?: string, shop_id?: int|null}  $data
     */
    public function createCase(Tenant $tenant, array $data, ?PlatformUser $assignedAgent = null): SupportCase
    {
        $priority = $data['priority'] ?? 'medium';

        // SLA calculation per Annex A 13.1
        $slaHours = match ($priority) {
            'urgent' => 4,
            'high' => 12,
            'medium' => 24,
            'low' => 72,
            default => 24,
        };

        $caseNumber = 'CASE-'.now()->format('Ymd').'-'.strtoupper(Str::random(5));

        return SupportCase::query()->create([
            'case_number' => $caseNumber,
            'tenant_id' => $tenant->id,
            'shop_id' => $data['shop_id'] ?? null,
            'assigned_agent_id' => $assignedAgent?->id,
            'requester_name' => $data['requester_name'],
            'requester_email' => $data['requester_email'],
            'subject' => $data['subject'],
            'description' => $data['description'],
            'category' => $data['category'] ?? 'general',
            'priority' => $priority,
            'status' => 'open',
            'sla_due_at' => now()->addHours($slaHours),
        ]);
    }

    /**
     * Assign a support case to a platform agent.
     */
    public function assignCase(SupportCase $case, ?PlatformUser $agent, ?PlatformUser $actor = null): SupportCase
    {
        $case->update([
            'assigned_agent_id' => $agent?->id,
            'status' => $case->status === 'open' && $agent ? 'in_progress' : $case->status,
        ]);

        $agentName = $agent ? $agent->name : 'Unassigned';
        $actorName = $actor ? $actor->name : 'System';

        $case->notes()->create([
            'platform_user_id' => $actor?->id,
            'author_name' => $actorName,
            'content' => "Assignment changed to: {$agentName}",
            'is_internal' => true,
        ]);

        return $case;
    }

    /**
     * Update case status with resolution tracking.
     */
    public function updateStatus(SupportCase $case, string $newStatus, ?string $resolutionNotes = null, ?PlatformUser $actor = null): SupportCase
    {
        $allowedStatuses = ['open', 'in_progress', 'waiting_on_tenant', 'waiting_on_platform', 'resolved', 'closed'];

        if (! in_array($newStatus, $allowedStatuses, true)) {
            throw ValidationException::withMessages([
                'status' => "Invalid support case status '{$newStatus}'.",
            ]);
        }

        $isResolved = in_array($newStatus, ['resolved', 'closed'], true);

        $case->update([
            'status' => $newStatus,
            'resolution_notes' => $isResolved ? ($resolutionNotes ?? $case->resolution_notes) : null,
            'resolved_at' => $isResolved ? now() : null,
        ]);

        $actorName = $actor ? $actor->name : 'System';
        $case->notes()->create([
            'platform_user_id' => $actor?->id,
            'author_name' => $actorName,
            'content' => 'Case status changed to: '.strtoupper(str_replace('_', ' ', $newStatus)).($resolutionNotes ? " — Resolution: {$resolutionNotes}" : ''),
            'is_internal' => true,
        ]);

        return $case;
    }

    /**
     * Add an internal note or customer reply to a case.
     */
    public function addNote(SupportCase $case, string $content, bool $isInternal, PlatformUser $author): SupportCaseNote
    {
        return $case->notes()->create([
            'platform_user_id' => $author->id,
            'author_name' => $author->name,
            'content' => $content,
            'is_internal' => $isInternal,
        ]);
    }

    /**
     * Run permitted data integrity check for a tenant (Annex A 13.2).
     * Returns pass/fail counts only to prevent unintended PII exposure.
     *
     * @return array{pass_count: int, fail_count: int, checks: array<string, array{status: string, message: string}>}
     */
    public function runTenantIntegrityCheck(Tenant $tenant): array
    {
        $checks = [];
        $passCount = 0;
        $failCount = 0;

        // Check 1: Shop configuration integrity
        $shopsCount = $tenant->shops()->count();
        if ($shopsCount > 0) {
            $checks['shops'] = ['status' => 'PASS', 'message' => "{$shopsCount} shop branch(es) registered and active."];
            $passCount++;
        } else {
            $checks['shops'] = ['status' => 'FAIL', 'message' => 'No active shop branches registered.'];
            $failCount++;
        }

        // Check 2: Register to Shop linkage
        $registersWithoutShop = $tenant->registers()->whereNull('shop_id')->count();
        if ($registersWithoutShop === 0) {
            $checks['registers'] = ['status' => 'PASS', 'message' => 'All POS till terminals correctly mapped to shop branches.'];
            $passCount++;
        } else {
            $checks['registers'] = ['status' => 'FAIL', 'message' => "{$registersWithoutShop} register(s) have unassigned shop branches."];
            $failCount++;
        }

        // Check 3: Active subscription check
        $activeSubscription = $tenant->subscriptions()->whereIn('status', ['active', 'trial'])->exists();
        if ($activeSubscription) {
            $checks['subscription'] = ['status' => 'PASS', 'message' => 'Valid active or trial commercial subscription in place.'];
            $passCount++;
        } else {
            $checks['subscription'] = ['status' => 'FAIL', 'message' => 'Tenant subscription is inactive, past-due, or suspended.'];
            $failCount++;
        }

        // Check 4: User account hierarchy check
        $ownerExists = $tenant->users()->exists();
        if ($ownerExists) {
            $checks['users'] = ['status' => 'PASS', 'message' => 'Tenant user accounts and staff role allocations present.'];
            $passCount++;
        } else {
            $checks['users'] = ['status' => 'FAIL', 'message' => 'No user accounts associated with this tenant.'];
            $failCount++;
        }

        return [
            'pass_count' => $passCount,
            'fail_count' => $failCount,
            'checks' => $checks,
        ];
    }

    /**
     * Create a platform announcement.
     *
     * @param  array{title: string, content: string, priority?: string, target_type?: string, target_value?: string|null, starts_at?: string|null, expires_at?: string|null}  $data
     */
    public function createAnnouncement(array $data, ?PlatformUser $creator = null): PlatformAnnouncement
    {
        return PlatformAnnouncement::query()->create([
            'title' => $data['title'],
            'content' => $data['content'],
            'priority' => $data['priority'] ?? 'info',
            'target_type' => $data['target_type'] ?? 'all',
            'target_value' => $data['target_value'] ?? null,
            'status' => 'published',
            'starts_at' => $data['starts_at'] ?? now(),
            'expires_at' => $data['expires_at'] ?? null,
            'created_by' => $creator?->id,
        ]);
    }

    /**
     * Get aggregate Support Centre metrics.
     *
     * @return array{open_cases: int, urgent_cases: int, unassigned_cases: int, overdue_cases: int, resolved_cases: int}
     */
    public function getSupportSummary(): array
    {
        $now = now();

        return [
            'open_cases' => SupportCase::query()->whereNotIn('status', ['resolved', 'closed'])->count(),
            'urgent_cases' => SupportCase::query()->whereNotIn('status', ['resolved', 'closed'])->where('priority', 'urgent')->count(),
            'unassigned_cases' => SupportCase::query()->whereNotIn('status', ['resolved', 'closed'])->whereNull('assigned_agent_id')->count(),
            'overdue_cases' => SupportCase::query()->whereNotIn('status', ['resolved', 'closed'])->where('sla_due_at', '<', $now)->count(),
            'resolved_cases' => SupportCase::query()->whereIn('status', ['resolved', 'closed'])->count(),
        ];
    }
}
