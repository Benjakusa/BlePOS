<?php

namespace App\Http\Controllers;

use App\Models\MpesaCredential;
use App\Models\MpesaTransaction;
use App\Models\PlatformPayment;
use App\Models\PlatformUser;
use App\Models\Sale;
use App\Models\UnmatchedMpesaCallback;
use App\Models\User;
use App\Notifications\WorkspaceAlert;
use App\Services\PlatformBillingService;
use App\Services\SaleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MpesaCallbackController extends Controller
{
    public function handle(Request $request, string $token, PlatformBillingService $billing, SaleService $sales): JsonResponse
    {
        $allowedIps = (array) config('services.mpesa.callback_allowed_ips', []);
        if ($allowedIps !== [] && ! $this->ipAllowed((string) $request->ip(), $allowedIps)) {
            Log::warning('M-Pesa callback rejected: source IP is not allowlisted.', [
                'ip' => $request->ip(),
                'token_hash' => hash('sha256', $token),
            ]);

            return response()->json(['ResultCode' => 1, 'ResultDesc' => 'Callback source rejected.'], 403);
        }

        $credential = MpesaCredential::withoutGlobalScopes()
            ->where(function ($query) use ($token): void {
                $tokenHash = hash('sha256', $token);
                $query->where('callback_token_hash', $tokenHash)
                    ->orWhere('legacy_callback_token_hash', $tokenHash);
            })
            ->where('is_active', true)
            ->first();

        if ($credential === null) {
            Log::warning('M-Pesa callback rejected: invalid or inactive token.', [
                'ip' => $request->ip(),
                'token_hash' => hash('sha256', $token),
            ]);

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
        if ($credential->owner_type !== 'platform' && (int) $transaction->tenant_id !== (int) $credential->tenant_id) {
            return $this->storeUnmatched($request, $credential, 'tenant_scope_mismatch', (string) $resultCode);
        }

        if (in_array($transaction->status, ['confirmed', 'refund_required'], true)) {
            return response()->json(['ResultCode' => 0, 'ResultDesc' => 'Duplicate callback acknowledged.']);
        }
        if (! in_array($transaction->status, ['pending', 'timed_out'], true)) {
            return response()->json(['ResultCode' => 1, 'ResultDesc' => 'Transaction is not pending.'], 409);
        }

        if ((string) $resultCode !== '0') {
            DB::transaction(function () use ($transaction, $resultCode, $sales): void {
                $locked = MpesaTransaction::query()->lockForUpdate()->findOrFail($transaction->id);
                if (! in_array($locked->status, ['pending', 'timed_out'], true)) {
                    return;
                }
                $locked->forceFill(['status' => 'failed', 'result_code' => (string) $resultCode])->save();
                $otherAttemptPending = $locked->sale_id !== null && MpesaTransaction::query()
                    ->where('sale_id', $locked->sale_id)
                    ->where('id', '!=', $locked->id)
                    ->where('status', 'pending')
                    ->exists();
                if ($locked->sale_id && ! $otherAttemptPending) {
                    $sale = Sale::withoutGlobalScopes()->find($locked->sale_id);
                    if ($sale) {
                        $sales->releasePendingMpesaStock($sale);
                    }
                }
            });

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
        $tolerance = $transaction->sale_id !== null
            ? '0.00'
            : number_format((float) config('services.mpesa.callback_amount_tolerance', 0), 2, '.', '');
        $difference = bccomp($receivedAmount, $expectedAmount, 2) >= 0
            ? bcsub($receivedAmount, $expectedAmount, 2)
            : bcsub($expectedAmount, $receivedAmount, 2);
        if (bccomp($difference, $tolerance, 2) > 0) {
            return $this->storeUnmatched($request, $credential, 'amount_mismatch', (string) $resultCode);
        }

        try {
            DB::transaction(function () use ($transaction, $credential, $receipt, $resultCode, $billing, $sales): void {
                $locked = MpesaTransaction::query()->lockForUpdate()->findOrFail($transaction->id);
                if ($locked->status === 'confirmed') {
                    return;
                }
                if (! in_array($locked->status, ['pending', 'timed_out'], true) || (int) $locked->mpesa_credential_id !== (int) $credential->id) {
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
                } elseif ($locked->sale_id) {
                    $sales->finalizeVerifiedMpesaSale($locked);
                }
            });
        } catch (\Throwable $exception) {
            Log::warning('M-Pesa callback settlement rejected.', [
                'credential_id' => $credential->id,
                'transaction_id' => $transaction->id,
                'error_class' => $exception::class,
            ]);

            try {
                $sale = $transaction->sale_id ? Sale::withoutGlobalScopes()->find($transaction->sale_id) : null;
                $flagged = DB::transaction(function () use ($transaction, $receipt, $sales, $sale): string {
                    $locked = MpesaTransaction::query()->lockForUpdate()->findOrFail($transaction->id);
                    if ($locked->status === 'confirmed') {
                        return 'confirmed';
                    }
                    if ($locked->status === 'refund_required') {
                        return 'refund_required';
                    }
                    if (! in_array($locked->status, ['pending', 'timed_out'], true)) {
                        throw new \DomainException('Paid callback cannot be assigned to refund review from the current transaction state.');
                    }
                    $otherAttemptPending = $sale !== null && MpesaTransaction::query()
                        ->where('sale_id', $sale->id)
                        ->where('id', '!=', $locked->id)
                        ->where('status', 'pending')
                        ->exists();
                    if ($sale && $sale->status === 'pending_payment' && ! $otherAttemptPending) {
                        $sales->releasePendingMpesaStock($sale);
                    }
                    $locked->forceFill([
                        'status' => 'refund_required',
                        'provider_receipt' => $receipt,
                        'result_code' => 'settlement_failed',
                    ])->save();

                    return 'refund_required';
                });
                if ($flagged === 'confirmed') {
                    return response()->json(['ResultCode' => 0, 'ResultDesc' => 'Duplicate callback acknowledged.']);
                }
                $this->notifyRefundRequired($transaction, $sale, $receipt);

                return response()->json([
                    'ResultCode' => 0,
                    'ResultDesc' => 'Payment received; settlement requires refund review.',
                ]);
            } catch (\Throwable $flagException) {
                Log::critical('Paid M-Pesa callback could not be flagged for refund review.', [
                    'transaction_id' => $transaction->id,
                    'error_class' => $flagException::class,
                ]);
            }

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

    private function notifyRefundRequired(MpesaTransaction $transaction, ?Sale $sale, string $receipt): void
    {
        $message = sprintf(
            'M-Pesa payment %s (%s) was received but could not complete sale %s. Refund or settlement review is required.',
            $receipt,
            number_format((float) $transaction->amount, 2),
            $sale?->sale_number ?? 'unknown'
        );
        if ($sale) {
            $cashier = User::withoutGlobalScopes()->find($sale->user_id);
            $cashier?->notify(new WorkspaceAlert([
                'title' => 'Paid M-Pesa checkout needs review',
                'message' => $message,
                'url' => route('tenant.pos.sales'),
                'kind' => 'payment',
            ]));
        }
        PlatformUser::query()->where('status', 'active')->get()
            ->filter(fn (PlatformUser $user): bool => $user->hasPermission('platform.billing.subscriptions.manage'))
            ->each(fn (PlatformUser $user) => $user->notify(new WorkspaceAlert([
                'title' => 'M-Pesa settlement requires refund review',
                'message' => $message,
                'url' => route('platform.mpesa.refunds.index'),
                'kind' => 'payment',
            ])));
    }

    /** @param list<string> $allowedIps */
    private function ipAllowed(string $ip, array $allowedIps): bool
    {
        $address = inet_pton($ip);
        if ($address === false) {
            return false;
        }

        foreach ($allowedIps as $rule) {
            if (! is_string($rule) || $rule === '') {
                continue;
            }
            [$network, $prefix] = array_pad(explode('/', $rule, 2), 2, null);
            $packedNetwork = inet_pton($network);
            if ($packedNetwork === false || strlen($packedNetwork) !== strlen($address)) {
                continue;
            }
            $bits = $prefix === null ? strlen($address) * 8 : filter_var($prefix, FILTER_VALIDATE_INT);
            if ($bits === false || $bits < 0 || $bits > strlen($address) * 8) {
                continue;
            }

            $wholeBytes = intdiv($bits, 8);
            $remainingBits = $bits % 8;
            if (substr($address, 0, $wholeBytes) !== substr($packedNetwork, 0, $wholeBytes)) {
                continue;
            }
            if ($remainingBits === 0 || $wholeBytes >= strlen($address)) {
                return true;
            }
            $mask = (0xFF << (8 - $remainingBits)) & 0xFF;
            if ((ord($address[$wholeBytes]) & $mask) === (ord($packedNetwork[$wholeBytes]) & $mask)) {
                return true;
            }
        }

        return false;
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
