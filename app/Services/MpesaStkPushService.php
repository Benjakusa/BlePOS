<?php

namespace App\Services;

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
     *     success: bool,
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
        ?string $environment = null
    ): array {
        $env = $environment ?? (config('services.mpesa.sandbox_mode', true) ? 'sandbox' : 'production');
        $cred = $this->resolver->resolve($tenantId, $branchId, $env);

        $baseUrl = $cred['environment'] === 'sandbox'
            ? config('services.mpesa.sandbox_base_url')
            : config('services.mpesa.production_base_url');

        $oauthResponse = Http::withBasicAuth($cred['consumer_key'], $cred['consumer_secret'])
            ->timeout(15)
            ->get("{$baseUrl}/oauth/v1/generate", ['grant_type' => 'client_credentials']);

        $accessToken = $oauthResponse->json('access_token');
        if (! $oauthResponse->successful() || empty($accessToken)) {
            throw new RuntimeException('Unable to obtain M-Pesa OAuth access token.');
        }

        $timestamp = now()->format('YmdHis');
        $password = base64_encode($cred['shortcode'].$cred['passkey'].$timestamp);
        $formattedPhone = $this->normalizePhone($phoneNumber);
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
        $success = $stkResponse->successful() && (($data['ResponseCode'] ?? null) === '0');

        Log::info('M-Pesa STK Push initiated', [
            'credential_id' => $cred['credential_id'],
            'resolved_via' => $cred['resolved_via'],
            'shortcode' => $cred['shortcode'],
            'environment' => $cred['environment'],
            'amount' => (int) ceil($amount),
            'success' => $success,
            'checkout_request_id' => $data['CheckoutRequestID'] ?? null,
        ]);

        return [
            'success' => $success,
            'credential_id' => $cred['credential_id'],
            'resolved_via' => $cred['resolved_via'],
            'shortcode' => $cred['shortcode'],
            'checkout_request_id' => $data['CheckoutRequestID'] ?? null,
            'merchant_request_id' => $data['MerchantRequestID'] ?? null,
            'customer_message' => $data['CustomerMessage'] ?? $data['errorMessage'] ?? ($success ? 'STK Push sent to customer phone.' : 'STK Push request failed.'),
        ];
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
