<?php

namespace App\Services;

use App\Models\MpesaCredential;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class MpesaCredentialResolver
{
    public function __construct(private MpesaEncryptionService $encryption) {}

    /**
     * Resolve credential for a transaction.
     * Priority: branch → tenant → platform
     * Returns decrypted credential data array or throws.
     */
    public function resolve(?int $tenantId, ?int $branchId, string $environment = 'sandbox'): array
    {
        $credential = null;
        $resolvedVia = null;

        // 1. Branch-level
        if ($branchId !== null) {
            $credential = MpesaCredential::withoutGlobalScopes()
                ->where('owner_type', 'branch')
                ->where('branch_id', $branchId)
                ->where('environment', $environment)
                ->where('is_active', true)
                ->where('is_default', true)
                ->first();
            if ($credential) {
                $resolvedVia = 'branch';
            }
        }

        // 2. Tenant-level
        if ($credential === null && $tenantId !== null) {
            $credential = MpesaCredential::withoutGlobalScopes()
                ->where('owner_type', 'tenant')
                ->where('tenant_id', $tenantId)
                ->where('environment', $environment)
                ->where('is_active', true)
                ->where('is_default', true)
                ->first();
            if ($credential) {
                $resolvedVia = 'tenant';
            }
        }

        // Tenant sales may not silently collect into the platform's shortcode.
        // Platform billing passes null tenant/branch IDs and still resolves its own credential.
        if ($credential === null && ($tenantId === null || (bool) config('services.mpesa.allow_platform_fallback_for_tenant_sales', false))) {
            $credential = MpesaCredential::withoutGlobalScopes()
                ->where('owner_type', 'platform')
                ->where('environment', $environment)
                ->where('is_active', true)
                ->where('is_default', true)
                ->first();
            if ($credential) {
                $resolvedVia = 'platform';
            }
        }

        if ($credential === null) {
            Log::warning('MpesaCredentialResolver: no active credential found', [
                'tenant_id' => $tenantId,
                'branch_id' => $branchId,
                'environment' => $environment,
            ]);
            throw new RuntimeException('No active M-Pesa credential configured for this context.');
        }

        Log::info('MpesaCredentialResolver: credential resolved', [
            'credential_id' => $credential->id,
            'owner_type' => $credential->owner_type,
            'shortcode' => $credential->shortcode,
            'resolved_via' => $resolvedVia,
            'environment' => $environment,
        ]);

        return [
            'credential_id' => $credential->id,
            'shortcode' => $credential->shortcode,
            'passkey' => $this->encryption->decrypt($credential->passkey),
            'consumer_key' => $this->encryption->decrypt($credential->consumer_key),
            'consumer_secret' => $this->encryption->decrypt($credential->consumer_secret),
            'initiator_name' => $credential->initiator_name ? $this->encryption->decrypt($credential->initiator_name) : null,
            'security_credential' => $credential->security_credential ? $this->encryption->decrypt($credential->security_credential) : null,
            'callback_token' => $credential->callback_token,
            'environment' => $credential->environment,
            'resolved_via' => $resolvedVia,
        ];
    }
}
