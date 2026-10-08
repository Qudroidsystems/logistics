<?php

namespace App\Modules\Marketplace;

use Illuminate\Support\Facades\DB;

/**
 * The rules for a delivery that fails because of the receiver (not reachable, refuses the parcel, wrong address):
 *
 *   customer_fee_bp   share of the delivery price the customer still pays (basis points; 5000 = 50%)
 *   return_to_sender  whether the driver brings the parcel back to the pickup address
 *   return_fee_bp     extra share of the price kept for the return trip (only when the parcel is returned)
 *   driver_paid       whether the driver still gets their usual share of what the company earns on a failed trip
 *   wait_minutes      how long the driver must wait at the drop-off before they can report the failure
 *
 * Staff set the defaults in Fees and settings. The values are copied onto each agreement when it is created
 * (agreements.failed_delivery_policy), shown to the customer before they pay, and never change afterwards.
 */
class FailedDeliveryPolicy
{
    public const DEFAULTS = [
        'customer_fee_bp' => 5000,
        'return_to_sender' => true,
        'return_fee_bp' => 2000,
        'driver_paid' => true,
        'wait_minutes' => 10,
    ];

    /** setting key for each policy field */
    public const KEYS = [
        'customer_fee_bp' => 'failed.customer_fee_bp',
        'return_to_sender' => 'failed.return_to_sender',
        'return_fee_bp' => 'failed.return_fee_bp',
        'driver_paid' => 'failed.driver_paid',
        'wait_minutes' => 'failed.wait_minutes',
    ];

    /** The platform's current defaults: saved settings over the built-in values. */
    public static function current(): array
    {
        $saved = [];
        $rows = DB::table('platform_settings')->whereNull('operator_id')->where('scope', 'global')->whereIn('key', array_values(self::KEYS))->pluck('value', 'key');
        foreach (self::KEYS as $field => $key) {
            if (isset($rows[$key])) {
                $saved[$field] = json_decode($rows[$key], true);
            }
        }

        return self::normalize($saved);
    }

    /** Pure: fills gaps with defaults and clamps every field to a sane range. */
    public static function normalize(?array $p): array
    {
        $p = ($p ?? []) + self::DEFAULTS;

        return [
            'customer_fee_bp' => max(0, min(10000, (int) $p['customer_fee_bp'])),
            'return_to_sender' => (bool) $p['return_to_sender'],
            'return_fee_bp' => max(0, min(10000, (int) $p['return_fee_bp'])),
            'driver_paid' => (bool) $p['driver_paid'],
            'wait_minutes' => max(0, min(120, (int) $p['wait_minutes'])),
        ];
    }

    /** Pure: how much of the delivery price (kobo) is kept when delivery fails because of the receiver. Never more than the price. */
    public static function charge(int $price, ?array $policy): int
    {
        $p = self::normalize($policy);
        $bp = min(10000, $p['customer_fee_bp'] + ($p['return_to_sender'] ? $p['return_fee_bp'] : 0));

        return intdiv(max($price, 0) * $bp, 10000);
    }

    /** Pure: the policy in plain sentences, for the page the customer reads before paying. @return string[] */
    public static function describe(?array $policy): array
    {
        $p = self::normalize($policy);
        $pct = fn (int $bp) => rtrim(rtrim(number_format($bp / 100, 2, '.', ''), '0'), '.').'%';
        $lines = ["If the receiver cannot be reached, refuses the parcel or the address is wrong, the driver waits {$p['wait_minutes']} minutes before giving up."];
        $total = $p['customer_fee_bp'] + ($p['return_to_sender'] ? $p['return_fee_bp'] : 0);
        $keep = $pct($p['customer_fee_bp']).' of the delivery price';
        if ($p['return_to_sender'] && $p['return_fee_bp'] > 0) {
            $keep .= ', plus '.$pct($p['return_fee_bp']).' of it for bringing the parcel back';
        }
        $lines[] = $total > 0
            ? "You then still pay {$keep}. Everything else you paid, including any tip and unspent goods money, goes back to your wallet."
            : 'You are not charged, and everything you paid goes back to your wallet.';
        $lines[] = $p['return_to_sender']
            ? 'The driver brings the parcel back to the pickup address.'
            : 'The parcel is not brought back automatically; the provider will contact the sender.';
        $lines[] = $p['driver_paid'] ? 'The driver is still paid their share for the trip.' : 'The driver is not paid for a failed trip.';

        return $lines;
    }
}
