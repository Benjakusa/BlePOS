<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

/**
 * Shared payment recording helper for industry modules.
 *
 * Supports Cash and M-Pesa only. Uses MpesaCredentialResolver (branch -> tenant -> platform)
 * without storing credentials or executing STK/callback flows (out of scope).
 */
class ModulePaymentService
{
    public function __construct(private readonly MpesaCredentialResolver $resolver) {}

    /**
     * Resolve payment metadata for a module transaction.
     *
     * @return array{
     *     method: string,
     *     status: string,
     *     credential_id: ?int,
     *     resolved_via: ?string,
     *     shortcode: ?string
     * }
     */
    public function preparePayment(
        int $tenantId,
        ?int $shopId,
        string $method,
        float $amount,
        string $module,
        ?string $reference = null
    ): array {
        $normalized = strtolower(trim($method));
        abort_unless(in_array($normalized, ['cash', 'mpesa'], true), 422, 'Only cash and mpesa payment methods are supported.');

        if ($normalized === 'cash') {
            Log::info('ModulePaymentService: cash payment recorded', [
                'module' => $module,
                'tenant_id' => $tenantId,
                'shop_id' => $shopId,
                'amount' => $amount,
                'reference' => $reference,
            ]);

            return [
                'method' => 'cash',
                'status' => 'completed',
                'credential_id' => null,
                'resolved_via' => null,
                'shortcode' => null,
            ];
        }

        try {
            $env = config('services.mpesa.sandbox_mode', true) ? 'sandbox' : 'production';
            $cred = $this->resolver->resolve($tenantId, $shopId, $env);

            Log::info('ModulePaymentService: mpesa payment recorded', [
                'module' => $module,
                'tenant_id' => $tenantId,
                'shop_id' => $shopId,
                'amount' => $amount,
                'reference' => $reference,
                'credential_id' => $cred['credential_id'],
                'resolved_via' => $cred['resolved_via'],
                'shortcode' => $cred['shortcode'],
            ]);

            return [
                'method' => 'mpesa',
                'status' => 'completed',
                'credential_id' => $cred['credential_id'],
                'resolved_via' => $cred['resolved_via'],
                'shortcode' => $cred['shortcode'],
            ];
        } catch (\Throwable) {
            Log::warning('ModulePaymentService: mpesa credential unresolved, marked pending-resolution', [
                'module' => $module,
                'tenant_id' => $tenantId,
                'shop_id' => $shopId,
                'amount' => $amount,
                'reference' => $reference,
            ]);

            return [
                'method' => 'mpesa',
                'status' => 'pending-resolution',
                'credential_id' => null,
                'resolved_via' => null,
                'shortcode' => null,
            ];
        }
    }
}
