<?php

namespace App\Modules\Backoffice;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

/**
 * Many deliveries from one spreadsheet (CSV). The sheet is checked first and every problem shown against its line;
 * nothing is booked until the provider looks at the check and confirms. Each good row is then booked exactly as a
 * single manual booking is (ManualBookingService), so it gets the same agreement, payment, order and dispatch.
 *
 *   - settings chosen on the upload form (service, city, payment, the customer paying, the usual pickup) apply to every
 *     row; a row can give its own pickup or customer instead
 *   - a row's reference (the sender's own order number) books once per provider: a sheet sent twice is not doubled
 *   - rows that fail to book (no price list, an empty wallet) are reported and the rest still go through
 */
class BulkBookingService
{
    public const MAX_ROWS = 200;

    /** Template columns, in order. Only the drop-off address and its position are needed on every row. */
    public const COLUMNS = [
        'reference' => 'Your order number, so the same delivery is never booked twice',
        'recipient_name' => 'Who receives the parcel',
        'recipient_phone' => 'Their phone number',
        'dropoff_address' => 'Required, unless the parcel goes to a pickup point. Street address',
        'dropoff_lat' => 'Required, unless pickup_point is given. Latitude, like 6.5244',
        'dropoff_lng' => 'Required, unless pickup_point is given. Longitude, like 3.3792',
        'pickup_point' => 'The code of one of your pickup points, to leave the parcel there for the receiver to collect',
        'dropoff_landmark' => 'Nearby landmark',
        'instructions' => 'Notes for the rider',
        'items' => 'What is being sent; separate items with ;',
        'weight_kg' => 'Total weight in kg',
        'price_naira' => 'Price for this delivery; leave empty to use your price list',
        'cod_naira' => 'Cash on delivery: goods money the rider collects from the receiver, if any',
        'pickup_address' => 'Only when this row is picked up somewhere other than the usual pickup',
        'pickup_lat' => '',
        'pickup_lng' => '',
        'pickup_phone' => '',
        'customer_name' => 'Only when this row is for a different customer',
        'customer_phone' => '',
        'customer_email' => '',
    ];

    public function __construct(private ManualBookingService $bookings) {}

    /** A CSV with the headings and one example row. */
    public static function template(): string
    {
        $out = fopen('php://temp', 'w+');
        fputcsv($out, array_keys(self::COLUMNS));
        fputcsv($out, ['ORD-1001', 'Bisi Ade', '08030000002', '9 Hill St, Ikeja', '6.6018', '3.3515', '', 'Opposite the church', 'Call on arrival', 'Shoes; Belt', '2', '', '', '', '', '', '', '', '']);
        rewind($out);

        return stream_get_contents($out);
    }

    /**
     * Reads the sheet and saves the check. Nothing is booked yet.
     *
     * @param  array{service_type_id:int, city_id:int, payment:string, customer:array, pickup:array}  $defaults
     */
    public function check(int $operatorId, int $userId, string $csv, array $defaults, ?string $filename = null): string
    {
        $rows = self::parse($csv);
        if (! $rows) {
            throw new RuntimeException('The file has no deliveries in it. Use the template: one delivery per row under the headings.');
        }
        if (count($rows) > self::MAX_ROWS) {
            throw new RuntimeException('Up to '.self::MAX_ROWS.' deliveries at a time. Split the file and send the rest separately.');
        }

        $booked = DB::table('bulk_batch_rows')->where('operator_id', $operatorId)->where('status', 'booked')
            ->whereIn('reference', array_values(array_filter(array_map(fn ($r) => trim((string) ($r['cells']['reference'] ?? '')), $rows))))->pluck('reference')->all();
        $points = DB::table('pickup_points')->where('operator_id', $operatorId)->where('status', 'active')->pluck('code')->all();
        if ($defaults['payment'] === 'account') {
            // Billed to a business account: its billing contact is the customer unless the form or a row names one.
            $acc = app(\App\Modules\Billing\BusinessAccountService::class)->find($operatorId, (string) ($defaults['business_account'] ?? ''));
            if (! $acc || $acc->status !== 'active') {
                throw new RuntimeException('That business account cannot take bookings right now.');
            }
            if (empty($defaults['customer']['phone']) && empty($defaults['customer']['email'])) {
                $defaults['customer'] = array_filter(['name' => $acc->name, 'phone' => $acc->billing_phone, 'email' => $acc->billing_email]);
            }
        }
        $seen = [];
        $checked = [];
        foreach ($rows as $r) {
            $c = self::row($r['cells'], $defaults);
            if (isset($c['input']['dropoff']['pickup_point']) && ! in_array($c['input']['dropoff']['pickup_point'], $points, true)) {
                $c['errors'][] = "There is no active pickup point with the code {$c['input']['dropoff']['pickup_point']}.";
            }
            $ref = $c['input']['reference'];
            if ($ref !== null && isset($seen[$ref])) {
                $c['errors'][] = "Reference {$ref} is already on line {$seen[$ref]}.";
            } elseif ($ref !== null && in_array($ref, $booked, true)) {
                $c['errors'][] = "Reference {$ref} was booked before.";
            }
            if ($ref !== null) {
                $seen[$ref] ??= $r['line'];
            }
            $checked[] = ['line' => $r['line'], 'reference' => $ref] + $c;
        }

        $publicId = (string) Str::ulid();
        DB::transaction(function () use ($operatorId, $userId, $defaults, $filename, $checked, $publicId) {
            $ok = count(array_filter($checked, fn ($c) => ! $c['errors']));
            $batchId = DB::table('bulk_batches')->insertGetId([
                'public_id' => $publicId, 'operator_id' => $operatorId, 'created_by' => $userId, 'filename' => $filename ? mb_substr($filename, 0, 160) : null,
                'defaults' => json_encode($defaults), 'status' => 'checked', 'row_count' => count($checked), 'ok_count' => $ok, 'created_at' => now(), 'updated_at' => now(),
            ]);
            foreach (array_chunk($checked, 100) as $chunk) {
                DB::table('bulk_batch_rows')->insert(array_map(fn ($c) => [
                    'batch_id' => $batchId, 'operator_id' => $operatorId, 'line' => $c['line'], 'reference' => $c['reference'],
                    'input' => json_encode($c['input']), 'errors' => $c['errors'] ? json_encode($c['errors']) : null,
                    'status' => $c['errors'] ? 'invalid' : 'ready', 'created_at' => now(), 'updated_at' => now(),
                ], $chunk));
            }
        });

        return $publicId;
    }

    /**
     * Books every good row of a checked sheet. Safe to press twice: only the first press books.
     *
     * @return array{booked:int, failed:int}
     */
    public function book(int $operatorId, string $publicId, int $userId): array
    {
        $batch = DB::table('bulk_batches')->where(['public_id' => $publicId, 'operator_id' => $operatorId])->first();
        if (! $batch) {
            throw new RuntimeException('That upload was not found.');
        }
        $claimed = DB::table('bulk_batches')->where('id', $batch->id)->where('status', 'checked')->update(['status' => 'booking', 'updated_at' => now()]);
        if (! $claimed) {
            throw new RuntimeException($batch->status === 'discarded' ? 'That upload was discarded.' : 'That upload has already been booked.');
        }
        @set_time_limit(600);

        $defaults = json_decode($batch->defaults, true);
        $booked = $failed = 0;
        foreach (DB::table('bulk_batch_rows')->where('batch_id', $batch->id)->where('status', 'ready')->orderBy('line')->get() as $row) {
            $in = json_decode($row->input, true);
            try {
                $r = $this->bookings->book($operatorId, $in + ['payment' => $defaults['payment'], 'business_account' => $defaults['business_account'] ?? null, 'service_type_id' => $defaults['service_type_id'],
                    'city_id' => $defaults['city_id'], 'vehicle_type_id' => $defaults['vehicle_type_id'] ?? null, 'driver_user_id' => null], $userId);
                DB::table('bulk_batch_rows')->where('id', $row->id)->update(['status' => 'booked', 'message' => mb_substr($r['message'], 0, 255),
                    'agreement_id' => $r['agreement_id'], 'shipment_id' => $r['shipment_id'], 'updated_at' => now()]);
                $booked++;
            } catch (Throwable $e) {
                if (! $e instanceof RuntimeException) {
                    Log::warning("Bulk booking {$publicId} line {$row->line} failed: {$e->getMessage()}");
                }
                $msg = $e instanceof RuntimeException ? $e->getMessage() : 'This delivery could not be booked. Try it on its own from New booking.';
                DB::table('bulk_batch_rows')->where('id', $row->id)->update(['status' => 'failed', 'message' => mb_substr($msg, 0, 255), 'updated_at' => now()]);
                $failed++;
            }
        }
        DB::table('bulk_batches')->where('id', $batch->id)->update(['status' => 'booked', 'booked_count' => $booked, 'failed_count' => $failed, 'booked_at' => now(), 'updated_at' => now()]);

        return ['booked' => $booked, 'failed' => $failed];
    }

    public function discard(int $operatorId, string $publicId): void
    {
        $n = DB::table('bulk_batches')->where(['public_id' => $publicId, 'operator_id' => $operatorId, 'status' => 'checked'])->update(['status' => 'discarded', 'updated_at' => now()]);
        if (! $n) {
            throw new RuntimeException('Only an upload that has not been booked can be discarded.');
        }
    }

    // ---------------------------------------------------------------- pure helpers (unit tested)

    /**
     * The sheet's rows keyed by heading. Headings are matched without regard to case, spaces or dashes. Blank lines
     * are skipped; line numbers are the spreadsheet's, counting the heading row as line 1.
     *
     * @return array<int, array{line:int, cells:array<string,string>}>
     */
    public static function parse(string $csv): array
    {
        $csv = preg_replace('/^\xEF\xBB\xBF/', '', $csv);
        $f = fopen('php://temp', 'w+');
        fwrite($f, $csv);
        rewind($f);
        $head = fgetcsv($f, null, self::delimiter($csv));
        if (! $head) {
            return [];
        }
        $keys = array_map(fn ($h) => trim(preg_replace('/[\s\-]+/', '_', strtolower(trim((string) $h))), '_'), $head);
        $rows = [];
        $line = 1;
        while (($cells = fgetcsv($f, null, self::delimiter($csv))) !== false) {
            $line++;
            if (! array_filter($cells, fn ($c) => trim((string) $c) !== '')) {
                continue;
            }
            $row = [];
            foreach ($keys as $i => $k) {
                if ($k !== '') {
                    $row[$k] = trim((string) ($cells[$i] ?? ''));
                }
            }
            $rows[] = ['line' => $line, 'cells' => $row];
        }
        fclose($f);

        return $rows;
    }

    /**
     * One row turned into a booking, with what is wrong with it.
     *
     * @return array{input:array, errors:string[]}
     */
    public static function row(array $c, array $defaults): array
    {
        $get = fn (string $k) => ($v = trim((string) ($c[$k] ?? ''))) === '' ? null : $v;
        $errors = [];
        $num = function (?string $v, float $min, float $max, string $label) use (&$errors): ?float {
            if ($v === null) {
                return null;
            }
            $v = str_replace(',', '', $v);
            if (! is_numeric($v) || (float) $v < $min || (float) $v > $max) {
                $errors[] = "{$label} is not a valid number.";

                return null;
            }

            return (float) $v;
        };

        $drop = ['line1' => $get('dropoff_address'), 'lat' => $num($get('dropoff_lat'), -90, 90, 'Drop-off latitude'), 'lng' => $num($get('dropoff_lng'), -180, 180, 'Drop-off longitude'),
            'landmark' => $get('dropoff_landmark'), 'contact_name' => $get('recipient_name'), 'contact_phone' => $get('recipient_phone'), 'instructions' => $get('instructions')];
        if ($point = $get('pickup_point')) {
            // The point's own address is used when the row is booked (ManualBookingService); the receiver stays the contact.
            $drop['pickup_point'] = strtoupper($point);
        } else {
            if (! $drop['line1']) {
                $errors[] = 'The drop-off address is missing.';
            }
            if ($drop['lat'] === null || $drop['lng'] === null) {
                $errors[] = 'The drop-off position (latitude and longitude) is missing.';
            }
        }

        $ownPickup = $get('pickup_address') || $get('pickup_lat') || $get('pickup_lng');
        $pick = $ownPickup
            ? ['line1' => $get('pickup_address'), 'lat' => $num($get('pickup_lat'), -90, 90, 'Pickup latitude'), 'lng' => $num($get('pickup_lng'), -180, 180, 'Pickup longitude'), 'contact_phone' => $get('pickup_phone')]
            : ($defaults['pickup'] ?? []) + ['contact_phone' => $get('pickup_phone') ?? ($defaults['pickup']['contact_phone'] ?? null)];
        if (empty($pick['line1']) || ! isset($pick['lat'], $pick['lng'])) {
            $errors[] = $ownPickup ? 'The pickup needs an address, latitude and longitude.' : 'There is no pickup: give one on the form or in the row.';
        }

        $ownCustomer = $get('customer_phone') || $get('customer_email');
        $customer = $ownCustomer ? ['name' => $get('customer_name'), 'phone' => $get('customer_phone'), 'email' => $get('customer_email')] : ($defaults['customer'] ?? []);
        if (empty($customer['phone']) && empty($customer['email'])) {
            $errors[] = 'There is no customer: give one on the form or in the row.';
        }
        if (! empty($customer['email']) && ! filter_var($customer['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'The customer email is not valid.';
        }

        $weight = $num($get('weight_kg'), 0, 50000, 'Weight');
        $price = $num($get('price_naira'), 1, 100000000, 'Price');
        $cod = $num($get('cod_naira'), 0, 100000000, 'Cash on delivery');
        if ($cod > 0 && isset($drop['pickup_point'])) {
            $errors[] = 'Cash on delivery cannot go to a pickup point.';
        }
        $items = array_values(array_filter(array_map('trim', explode(';', (string) $get('items')))));
        $packages = array_map(fn ($t) => ['description' => mb_substr($t, 0, 255)], $items ?: ['Parcel']);
        $grams = $weight ? (int) round($weight * 1000) : 0;
        if ($grams > 0) {
            $each = intdiv($grams, count($packages));
            foreach ($packages as $i => $p) {
                $packages[$i]['weight_g'] = $i === 0 ? $grams - $each * (count($packages) - 1) : $each;
            }
        }
        $ref = $get('reference') ? mb_substr($get('reference'), 0, 80) : null;

        return ['input' => [
            'reference' => $ref, 'customer' => array_filter($customer, fn ($v) => $v !== null), 'pickup' => array_filter($pick, fn ($v) => $v !== null),
            'dropoff' => array_filter($drop, fn ($v) => $v !== null), 'packages' => $packages, 'weight_g' => $grams,
            'price' => $price !== null ? (int) round($price * 100) : null, 'cod_amount' => $cod ? (int) round($cod * 100) : 0, 'note' => $ref ? "Bulk booking, ref {$ref}" : 'Bulk booking',
        ], 'errors' => $errors];
    }

    /** Comma, or semicolon for spreadsheets saved with European settings. */
    private static function delimiter(string $csv): string
    {
        $first = strtok($csv, "\n") ?: '';

        return substr_count($first, ';') > substr_count($first, ',') ? ';' : ',';
    }
}
