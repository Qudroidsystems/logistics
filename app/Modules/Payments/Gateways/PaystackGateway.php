<?php

namespace App\Modules\Payments\Gateways;

use App\Modules\Payments\Gateways\Concerns\UsesAdminCredentials;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class PaystackGateway implements CollectionGateway, SavesCards
{
    use UsesAdminCredentials;

    public function key(): string
    {
        return 'paystack';
    }

    public function label(): string
    {
        return 'Card, bank transfer or USSD (Paystack)';
    }

    /** Keys saved in Finance > Payment Gateways win; .env is the fallback for servers with no saved keys. */
    public function isReady(): bool
    {
        if ($this->row()) {
            return $this->adminReady();
        }
        try {
            $this->secret();
        } catch (RuntimeException) {
            return false;
        }

        return (bool) config('services.paystack.active', true);
    }

    public function supportsRefund(): bool
    {
        return true;
    }

    public function verify(string $reference, ?array $intentRaw = null): array
    {
        $res = Http::withToken($this->secret())->acceptJson()->timeout(15)->get('https://api.paystack.co/transaction/verify/'.rawurlencode($reference));
        if (! $res->ok() || ! $res->json('status')) {
            return ['status' => 'pending', 'amount' => 0, 'currency' => 'NGN', 'channel' => null, 'raw' => []];
        }
        $d = (array) $res->json('data');
        $st = (string) ($d['status'] ?? '');

        return [
            'status' => $st === 'success' ? 'success' : (in_array($st, ['failed', 'abandoned', 'reversed'], true) ? 'failed' : 'pending'),
            'amount' => (int) ($d['amount'] ?? 0), 'currency' => (string) ($d['currency'] ?? 'NGN'), 'channel' => $d['channel'] ?? null, 'raw' => $d,
        ];
    }

    public function secret(): string
    {
        if ($this->row() && $this->credential('secret_key')) {
            return $this->credential('secret_key');
        }
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

    /** @return array{authorization_url:string, access_code:string, reference:string, gateway_ref:?string} */
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

        return $res->json('data') + ['gateway_ref' => $res->json('data.access_code')];
    }

    // ---------------------------------------------------------------- saved cards

    public function saveCardOptions(): array
    {
        return []; // Paystack returns a reusable authorization with every card payment.
    }

    /** Pure: the reusable card from a verified Paystack transaction (data.authorization). */
    public function savedCard(array $verifiedRaw): ?array
    {
        $a = (array) ($verifiedRaw['authorization'] ?? []);
        if (empty($a['authorization_code']) || empty($a['reusable']) || ($a['channel'] ?? 'card') !== 'card') {
            return null;
        }

        return ['token' => (string) $a['authorization_code'], 'customer_ref' => $verifiedRaw['customer']['customer_code'] ?? null,
            'email' => $verifiedRaw['customer']['email'] ?? null, 'brand' => isset($a['brand']) ? ucfirst(trim((string) $a['brand'])) : null,
            'last4' => $a['last4'] ?? null, 'exp_month' => isset($a['exp_month']) ? (int) $a['exp_month'] : null,
            'exp_year' => isset($a['exp_year']) ? (int) $a['exp_year'] : null, 'fingerprint' => $a['signature'] ?? null];
    }

    public function chargeSaved(array $card, int $amount, string $reference, string $email): array
    {
        $res = Http::withToken($this->secret())->acceptJson()->timeout(30)->post('https://api.paystack.co/transaction/charge_authorization', [
            // The authorization belongs to the email it was made with.
            'email' => $card['email'] ?: $email, 'amount' => $amount, 'authorization_code' => $card['token'], 'reference' => $reference, 'currency' => \App\Support\Money::code(),
        ]);
        if ($res->serverError()) {
            return ['status' => 'pending', 'amount' => 0, 'currency' => 'NGN', 'raw' => [], 'message' => null];
        }
        $d = (array) $res->json('data');
        $st = (string) ($d['status'] ?? '');
        $state = $st === 'success' ? 'success' : (in_array($st, ['failed', 'abandoned', 'reversed'], true) || ! $res->json('status') ? 'failed' : 'pending');

        return ['status' => $state, 'amount' => (int) ($d['amount'] ?? 0), 'currency' => (string) ($d['currency'] ?? 'NGN'), 'raw' => $d,
            'message' => $state === 'failed' ? (string) ($d['gateway_response'] ?? $res->json('message') ?? 'The card was declined.') : null];
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
    public function refund(string $transactionReference, int $amountKobo, ?array $intentRaw = null): string
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
