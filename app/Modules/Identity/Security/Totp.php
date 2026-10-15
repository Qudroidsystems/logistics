<?php

namespace App\Modules\Identity\Security;

/**
 * Time-based one-time passwords (RFC 6238), the 6-digit codes shown by Google Authenticator, Microsoft Authenticator,
 * Authy, 1Password and the like. SHA-1, 30-second steps, one step of clock drift allowed either side.
 */
class Totp
{
    public const PERIOD = 30;

    public const DIGITS = 6;

    private const ALPHABET = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

    /** A new random secret, base32 (160 bits, as the RFC recommends). */
    public static function newSecret(): string
    {
        return self::base32Encode(random_bytes(20));
    }

    /** The code for a given time step. */
    public static function at(string $secret, int $step): string
    {
        $hash = hash_hmac('sha1', pack('J', $step), self::base32Decode($secret), true);
        $offset = ord($hash[19]) & 0x0F;
        $value = ((ord($hash[$offset]) & 0x7F) << 24) | (ord($hash[$offset + 1]) << 16) | (ord($hash[$offset + 2]) << 8) | ord($hash[$offset + 3]);

        return str_pad((string) ($value % 10 ** self::DIGITS), self::DIGITS, '0', STR_PAD_LEFT);
    }

    public static function step(?int $time = null): int
    {
        return intdiv($time ?? time(), self::PERIOD);
    }

    /**
     * The matching time step, or null. Steps at or before $afterStep are refused, so a code that was already used
     * (or an older one) cannot be replayed.
     */
    public static function verify(string $secret, string $code, ?int $afterStep = null, int $window = 1, ?int $time = null): ?int
    {
        $code = preg_replace('/\D/', '', $code);
        if (strlen($code) !== self::DIGITS) {
            return null;
        }
        $now = self::step($time);
        for ($s = $now - $window; $s <= $now + $window; $s++) {
            if ($afterStep !== null && $s <= $afterStep) {
                continue;
            }
            if (hash_equals(self::at($secret, $s), $code)) {
                return $s;
            }
        }

        return null;
    }

    /** otpauth:// link the authenticator app reads from the QR code. */
    public static function uri(string $secret, string $account, string $issuer): string
    {
        $label = rawurlencode($issuer).':'.rawurlencode($account);

        return 'otpauth://totp/'.$label.'?'.http_build_query(['secret' => $secret, 'issuer' => $issuer, 'algorithm' => 'SHA1', 'digits' => self::DIGITS, 'period' => self::PERIOD], '', '&', PHP_QUERY_RFC3986);
    }

    public static function base32Encode(string $bytes): string
    {
        $bits = '';
        foreach (str_split($bytes) as $c) {
            $bits .= str_pad(decbin(ord($c)), 8, '0', STR_PAD_LEFT);
        }
        $out = '';
        foreach (str_split($bits, 5) as $chunk) {
            $out .= self::ALPHABET[bindec(str_pad($chunk, 5, '0'))];
        }

        return $out;
    }

    public static function base32Decode(string $text): string
    {
        $text = strtoupper(preg_replace('/[\s=-]/', '', $text));
        $bits = '';
        foreach (str_split($text) as $c) {
            $i = strpos(self::ALPHABET, $c);
            if ($i === false) {
                continue;
            }
            $bits .= str_pad(decbin($i), 5, '0', STR_PAD_LEFT);
        }
        $out = '';
        foreach (str_split($bits, 8) as $byte) {
            if (strlen($byte) === 8) {
                $out .= chr(bindec($byte));
            }
        }

        return $out;
    }
}
