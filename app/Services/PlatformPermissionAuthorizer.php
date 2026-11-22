<?php

namespace App\Services;

use App\Models\PlatformUser;
use Illuminate\Contracts\Auth\Authenticatable;

class PlatformPermissionAuthorizer
{
    public function allows(?Authenticatable $user, string $permission): bool
    {
        return $user instanceof PlatformUser && $user->hasPermission($permission);
    }

    public function ensure(?Authenticatable $user, string $permission): void
    {
        abort_unless($this->allows($user, $permission), 403);
    }
}
