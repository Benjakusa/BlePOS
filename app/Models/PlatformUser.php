<?php

namespace App\Models;

use App\Notifications\PlatformPasswordResetNotification;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class PlatformUser extends Authenticatable implements CanResetPasswordContract
{
    use CanResetPassword;
    use HasFactory;
    use Notifiable;

    protected $fillable = ['name', 'email', 'password', 'status', 'must_reset_password'];

    protected $hidden = ['password', 'remember_token', 'totp_secret', 'recovery_code_hashes'];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'totp_secret' => 'encrypted',
            'recovery_code_hashes' => 'array',
            'mfa_confirmed_at' => 'datetime',
            'locked_until' => 'datetime',
            'last_login_at' => 'datetime',
            'must_reset_password' => 'boolean',
        ];
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(PlatformRole::class, 'platform_role_user')
            ->withPivot('assigned_by')
            ->withTimestamps();
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(PlatformSession::class);
    }

    public function hasPermission(string $permission): bool
    {
        return $this->status === 'active'
            && $this->roles()->whereHas('permissions', fn ($query) => $query->where('name', $permission))->exists();
    }

    public function sendPasswordResetNotification(#[\SensitiveParameter] $token): void
    {
        $this->notify(new PlatformPasswordResetNotification($token));
    }
}
