<?php

namespace Tests\Feature;

use App\Models\PlatformRole;
use App\Models\PlatformUser;
use Database\Seeders\PlatformRoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PlatformSuperadminCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_cli_creates_the_initial_super_administrator_without_echoing_the_password(): void
    {
        $password = 'Unique-platform-Password-123!';
        $this->artisan('platform:create-superadmin')
            ->expectsQuestion('Administrator name', 'Initial Administrator')
            ->expectsQuestion('Administrator email', 'initial-admin@example.test')
            ->expectsQuestion('Set a unique password (minimum 14 characters)', $password)
            ->doesntExpectOutputToContain($password)
            ->assertSuccessful();

        $user = PlatformUser::query()->where('email', 'initial-admin@example.test')->firstOrFail();
        $role = PlatformRole::query()->where('slug', 'super-administrator')->firstOrFail();
        $this->assertTrue(Hash::check($password, $user->password));
        $this->assertTrue($user->roles()->whereKey($role->id)->exists());
        $this->assertNull($user->mfa_confirmed_at);
        $this->assertDatabaseHas('platform_audit_log', ['action' => 'platform.superadmin.initial_created']);
    }

    public function test_the_cli_refuses_to_create_a_second_initial_super_administrator(): void
    {
        $this->seed(PlatformRoleSeeder::class);
        $user = PlatformUser::factory()->create();
        $role = PlatformRole::query()->where('slug', 'super-administrator')->firstOrFail();
        $user->roles()->attach($role->id);

        $this->artisan('platform:create-superadmin')->assertFailed();
        $this->assertDatabaseCount('platform_users', 1);
    }
}
