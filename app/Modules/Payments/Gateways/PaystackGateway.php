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

    /** @return string the transfer recipient code */
    public function createRecipient(string $name, string $accountNumber, string $bankCode): string
    {
        $res = Http::withToken($this->secret())->acceptJson()->timeout(15)->post('https://api.paystack.co/transferrecipient', [
            'type' => 'nuban', 'name' => $name, 'account_number' => $accountNumber, 'bank_code' => $bankCode, 'currency' => 'NGN',
        ]);
        if (! $res->ok() || ! $res->json('status')) {
            throw new RuntimeException('Could not register bank account: '.$res->json('message', $res->status()));
        }

        return $res->json('data.recipient_code');
    }

    /** The reference makes the transfer idempotent on Paystack's side: a retry cannot pay twice. */
    public function transfer(int $amountKobo, string $recipientCode, string $reference, string $reason): void
    {
        $res = Http::withToken($this->secret())->acceptJson()->timeout(20)->post('https://api.paystack.co/transfer', [
            'source' => 'balance', 'amount' => $amountKobo, 'recipient' => $recipientCode, 'reference' => $reference, 'reason' => $reason,
        ]);
        if (! $res->successful() && $res->status() < 500) {
            throw new RuntimeException('Transfer rejected: '.$res->json('message', $res->status()));
        }
        // 5xx / timeouts fall through as non-RuntimeException failures so the payout stays 'processing'.
        if ($res->serverError()) {
            throw new \Exception('Paystack unavailable');
        }
    }

    /** @return string Paystack's refund id, used to match the refund.processed webhook */
    public function refund(string $transactionReference, int $amountKobo): string
    {
        $res = Http::withToken($this->secret())->acceptJson()->timeout(20)->post('https://api.paystack.co/refund', ['transaction' => $transactionReference, 'amount' => $amountKobo]);
        if (! $res->successful() || ! $res->json('status')) {
            throw new RuntimeException('Refund rejected: '.$res->json('message', $res->status()));
        }

        return (string) ($res->json('data.id') ?? $res->json('data.transaction.reference'));
    }

    /** Confirms a bank account and returns the registered name. */
    public function resolveAccount(string $accountNumber, string $bankCode): string
    {
        $res = Http::withToken($this->secret())->acceptJson()->timeout(15)->get('https://api.paystack.co/bank/resolve', ['account_number' => $accountNumber, 'bank_code' => $bankCode]);
        if (! $res->ok() || ! $res->json('status')) {
            throw new RuntimeException('Could not verify this bank account.');
        }

        return (string) $res->json('data.account_name');
    }
}
