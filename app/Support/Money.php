<?php

namespace App\Support;

/**
 * Showing money in the install's currency (config/region.php). Every amount in the database is an integer in the
 * minor unit (kobo, cents); this turns it into "$1,234.50" or "₦12,000". Use Money::format() in PHP and Blade
 * (the class is aliased as Money for views) instead of writing a currency symbol by hand.
 */
final class Money
{
    public static function symbol(): string
    {
        return (string) config('region.symbol', '₦');
    }

    public static function code(): string
    {
        return (string) config('region.currency', 'NGN');
    }

    /**
     * "$1,234.50" from 123450. Whole amounts drop the cents ("$1,200") unless $cents is true.
     * Negative amounts read "-$12".
     */
    public static function format(int|float|string|null $minor, bool $cents = false): string
    {
        $m = (int) round((float) ($minor ?? 0));
        $abs = abs($m);
        $decimals = $cents || $abs % 100 ? 2 : 0;

        return ($m < 0 ? '-' : '').self::symbol().number_format($abs / 100, $decimals);
    }

    /** Whole units only, rounded ("$1,235"), for dashboards and charts. */
    public static function whole(int|float|string|null $minor): string
    {
        $m = (int) round((float) ($minor ?? 0));

        return ($m < 0 ? '-' : '').self::symbol().number_format(abs($m) / 100);
    }

    /** Minor units from what a person typed in major units ("12.50" -> 1250); null when it is not a number. */
    public static function toMinor(int|float|string|null $major): ?int
    {
        $s = str_replace([',', ' ', self::symbol()], '', trim((string) $major));
        if ($s === '' || ! is_numeric($s)) {
            return null;
        }

        return (int) round(((float) $s) * 100);
    }

    /** What the apps and the browser need to show amounts the same way. */
    public static function forClient(): array
    {
        return ['currency' => self::code(), 'symbol' => self::symbol(), 'locale' => (string) config('region.locale', 'en-NG'), 'minor_units' => 100];
    }
}
