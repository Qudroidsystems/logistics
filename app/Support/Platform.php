<?php

namespace App\Support;

/** Which way this install runs: one company with its own fleet, a marketplace of providers, or both. */
class Platform
{
    public const COMPANY = 'company';
    public const MARKETPLACE = 'marketplace';
    public const BOTH = 'both';

    public static function mode(): string
    {
        $mode = (string) config('platform.mode', self::BOTH);

        return in_array($mode, [self::COMPANY, self::MARKETPLACE, self::BOTH], true) ? $mode : self::BOTH;
    }

    /** Outside providers can sign up, be listed and receive customer requests. */
    public static function marketplace(): bool
    {
        return self::mode() !== self::COMPANY;
    }

    /** Single-company install: every request goes to the house operator. */
    public static function companyOnly(): bool
    {
        return self::mode() === self::COMPANY;
    }

    public static function houseOperatorId(): int
    {
        $id = (int) config('platform.house_operator_id');
        if ($id < 1) {
            throw new \RuntimeException('PLATFORM_MODE is "company" but PLATFORM_HOUSE_OPERATOR_ID is not set.');
        }

        return $id;
    }

    /** What the mobile apps need to know to show or hide marketplace screens. */
    public static function forApp(): array
    {
        return ['mode' => self::mode(), 'marketplace' => self::marketplace()];
    }
}
