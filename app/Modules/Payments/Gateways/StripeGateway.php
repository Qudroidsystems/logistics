<?php

namespace App\Modules\Payments\Gateways;

use App\Modules\Payments\Gateways\Concerns\UsesAdminCredentials;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/** Stripe Checkout (hosted page). Keys: secret_key and webhook_secret from Finance > Payment Gateways. */
class StripeGateway implements CollectionGateway, SavesCards
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
            'payment_intent_data' => ['metadata' => ['reference' => $reference]] + (! empty($meta['save_card']) ? ['setup_future_usage' => 'off_session'] : []),
            'metadata' => ['reference' => $reference],
        ] + (! empty($meta['save_card']) ? ['customer_creation' => 'always'] : []));
        if (! $res->successful() || ! $res->json('url')) {
            throw new RuntimeException('Stripe could not start the payment: '.$res->json('error.message', $res->status()));
        }

        return ['authorization_url' => (string) $res->json('url'), 'gateway_ref' => (string) $res->json('id')];
    }

    public function verify(string $reference, ?array $intentRaw = null): array
    {
        // A charge on a saved card has no Checkout session: ask about the PaymentIntent itself.
        if (! empty($intentRaw['off_session'])) {
            return $this->verifyOffSession($reference, $intentRaw);
        }
        $session = $intentRaw['gateway_ref'] ?? null;
        if (! $session) {
            return ['status' => 'pending', 'amount' => 0, 'currency' => \App\Support\Money::code(), 'channel' => null, 'raw' => []];
        }
        $res = Http::withToken((string) $this->credential('secret_key'))->acceptJson()->timeout(20)->get(self::BASE.'/checkout/sessions/'.rawurlencode($session));
        if (! $res->successful()) {
            return ['status' => 'pending', 'amount' => 0, 'currency' => \App\Support\Money::code(), 'channel' => null, 'raw' => []];
        }
        $d = (array) $res->json();
        $status = ($d['payment_status'] ?? '') === 'paid' ? 'success' : (($d['status'] ?? '') === 'expired' ? 'failed' : 'pending');

        return [
            'status' => $status, 'amount' => (int) ($d['amount_total'] ?? 0), 'currency' => strtoupper((string) ($d['currency'] ?? '')), 'channel' => 'card',
            'raw' => ['payment_intent' => $d['payment_intent'] ?? null, 'gateway_ref' => $session],
        ];
    }

    // ---------------------------------------------------------------- saved cards

    public function saveCardOptions(): array
    {
        return ['save_card' => true];
    }

    public function savedCard(array $verifiedRaw): ?array
    {
        $pi = $verifiedRaw['payment_intent'] ?? null;
        if (! $pi) {
            return null;
        }
        $res = Http::withToken((string) $this->credential('secret_key'))->acceptJson()->timeout(20)
            ->get(self::BASE.'/payment_intents/'.rawurlencode((string) $pi), ['expand' => ['payment_method']]);

        return $res->successful() ? self::cardFromIntent((array) $res->json()) : null;
    }

    /** Pure: the card on a PaymentIntent that was set up for later use. */
    public static function cardFromIntent(array $pi): ?array
    {
        $pm = $pi['payment_method'] ?? null;
        $customer = $pi['customer'] ?? null;
        if (! is_array($pm) || ! $customer || ($pi['setup_future_usage'] ?? null) !== 'off_session' || ($pm['type'] ?? '') !== 'card') {
            return null;
        }
        $c = (array) ($pm['card'] ?? []);

        return ['token' => (string) $pm['id'], 'customer_ref' => is_array($customer) ? (string) $customer['id'] : (string) $customer, 'email' => $pi['receipt_email'] ?? null,
            'brand' => isset($c['brand']) ? ucfirst((string) $c['brand']) : null, 'last4' => $c['last4'] ?? null,
            'exp_month' => isset($c['exp_month']) ? (int) $c['exp_month'] : null, 'exp_year' => isset($c['exp_year']) ? (int) $c['exp_year'] : null,
            'fingerprint' => $c['fingerprint'] ?? null];
    }

    public function chargeSaved(array $card, int $amount, string $reference, string $email): array
    {
        // The reference is the idempotency key: a retry after a timeout returns the same charge, never a second one.
        $res = $this->http()->withHeaders(['Idempotency-Key' => $reference])->post(self::BASE.'/payment_intents', [
            'amount' => $amount, 'currency' => (string) config('payments.stripe_currency', 'ngn'),
            'customer' => $card['customer_ref'], 'payment_method' => $card['token'],
            'off_session' => 'true', 'confirm' => 'true', 'description' => 'Wallet top-up for plan renewal',
            'metadata' => ['reference' => $reference],
        ]);

        return self::offSessionResult((array) $res->json(), $res->status());
    }

    /** Pure: what an off-session PaymentIntent answer means. A decline comes back as HTTP 402 with the intent inside the error. */
    public static function offSessionResult(array $body, int $http): array
    {
        $pi = $body['error']['payment_intent'] ?? $body;
        $status = (string) ($pi['status'] ?? '');
        $state = match (true) {
            $status === 'succeeded' => 'success',
            $status === 'processing' => 'pending',
            $http >= 500 => 'pending',
            default => 'failed',
        };

        return ['status' => $state, 'amount' => (int) ($pi['amount_received'] ?? $pi['amount'] ?? 0), 'currency' => strtoupper((string) ($pi['currency'] ?? '')),
            'raw' => ['payment_intent' => $pi['id'] ?? null, 'off_session' => true],
            'message' => $state === 'failed' ? (string) ($body['error']['message'] ?? 'The card was declined.') : null];
    }

    private function verifyOffSession(string $reference, array $raw): array
    {
        $http = Http::withToken((string) $this->credential('secret_key'))->acceptJson()->timeout(20);
        $pi = $raw['payment_intent'] ?? null;
        if (! $pi) {
            $found = $http->get(self::BASE.'/payment_intents/search', ['query' => "metadata['reference']:'".str_replace("'", '', $reference)."'"]);
            $pi = $found->successful() ? ($found->json('data.0.id') ?? null) : null;
            if (! $pi) {
                return ['status' => 'pending', 'amount' => 0, 'currency' => \App\Support\Money::code(), 'channel' => null, 'raw' => []];
            }
        }
        $res = $http->get(self::BASE.'/payment_intents/'.rawurlencode((string) $pi));
        if (! $res->successful()) {
            return ['status' => 'pending', 'amount' => 0, 'currency' => \App\Support\Money::code(), 'channel' => null, 'raw' => []];
        }
        $r = self::offSessionResult((array) $res->json(), 200);

        return ['status' => $r['status'], 'amount' => $r['amount'], 'currency' => $r['currency'], 'channel' => 'card', 'raw' => $r['raw']];
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
