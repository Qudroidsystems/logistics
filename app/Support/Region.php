<?php

namespace App\Support;

/** Lookups on config/region.php: provinces/states and the default sales tax for a city. */
final class Region
{
    /** @return array<string,string> code => name */
    public static function subdivisions(): array
    {
        return (array) config('region.subdivisions', []);
    }

    /** The code for a province/state given as its code or name ("ON", "Ontario"), or null. */
    public static function subdivisionCode(?string $value): ?string
    {
        $v = trim((string) $value);
        if ($v === '') {
            return null;
        }
        foreach (self::subdivisions() as $code => $name) {
            if (strcasecmp($v, $code) === 0 || strcasecmp($v, $name) === 0) {
                return $code;
            }
        }

        return null;
    }

    /** @return array{name:string, rate_bp:int} the default tax for a city in this province/state */
    public static function taxFor(?string $subdivision): array
    {
        $tax = (array) config('region.tax', ['name' => 'Tax', 'rate_bp' => 0, 'by_subdivision' => []]);
        $code = self::subdivisionCode($subdivision);
        $own = $code ? ($tax['by_subdivision'][$code] ?? null) : null;

        return ['name' => (string) ($own['name'] ?? $tax['name'] ?? 'Tax'), 'rate_bp' => (int) ($own['rate_bp'] ?? $tax['rate_bp'] ?? 0)];
    }
}
