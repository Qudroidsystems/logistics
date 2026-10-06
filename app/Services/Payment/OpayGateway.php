<?php

namespace App\Services\Payment;

use App\Models\PaymentGateway;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * OPay Checkout (cashier) for online school fees — Nigeria (country NG, NGN).
 *
 * Keys come from Finance › Payment Gateways (provider_key "opay"):
 *   merchant_id, public_key (used to create the cashier), secret_key (the
 *   "private key": signs status queries and callbacks). Test keys hit
 *   testapi.opaycheckout.com, live keys liveapi.opaycheckout.com.
 *
 * verify() returns the same shape as PaystackGateway::verify() so the
 * checkout service can finalise either gateway the same way.
 * Amounts are in kobo ("cent units") both ways.
 */
class OpayGateway
{
    protected const LIVE = 'https://liveapi.opaycheckout.com';
    protected const TEST = 'https://testapi.opaycheckout.com';

    protected ?string $merchantId = null;
    protected ?string $publicKey = null;
    protected ?string $secretKey = null;
    protected string $mode = 'sandbox';
    protected bool $active = false;
    protected bool $hasRow = false;

    public function __construct()
    {
        $gw = PaymentGateway::where('provider_key', 'opay')->first();
        $this->hasRow = (bool) $gw;
        if (!$gw) return;
        $this->mode = $gw->mode === 'live' ? 'live' : 'sandbox';
        $set = $this->mode === 'live' ? 'live' : 'test';
        $this->merchantId = $gw->credential('merchant_id', $set);
        $this->publicKey = $gw->credential('public_key', $set);
        $this->secretKey = $gw->credential('secret_key', $set);
        $this->active = (bool) $gw->is_active;
    }

    public function key(): string { return 'opay'; }
    public function name(): string { return 'OPay'; }
    public function mode(): string { return $this->mode; }

    public function problem(): ?string
    {
        if (!$this->hasRow) return 'OPay has not been set up.';
        if (!$this->active) return 'OPay is switched off.';
        if (!$this->merchantId || !$this->publicKey || !$this->secretKey) return 'OPay ' . ($this->mode === 'live' ? 'live' : 'test') . ' keys are incomplete (merchant ID, public key and private key are all needed).';
        return null;
    }

    public function isReady(): bool
    {
        return $this->problem() === null;
    }

    protected function base(): string
    {
        return $this->mode === 'live' ? self::LIVE : self::TEST;
    }

    /** HMAC-SHA512 of the key-sorted JSON body, signed with the private key. */
    public static function sign(string $json, string $secret): string
    {
        return hash_hmac('sha512', $json, $secret);
    }

    protected static function body(array $data): string
    {
        ksort($data);
        return json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    /**
     * Open an OPay cashier. Returns the same keys as PaystackGateway::initialize():
     * ['ok', 'authorization_url', 'access_code' (OPay orderNo)] or ['ok' => false, 'message'].
     */
    public function initialize(string $email, int $amountKobo, string $reference, string $returnUrl, array $meta = []): array
    {
        if (!$this->isReady()) return ['ok' => false, 'message' => 'OPay is not available right now.'];

        $sep = str_contains($returnUrl, '?') ? '&' : '?';
        $back = $returnUrl . $sep . 'reference=' . urlencode($reference);
        $payload = [
            'country' => 'NG',
            'reference' => $reference,
            'amount' => ['total' => $amountKobo, 'currency' => 'NGN'],
            'returnUrl' => $back,
            'cancelUrl' => $back,
            'callbackUrl' => route('webhook.opay'),
            'expireAt' => 30,
            'product' => [
                'name' => mb_substr((string) ($meta['product_name'] ?? 'School fees'), 0, 100),
                'description' => mb_substr((string) ($meta['product_description'] ?? ('School fees ' . $reference)), 0, 200),
            ],
            'userInfo' => array_filter([
                'userEmail' => $email,
                'userId' => isset($meta['student_id']) ? (string) $meta['student_id'] : null,
                'userName' => $meta['user_name'] ?? null,
                'userMobile' => $meta['user_mobile'] ?? null,
            ]),
        ];

        try {
            $res = Http::withToken((string) $this->publicKey)->withHeaders(['MerchantId' => (string) $this->merchantId])
                ->acceptJson()->timeout(30)->asJson()
                ->post($this->base() . '/api/v1/international/cashier/create', $payload);
            $j = $res->json() ?? [];
            if ($res->successful() && ($j['code'] ?? '') === '00000' && !empty($j['data']['cashierUrl'])) {
                return ['ok' => true, 'authorization_url' => (string) $j['data']['cashierUrl'], 'access_code' => (string) ($j['data']['orderNo'] ?? '')];
            }
            Log::warning('OPay cashier create refused', ['reference' => $reference, 'code' => $j['code'] ?? $res->status(), 'message' => $j['message'] ?? null]);
            return ['ok' => false, 'message' => 'OPay could not start the payment' . (!empty($j['message']) ? ': ' . $j['message'] : '.')];
        } catch (\Throwable $e) {
            Log::warning('OPay cashier create failed', ['reference' => $reference, 'error' => $e->getMessage()]);
            return ['ok' => false, 'message' => 'OPay could not be reached. Please try again.'];
        }
    }

    /** Raw cashier status call. */
    public function status(string $reference): array
    {
        $json = self::body(['country' => 'NG', 'reference' => $reference]);
        $res = Http::withToken(self::sign($json, (string) $this->secretKey))->withHeaders(['MerchantId' => (string) $this->merchantId])
            ->acceptJson()->timeout(30)->withBody($json, 'application/json')
            ->post($this->base() . '/api/v1/international/cashier/status');
        return ['http' => $res->status(), 'json' => $res->json() ?? []];
    }

    /**
     * Ask OPay for the real state of a payment. Normalised to the Paystack shape:
     * status success|failed|abandoned|pending, amount (kobo), currency, reference, channel…
     */
    public function verify(string $reference): array
    {
        if (!$this->merchantId || !$this->secretKey) return ['ok' => false, 'message' => 'OPay keys are missing.'];
        try {
            $r = $this->status($reference);
            $j = $r['json'];
            if (($j['code'] ?? '') !== '00000' || empty($j['data'])) {
                return ['ok' => false, 'message' => $j['message'] ?? ('OPay HTTP ' . $r['http'])];
            }
            $d = $j['data'];
            $map = ['SUCCESS' => 'success', 'FAIL' => 'failed', 'CLOSE' => 'abandoned', 'INITIAL' => 'pending', 'PENDING' => 'pending'];
            $st = strtoupper((string) ($d['status'] ?? ''));
            return ['ok' => true, 'data' => [
                'id' => $d['orderNo'] ?? null,
                'status' => $map[$st] ?? 'pending',
                'reference' => (string) ($d['reference'] ?? ''),
                'amount' => (int) ($d['amount']['total'] ?? 0),
                'currency' => (string) ($d['amount']['currency'] ?? 'NGN'),
                'channel' => 'opay',
                'fees' => 0,
                'paid_at' => !empty($d['createTime']) && $st === 'SUCCESS' ? date('c', (int) ($d['createTime'] / 1000)) : null,
                'gateway_response' => $st === 'SUCCESS' ? 'Approved by OPay' : ('OPay status: ' . ($st ?: 'unknown')),
            ]];
        } catch (\Throwable $e) {
            Log::warning('OPay status check failed', ['reference' => $reference, 'error' => $e->getMessage()]);
            return ['ok' => false, 'message' => 'OPay could not be reached.'];
        }
    }

    /**
     * Check a callback's sha512 field. OPay documents HMAC-SHA3-512 over a fixed
     * string of the payload fields; plain HMAC-SHA512 is accepted too. The
     * checkout never trusts the callback on its own — it always re-queries.
     */
    public function validCallback(array $body): bool
    {
        $p = (array) ($body['payload'] ?? []);
        $sig = strtolower((string) ($body['sha512'] ?? ''));
        if (!$this->secretKey || $sig === '' || !$p) return false;
        $str = sprintf('{Amount:"%s",Currency:"%s",Reference:"%s",Refunded:%s,Status:"%s",Timestamp:"%s",Token:"%s",TransactionID:"%s"}',
            $p['amount'] ?? '', $p['currency'] ?? '', $p['reference'] ?? '', !empty($p['refunded']) ? 't' : 'f',
            $p['status'] ?? '', $p['timestamp'] ?? '', $p['token'] ?? '', $p['transactionId'] ?? '');
        foreach (['sha3-512', 'sha512'] as $algo) {
            if (in_array($algo, hash_hmac_algos(), true) && hash_equals(hash_hmac($algo, $str, $this->secretKey), $sig)) return true;
        }
        return false;
    }

    /** Connection test for the admin page: a lookup of a reference that doesn't exist. */
    public static function testKeys(string $mode, ?string $merchantId, ?string $secret): array
    {
        $json = self::body(['country' => 'NG', 'reference' => 'CSK-KEYTEST-' . time()]);
        $res = Http::withToken(self::sign($json, (string) $secret))->withHeaders(['MerchantId' => (string) $merchantId])
            ->acceptJson()->timeout(20)->withBody($json, 'application/json')
            ->post(($mode === 'live' ? self::LIVE : self::TEST) . '/api/v1/international/cashier/status');
        $code = (string) ($res->json('code') ?? '');
        if (in_array($code, ['00000', '02006'], true)) return ['success' => true];
        return ['success' => false, 'plain' => true, 'message' => 'OPay refused the keys' . ($res->json('message') ? ': ' . $res->json('message') : ' (HTTP ' . $res->status() . ').')];
    }
}
