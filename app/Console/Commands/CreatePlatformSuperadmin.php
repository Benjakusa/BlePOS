<?php

namespace App\Console\Commands;

use App\Models\PlatformRole;
use App\Models\PlatformUser;
use App\Services\PlatformAuditRecorder;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

use function Laravel\Prompts\password;
use function Laravel\Prompts\text;

#[Signature('platform:create-superadmin')]
#[Description('Create the initial platform Super Administrator from a secure server prompt.')]
class CreatePlatformSuperadmin extends Command
{
    public function handle(PlatformAuditRecorder $audit): int
    {
        $role = PlatformRole::query()->where('slug', 'super-administrator')->first();
        if (! $role) {
            $this->call('db:seed', ['--class' => 'Database\\Seeders\\PlatformRoleSeeder']);
            $role = PlatformRole::query()->where('slug', 'super-administrator')->firstOrFail();
        }

        if (PlatformUser::query()->whereHas('roles', fn ($query) => $query->where('platform_roles.id', $role->id))->exists()) {
            $this->components->error('A platform Super Administrator already exists. Use the audited recovery procedure.');

            return self::FAILURE;
        }

        $name = text('Administrator name');
        $emailAddress = mb_strtolower(text('Administrator email', validate: function (string $value): ?string {
            $emailValidator = Validator::make(['email' => $value], [
                'email' => ['required', 'email:rfc', 'max:255'],
            ]);

            return $emailValidator->fails() ? $emailValidator->errors()->first('email') : null;
        }));
        $validator = Validator::make(['name' => $name, 'email' => $emailAddress], [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email:rfc', 'max:255', 'unique:platform_users,email'],
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->components->error($error);
            }

            return self::FAILURE;
        }

        $secret = password('Set a unique password (minimum 14 characters)', validate: function (string $value): ?string {
            $passwordValidator = Validator::make(['password' => $value], [
                'password' => ['required', Password::min(14)->letters()->mixedCase()->numbers()->symbols()],
            ]);

            return $passwordValidator->fails() ? $passwordValidator->errors()->first('password') : null;
        });

        $user = DB::transaction(function () use ($role, $name, $emailAddress, $secret, $audit): ?PlatformUser {
            $lockedRole = PlatformRole::query()->whereKey($role->id)->lockForUpdate()->firstOrFail();
            if ($lockedRole->users()->exists()) {
                return null;
            }

            $user = PlatformUser::query()->create([
                'name' => $name,
                'email' => $emailAddress,
                'password' => Hash::make($secret),
                'status' => 'active',
            ]);
            $user->roles()->attach($lockedRole->id, ['assigned_by' => null]);
            $audit->record(null, 'platform.superadmin.initial_created', PlatformUser::class, (string) $user->id, ['email' => $user->email]);

            return $user;
        });

        if (! $user) {
            $this->components->error('A platform Super Administrator was created while this command was prompting. No additional administrator was created.');

            return self::FAILURE;
        }

        $this->components->info('Initial Super Administrator created. MFA enrollment is required at first login.');

        return self::SUCCESS;
    }
}
