<?php

namespace App\Models;

use App\Services\MpesaEncryptionService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class MpesaCredential extends Model
{
    protected $guarded = ['id'];

    protected $hidden = [
        'passkey',
        'consumer_key',
        'consumer_secret',
        'initiator_name',
        'security_credential',
        'callback_token_hash',
        'legacy_callback_token_hash',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_default' => 'boolean',
    ];

    // -------------------------------------------------------------------------
    // Boot
    // -------------------------------------------------------------------------

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $model): void {
            $model->uuid = $model->uuid ?? (string) Str::uuid();
            $rawToken = $model->deriveCallbackToken();
            $model->callback_token_hash = hash('sha256', $rawToken);
            $model->callback_token = null;
        });
    }

    // -------------------------------------------------------------------------
    // Accessors
    // -------------------------------------------------------------------------

    public function maskedConsumerKey(): string
    {
        return app(MpesaEncryptionService::class)->mask(
            app(MpesaEncryptionService::class)->decrypt($this->consumer_key)
        );
    }

    public function maskedConsumerSecret(): string
    {
        return app(MpesaEncryptionService::class)->mask(
            app(MpesaEncryptionService::class)->decrypt($this->consumer_secret)
        );
    }

    public function maskedPasskey(): string
    {
        return app(MpesaEncryptionService::class)->mask(
            app(MpesaEncryptionService::class)->decrypt($this->passkey)
        );
    }

    public function getCallbackTokenAttribute(): string
    {
        return $this->deriveCallbackToken();
    }

    private function deriveCallbackToken(): string
    {
        $secret = (string) config('services.mpesa.callback_secret', '');

        return hash_hmac('sha256', 'mpesa-callback:'.(string) $this->uuid, $secret);
    }

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Shop::class, 'branch_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function audits(): HasMany
    {
        return $this->hasMany(MpesaCredentialAudit::class);
    }

    // -------------------------------------------------------------------------
    // Scopes
    // -------------------------------------------------------------------------

    public function scopeForOwner(Builder $query, string $type, ?int $ownerId): Builder
    {
        return $query->where('owner_type', $type)->where('owner_id', $ownerId);
    }

    public function scopeDefaultFor(Builder $query, string $type, ?int $ownerId, string $env): Builder
    {
        return $query
            ->where('owner_type', $type)
            ->where('owner_id', $ownerId)
            ->where('environment', $env)
            ->where('is_default', true);
    }
}
