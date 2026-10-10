<?php

namespace App\Modules\Tracking;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Identity for every parcel: a short human-readable code printed on the label and read out over the phone,
 * and an opaque QR payload that carries no personal data. Each package of a multi-parcel job gets its own pair.
 *
 * Codes use Crockford base32 (no I, L, O or U) and end in a check character, so a code typed by hand
 * with one wrong character is caught before it is looked up.
 */
class ParcelCodes
{
    private const ALPHABET = '0123456789ABCDEFGHJKMNPQRSTVWXYZ';
    public const QR_PREFIX = 'QDP1.';

    /** A random code like PK7KX9M2PA3, made of a prefix, eight characters and a check character. */
    public static function make(string $prefix = 'PK', int $length = 8): string
    {
        $body = '';
        for ($i = 0; $i < $length; $i++) {
            $body .= self::ALPHABET[random_int(0, 31)];
        }

        return $prefix.$body.self::check($body);
    }

    /** Mod-31 weighted check character over the body. Pure, unit tested. */
    public static function check(string $body): string
    {
        $sum = 0;
        foreach (str_split(strtoupper($body)) as $i => $c) {
            $sum += (strpos(self::ALPHABET, $c) ?: 0) * ($i + 1);
        }

        return self::ALPHABET[$sum % 31];
    }

    /** Normalises what was typed or scanned and says whether a human code's check character agrees. */
    public static function valid(string $code, string $prefix = 'PK'): bool
    {
        $c = self::normalise($code);
        if (! str_starts_with($c, $prefix) || strlen($c) < strlen($prefix) + 2) {
            return false;
        }
        $body = substr($c, strlen($prefix), -1);

        return self::check($body) === substr($c, -1);
    }

    /** Upper-cases, strips spaces and dashes, and maps the look-alikes people type (O to 0, I and L to 1). */
    public static function normalise(string $code): string
    {
        $c = strtoupper(preg_replace('/[\s\-]/', '', $code));

        return $c === '' ? '' : substr($c, 0, 2).strtr(substr($c, 2), ['O' => '0', 'I' => '1', 'L' => '1']);
    }

    public static function qrPayload(): string
    {
        return self::QR_PREFIX.Str::random(32);
    }

    /**
     * Creates the package rows for a new shipment, one per parcel, each with its own code.
     *
     * @param  array<int, array{description?:string, weight_g?:int, declared_value?:int, fragile?:bool, quantity?:int}|string>  $items
     */
    public function createPackages(int $shipmentId, array $items, ?int $pickupStopId, ?int $dropoffStopId): array
    {
        $items = array_values(array_filter($items, fn ($i) => is_string($i) ? trim($i) !== '' : is_array($i)));
        if (! $items) {
            $items = [['description' => 'Parcel']];
        }

        $codes = [];
        foreach ($items as $n => $item) {
            $item = is_string($item) ? ['description' => $item] : $item;
            $barcode = $this->unique('barcode', fn () => self::make());
            DB::table('packages')->insert([
                'public_id' => (string) Str::ulid(), 'shipment_id' => $shipmentId, 'seq' => $n + 1,
                'pickup_stop_id' => $pickupStopId, 'dropoff_stop_id' => $dropoffStopId,
                'description' => Str::limit((string) ($item['description'] ?? 'Parcel'), 250, ''),
                'weight_g' => isset($item['weight_g']) ? (int) $item['weight_g'] : null,
                'declared_value' => (int) ($item['declared_value'] ?? 0), 'fragile' => (bool) ($item['fragile'] ?? false),
                'quantity' => max(1, (int) ($item['quantity'] ?? 1)),
                'barcode' => $barcode, 'qr_payload' => $this->unique('qr_payload', fn () => self::qrPayload()),
                'status' => 'created', 'created_at' => now(), 'updated_at' => now(),
            ]);
            $codes[] = $barcode;
        }

        return $codes;
    }

    /** The package a scanned QR payload or typed code points at, or null. */
    public function find(string $scanned): ?object
    {
        $raw = trim($scanned);
        if (str_starts_with($raw, self::QR_PREFIX)) {
            return DB::table('packages')->where('qr_payload', $raw)->first();
        }
        $code = self::normalise($raw);

        return self::valid($code) ? DB::table('packages')->where('barcode', $code)->first() : null;
    }

    /** What any party to the job may see of its parcels. */
    public function forShipment(int $shipmentId, bool $withQr = false): array
    {
        $cols = ['public_id', 'seq', 'description', 'quantity', 'fragile', 'weight_g', 'barcode', 'seal_number', 'status'];

        return DB::table('packages')->where('shipment_id', $shipmentId)->orderBy('seq')->get($withQr ? [...$cols, 'qr_payload'] : $cols)
            ->map(fn ($p) => (array) $p)->all();
    }

    private function unique(string $column, callable $make): string
    {
        do {
            $value = $make();
        } while (DB::table('packages')->where($column, $value)->exists());

        return $value;
    }
}
