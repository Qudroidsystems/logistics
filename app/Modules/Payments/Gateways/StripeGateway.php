<?php

namespace App\Modules\Payments\Gateways;

use App\Modules\Payments\Gateways\Concerns\UsesAdminCredentials;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/** Stripe Checkout (hosted page). Keys: secret_key and webhook_secret from Finance > Payment Gateways. */
class StripeGateway implements CollectionGateway
{
    use UsesAdminCredentials;

    private const BASE = 'https://api.stripe.com/v1';

    public function key(): string
    {
        return 'stripe';
    }

    public function label(): string
    {
        return 'International card (Stripe)';
    }

    public function isReady(): bool
    {
        return $this->adminReady();
    }

    public function supportsRefund(): bool
    {
        return true;
    }

    private function http()
    {
        return Http::withToken((string) $this->credential('secret_key'))->acceptJson()->asForm()->timeout(20);
    }

    public function initialize(string $email, int $amountKobo, string $reference, string $callbackUrl, array $meta = []): array
    {
        $sep = str_contains($callbackUrl, '?') ? '&' : '?';
        $res = $this->http()->post(self::BASE.'/checkout/sessions', [
            'mode' => 'payment',
            'success_url' => $callbackUrl.$sep.'reference='.urlencode($reference),
            'cancel_url' => $callbackUrl.$sep.'reference='.urlencode($reference).'&cancelled=1',
            'client_reference_id' => $reference,
            'customer_email' => $email,
            'line_items' => [[
                'quantity' => 1,
                'price_data' => [
                    'currency' => (string) config('payments.stripe_currency', 'ngn'),
                    'unit_amount' => $amountKobo,
                    'product_data' => ['name' => (string) ($meta['product_name'] ?? 'Delivery payment')],
                ],
            ]],
            'payment_intent_data' => ['metadata' => ['reference' => $reference]],
            'metadata' => ['reference' => $reference],
        ]);
        if (! $res->successful() || ! $res->json('url')) {
            throw new RuntimeException('Stripe could not start the payment: '.$res->json('error.message', $res->status()));
        }

        return ['authorization_url' => (string) $res->json('url'), 'gateway_ref' => (string) $res->json('id')];
    }

    public function verify(string $reference, ?array $intentRaw = null): array
    {
        $session = $intentRaw['gateway_ref'] ?? null;
        if (! $session) {
            return ['status' => 'pending', 'amount' => 0, 'currency' => 'NGN', 'channel' => null, 'raw' => []];
        }
        $res = Http::withToken((string) $this->credential('secret_key'))->acceptJson()->timeout(20)->get(self::BASE.'/checkout/sessions/'.rawurlencode($session));
        if (! $res->successful()) {
            return ['status' => 'pending', 'amount' => 0, 'currency' => 'NGN', 'channel' => null, 'raw' => []];
        }
        $d = (array) $res->json();
        $status = ($d['payment_status'] ?? '') === 'paid' ? 'success' : (($d['status'] ?? '') === 'expired' ? 'failed' : 'pending');

        return [
            'status' => $status, 'amount' => (int) ($d['amount_total'] ?? 0), 'currency' => strtoupper((string) ($d['currency'] ?? '')), 'channel' => 'card',
            'raw' => ['payment_intent' => $d['payment_intent'] ?? null, 'gateway_ref' => $session],
        ];
    }

    public function refund(string $reference, int $amountKobo, ?array $intentRaw = null): string
    {
        $pi = $intentRaw['payment_intent'] ?? null;
        if (! $pi) {
            throw new RuntimeException('This Stripe payment cannot be refunded automatically.');
        }
        $res = $this->http()->post(self::BASE.'/refunds', ['payment_intent' => $pi, 'amount' => $amountKobo]);
        if (! $res->successful()) {
            throw new RuntimeException('Refund rejected: '.$res->json('error.message', $res->status()));
        }

        return (string) $res->json('id');
    }

    /** Stripe-Signature: t=timestamp,v1=hmac_sha256("t.body", webhook secret). Five minutes of tolerance. */
    public function validSignature(string $rawBody, ?string $header): bool
    {
        $secret = $this->credential('webhook_secret');
        if (! $secret || ! $header) {
            return false;
        }
        $t = null;
        $sigs = [];
        foreach (explode(',', $header) as $part) {
            [$k, $v] = array_pad(explode('=', trim($part), 2), 2, '');
            if ($k === 't') {
                $t = $v;
            } elseif ($k === 'v1') {
                $sigs[] = $v;
            }
        }
        if (! $t || abs(time() - (int) $t) > 300) {
            return false;
        }
        $expected = hash_hmac('sha256', $t.'.'.$rawBody, $secret);
        foreach ($sigs as $s) {
            if (hash_equals($expected, $s)) {
                return true;
            }
        }

        return false;
    }
}
