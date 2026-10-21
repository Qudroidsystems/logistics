<?php

namespace App\Modules\Recurring;

use App\Modules\Booking\InstantQuoteService;
use App\Modules\Notifications\NotificationService;
use App\Modules\Payments\Ledger\InsufficientFunds;
use App\Modules\Payments\PaymentService;
use App\Support\Money;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

/**
 * Recurring deliveries: the same trip on a schedule. recurring:run books each trip LEAD_HOURS ahead as a scheduled
 * instant booking and pays it from the wallet (a business plan covers it first when it can). A short wallet leaves the
 * booking waiting for payment and tells the customer; three failures in a row pause the schedule.
 */
class RecurringDeliveryService
{
    public const PATTERNS = ['daily' => 'Every day', 'weekdays' => 'Every weekday (Mon to Fri)', 'weekly' => 'On chosen days'];

    public const DAYS = [1 => 'Mon', 2 => 'Tue', 3 => 'Wed', 4 => 'Thu', 5 => 'Fri', 6 => 'Sat', 7 => 'Sun'];

    /** Book this far ahead of the pickup. */
    public const LEAD_HOURS = 20;

    /** A scheduled booking must be at least an hour away (InstantQuoteService::pickupAt). */
    public const MIN_MINUTES = 65;

    public const MAX_FAILURES = 3;

    public function __construct(private InstantQuoteService $instant, private PaymentService $payments, private NotificationService $notifications) {}

    /** Pure: the first pickup time strictly after $after that fits the schedule, or null when the schedule has ended. */
    public static function nextOccurrence(string $pattern, array $days, string $time, CarbonImmutable $after, string $startsOn, ?string $endsOn, string $tz): ?CarbonImmutable
    {
        [$h, $m] = array_map('intval', explode(':', $time) + [0, 0]);
        $start = CarbonImmutable::parse($startsOn, $tz)->startOfDay();
        $from = $after->setTimezone($tz)->startOfDay();
        $day = $from->lt($start) ? $start : $from;
        $last = $endsOn ? CarbonImmutable::parse($endsOn, $tz)->endOfDay() : null;
        $days = array_map('intval', $days);
        for ($i = 0; $i < 400; $i++) {
            $d = $day->addDays($i);
            if ($last && $d->gt($last)) {
                return null;
            }
            $fits = match ($pattern) {
                'daily' => true,
                'weekdays' => $d->isoWeekday() <= 5,
                'weekly' => in_array($d->isoWeekday(), $days, true),
                default => false,
            };
            $at = $d->setTime($h, $m);
            if ($fits && $at->gt($after)) {
                return $at;
            }
        }

        return null;
    }

    private static function tz(): string
    {
        return (string) config('app.timezone');
    }

    // ---------------------------------------------------------------- the customer's schedules

    public function list(int $userId): array
    {
        return DB::table('recurring_deliveries')->where('user_id', $userId)->where('status', '<>', 'ended')->orderBy('name')->get()
            ->map(fn ($r) => $this->present($r))->all();
    }

    public function present(object $r): array
    {
        $runs = DB::table('recurring_delivery_runs as x')->leftJoin('agreements as a', 'a.id', '=', 'x.agreement_id')->leftJoin('shipments as s', 's.order_id', '=', 'x.order_id')
            ->where('x.recurring_delivery_id', $r->id)->orderByDesc('x.pickup_at')->limit(10)
            ->get(['x.pickup_at', 'x.status', 'x.amount', 'x.message', 'a.public_id as agreement', 's.public_id as shipment']);

        return [
            'id' => $r->public_id, 'name' => $r->name, 'status' => $r->status, 'pattern' => $r->pattern, 'days' => json_decode((string) $r->days, true) ?: [],
            'time' => $r->time, 'starts_on' => $r->starts_on, 'ends_on' => $r->ends_on,
            'when' => self::describe($r->pattern, json_decode((string) $r->days, true) ?: [], $r->time),
            'pickup' => json_decode((string) $r->pickup, true), 'dropoff_centre' => $r->dropoff_centre, 'dropoff' => json_decode((string) $r->dropoff, true),
            'size' => $r->size, 'vehicle' => $r->vehicle, 'fragile' => (bool) $r->fragile, 'description' => $r->description,
            'next' => $r->next_run_at ? CarbonImmutable::parse($r->next_run_at)->toIso8601String() : null,
            'runs' => $runs->map(fn ($x) => ['pickup_at' => CarbonImmutable::parse($x->pickup_at)->toIso8601String(), 'status' => $x->status,
                'amount' => $x->amount !== null ? (int) $x->amount : null, 'message' => $x->message, 'agreement' => $x->agreement, 'shipment' => $x->shipment])->all(),
        ];
    }

    public static function describe(string $pattern, array $days, string $time): string
    {
        $t = CarbonImmutable::createFromFormat('H:i', $time)->format('g:i a');

        return match ($pattern) {
            'daily' => "Every day at $t",
            'weekdays' => "Weekdays at $t",
            default => implode(', ', array_map(fn ($d) => self::DAYS[(int) $d] ?? '', $days)).' at '.$t,
        };
    }

    /** @param array{name:string, pickup:?array, dropoff_centre:?string, dropoff:array, size:string, vehicle:?string, fragile:bool, description:string, pattern:string, days:?array, time:string, starts_on:string, ends_on:?string} $d */
    public function save(int $userId, array $d, ?string $publicId = null): string
    {
        if (! isset(InstantQuoteService::sizes()[$d['size']])) {
            throw new RuntimeException('Choose the item size.');
        }
        if ($d['pattern'] === 'weekly' && empty($d['days'])) {
            throw new RuntimeException('Pick the days of the week.');
        }
        if (empty($d['dropoff_centre']) && empty($d['pickup']['lat'])) {
            throw new RuntimeException('Choose the pickup address.');
        }
        $next = self::nextOccurrence($d['pattern'], (array) ($d['days'] ?? []), $d['time'], CarbonImmutable::now()->addMinutes(self::MIN_MINUTES), $d['starts_on'], $d['ends_on'] ?? null, self::tz());
        if (! $next) {
            throw new RuntimeException('That schedule has no dates left. Check the start and end dates.');
        }
        $row = [
            'name' => mb_substr(trim($d['name']), 0, 80), 'pickup' => empty($d['dropoff_centre']) ? json_encode($d['pickup']) : null, 'dropoff_centre' => $d['dropoff_centre'] ?: null,
            'dropoff' => json_encode($d['dropoff']), 'size' => $d['size'], 'vehicle' => $d['vehicle'] ?? null, 'fragile' => (bool) ($d['fragile'] ?? false),
            'description' => mb_substr(trim($d['description']), 0, 200), 'pattern' => $d['pattern'],
            'days' => $d['pattern'] === 'weekly' ? json_encode(array_values(array_map('intval', $d['days']))) : null,
            'time' => $d['time'], 'starts_on' => $d['starts_on'], 'ends_on' => $d['ends_on'] ?? null, 'next_run_at' => $next, 'status' => 'active', 'failures' => 0, 'updated_at' => now(),
        ];
        if ($publicId) {
            $n = DB::table('recurring_deliveries')->where('public_id', $publicId)->where('user_id', $userId)->where('status', '<>', 'ended')->update($row);
            if (! $n) {
                throw new RuntimeException('That schedule was not found.');
            }

            return $publicId;
        }
        if (DB::table('recurring_deliveries')->where('user_id', $userId)->where('status', '<>', 'ended')->count() >= 50) {
            throw new RuntimeException('You can have up to 50 schedules.');
        }
        $publicId = (string) Str::ulid();
        DB::table('recurring_deliveries')->insert($row + ['public_id' => $publicId, 'user_id' => $userId, 'created_at' => now()]);

        return $publicId;
    }

    public function setStatus(int $userId, string $publicId, string $status): void
    {
        $r = DB::table('recurring_deliveries')->where('public_id', $publicId)->where('user_id', $userId)->where('status', '<>', 'ended')->first();
        if (! $r) {
            throw new RuntimeException('That schedule was not found.');
        }
        $update = ['status' => $status, 'updated_at' => now()];
        if ($status === 'active') {
            // Starting again: from the next time that is far enough ahead.
            $update['failures'] = 0;
            $update['next_run_at'] = self::nextOccurrence($r->pattern, json_decode((string) $r->days, true) ?: [], $r->time, CarbonImmutable::now()->addMinutes(self::MIN_MINUTES), $r->starts_on, $r->ends_on, self::tz());
            if (! $update['next_run_at']) {
                $update['status'] = 'ended';
            }
        }
        DB::table('recurring_deliveries')->where('id', $r->id)->update($update);
    }

    // ---------------------------------------------------------------- booking them

    /** Books every trip coming up within LEAD_HOURS. Returns how many trips were handled. */
    public function runDue(?CarbonImmutable $now = null): int
    {
        $now ??= CarbonImmutable::now();
        $due = DB::table('recurring_deliveries')->where('status', 'active')->whereNotNull('next_run_at')
            ->where('next_run_at', '<=', $now->addHours(self::LEAD_HOURS))->orderBy('next_run_at')->limit(200)->pluck('id');
        $n = 0;
        foreach ($due as $id) {
            // A schedule can owe several trips (it was paused, or the job did not run): book them in order.
            for ($guard = 0; $guard < 10; $guard++) {
                $r = DB::table('recurring_deliveries')->where('id', $id)->first();
                if (! $r || $r->status !== 'active' || ! $r->next_run_at || CarbonImmutable::parse($r->next_run_at)->gt($now->addHours(self::LEAD_HOURS))) {
                    break;
                }
                $this->runOne($r, $now);
                $n++;
            }
        }

        return $n;
    }

    private function runOne(object $r, CarbonImmutable $now): void
    {
        $at = CarbonImmutable::parse($r->next_run_at);
        $record = fn (string $status, ?string $message = null, ?int $agreementId = null, ?int $orderId = null, ?int $amount = null) => DB::table('recurring_delivery_runs')->insertOrIgnore([
            'recurring_delivery_id' => $r->id, 'pickup_at' => $at, 'status' => $status, 'message' => $message ? mb_substr($message, 0, 300) : null,
            'agreement_id' => $agreementId, 'order_id' => $orderId, 'amount' => $amount, 'created_at' => now(), 'updated_at' => now(),
        ]);
        $failed = false;

        if (DB::table('recurring_delivery_runs')->where('recurring_delivery_id', $r->id)->where('pickup_at', $at)->exists()) {
            // Already handled (the job ran twice): just move on.
        } elseif ($at->lt($now->addMinutes(self::MIN_MINUTES))) {
            $record('skipped', 'Too close to book in time.');
        } else {
            try {
                [$agreementId, $orderId, $amount, $status] = $this->book($r, $at);
                $record($status, null, $agreementId, $orderId, $amount);
                if ($status === 'needs_payment') {
                    $pub = DB::table('agreements')->where('id', $agreementId)->value('public_id');
                    $this->notifications->notify((int) $r->user_id, 'recurring.needs_payment', [
                        'name' => $r->name, 'when' => $at->setTimezone(self::tz())->format('D j M, g:i a'), 'amount' => Money::format((int) $amount, true),
                    ], "/account/pay/{$pub}");
                }
            } catch (Throwable $e) {
                $failed = true;
                Log::info("Recurring delivery {$r->id} at {$at}: {$e->getMessage()}");
                $record('failed', $e instanceof RuntimeException ? $e->getMessage() : 'Could not book this trip.');
            }
        }

        $failures = $failed ? (int) $r->failures + 1 : 0;
        $next = self::nextOccurrence($r->pattern, json_decode((string) $r->days, true) ?: [], $r->time, $at, $r->starts_on, $r->ends_on, self::tz());
        $status = $failures >= self::MAX_FAILURES ? 'paused' : ($next ? 'active' : 'ended');
        DB::table('recurring_deliveries')->where('id', $r->id)->update(['next_run_at' => $next, 'failures' => $failures, 'status' => $status, 'updated_at' => now()]);
        if ($status === 'paused') {
            $this->notifications->notify((int) $r->user_id, 'recurring.paused', ['name' => $r->name], '/account/recurring');
        }
    }

    /** @return array{0:int, 1:?int, 2:int, 3:string} [agreement id, order id, amount charged, paid|needs_payment] */
    private function book(object $r, CarbonImmutable $at): array
    {
        $pickup = json_decode((string) $r->pickup, true) ?: [];
        $drop = json_decode((string) $r->dropoff, true);
        $quote = $this->instant->quote([
            'pickup' => $r->dropoff_centre ? null : ['lat' => $pickup['lat'], 'lng' => $pickup['lng'], 'line1' => $pickup['line1'] ?? null],
            'dropoff_centre' => $r->dropoff_centre, 'dropoff' => ['lat' => $drop['lat'], 'lng' => $drop['lng'], 'line1' => $drop['line1'] ?? null],
            'service' => 'scheduled', 'size' => $r->size, 'fragile' => (bool) $r->fragile, 'vehicle' => $r->vehicle,
            'pickup_at' => $at->setTimezone(self::tz())->format('Y-m-d\TH:i'),
        ], (int) $r->user_id);
        $pub = $this->instant->book((int) $r->user_id, $quote['quote'], [
            'pickup_name' => $pickup['contact_name'] ?? null, 'pickup_phone' => $pickup['contact_phone'] ?? null, 'pickup_instructions' => $pickup['instructions'] ?? null,
            'dropoff_name' => $drop['contact_name'] ?? '', 'dropoff_phone' => $drop['contact_phone'] ?? '', 'dropoff_instructions' => $drop['instructions'] ?? null,
            'description' => $r->description,
        ]);
        $agreementId = (int) DB::table('agreements')->where('public_id', $pub)->value('id');
        try {
            $made = $this->payments->payWithWallet($agreementId, (int) $r->user_id);
        } catch (InsufficientFunds) {
            $a = DB::table('agreements')->where('id', $agreementId)->first();

            return [$agreementId, null, (int) $a->price - (int) $a->discount, 'needs_payment'];
        }
        $a = DB::table('agreements')->where('id', $agreementId)->first();

        return [$agreementId, (int) $made['order_id'], (int) $a->price - (int) $a->discount, 'paid'];
    }
}
