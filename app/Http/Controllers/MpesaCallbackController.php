<?php

namespace App\Http\Controllers;

use App\Models\MpesaCredential;
use App\Services\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MpesaCallbackController extends Controller
{
    /**
     * Handle incoming Safaricom M-Pesa callback via opaque credential token.
     */
    public function handle(Request $request, string $token): JsonResponse
    {
        $credential = MpesaCredential::withoutGlobalScopes()
            ->with(['tenant', 'branch'])
            ->where('callback_token', $token)
            ->where('is_active', true)
            ->first();

        if ($credential === null) {
            Log::warning('M-Pesa callback rejected: invalid or inactive token.');

            return response()->json([
                'ResultCode' => 1,
                'ResultDesc' => 'Invalid or inactive callback token.',
            ], 403);
        }

        if ($credential->tenant !== null) {
            TenantContext::setTenant($credential->tenant);
        }

        Log::info('M-Pesa callback received and routed', [
            'credential_id' => $credential->id,
            'owner_type' => $credential->owner_type,
            'owner_id' => $credential->owner_id,
            'tenant_id' => $credential->tenant_id,
            'branch_id' => $credential->branch_id,
            'shortcode' => $credential->shortcode,
            'environment' => $credential->environment,
        ]);

        return response()->json([
            'ResultCode' => 0,
            'ResultDesc' => 'Accepted',
            'context' => [
                'credential_id' => $credential->id,
                'owner_type' => $credential->owner_type,
                'tenant_id' => $credential->tenant_id,
                'branch_id' => $credential->branch_id,
                'shortcode' => $credential->shortcode,
                'environment' => $credential->environment,
            ],
        ]);
    }
}
