<?php

namespace App\Services\Payment;

use App\Models\PaymentGateway;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Thin Paystack client for school-fee checkouts.
 *
 * Keys come from Finance › Payment Gateways (payment_gateways row with
 * provider_key "paystack": live keys in secret_key/public_key, test keys in
 * config.test_secret_key / config.test_public_key), falling back to
 * PAYSTACK_SECRET_KEY / PAYSTACK_PUBLIC_KEY in .env.
 */
class PaystackGateway
{
    protected const BASE_URL = 'https://api.paystack.co';

    protected ?string $secretKey = null;
    protected ?string $publicKey = null;
    protected string $mode = 'sandbox';
    protected bool $active = false;

    public function __construct()
    {
        $gw     = PaymentGateway::where('provider_key', 'paystack')->first();
        $env    = (array) config('services.paystack', []);

        $this->mode = ($gw->mode ?? ($env['mode'] ?? 'sandbox')) === 'live' ? 'live' : 'sandbox';

        // Keys entered by the admin (Finance › Payment Gateways) come first;
        // .env is only a fallback for servers that have no saved keys yet.
        $set = $this->mode === 'live' ? 'live' : 'test';
        $this->secretKey = $gw ? $gw->credential('secret_key', $set) : null;
        $this->publicKey = $gw ? $gw->credential('public_key', $set) : null;

        if (!$this->secretKey) {
            $secrets = $set === 'live'
                ? [$env['live_secret_key'] ?? null, $env['secret_key'] ?? null]
                : [$env['test_secret_key'] ?? null, $env['secret_key'] ?? null];
            $publics = $set === 'live'
                ? [$env['live_public_key'] ?? null, $env['public_key'] ?? null]
                : [$env['test_public_key'] ?? null, $env['public_key'] ?? null];
            $this->secretKey = $this->firstRealKey($secrets, 'sk_' . $set . '_');
            $this->publicKey = $this->firstRealKey($publics, 'pk_' . $set . '_');
        }

        // Active when the admin switched it on, or (no gateway row) when .env says so.
        $this->active = $gw ? (bool) $gw->is_active : filter_var($env['active'] ?? true, FILTER_VALIDATE_BOOLEAN);
    }

    protected function firstRealKey(array $candidates, string $prefix): ?string
    {
        foreach ($candidates as $key) {
            $key = trim((string) $key);
            if (str_starts_with($key, $prefix) && strlen($key) >= 30 && !str_contains(strtolower($key), 'xxxx')) {
                return $key;
            }
        }
        return null;
    }

    /** Why the gateway can't be used (for admins), or null when it's ready. */
    public function key(): string { return 'paystack'; }
    public function name(): string { return 'Paystack'; }

    public function problem(): ?string
    {
        if (!$this->active) return 'Paystack is switched off in Payment Gateways.';
        if (!$this->secretKey) return 'No valid Paystack ' . ($this->mode === 'live' ? 'live (sk_live_…)' : 'test (sk_test_…)') . ' secret key has been saved in Payment Gateways.';
        return null;
    }

    public function isReady(): bool
    {
        return $this->active && !empty($this->secretKey);
    }

    public function mode(): string
    {
        return $this->mode;
    }

    /**
     * @return array{ok: bool, authorization_url?: string, access_code?: string, message?: string}
     */
    public function initialize(string $email, int $amountKobo, string $reference, string $callbackUrl, array $metadata = []): array
    {
        if (!$this->secretKey) {
            return ['ok' => false, 'message' => 'Online payment is not configured.'];
        }
        try {
            $res = Http::withToken($this->secretKey)->acceptJson()->timeout(30)
                ->post(self::BASE_URL . '/transaction/initialize', [
                    'email'        => $email,
                    'amount'       => $amountKobo,
                    'currency'     => 'NGN',
                    'reference'    => $reference,
                    'callback_url' => $callbackUrl,
                    'metadata'     => $metadata,
                ]);

            if ($res->successful() && $res->json('status') === true) {
                return [
                    'ok'                => true,
                    'authorization_url' => $res->json('data.authorization_url'),
                    'access_code'       => $res->json('data.access_code'),
                ];
            }

            Log::warning('Paystack initialize failed', ['reference' => $reference, 'body' => $res->json()]);
            return ['ok' => false, 'message' => $res->json('message') ?: 'Could not start the payment.'];
        } catch (\Throwable $e) {
            Log::error('Paystack initialize error', ['reference' => $reference, 'error' => $e->getMessage()]);
            return ['ok' => false, 'message' => 'The payment service could not be reached. Please try again.'];
        }
    }

    /**
     * @return array{ok: bool, data?: array, message?: string}  ok = the API answered (not that the payment succeeded)
     */
    public function verify(string $reference): array
    {
        if (!$this->secretKey) {
            return ['ok' => false, 'message' => 'Online payment is not configured.'];
        }
        try {
            $res = Http::withToken($this->secretKey)->acceptJson()->timeout(30)
                ->get(self::BASE_URL . '/transaction/verify/' . rawurlencode($reference));

            if ($res->successful() && $res->json('status') === true) {
                return ['ok' => true, 'data' => (array) $res->json('data')];
            }

            return ['ok' => false, 'message' => $res->json('message') ?: 'Verification failed.'];
        } catch (\Throwable $e) {
            Log::error('Paystack verify error', ['reference' => $reference, 'error' => $e->getMessage()]);
            return ['ok' => false, 'message' => 'The payment service could not be reached.'];
        }
    }

    /** Nigerian banks (name + code), cached for a day. */
    public function banks(): array
    {
        if (!$this->secretKey) return [];
        return \Illuminate\Support\Facades\Cache::remember('paystack_banks_ng', now()->addDay(), function () {
            try {
                $res = Http::withToken($this->secretKey)->acceptJson()->timeout(30)
                    ->get(self::BASE_URL . '/bank', ['country' => 'nigeria', 'perPage' => 200]);
                if ($res->successful() && $res->json('status') === true) {
                    return collect($res->json('data'))->where('active', true)
                        ->map(fn ($b) => ['code' => (string) $b['code'], 'name' => $b['name']])->unique('code')->sortBy('name')->values()->all();
                }
            } catch (\Throwable $e) {
                Log::warning('Paystack bank list failed', ['error' => $e->getMessage()]);
            }
            return [];
        });
    }

    /** Look up the account name for an account number (checks the account exists). */
    public function resolveAccount(string $accountNumber, string $bankCode): array
    {
        if (!$this->secretKey) return ['ok' => false, 'message' => 'Paystack is not configured.'];
        try {
            $res = Http::withToken($this->secretKey)->acceptJson()->timeout(30)
                ->get(self::BASE_URL . '/bank/resolve', ['account_number' => $accountNumber, 'bank_code' => $bankCode]);
            if ($res->successful() && $res->json('status') === true) {
                return ['ok' => true, 'account_name' => (string) $res->json('data.account_name')];
            }
            return ['ok' => false, 'message' => $res->json('message') ?: 'Could not verify this account.'];
        } catch (\Throwable $e) {
            Log::warning('Paystack resolve failed', ['error' => $e->getMessage()]);
            return ['ok' => false, 'message' => 'The payment service could not be reached.'];
        }
    }

    public function validSignature(string $payload, ?string $signature): bool
    {
        if (!$signature || !$this->secretKey) {
            return false;
        }
        return hash_equals(hash_hmac('sha512', $payload, $this->secretKey), $signature);
    }
}
