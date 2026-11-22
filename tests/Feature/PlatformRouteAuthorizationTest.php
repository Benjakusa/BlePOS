<?php

namespace Tests\Feature;

use App\Http\Middleware\EnsurePlatformPermission;
use App\Models\PlatformPermission;
use App\Models\PlatformRole;
use App\Models\PlatformSession;
use App\Models\PlatformUser;
use Database\Seeders\PlatformRoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class PlatformRouteAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_platform_control_route_requires_an_explicit_permission(): void
    {
        $protectedPrefixes = [
            'platform.dashboard',
            'platform.audit.',
            'platform.approvals.',
            'platform.security.events.',
            'platform.roles.',
            'platform.sessions.',
            'platform.users.',
        ];
        $protectedRoutes = collect(Route::getRoutes())
            ->filter(fn ($route): bool => collect($protectedPrefixes)->contains(
                fn (string $prefix): bool => str_starts_with((string) $route->getName(), $prefix),
            ));

        $this->assertNotEmpty($protectedRoutes);

        foreach ($protectedRoutes as $route) {
            $middleware = $route->gatherMiddleware();
            $this->assertContains('auth:platform', $middleware, "{$route->getName()} must use platform authentication.");
            $this->assertContains('platform.mfa', $middleware, "{$route->getName()} must require MFA.");
            $this->assertContains('platform.session', $middleware, "{$route->getName()} must track and validate its session.");
            $this->assertTrue(
                collect($middleware)->contains(fn (string $entry): bool => str_starts_with($entry, 'platform.permission:')),
                "{$route->getName()} must require a named permission.",
            );
        }
    }

    public function test_every_platform_control_mutation_requires_step_up_authentication(): void
    {
        $actionPrefixes = [
            'platform.approvals.',
            'platform.roles.',
            'platform.users.',
            'platform.sessions.',
        ];
        $mutations = collect(Route::getRoutes())
            ->filter(fn ($route): bool => collect($actionPrefixes)->contains(
                fn (string $prefix): bool => str_starts_with((string) $route->getName(), $prefix),
            ))
            ->filter(fn ($route): bool => array_intersect($route->methods(), ['POST', 'PUT', 'PATCH', 'DELETE']) !== []);

        $this->assertNotEmpty($mutations);

        foreach ($mutations as $route) {
            $middleware = $route->gatherMiddleware();
            $this->assertContains('auth:platform', $middleware, "{$route->getName()} must use platform authentication.");
            $this->assertContains('platform.step-up', $middleware, "{$route->getName()} must require step-up authentication.");
            $this->assertTrue(
                collect($middleware)->contains(fn (string $entry): bool => str_starts_with($entry, 'platform.permission:')),
                "{$route->getName()} must require a named permission.",
            );
        }
    }

    public function test_every_platform_control_route_uses_a_permission_in_the_catalogue(): void
    {
        $this->seed(PlatformRoleSeeder::class);

        $protectedPrefixes = [
            'platform.dashboard',
            'platform.audit.',
            'platform.approvals.',
            'platform.security.events.',
            'platform.roles.',
            'platform.sessions.',
            'platform.users.',
        ];
        $protectedRoutes = collect(Route::getRoutes())
            ->filter(fn ($route): bool => collect($protectedPrefixes)->contains(
                fn (string $prefix): bool => str_starts_with((string) $route->getName(), $prefix),
            ));

        $this->assertNotEmpty($protectedRoutes);

        foreach ($protectedRoutes as $route) {
            $permissionMiddleware = collect($route->gatherMiddleware())
                ->first(fn (string $entry): bool => str_starts_with($entry, 'platform.permission:'));

            $this->assertNotNull($permissionMiddleware, "{$route->getName()} must name its required permission.");

            $permission = substr($permissionMiddleware, strlen('platform.permission:'));
            $this->assertTrue(
                PlatformPermission::query()->where('name', $permission)->exists(),
                "Unknown permission for {$route->getName()}.",
            );
        }
    }

    public function test_platform_action_controllers_recheck_permissions_without_route_middleware(): void
    {
        $this->seed(PlatformRoleSeeder::class);
        $restrictedUser = PlatformUser::factory()->create(['mfa_confirmed_at' => now()]);
        $restrictedRole = PlatformRole::query()->create([
            'name' => 'Restricted Test Role',
            'slug' => 'restricted-test-role',
            'description' => 'No platform permissions.',
            'is_builtin' => false,
        ]);
        $restrictedUser->roles()->attach($restrictedRole->id);
        $sessionId = app('session.store')->getId();
        PlatformSession::query()->create([
            'platform_user_id' => $restrictedUser->id,
            'session_hash' => hash('sha256', $sessionId),
            'session_identifier' => $sessionId,
            'logged_in_at' => now(),
            'last_activity_at' => now(),
            'expires_at' => now()->addHours(8),
        ]);

        $this->withoutMiddleware(EnsurePlatformPermission::class)
            ->actingAs($restrictedUser, 'platform')
            ->withSession([
                'platform_mfa_verified_at' => now()->timestamp,
                'platform_step_up_at' => now()->timestamp,
            ])
            ->withCookie(config('session.platform.cookie'), $sessionId);

        foreach ([
            'platform.dashboard',
            'platform.users.index',
            'platform.roles.index',
            'platform.approvals.index',
            'platform.sessions.index',
            'platform.security.events.index',
            'platform.audit.index',
        ] as $routeName) {
            $this->get(route($routeName))->assertForbidden();
        }

        $this->post(route('platform.users.invite'), [
            'email' => 'unauthorized-direct-action@example.test',
            'platform_role_id' => $restrictedRole->id,
            'reason' => 'Direct action authorization check.',
        ])->assertForbidden();

        $this->assertDatabaseMissing('platform_invitations', ['email' => 'unauthorized-direct-action@example.test']);
    }
}
