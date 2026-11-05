<?php

namespace App\Modules\Payments\Gateways;

/**
 * A gateway that can keep a card after a payment and charge it later without the customer present
 * (plan renewals). Amounts are minor units.
 */
interface SavesCards
{
    /** Extra fields for initialize() so the card is kept after this payment. */
    public function saveCardOptions(): array;

    /**
     * The reusable card from a verified payment, or null when there is none (bank transfer, a card that can't be reused).
     *
     * @param  array  $verifiedRaw  verify()['raw'] merged with the intent's raw
     * @return array{token:string, customer_ref:?string, email:?string, brand:?string, last4:?string, exp_month:?int, exp_year:?int, fingerprint:?string}|null
     */
    public function savedCard(array $verifiedRaw): ?array;

    /**
     * Charges a saved card now. Never throws for a decline: returns status failed with the gateway's reason.
     * Throws only when the gateway could not be reached, so the caller can leave the payment to be reconciled.
     *
     * @return array{status:string, amount:int, currency:string, raw:array, message:?string} status: success | failed | pending
     */
    public function chargeSaved(array $card, int $amount, string $reference, string $email): array;
}
