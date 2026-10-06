<?php

namespace App\Modules\Payments\Gateways;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class PaystackGateway
{
    public function secret(): string
    {
        $c = config('services.paystack');
        $key = $c['mode'] === 'live' ? ($c['live_secret_key'] ?? $c['secret_key']) : ($c['test_secret_key'] ?? $c['secret_key']);
        if (! $key) {
            throw new RuntimeException('Paystack secret key is not configured.');
        }

        return $key;
    }

    /** Paystack signs the raw body with HMAC-SHA512 and sends it in x-paystack-signature. */
    public function validSignature(string $rawBody, ?string $signature): bool
    {
        return $signature !== null && hash_equals(hash_hmac('sha512', $rawBody, $this->secret()), $signature);
    }

    /** @return array{authorization_url:string, access_code:string, reference:string} */
    public function initialize(string $email, int $amountKobo, string $reference, string $callbackUrl, array $metadata = []): array
    {
        $res = Http::withToken($this->secret())->acceptJson()->timeout(15)
            ->post('https://api.paystack.co/transaction/initialize', [
                'email' => $email, 'amount' => $amountKobo, 'currency' => 'NGN',
                'reference' => $reference, 'callback_url' => $callbackUrl, 'metadata' => $metadata,
            ]);
        if (! $res->ok() || ! $res->json('status')) {
            throw new RuntimeException('Paystack initialise failed: '.$res->json('message', $res->status()));
        }

        return $res->json('data');
    }
}
