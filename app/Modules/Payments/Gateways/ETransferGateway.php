<?php

namespace App\Modules\Payments\Gateways;

use App\Modules\Payments\Gateways\Concerns\UsesAdminCredentials;
use RuntimeException;

/**
 * Interac e-Transfer, the way most Canadian customers pay a small business. There is no API to call: the customer is
 * shown where to send the money and the reference to put in the message, and staff mark the payment received
 * (ETransferService::confirm). verify() reports what staff recorded, so the rest of the payment code treats an
 * e-Transfer like any other gateway.
 *
 * Settings live in Finance > Payment Gateways ("Interac e-Transfer").
 */
class ETransferGateway implements CollectionGateway
{
    use UsesAdminCredentials;

    public const KEY = 'etransfer';

    public const DEFAULT_HOLD_HOURS = 48;

    public function key(): string
    {
        return self::KEY;
    }

    public function label(): string
    {
        return 'Interac e-Transfer';
    }

    public function isReady(): bool
    {
        return $this->adminReady();
    }

    /** The "checkout page" is our own instructions page for this payment; the customer also gets the details by email. */
    public function initialize(string $email, int $amountKobo, string $reference, string $callbackUrl, array $meta = []): array
    {
        app(\App\Modules\Payments\ETransferService::class)->opened($reference);

        return ['authorization_url' => route('etransfer.show', $reference), 'gateway_ref' => null];
    }

    /** What staff recorded: success once confirmed, failed once rejected, otherwise still waiting. */
    public function verify(string $reference, ?array $intentRaw = null): array
    {
        $e = \Illuminate\Support\Facades\DB::table('etransfer_payments as e')->join('payment_intents as i', 'i.id', '=', 'e.payment_intent_id')
            ->where('i.reference', $reference)->first(['e.confirmed_at', 'e.rejected_at', 'e.received_amount', 'i.currency']);
        if ($e?->confirmed_at) {
            return ['status' => 'success', 'amount' => (int) $e->received_amount, 'currency' => (string) $e->currency, 'channel' => 'etransfer', 'raw' => []];
        }
        if ($e?->rejected_at) {
            return ['status' => 'failed', 'amount' => 0, 'currency' => \App\Support\Money::code(), 'channel' => 'etransfer', 'raw' => []];
        }

        return ['status' => 'pending', 'amount' => 0, 'currency' => \App\Support\Money::code(), 'channel' => null, 'raw' => []];
    }

    public function supportsRefund(): bool
    {
        return false;
    }

    public function refund(string $reference, int $amountKobo, ?array $intentRaw = null): string
    {
        throw new RuntimeException('e-Transfer payments are refunded to the customer\'s wallet, or by sending an e-Transfer back yourself.');
    }

    /** @return array{email:string, name:string, question:?string, answer:?string, hold_hours:int} */
    public function instructions(): array
    {
        $hours = (int) ($this->credential('hold_hours') ?: self::DEFAULT_HOLD_HOURS);

        return [
            'email' => (string) $this->credential('recipient_email'), 'name' => (string) $this->credential('recipient_name'),
            'question' => $this->credential('security_question') ?: null, 'answer' => $this->credential('security_answer') ?: null,
            'hold_hours' => max(1, min(24 * 14, $hours)),
        ];
    }
}
