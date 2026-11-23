<?php

namespace Tests\Feature;

use App\Models\PlatformPermission;
use App\Models\PlatformRole;
use Database\Seeders\PlatformRoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlatformRolePermissionMatrixTest extends TestCase
{
    use RefreshDatabase;

    public function test_builtin_platform_roles_have_only_the_declared_permission_grants(): void
    {
        $this->seed(PlatformRoleSeeder::class);

        $expectedPermissions = [
            'billing-administrator' => [
                'platform.dashboard.view',
                'platform.tenants.view',
                'platform.billing.view',
                'platform.billing.plans.manage',
                'platform.billing.subscriptions.manage',
            ],
            'compliance-etims-officer' => [
                'platform.dashboard.view',
                'platform.tenants.view',
                'platform.compliance.view',
            ],
            'platform-auditor' => [
                'platform.dashboard.view',
                'platform.tenants.view',
                'platform.audit.view',
                'platform.audit.export',
                'platform.security.view',
                'platform.billing.view',
            ],
            'platform-operations-administrator' => [
                'platform.dashboard.view',
                'platform.tenants.view',
                'platform.tenants.suspend',
                'platform.tenants.reactivate',
                'platform.provisioning.view',
                'platform.provisioning.retry',
                'platform.provisioning.abort',
                'platform.infrastructure.view',
            ],
            'security-officer' => [
                'platform.dashboard.view',
                'platform.security.view',
                'platform.security.users.view',
                'platform.security.users.status',
                'platform.security.users.mfa-reset.request',
                'platform.security.sessions.revoke',
                'platform.security.approvals.review',
                'platform.audit.view',
            ],
            'support-agent' => [
                'platform.dashboard.view',
                'platform.tenants.view',
                'platform.support.view',
            ],
        ];

        $expectedSlugs = [...array_keys($expectedPermissions), 'super-administrator'];
        sort($expectedSlugs);

        $this->assertSame($expectedSlugs, PlatformRole::query()
            ->where('is_builtin', true)
            ->orderBy('slug')
            ->pluck('slug')
            ->all());

        foreach ($expectedPermissions as $slug => $expected) {
            $actual = PlatformRole::query()
                ->where('slug', $slug)
                ->firstOrFail()
                ->permissions()
                ->orderBy('name')
                ->pluck('name')
                ->all();

            sort($expected);
            $this->assertSame($expected, $actual, "Unexpected permission grants for {$slug}.");
        }

        $superAdministratorPermissions = PlatformRole::query()
            ->where('slug', 'super-administrator')
            ->firstOrFail()
            ->permissions()
            ->orderBy('name')
            ->pluck('name')
            ->all();
        $permissionCatalogue = PlatformPermission::query()->orderBy('name')->pluck('name')->all();

        $this->assertSame($permissionCatalogue, $superAdministratorPermissions);
        $this->assertNotContains('platform.tenants.delete', $superAdministratorPermissions);
    }
}
