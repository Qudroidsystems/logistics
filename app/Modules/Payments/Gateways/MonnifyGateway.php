<?php

namespace App\Modules\Payments\Gateways;

use App\Modules\Payments\Gateways\Concerns\UsesAdminCredentials;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Monnify (by Moniepoint): hosted checkout for card, bank transfer and USSD.
 * Keys from Finance > Payment Gateways: api_key, secret_key, contract_code.
 * The webhook only names a payment; verify() asks Monnify what happened.
 */
class MonnifyGateway implements CollectionGateway
{
    use UsesAdminCredentials;

    public function key(): string
    {
        return 'monnify';
    }

    public function label(): string
    {
        return 'Bank transfer, card or USSD (Moniepoint)';
    }

    public function isReady(): bool
    {
        return $this->adminReady();
    }

    public function supportsRefund(): bool
    {
        return false;
    }

    private function base(): string
    {
        return $this->isLive() ? 'https://api.monnify.com' : 'https://sandbox.monnify.com';
    }

    /** Bearer token, valid for an hour; kept for 50 minutes. */
    private function token(): string
    {
        return Cache::remember('monnify.token.'.$this->set(), now()->addMinutes(50), function () {
            $res = Http::withBasicAuth((string) $this->credential('api_key'), (string) $this->credential('secret_key'))->acceptJson()->timeout(20)->post($this->base().'/api/v1/auth/login');
            $t = $res->json('responseBody.accessToken');
            if (! $res->successful() || ! $t) {
                throw new RuntimeException('Moniepoint rejected the saved keys.');
            }

            return (string) $t;
        });
    }

    public function initialize(string $email, int $amountKobo, string $reference, string $callbackUrl, array $meta = []): array
    {
        $sep = str_contains($callbackUrl, '?') ? '&' : '?';
        $res = Http::withToken($this->token())->acceptJson()->asJson()->timeout(25)->post($this->base().'/api/v1/merchant/transactions/init-transaction', [
            'amount' => round($amountKobo / 100, 2),
            'customerName' => (string) ($meta['user_name'] ?? 'Customer'),
            'customerEmail' => $email,
            'paymentReference' => $reference,
            'paymentDescription' => (string) ($meta['product_name'] ?? 'Delivery payment'),
            'currencyCode' => 'NGN',
            'contractCode' => (string) $this->credential('contract_code'),
            'redirectUrl' => $callbackUrl.$sep.'reference='.urlencode($reference),
            'paymentMethods' => ['CARD', 'ACCOUNT_TRANSFER', 'USSD'],
        ]);
        $url = $res->json('responseBody.checkoutUrl');
        if (! $res->successful() || ! $url) {
            throw new RuntimeException('Moniepoint could not start the payment: '.$res->json('responseMessage', $res->status()));
        }

        return ['authorization_url' => (string) $url, 'gateway_ref' => $res->json('responseBody.transactionReference')];
    }

    public function verify(string $reference, ?array $intentRaw = null): array
    {
        $pending = ['status' => 'pending', 'amount' => 0, 'currency' => 'NGN', 'channel' => null, 'raw' => []];
        $res = Http::withToken($this->token())->acceptJson()->timeout(20)->get($this->base().'/api/v2/merchant/transactions/query', ['paymentReference' => $reference]);
        if (! $res->successful() || ! $res->json('requestSuccessful')) {
            return $pending;
        }
        $d = (array) $res->json('responseBody');
        $st = strtoupper((string) ($d['paymentStatus'] ?? ''));
        $status = $st === 'PAID' ? 'success' : (in_array($st, ['FAILED', 'EXPIRED', 'CANCELLED', 'ABANDONED', 'REVERSED'], true) ? 'failed' : 'pending');

        return [
            'status' => $status,
            // amountPaid is in naira; the intent is in kobo.
            'amount' => (int) round(((float) ($d['amountPaid'] ?? 0)) * 100),
            'currency' => strtoupper((string) ($d['currencyCode'] ?? $d['currency'] ?? 'NGN')),
            'channel' => isset($d['paymentMethod']) ? strtolower((string) $d['paymentMethod']) : null,
            'raw' => ['transactionReference' => $d['transactionReference'] ?? null],
        ];
    }

    public function refund(string $reference, int $amountKobo, ?array $intentRaw = null): string
    {
        throw new RuntimeException('Refund this payment from your Moniepoint (Monnify) dashboard, or credit the customer wallet instead.');
    }

    /** monnify-signature: HMAC-SHA512 of the raw body with the secret key (production only). */
    public function validSignature(string $rawBody, ?string $header): bool
    {
        $secret = $this->credential('secret_key');

        return $secret && $header && hash_equals(hash_hmac('sha512', $rawBody, $secret), strtolower($header));
    }
}
