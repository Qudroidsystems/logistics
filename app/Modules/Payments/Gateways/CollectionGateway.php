<?php

namespace App\Modules\Payments\Gateways;

/**
 * A gateway that takes money from a customer (hosted checkout). Payouts stay on Paystack transfers.
 * Amounts are always kobo. verify() is the source of truth: webhooks only tell us WHICH payment to check.
 */
interface CollectionGateway
{
    public function key(): string;

    public function label(): string;

    /** Switched on in Finance > Payment Gateways, with a complete set of keys for the current mode. */
    public function isReady(): bool;

    /** @return array{authorization_url:string, gateway_ref:?string} */
    public function initialize(string $email, int $amountKobo, string $reference, string $callbackUrl, array $meta = []): array;

    /**
     * Asks the gateway what really happened to a payment.
     *
     * @param  array|null  $intentRaw  the payment_intents.raw json (holds gateway_ref saved at initialize)
     * @return array{status:string, amount:int, currency:string, channel:?string, raw:array} status: success | failed | pending
     */
    public function verify(string $reference, ?array $intentRaw = null): array;

    public function supportsRefund(): bool;

    /** @return string the gateway's refund id */
    public function refund(string $reference, int $amountKobo, ?array $intentRaw = null): string;
}
