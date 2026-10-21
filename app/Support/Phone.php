<?php

namespace App\Support;

/**
 * Phone numbers for the install's country (config/region.php), and anyone abroad who types the "+" form.
 *
 *   international()  digits with the country code and no "+" ("12263398404", "2348031234567"): what SMS, WhatsApp
 *                    and call providers are given
 *   store()          the form kept on users.phone_number and used to find an account at sign-in: local ("08031234567")
 *                    for Nigerian installs, which always stored them that way, "+12263398404" everywhere else
 *
 * Canada and the US share +1 (NANP): a 10-digit number whose area code does not start with 0 or 1.
 */
final class Phone
{
    /** @return string|null digits with country code, or null when it cannot be a phone number */
    public static function international(?string $raw, ?string $callingCode = null, ?string $trunk = null): ?string
    {
        $raw = trim((string) $raw);
        if ($raw === '') {
            return null;
        }
        $cc = $callingCode ?? (string) config('region.calling_code', '234');
        $trunk ??= (string) config('region.trunk_prefix', '0');
        $plus = str_starts_with($raw, '+') || str_starts_with(preg_replace('/[\s().-]+/', '', $raw), '00');
        $d = preg_replace('/\D+/', '', $raw);
        if ($plus && str_starts_with($d, '00')) {
            $d = substr($d, 2);
        }
        if ($d === '') {
            return null;
        }
        // Written with "+": already international.
        if ($plus) {
            return strlen($d) >= 8 && strlen($d) <= 15 ? $d : null;
        }
        // Nigeria: 0803..., 803..., 234803...
        if (str_starts_with($d, '234') && strlen($d) === 13) {
            return $d;
        }
        if ($cc === '234') {
            if (str_starts_with($d, '0') && strlen($d) === 11) {
                return '234'.substr($d, 1);
            }
            if (strlen($d) === 10 && in_array($d[0], ['7', '8', '9'], true)) {
                return '234'.$d;
            }
        }
        // North America (+1): 226 339 8404, 1 226 339 8404.
        if ($cc === '1') {
            if (strlen($d) === 10 && ! in_array($d[0], ['0', '1'], true)) {
                return '1'.$d;
            }
            if (strlen($d) === 11 && $d[0] === '1' && ! in_array($d[1], ['0', '1'], true)) {
                return $d;
            }
        }
        // Any other country with a trunk prefix: 0 + national number.
        if ($trunk !== '' && str_starts_with($d, $trunk) && strlen($d) >= 9 && strlen($d) <= 12 && $cc !== '234') {
            return $cc.substr($d, strlen($trunk));
        }
        // Already starts with the country code (North America is fully covered above).
        if ($cc !== '1' && str_starts_with($d, $cc) && strlen($d) >= 10 && strlen($d) <= 15) {
            return $d;
        }

        return null;
    }

    /** The stored form, or null when it is not a phone number. */
    public static function store(?string $raw, ?bool $local = null, ?string $callingCode = null): ?string
    {
        $cc = $callingCode ?? (string) config('region.calling_code', '234');
        $intl = self::international($raw, $cc);
        if ($intl === null) {
            return null;
        }
        $local ??= (bool) config('region.store_local', true);
        if ($local && $cc === '234' && str_starts_with($intl, '234') && strlen($intl) === 13) {
            return '0'.substr($intl, 3);
        }

        return '+'.$intl;
    }

    /** "+1 (226) 339-8404" / "0803 123 4567" for screens. */
    public static function display(?string $stored): string
    {
        $intl = self::international($stored);
        if ($intl === null) {
            return (string) $stored;
        }
        if (strlen($intl) === 11 && $intl[0] === '1') {
            return sprintf('+1 (%s) %s-%s', substr($intl, 1, 3), substr($intl, 4, 3), substr($intl, 7));
        }
        if (strlen($intl) === 13 && str_starts_with($intl, '234')) {
            $n = '0'.substr($intl, 3);

            return substr($n, 0, 4).' '.substr($n, 4, 3).' '.substr($n, 7);
        }

        return '+'.$intl;
    }
}
