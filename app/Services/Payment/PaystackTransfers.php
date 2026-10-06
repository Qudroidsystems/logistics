<?php

namespace App\Services\Payment;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Paystack Transfers (paying money out to Nigerian bank accounts).
 * Uses the same keys and test/live mode as school-fee checkout.
 * Account numbers are passed in and never logged.
 */
class PaystackTransfers extends PaystackGateway
{
    protected function api()
    {
        return Http::withToken((string) $this->secretKey)->acceptJson()->timeout(45);
    }

    protected function fail(string $what, \Throwable|string $e): array
    {
        $msg = $e instanceof \Throwable ? $e->getMessage() : $e;
        Log::warning("Paystack {$what} failed", ['error' => mb_substr($msg, 0, 300)]);
        return ['ok' => false, 'message' => $e instanceof \Throwable ? 'The payment service could not be reached.' : $msg];
    }

    /** Available NGN balance in naira (null if unknown). */
    public function balance(): ?float
    {
        if (!$this->secretKey) return null;
        try {
            $res = $this->api()->get(self::BASE_URL . '/balance');
            if ($res->successful() && $res->json('status') === true) {
                $ngn = collect($res->json('data'))->firstWhere('currency', 'NGN');
                return $ngn ? round(((int) $ngn['balance']) / 100, 2) : 0.0;
            }
        } catch (\Throwable $e) {
            $this->fail('balance', $e);
        }
        return null;
    }

    /** Create (or reuse) a transfer recipient. Returns ['ok', 'code'|'message']. */
    public function createRecipient(string $name, string $accountNumber, string $bankCode): array
    {
        if (!$this->secretKey) return ['ok' => false, 'message' => 'Paystack is not configured.'];
        try {
            $res = $this->api()->post(self::BASE_URL . '/transferrecipient', [
                'type' => 'nuban', 'name' => mb_substr($name, 0, 100), 'account_number' => $accountNumber,
                'bank_code' => $bankCode, 'currency' => 'NGN',
            ]);
            if ($res->successful() && $res->json('status') === true) {
                return ['ok' => true, 'code' => (string) $res->json('data.recipient_code')];
            }
            return $this->fail('recipient', (string) ($res->json('message') ?: 'Could not register this bank account.'));
        } catch (\Throwable $e) {
            return $this->fail('recipient', $e);
        }
    }

    /**
     * Bulk transfer (max 100 per call). $transfers: [['amount_kobo','recipient','reference','reason']].
     * Returns ['ok', 'results' => [reference => ['status','transfer_code']], 'message'].
     */
    public function bulk(array $transfers): array
    {
        if (!$this->secretKey) return ['ok' => false, 'message' => 'Paystack is not configured.'];
        try {
            $res = $this->api()->post(self::BASE_URL . '/transfer/bulk', [
                'currency' => 'NGN', 'source' => 'balance',
                'transfers' => array_map(fn ($t) => [
                    'amount' => (int) $t['amount_kobo'], 'recipient' => $t['recipient'],
                    'reference' => $t['reference'], 'reason' => mb_substr($t['reason'], 0, 100),
                ], $transfers),
            ]);
            if ($res->successful() && $res->json('status') === true) {
                $out = [];
                foreach ((array) $res->json('data') as $d) {
                    $out[(string) ($d['reference'] ?? '')] = ['status' => (string) ($d['status'] ?? 'pending'), 'transfer_code' => $d['transfer_code'] ?? null];
                }
                return ['ok' => true, 'results' => $out];
            }
            return $this->fail('bulk transfer', (string) ($res->json('message') ?: 'Paystack refused the transfer.'));
        } catch (\Throwable $e) {
            return $this->fail('bulk transfer', $e);
        }
    }

    /** Single transfer. Returns ['ok', 'status', 'transfer_code'] — status may be "otp". */
    public function single(int $amountKobo, string $recipient, string $reference, string $reason): array
    {
        if (!$this->secretKey) return ['ok' => false, 'message' => 'Paystack is not configured.'];
        try {
            $res = $this->api()->post(self::BASE_URL . '/transfer', [
                'source' => 'balance', 'amount' => $amountKobo, 'recipient' => $recipient,
                'reference' => $reference, 'reason' => mb_substr($reason, 0, 100), 'currency' => 'NGN',
            ]);
            if ($res->successful() && $res->json('status') === true) {
                return ['ok' => true, 'status' => (string) $res->json('data.status'), 'transfer_code' => $res->json('data.transfer_code')];
            }
            return $this->fail('transfer', (string) ($res->json('message') ?: 'Paystack refused the transfer.'));
        } catch (\Throwable $e) {
            return $this->fail('transfer', $e);
        }
    }

    /** Complete a transfer that needs an OTP (sent by Paystack to the business owner's phone). */
    public function finalize(string $transferCode, string $otp): array
    {
        try {
            $res = $this->api()->post(self::BASE_URL . '/transfer/finalize_transfer', ['transfer_code' => $transferCode, 'otp' => $otp]);
            if ($res->successful() && $res->json('status') === true) {
                return ['ok' => true, 'status' => (string) $res->json('data.status')];
            }
            return $this->fail('finalize', (string) ($res->json('message') ?: 'OTP was not accepted.'));
        } catch (\Throwable $e) {
            return $this->fail('finalize', $e);
        }
    }

    /** Current state of a transfer by our reference. */
    public function verifyTransfer(string $reference): array
    {
        try {
            $res = $this->api()->get(self::BASE_URL . '/transfer/verify/' . urlencode($reference));
            if ($res->successful() && $res->json('status') === true) {
                return ['ok' => true, 'status' => (string) $res->json('data.status'), 'transfer_code' => $res->json('data.transfer_code'),
                        'reason' => $res->json('data.gateway_response') ?? $res->json('data.reason')];
            }
            return $this->fail('verify transfer', (string) ($res->json('message') ?: 'Transfer not found.'));
        } catch (\Throwable $e) {
            return $this->fail('verify transfer', $e);
        }
    }
}
