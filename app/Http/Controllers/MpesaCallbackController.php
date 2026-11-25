<?php

namespace App\Http\Controllers;

use App\Models\MpesaCredential;
use App\Models\MpesaTransaction;
use App\Models\PlatformPayment;
use App\Models\UnmatchedMpesaCallback;
use App\Services\PlatformBillingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MpesaCallbackController extends Controller
{
    public function handle(Request $request, string $token, PlatformBillingService $billing): JsonResponse
    {
        $credential = MpesaCredential::withoutGlobalScopes()
            ->where('callback_token', $token)
            ->where('is_active', true)
            ->first();

        if ($credential === null) {
            Log::warning('M-Pesa callback rejected: invalid or inactive token.');

            return response()->json(['ResultCode' => 1, 'ResultDesc' => 'Invalid or inactive callback token.'], 403);
        }

        $callback = $request->input('Body.stkCallback');
        if (! is_array($callback)) {
            return $this->storeUnmatched($request, $credential, 'invalid_payload', null);
        }

        $checkoutId = $callback['CheckoutRequestID'] ?? null;
        $merchantId = $callback['MerchantRequestID'] ?? null;
        $resultCode = $callback['ResultCode'] ?? null;
        if (! is_scalar($checkoutId) || ! is_scalar($merchantId) || ! is_numeric($resultCode)) {
            return $this->storeUnmatched($request, $credential, 'missing_callback_fields', is_scalar($resultCode) ? (string) $resultCode : null);
        }

        $transaction = MpesaTransaction::query()
            ->where('mpesa_credential_id', $credential->id)
            ->where('checkout_request_id', (string) $checkoutId)
            ->where('merchant_request_id', (string) $merchantId)
            ->first();
        if (! $transaction) {
            return $this->storeUnmatched($request, $credential, 'no_matching_pending_transaction', (string) $resultCode);
        }

        if ($transaction->status === 'confirmed') {
            return response()->json(['ResultCode' => 0, 'ResultDesc' => 'Duplicate callback acknowledged.']);
        }
        if ($transaction->status !== 'pending') {
            return response()->json(['ResultCode' => 1, 'ResultDesc' => 'Transaction is not pending.'], 409);
        }

        if ((string) $resultCode !== '0') {
            $transaction->forceFill(['status' => 'failed', 'result_code' => (string) $resultCode])->save();

            return response()->json(['ResultCode' => 0, 'ResultDesc' => 'Failed payment recorded.']);
        }

        $metadata = collect(data_get($callback, 'CallbackMetadata.Item', []))
            ->filter(fn ($item) => is_array($item) && isset($item['Name']))
            ->mapWithKeys(fn ($item) => [(string) $item['Name'] => $item['Value'] ?? null]);
        $amount = $metadata->get('Amount');
        $receipt = $metadata->get('MpesaReceiptNumber');
        $receivedCheckoutId = $metadata->get('CheckoutRequestID', $checkoutId);
        $receivedMerchantId = $metadata->get('MerchantRequestID', $merchantId);

        if (! is_numeric($amount) || ! is_string($receipt) || trim($receipt) === ''
            || (string) $receivedCheckoutId !== (string) $checkoutId
            || (string) $receivedMerchantId !== (string) $merchantId) {
            return $this->storeUnmatched($request, $credential, 'invalid_success_metadata', (string) $resultCode);
        }

        $receivedAmount = number_format((float) $amount, 2, '.', '');
        $expectedAmount = (string) $transaction->amount;
        $tolerance = number_format((float) config('services.mpesa.callback_amount_tolerance', 0), 2, '.', '');
        $difference = bccomp($receivedAmount, $expectedAmount, 2) >= 0
            ? bcsub($receivedAmount, $expectedAmount, 2)
            : bcsub($expectedAmount, $receivedAmount, 2);
        if (bccomp($difference, $tolerance, 2) > 0) {
            return $this->storeUnmatched($request, $credential, 'amount_mismatch', (string) $resultCode);
        }

        try {
            DB::transaction(function () use ($transaction, $credential, $receipt, $resultCode, $billing): void {
                $locked = MpesaTransaction::query()->lockForUpdate()->findOrFail($transaction->id);
                if ($locked->status === 'confirmed') {
                    return;
                }
                if ($locked->status !== 'pending' || (int) $locked->mpesa_credential_id !== (int) $credential->id) {
                    throw new \DomainException('M-Pesa transaction is no longer eligible for confirmation.');
                }

                $locked->forceFill([
                    'status' => 'confirmed',
                    'provider_receipt' => $receipt,
                    'result_code' => (string) $resultCode,
                    'confirmed_at' => now(),
                ])->save();

                if ($locked->platform_payment_id) {
                    $payment = PlatformPayment::query()->findOrFail($locked->platform_payment_id);
                    $billing->confirmVerifiedProviderPayment($payment, $locked, $receipt);
                }
            });
        } catch (\Throwable $exception) {
            Log::warning('M-Pesa callback settlement rejected.', [
                'credential_id' => $credential->id,
                'transaction_id' => $transaction->id,
                'error_class' => $exception::class,
            ]);

            return $this->storeUnmatched($request, $credential, 'settlement_failed', (string) $resultCode);
        }

        Log::info('M-Pesa callback settlement confirmed.', [
            'credential_id' => $credential->id,
            'transaction_id' => $transaction->id,
            'tenant_id' => $transaction->tenant_id,
            'shop_id' => $transaction->shop_id,
        ]);

        return response()->json(['ResultCode' => 0, 'ResultDesc' => 'Payment confirmed.']);
    }

    private function storeUnmatched(
        Request $request,
        MpesaCredential $credential,
        string $reason,
        ?string $resultCode
    ): JsonResponse {
        $bodyHash = hash('sha256', $request->getContent());
        UnmatchedMpesaCallback::query()->firstOrCreate(
            ['callback_hash' => hash('sha256', $credential->id.'|'.$bodyHash)],
            [
                'mpesa_credential_id' => $credential->id,
                'reason' => $reason,
                'result_code' => $resultCode,
                'received_at' => now(),
            ]
        );
        Log::warning('Unmatched M-Pesa callback stored for operations review.', [
            'credential_id' => $credential->id,
            'reason' => $reason,
        ]);

        return response()->json(['ResultCode' => 1, 'ResultDesc' => 'Callback stored for review.'], 202);
    }
}
