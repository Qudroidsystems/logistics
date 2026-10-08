<?php

namespace App\Modules\Payments\Gateways;

use App\Modules\Payments\Gateways\Concerns\UsesAdminCredentials;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * OPay Checkout (cashier). Keys from Finance > Payment Gateways: merchant_id, public_key, secret_key.
 * Test keys use testapi.opaycheckout.com, live keys liveapi.opaycheckout.com. Amounts are in kobo.
 */
class OpayGateway implements CollectionGateway
{
    use UsesAdminCredentials;

    public function key(): string
    {
        return 'opay';
    }

    public function label(): string
    {
        return 'OPay wallet, card or transfer';
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
        return $this->isLive() ? 'https://liveapi.opaycheckout.com' : 'https://testapi.opaycheckout.com';
    }

    /** HMAC-SHA512 of the key-sorted JSON body, signed with the private key. */
    private static function body(array $data): string
    {
        ksort($data);

        return json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    public function initialize(string $email, int $amountKobo, string $reference, string $callbackUrl, array $meta = []): array
    {
        $sep = str_contains($callbackUrl, '?') ? '&' : '?';
        $back = $callbackUrl.$sep.'reference='.urlencode($reference);
        $res = Http::withToken((string) $this->credential('public_key'))->withHeaders(['MerchantId' => (string) $this->credential('merchant_id')])
            ->acceptJson()->asJson()->timeout(25)->post($this->base().'/api/v1/international/cashier/create', [
                'country' => 'NG',
                'reference' => $reference,
                'amount' => ['total' => $amountKobo, 'currency' => 'NGN'],
                'returnUrl' => $back,
                'cancelUrl' => $back,
                'callbackUrl' => route('webhook.opay'),
                'expireAt' => 30,
                'product' => ['name' => mb_substr((string) ($meta['product_name'] ?? 'Delivery payment'), 0, 100), 'description' => 'Payment '.$reference],
                'userInfo' => array_filter(['userEmail' => $email, 'userName' => $meta['user_name'] ?? null]),
            ]);
        $j = $res->json() ?? [];
        if (! $res->successful() || ($j['code'] ?? '') !== '00000' || empty($j['data']['cashierUrl'])) {
            throw new RuntimeException('OPay could not start the payment'.(! empty($j['message']) ? ': '.$j['message'] : '.'));
        }

        return ['authorization_url' => (string) $j['data']['cashierUrl'], 'gateway_ref' => $j['data']['orderNo'] ?? null];
    }

    public function verify(string $reference, ?array $intentRaw = null): array
    {
        $json = self::body(['country' => 'NG', 'reference' => $reference]);
        $res = Http::withToken(hash_hmac('sha512', $json, (string) $this->credential('secret_key')))->withHeaders(['MerchantId' => (string) $this->credential('merchant_id')])
            ->acceptJson()->timeout(25)->withBody($json, 'application/json')->post($this->base().'/api/v1/international/cashier/status');
        $j = $res->json() ?? [];
        if (($j['code'] ?? '') !== '00000' || empty($j['data'])) {
            return ['status' => 'pending', 'amount' => 0, 'currency' => 'NGN', 'channel' => null, 'raw' => []];
        }
        $d = $j['data'];
        $st = strtoupper((string) ($d['status'] ?? ''));

        return [
            'status' => $st === 'SUCCESS' ? 'success' : (in_array($st, ['FAIL', 'CLOSE'], true) ? 'failed' : 'pending'),
            'amount' => (int) ($d['amount']['total'] ?? 0), 'currency' => (string) ($d['amount']['currency'] ?? 'NGN'), 'channel' => 'opay', 'raw' => ['orderNo' => $d['orderNo'] ?? null],
        ];
    }

    public function refund(string $reference, int $amountKobo, ?array $intentRaw = null): string
    {
        throw new RuntimeException('Refund this payment from your OPay merchant dashboard, or credit the customer wallet instead.');
    }
}
