<?php

namespace App\Services;

use App\Models\MpesaTransaction;
use App\Models\PlatformPayment;
use App\Models\Sale;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class MpesaStkPushService
{
    public function __construct(private readonly MpesaCredentialResolver $resolver) {}

    /**
     * Initiate an M-Pesa Express (STK Push) payment request using the resolved credential.
     *
     * @return array{
     *     status: string,
     *     transaction_id: int,
     *     credential_id: int,
     *     resolved_via: string,
     *     shortcode: string,
     *     checkout_request_id: ?string,
     *     merchant_request_id: ?string,
     *     customer_message: string
     * }
     */
    public function initiate(
        ?int $tenantId,
        ?int $branchId,
        string $phoneNumber,
        float $amount,
        string $accountReference,
        string $description = 'POS Payment',
        ?string $environment = null,
        ?PlatformPayment $paymentAttempt = null,
        ?int $saleId = null
    ): array {
        if ($paymentAttempt === null && $saleId === null) {
            throw new RuntimeException('M-Pesa initiation requires a server-created payment attempt or sale.');
        }
        $env = $environment ?? (config('services.mpesa.sandbox_mode', true) ? 'sandbox' : 'production');
        $cred = $this->resolver->resolve($tenantId, $branchId, $env);
        $formattedPhone = $this->normalizePhone($phoneNumber);
        $transaction = DB::transaction(function () use ($tenantId, $branchId, $cred, $paymentAttempt, $saleId, $accountReference, $formattedPhone, $amount): MpesaTransaction {
            if ($saleId !== null) {
                $sale = Sale::withoutGlobalScopes()->lockForUpdate()->findOrFail($saleId);
                abort_unless((int) $sale->tenant_id === (int) $tenantId && (int) $sale->shop_id === (int) $branchId
                    && $sale->status === 'pending_payment', 409, 'This POS checkout is no longer awaiting payment.');
                abort_if(MpesaTransaction::query()->where('sale_id', $sale->id)->where('status', 'pending')->exists(), 409, 'An STK request is already pending for this checkout.');
            }

            return MpesaTransaction::query()->create([
                'tenant_id' => $tenantId,
                'shop_id' => $branchId,
                'mpesa_credential_id' => $cred['credential_id'],
                'platform_payment_id' => $paymentAttempt?->id,
                'sale_id' => $saleId,
                'account_reference' => substr($accountReference, 0, 64),
                'phone_hash' => hash_hmac('sha256', $formattedPhone, (string) config('app.key')),
                // Daraja STK Push accepts whole shillings; persist the exact provider amount
                // so callback amount validation compares against what Safaricom was asked to charge.
                'amount' => number_format((float) ceil($amount), 2, '.', ''),
                'status' => 'pending',
            ]);
        });

        $baseUrl = $cred['environment'] === 'sandbox'
            ? config('services.mpesa.sandbox_base_url')
            : config('services.mpesa.production_base_url');

        try {
            $oauthResponse = Http::withBasicAuth($cred['consumer_key'], $cred['consumer_secret'])
                ->timeout(15)
                ->get("{$baseUrl}/oauth/v1/generate", ['grant_type' => 'client_credentials']);

            $accessToken = $oauthResponse->json('access_token');
            if (! $oauthResponse->successful() || empty($accessToken)) {
                throw new RuntimeException('Unable to obtain M-Pesa OAuth access token.');
            }

            $timestamp = now()->format('YmdHis');
            $password = base64_encode($cred['shortcode'].$cred['passkey'].$timestamp);
            $callbackUrl = route('mpesa.callback', $cred['callback_token']);

            $stkResponse = Http::withToken($accessToken)
                ->timeout(20)
                ->post("{$baseUrl}/mpesa/stkpush/v1/processrequest", [
                    'BusinessShortCode' => $cred['shortcode'],
                    'Password' => $password,
                    'Timestamp' => $timestamp,
                    'TransactionType' => 'CustomerPayBillOnline',
                    'Amount' => (int) ceil($amount),
                    'PartyA' => $formattedPhone,
                    'PartyB' => $cred['shortcode'],
                    'PhoneNumber' => $formattedPhone,
                    'CallBackURL' => $callbackUrl,
                    'AccountReference' => substr($accountReference, 0, 12),
                    'TransactionDesc' => substr($description, 0, 13),
                ]);

            $data = $stkResponse->json() ?? [];
            $initiated = $stkResponse->successful() && (($data['ResponseCode'] ?? null) === '0');
            $checkoutRequestId = $data['CheckoutRequestID'] ?? null;
            $merchantRequestId = $data['MerchantRequestID'] ?? null;
            $transaction->forceFill([
                'checkout_request_id' => $checkoutRequestId,
                'merchant_request_id' => $merchantRequestId,
                'status' => $initiated && $checkoutRequestId && $merchantRequestId ? 'pending' : 'failed',
            ])->save();

            Log::info('M-Pesa STK Push request processed', [
                'credential_id' => $cred['credential_id'],
                'resolved_via' => $cred['resolved_via'],
                'environment' => $cred['environment'],
                'amount' => (int) ceil($amount),
                'status' => $transaction->status,
                'transaction_id' => $transaction->id,
            ]);

            return [
                'status' => $transaction->status === 'pending' ? 'initiated' : 'failed',
                'transaction_id' => $transaction->id,
                'credential_id' => $cred['credential_id'],
                'resolved_via' => $cred['resolved_via'],
                'checkout_request_id' => $checkoutRequestId,
                'merchant_request_id' => $merchantRequestId,
                'customer_message' => $data['CustomerMessage'] ?? $data['errorMessage'] ?? ($initiated ? 'STK Push sent to customer phone.' : 'STK Push request failed.'),
            ];
        } catch (\Throwable $exception) {
            $transaction->forceFill(['status' => 'failed'])->save();
            throw $exception;
        }
    }

    private function normalizePhone(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?? '';
        if (str_starts_with($digits, '0')) {
            return '254'.substr($digits, 1);
        }
        if (str_starts_with($digits, '7') || str_starts_with($digits, '1')) {
            return '254'.$digits;
        }

        return $digits;
    }
}
