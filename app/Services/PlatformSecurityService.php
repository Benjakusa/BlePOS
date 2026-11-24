<?php

namespace App\Services;

use App\Models\PlatformIpRule;
use App\Models\PlatformSecurityEvent;
use App\Models\PlatformSecurityIncident;
use App\Models\PlatformSecurityIncidentNote;
use App\Models\PlatformSecurityKey;
use App\Models\PlatformSession;
use App\Models\PlatformUser;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\IpUtils;

class PlatformSecurityService
{
    public function __construct(
        private readonly PlatformAuditRecorder $auditRecorder
    ) {}

    /**
     * Get aggregate security metrics and recent activity for the Security Centre dashboard.
     *
     * @return array<string, mixed>
     */
    public function getSecurityOverview(): array
    {
        $this->ensureDefaultKeys();

        $activeIncidentsCount = PlatformSecurityIncident::query()
            ->whereIn('status', ['new', 'acknowledged', 'investigating', 'escalated'])
            ->count();

        $criticalEvents24h = PlatformSecurityEvent::query()
            ->where('occurred_at', '>=', Carbon::now()->subHours(24))
            ->whereIn('severity', ['warning', 'critical'])
            ->count();

        $activeDenylistCount = PlatformIpRule::query()
            ->where('rule_type', 'denylist')
            ->where('is_active', true)
            ->where(function ($query): void {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', Carbon::now());
            })
            ->count();

        $activeSessionsCount = PlatformSession::query()
            ->whereNull('revoked_at')
            ->where('last_activity', '>=', Carbon::now()->subMinutes(30))
            ->count();

        $recentIncidents = PlatformSecurityIncident::query()
            ->with(['assignedOfficer:id,name,email', 'reporter:id,name,email'])
            ->orderByDesc('id')
            ->limit(5)
            ->get();

        $recentEvents = PlatformSecurityEvent::query()
            ->with('user:id,name,email')
            ->orderByDesc('occurred_at')
            ->orderByDesc('id')
            ->limit(5)
            ->get();

        $keys = PlatformSecurityKey::query()
            ->orderBy('id')
            ->get();

        return [
            'metrics' => [
                'active_incidents' => $activeIncidentsCount,
                'critical_events_24h' => $criticalEvents24h,
                'blocked_ips' => $activeDenylistCount,
                'active_sessions' => $activeSessionsCount,
            ],
            'recent_incidents' => $recentIncidents,
            'recent_events' => $recentEvents,
            'keys' => $keys,
        ];
    }

    /**
     * Create a new security incident.
     */
    public function createIncident(array $data, PlatformUser $reporter): PlatformSecurityIncident
    {
        return DB::transaction(function () use ($data, $reporter): PlatformSecurityIncident {
            $year = Carbon::now()->year;
            $count = PlatformSecurityIncident::whereYear('created_at', $year)->count() + 1;
            $incidentNumber = sprintf('INC-%d-%04d', $year, $count);

            $incident = PlatformSecurityIncident::create([
                'incident_number' => $incidentNumber,
                'title' => $data['title'],
                'category' => $data['category'],
                'severity' => $data['severity'],
                'status' => 'new',
                'platform_user_id' => $data['platform_user_id'] ?? null,
                'reporter_id' => $reporter->id,
                'tenant_id' => $data['tenant_id'] ?? null,
                'description' => $data['description'],
                'evidence_metadata' => $data['evidence_metadata'] ?? null,
                'containment_steps' => $data['containment_steps'] ?? null,
            ]);

            if (! empty($data['initial_note'])) {
                $incident->notes()->create([
                    'platform_user_id' => $reporter->id,
                    'note' => $data['initial_note'],
                    'is_containment_action' => false,
                ]);
            }

            $this->auditRecorder->record(
                actorId: $reporter->id,
                action: 'platform.security.incident.created',
                targetType: 'PlatformSecurityIncident',
                targetId: (string) $incident->id,
                details: [
                    'incident_number' => $incident->incident_number,
                    'title' => $incident->title,
                    'severity' => $incident->severity,
                    'category' => $incident->category,
                ],
            );

            return $incident;
        });
    }

    /**
     * Update status and resolution of an incident.
     */
    public function updateIncidentStatus(
        PlatformSecurityIncident $incident,
        string $status,
        ?string $notes,
        PlatformUser $user
    ): PlatformSecurityIncident {
        return DB::transaction(function () use ($incident, $status, $notes, $user): PlatformSecurityIncident {
            $before = [
                'status' => $incident->status,
                'assigned_officer' => $incident->platform_user_id,
            ];

            $updates = ['status' => $status];

            if ($status === 'acknowledged' && ! $incident->acknowledged_at) {
                $updates['acknowledged_at'] = Carbon::now();
            }
            if ($status === 'resolved') {
                $updates['resolved_at'] = Carbon::now();
                if ($notes) {
                    $updates['resolution_notes'] = $notes;
                }
            }
            if ($status === 'closed') {
                $updates['closed_at'] = Carbon::now();
            }

            $incident->update($updates);

            if ($notes) {
                $incident->notes()->create([
                    'platform_user_id' => $user->id,
                    'note' => "Status transitioned to [{$status}]: ".$notes,
                    'is_containment_action' => in_array($status, ['investigating', 'escalated']),
                ]);
            }

            $this->auditRecorder->record(
                actorId: $user->id,
                action: 'platform.security.incident.status_updated',
                targetType: 'PlatformSecurityIncident',
                targetId: (string) $incident->id,
                details: [
                    'before' => $before,
                    'status' => $status,
                    'notes' => $notes,
                ],
            );

            return $incident->fresh();
        });
    }

    /**
     * Add a note / timeline action to an incident.
     */
    public function addIncidentNote(
        PlatformSecurityIncident $incident,
        string $note,
        bool $isContainment,
        PlatformUser $user
    ): PlatformSecurityIncidentNote {
        return DB::transaction(function () use ($incident, $note, $isContainment, $user): PlatformSecurityIncidentNote {
            $incidentNote = $incident->notes()->create([
                'platform_user_id' => $user->id,
                'note' => $note,
                'is_containment_action' => $isContainment,
            ]);

            $this->auditRecorder->record(
                actorId: $user->id,
                action: 'platform.security.incident.note_added',
                targetType: 'PlatformSecurityIncidentNote',
                targetId: (string) $incidentNote->id,
                details: [
                    'incident_id' => $incident->id,
                    'is_containment_action' => $isContainment,
                ],
            );

            return $incidentNote;
        });
    }

    /**
     * Create an IP filter rule.
     */
    public function createIpRule(array $data, PlatformUser $creator): PlatformIpRule
    {
        return DB::transaction(function () use ($data, $creator): PlatformIpRule {
            $expiresAt = null;
            if (! empty($data['duration_minutes'])) {
                $expiresAt = Carbon::now()->addMinutes((int) $data['duration_minutes']);
            } elseif (! empty($data['expires_at'])) {
                $expiresAt = Carbon::parse($data['expires_at']);
            }

            $rule = PlatformIpRule::create([
                'ip_or_cidr' => trim($data['ip_or_cidr']),
                'rule_type' => $data['rule_type'],
                'scope' => $data['scope'] ?? 'global',
                'reason' => $data['reason'],
                'is_active' => true,
                'expires_at' => $expiresAt,
                'created_by_user_id' => $creator->id,
            ]);

            $this->auditRecorder->record(
                actorId: $creator->id,
                action: 'platform.security.ip_rule.created',
                targetType: 'PlatformIpRule',
                targetId: (string) $rule->id,
                details: [
                    'ip_or_cidr' => $rule->ip_or_cidr,
                    'rule_type' => $rule->rule_type,
                    'scope' => $rule->scope,
                    'expires_at' => $rule->expires_at?->toIso8601String(),
                ],
            );

            return $rule;
        });
    }

    /**
     * Toggle active status of an IP rule.
     */
    public function toggleIpRule(PlatformIpRule $rule, PlatformUser $user): PlatformIpRule
    {
        return DB::transaction(function () use ($rule, $user): PlatformIpRule {
            $before = ['is_active' => $rule->is_active];
            $rule->update(['is_active' => ! $rule->is_active]);

            $this->auditRecorder->record(
                actorId: $user->id,
                action: 'platform.security.ip_rule.toggled',
                targetType: 'PlatformIpRule',
                targetId: (string) $rule->id,
                details: [
                    'before' => $before,
                    'is_active' => $rule->is_active,
                ],
            );

            return $rule;
        });
    }

    /**
     * Delete an IP rule.
     */
    public function deleteIpRule(PlatformIpRule $rule, PlatformUser $user): void
    {
        DB::transaction(function () use ($rule, $user): void {
            $before = [
                'ip_or_cidr' => $rule->ip_or_cidr,
                'rule_type' => $rule->rule_type,
            ];

            $rule->delete();

            $this->auditRecorder->record(
                actorId: $user->id,
                action: 'platform.security.ip_rule.deleted',
                targetType: 'PlatformIpRule',
                targetId: (string) $rule->id,
                details: [
                    'before' => $before,
                ],
            );
        });
    }

    /**
     * Check if an IP address is blocked by active denylist rules.
     */
    public function isIpBlocked(string $ip, string $scope = 'global'): bool
    {
        $denylist = PlatformIpRule::query()
            ->where('rule_type', 'denylist')
            ->where('is_active', true)
            ->where(function ($query) use ($scope): void {
                $query->where('scope', 'global')
                    ->orWhere('scope', $scope);
            })
            ->where(function ($query): void {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', Carbon::now());
            })
            ->get();

        foreach ($denylist as $rule) {
            if (IpUtils::checkIp($ip, $rule->ip_or_cidr)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Rotate a cryptographic key.
     */
    public function rotateKey(PlatformSecurityKey $key, PlatformUser $user, ?string $reason): PlatformSecurityKey
    {
        return DB::transaction(function () use ($key, $user, $reason): PlatformSecurityKey {
            $before = [
                'version' => $key->version,
                'fingerprint' => $key->fingerprint,
                'last_rotated_at' => $key->last_rotated_at?->toIso8601String(),
            ];

            $newVersion = $key->version + 1;
            $newFingerprint = 'SHA256:'.hash('sha256', Str::random(32).$newVersion.$key->key_id);
            $now = Carbon::now();
            $nextDue = (clone $now)->addDays($key->rotation_schedule_days);

            $key->update([
                'version' => $newVersion,
                'fingerprint' => $newFingerprint,
                'status' => 'active',
                'last_rotated_at' => $now,
                'next_rotation_due_at' => $nextDue,
                'reencryption_progress_percent' => 100,
            ]);

            $this->auditRecorder->record(
                actorId: $user->id,
                action: 'platform.security.key.rotated',
                targetType: 'PlatformSecurityKey',
                targetId: (string) $key->id,
                details: [
                    'key_id' => $key->key_id,
                    'version' => $key->version,
                    'fingerprint' => $key->fingerprint,
                    'next_rotation_due_at' => $key->next_rotation_due_at?->toIso8601String(),
                    'reason' => $reason,
                ],
            );

            return $key;
        });
    }

    /**
     * Seed initial standard encryption keys if table is empty.
     */
    public function ensureDefaultKeys(): void
    {
        if (PlatformSecurityKey::count() > 0) {
            return;
        }

        $now = Carbon::now();

        PlatformSecurityKey::create([
            'key_id' => 'pmk-master-v1',
            'name' => 'Platform Master Key',
            'key_type' => 'platform_master_key',
            'algorithm' => 'AES-256-GCM',
            'version' => 1,
            'status' => 'active',
            'fingerprint' => 'SHA256:'.hash('sha256', 'pmk-master-v1-seed'),
            'rotation_schedule_days' => 90,
            'last_rotated_at' => $now->copy()->subDays(15),
            'next_rotation_due_at' => $now->copy()->addDays(75),
            'reencryption_progress_percent' => 100,
            'metadata' => [
                'storage' => 'External KMS / Envelope',
                'tier' => 'Root of Trust',
            ],
        ]);

        PlatformSecurityKey::create([
            'key_id' => 'tdk-envelope-v1',
            'name' => 'Tenant Data Envelope Key',
            'key_type' => 'envelope_encryption_key',
            'algorithm' => 'AES-256-GCM',
            'version' => 1,
            'status' => 'active',
            'fingerprint' => 'SHA256:'.hash('sha256', 'tdk-envelope-v1-seed'),
            'envelope_wrapped_by_key_id' => 'pmk-master-v1',
            'rotation_schedule_days' => 90,
            'last_rotated_at' => $now->copy()->subDays(20),
            'next_rotation_due_at' => $now->copy()->addDays(70),
            'reencryption_progress_percent' => 100,
            'metadata' => [
                'storage' => 'Wrapped by pmk-master-v1',
                'tier' => 'Data Key Encryption Key',
            ],
        ]);

        PlatformSecurityKey::create([
            'key_id' => 'db-encryption-v1',
            'name' => 'Database Column Encryption Key',
            'key_type' => 'tenant_data_key',
            'algorithm' => 'AES-256-GCM',
            'version' => 1,
            'status' => 'active',
            'fingerprint' => 'SHA256:'.hash('sha256', 'db-encryption-v1-seed'),
            'envelope_wrapped_by_key_id' => 'pmk-master-v1',
            'rotation_schedule_days' => 180,
            'last_rotated_at' => $now->copy()->subDays(45),
            'next_rotation_due_at' => $now->copy()->addDays(135),
            'reencryption_progress_percent' => 100,
            'metadata' => [
                'storage' => 'Encrypted fields in database',
            ],
        ]);

        PlatformSecurityKey::create([
            'key_id' => 'wh-signing-v1',
            'name' => 'Webhook HMAC Signing Key',
            'key_type' => 'webhook_signing_key',
            'algorithm' => 'HMAC-SHA256',
            'version' => 1,
            'status' => 'active',
            'fingerprint' => 'SHA256:'.hash('sha256', 'wh-signing-v1-seed'),
            'rotation_schedule_days' => 60,
            'last_rotated_at' => $now->copy()->subDays(10),
            'next_rotation_due_at' => $now->copy()->addDays(50),
            'reencryption_progress_percent' => 100,
            'metadata' => [
                'scope' => 'External platform webhooks',
            ],
        ]);
    }
}
