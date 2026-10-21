<?php

namespace App\Modules\Fleet;

use App\Modules\Notifications\NotificationService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Looking after a provider's vehicles: what is due (insurance, roadworthiness, servicing by date or mileage),
 * service and fuel records, and the daily reminders for anything about to expire, including drivers' papers.
 * Money is integer kobo throughout.
 */
class VehicleCareService
{
    /** A reminder goes out once at each of these points before the date, on the day, and once after it passes. */
    public const STAGES = [30, 7, 0];

    /** Servicing by mileage is "due soon" within this many km of the target. */
    public const SERVICE_KM_WARNING = 500;

    /** Who hears about expiring papers and services. */
    public const ROLES = ['owner', 'admin', 'driver_manager'];

    public const DOC_LABELS = [
        'insurance' => 'Insurance', 'roadworthiness' => 'Roadworthiness', 'vehicle_registration' => 'Vehicle registration',
        'drivers_licence' => "Driver's licence", 'national_id' => 'National ID', 'cac_certificate' => 'CAC certificate',
        'utility_bill' => 'Utility bill', 'guarantor_form' => 'Guarantor form', 'police_report' => 'Background check', 'service' => 'Service',
    ];

    public function __construct(private NotificationService $notifications) {}

    // ---------------------------------------------------------------- reading

    /** Every vehicle with who drives it, what is due and this month's fuel. */
    public function vehicles(int $operatorId, ?CarbonImmutable $today = null): array
    {
        $today ??= CarbonImmutable::today();
        $monthStart = $today->startOfMonth()->toDateString();
        $fuel = DB::table('vehicle_fuel_logs')->where('operator_id', $operatorId)->where('filled_on', '>=', $monthStart)
            ->groupBy('vehicle_id')->selectRaw('vehicle_id, SUM(litres) AS litres, SUM(cost_kobo) AS cost, COUNT(*) AS fills')->get()->keyBy('vehicle_id');

        return DB::table('vehicles as v')->join('vehicle_types as t', 't.id', '=', 'v.vehicle_type_id')
            ->leftJoin('driver_profiles as p', fn ($j) => $j->on('p.current_vehicle_id', '=', 'v.id')->whereNull('p.deleted_at'))
            ->leftJoin('users as u', 'u.id', '=', 'p.user_id')
            ->where('v.operator_id', $operatorId)->whereNull('v.deleted_at')->orderBy('v.plate')
            ->get(['v.*', 't.name as type_name', 't.max_volume_cm3', 'u.name as driver'])
            ->map(function ($v) use ($today, $fuel) {
                $f = $fuel[$v->id] ?? null;

                return [
                    'id' => $v->public_id, 'plate' => $v->plate, 'name' => trim("{$v->make} {$v->model}") ?: $v->type_name, 'type' => $v->type_name,
                    'status' => $v->status, 'driver' => $v->driver, 'odometer_km' => $v->odometer_km !== null ? (int) $v->odometer_km : null,
                    'insurance_expires_on' => $v->insurance_expires_on, 'roadworthy_expires_on' => $v->roadworthy_expires_on,
                    'last_serviced_on' => $v->last_serviced_on, 'next_service_on' => $v->next_service_on, 'next_service_km' => $v->next_service_km !== null ? (int) $v->next_service_km : null,
                    'service_interval_days' => $v->service_interval_days, 'service_interval_km' => $v->service_interval_km,
                    'capacity' => ['kg' => $v->max_weight_g ? round($v->max_weight_g / 1000, 1) : null, 'parcels' => $v->capacity_parcels, 'volume_l' => $v->max_volume_cm3 ? (int) round($v->max_volume_cm3 / 1000) : null],
                    'fuel_month' => ['litres' => $f ? round((float) $f->litres, 1) : 0, 'cost' => $f ? (int) $f->cost : 0, 'fills' => $f ? (int) $f->fills : 0],
                    'due' => array_values(array_filter([
                        self::dateDue('Insurance', $v->insurance_expires_on, $today),
                        self::dateDue('Roadworthiness', $v->roadworthy_expires_on, $today),
                        self::dateDue('Service', $v->next_service_on, $today),
                        self::kmDue($v->odometer_km !== null ? (int) $v->odometer_km : null, $v->next_service_km !== null ? (int) $v->next_service_km : null),
                    ])),
                ];
            })->all();
    }

    /** Recent fuel fills and services for the page. */
    public function history(int $operatorId, int $limit = 30): array
    {
        $plates = DB::table('vehicles')->where('operator_id', $operatorId)->pluck('plate', 'id');
        $fuel = DB::table('vehicle_fuel_logs as f')->leftJoin('driver_profiles as p', 'p.id', '=', 'f.driver_profile_id')->leftJoin('users as u', 'u.id', '=', 'p.user_id')
            ->where('f.operator_id', $operatorId)->orderByDesc('f.filled_on')->orderByDesc('f.id')->limit($limit)
            ->get(['f.vehicle_id', 'f.filled_on', 'f.litres', 'f.cost_kobo', 'f.odometer_km', 'f.note', 'u.name as driver'])
            ->map(fn ($r) => ['plate' => $plates[$r->vehicle_id] ?? '', 'on' => $r->filled_on, 'litres' => (float) $r->litres, 'cost' => (int) $r->cost_kobo,
                'odometer_km' => $r->odometer_km, 'note' => $r->note, 'driver' => $r->driver])->all();
        $service = DB::table('vehicle_service_logs')->where('operator_id', $operatorId)->orderByDesc('serviced_on')->orderByDesc('id')->limit($limit)
            ->get(['vehicle_id', 'serviced_on', 'odometer_km', 'cost_kobo', 'description'])
            ->map(fn ($r) => ['plate' => $plates[$r->vehicle_id] ?? '', 'on' => $r->serviced_on, 'odometer_km' => $r->odometer_km, 'cost' => (int) $r->cost_kobo, 'description' => $r->description])->all();

        return ['fuel' => $fuel, 'service' => $service];
    }

    // ---------------------------------------------------------------- writing

    /** Paper expiry dates, odometer, service plan and parcel capacity. */
    public function saveCare(int $operatorId, string $vehiclePublicId, array $d): void
    {
        $v = $this->vehicle($operatorId, $vehiclePublicId);
        $set = [];
        foreach (['insurance_expires_on', 'roadworthy_expires_on', 'next_service_on'] as $k) {
            if (array_key_exists($k, $d)) {
                $set[$k] = $d[$k] ?: null;
            }
        }
        foreach (['service_interval_days', 'service_interval_km', 'next_service_km', 'capacity_parcels'] as $k) {
            if (array_key_exists($k, $d)) {
                $set[$k] = $d[$k] === null || $d[$k] === '' ? null : max(0, (int) $d[$k]);
            }
        }
        if (isset($d['odometer_km']) && $d['odometer_km'] !== '') {
            $set['odometer_km'] = $this->odometer($v, (int) $d['odometer_km']);
        }
        if ($set) {
            DB::table('vehicles')->where('id', $v->id)->update($set + ['updated_at' => now()]);
        }
    }

    /** A service was done: record it, and move the next one on by the vehicle's interval. */
    public function logService(int $operatorId, string $vehiclePublicId, array $d, int $userId): void
    {
        $v = $this->vehicle($operatorId, $vehiclePublicId);
        $on = CarbonImmutable::parse($d['serviced_on']);
        if ($on->isFuture()) {
            throw new RuntimeException('The service date cannot be in the future.');
        }
        $km = isset($d['odometer_km']) && $d['odometer_km'] !== '' ? $this->odometer($v, (int) $d['odometer_km']) : null;
        [$nextOn, $nextKm] = self::nextService($on, $km ?? ($v->odometer_km !== null ? (int) $v->odometer_km : null), $v->service_interval_days, $v->service_interval_km);

        DB::transaction(function () use ($v, $operatorId, $on, $km, $d, $userId, $nextOn, $nextKm) {
            DB::table('vehicle_service_logs')->insert([
                'vehicle_id' => $v->id, 'operator_id' => $operatorId, 'serviced_on' => $on->toDateString(), 'odometer_km' => $km,
                'cost_kobo' => max(0, (int) ($d['cost_kobo'] ?? 0)), 'description' => isset($d['description']) ? mb_substr(trim($d['description']), 0, 300) : null,
                'created_by' => $userId, 'created_at' => now(), 'updated_at' => now(),
            ]);
            DB::table('vehicles')->where('id', $v->id)->update(array_filter([
                'last_serviced_on' => $on->toDateString(), 'odometer_km' => $km, 'next_service_on' => $nextOn?->toDateString(), 'next_service_km' => $nextKm, 'updated_at' => now(),
            ], fn ($x) => $x !== null));
        });
    }

    /** A fuel fill, from the office or the driver. */
    public function logFuel(int $operatorId, string $vehiclePublicId, array $d, int $userId): void
    {
        $v = $this->vehicle($operatorId, $vehiclePublicId);
        $litres = round((float) ($d['litres'] ?? 0), 2);
        $cost = (int) ($d['cost_kobo'] ?? 0);
        if ($litres <= 0 || $litres > 2000) {
            throw new RuntimeException('Enter the litres filled.');
        }
        if ($cost <= 0) {
            throw new RuntimeException('Enter what the fuel cost.');
        }
        $on = CarbonImmutable::parse($d['filled_on'] ?? 'today');
        if ($on->isFuture()) {
            throw new RuntimeException('The fill date cannot be in the future.');
        }
        $km = isset($d['odometer_km']) && $d['odometer_km'] !== '' ? $this->odometer($v, (int) $d['odometer_km']) : null;
        $driver = DB::table('driver_profiles')->where('current_vehicle_id', $v->id)->whereNull('deleted_at')->value('id');

        DB::transaction(function () use ($v, $operatorId, $on, $litres, $cost, $km, $d, $userId, $driver) {
            DB::table('vehicle_fuel_logs')->insert([
                'vehicle_id' => $v->id, 'operator_id' => $operatorId, 'driver_profile_id' => $d['driver_profile_id'] ?? $driver, 'filled_on' => $on->toDateString(),
                'litres' => $litres, 'cost_kobo' => $cost, 'odometer_km' => $km, 'note' => isset($d['note']) ? mb_substr(trim($d['note']), 0, 200) : null,
                'created_by' => $userId, 'created_at' => now(), 'updated_at' => now(),
            ]);
            if ($km !== null) {
                DB::table('vehicles')->where('id', $v->id)->update(['odometer_km' => $km, 'updated_at' => now()]);
            }
        });
    }

    // ---------------------------------------------------------------- reminders

    /**
     * Sends every reminder that has come due today, once each: vehicle insurance, roadworthiness and servicing, and
     * approved documents with an expiry date (drivers' licences, vehicle papers). Returns how many went out.
     */
    public function sendReminders(?CarbonImmutable $today = null): int
    {
        $today ??= CarbonImmutable::today();
        $horizon = $today->addDays(max(self::STAGES))->toDateString();
        $sent = 0;

        $vehicles = DB::table('vehicles')->whereNull('deleted_at')->where('status', '<>', 'retired')
            ->where(fn ($q) => $q->where('insurance_expires_on', '<=', $horizon)->orWhere('roadworthy_expires_on', '<=', $horizon)->orWhere('next_service_on', '<=', $horizon))
            ->get(['id', 'operator_id', 'plate', 'insurance_expires_on', 'roadworthy_expires_on', 'next_service_on']);
        foreach ($vehicles as $v) {
            foreach (['insurance' => $v->insurance_expires_on, 'roadworthiness' => $v->roadworthy_expires_on, 'service' => $v->next_service_on] as $kind => $due) {
                $sent += $this->remind((int) $v->operator_id, 'vehicle', (int) $v->id, $kind, $due, $today, $v->plate, null);
            }
        }

        $docs = DB::table('kyc_documents')->where('status', 'approved')->whereNotNull('expires_on')->where('expires_on', '<=', $horizon)
            ->get(['id', 'operator_id', 'subject_type', 'subject_id', 'doc_type', 'expires_on']);
        foreach ($docs as $doc) {
            [$whose, $driverUser] = $this->docSubject($doc);
            $sent += $this->remind((int) $doc->operator_id, 'document', (int) $doc->id, (string) $doc->doc_type, $doc->expires_on, $today, $whose, $driverUser);
        }

        return $sent;
    }

    private function remind(int $operatorId, string $subjectType, int $subjectId, string $kind, ?string $due, CarbonImmutable $today, string $whose, ?int $alsoUser): int
    {
        $stage = $due ? self::stage(CarbonImmutable::parse($due), $today) : null;
        // Long-lapsed papers show on the Vehicles page; only things that lapsed in the last month get the "overdue" message,
        // so switching reminders on does not flood anyone with years-old dates.
        if ($stage === null || ($stage === 'overdue' && CarbonImmutable::parse($due)->lt($today->subDays(max(self::STAGES))))) {
            return 0;
        }
        $new = DB::table('fleet_reminders')->insertOrIgnore([
            'operator_id' => $operatorId, 'subject_type' => $subjectType, 'subject_id' => $subjectId, 'kind' => $kind,
            'due_on' => CarbonImmutable::parse($due)->toDateString(), 'stage' => $stage, 'sent_at' => now(),
        ]);
        if (! $new) {
            return 0;
        }
        $label = self::DOC_LABELS[$kind] ?? ucfirst(str_replace('_', ' ', $kind));
        $vars = ['item' => $label, 'whose' => $whose, 'when' => self::whenText(CarbonImmutable::parse($due), $today, $kind === 'service')];
        $this->notifications->notifyOperator($operatorId, 'fleet.reminder', $vars, $subjectType === 'vehicle' ? '/provider/vehicles' : '/provider/setup', self::ROLES);
        if ($alsoUser) {
            $this->notifications->notify($alsoUser, 'fleet.reminder', $vars);
        }

        return 1;
    }

    /** @return array{0:string,1:?int} a readable owner of the document, and the driver to tell as well */
    private function docSubject(object $doc): array
    {
        return match ($doc->subject_type) {
            'vehicle' => [(string) (DB::table('vehicles')->where('id', $doc->subject_id)->value('plate') ?? 'a vehicle'), null],
            'driver' => (function () use ($doc) {
                $u = DB::table('driver_profiles as p')->join('users as u', 'u.id', '=', 'p.user_id')->where('p.id', $doc->subject_id)->first(['u.id', 'u.name']);

                return [$u->name ?? 'a driver', $u ? (int) $u->id : null];
            })(),
            default => [(string) (DB::table('operators')->where('id', $doc->operator_id)->value('display_name') ?? 'your business'), null],
        };
    }

    // ---------------------------------------------------------------- pure rules

    /** Which reminder is due on $today for something due on $due: '30', '7', '0', 'overdue', or null for none. */
    public static function stage(CarbonImmutable $due, CarbonImmutable $today): ?string
    {
        $days = (int) $today->startOfDay()->diffInDays($due->startOfDay(), false);
        if ($days < 0) {
            return 'overdue';
        }
        foreach (array_reverse(self::STAGES) as $s) {   // 0, 7, 30: the closest point reached wins
            if ($days <= $s) {
                return (string) $s;
            }
        }

        return null;
    }

    /** Badge for a date: overdue (danger), within 30 days (warning), or null. */
    public static function dateDue(string $label, ?string $date, CarbonImmutable $today): ?array
    {
        if (! $date) {
            return null;
        }
        $days = (int) $today->startOfDay()->diffInDays(CarbonImmutable::parse($date)->startOfDay(), false);
        if ($days > max(self::STAGES)) {
            return null;
        }

        return ['label' => $label.' '.self::whenText(CarbonImmutable::parse($date), $today, $label === 'Service'), 'level' => $days < 0 ? 'danger' : ($days <= 7 ? 'warning' : 'info')];
    }

    /** Badge for servicing by mileage. */
    public static function kmDue(?int $odometer, ?int $nextKm): ?array
    {
        if ($odometer === null || $nextKm === null) {
            return null;
        }
        $left = $nextKm - $odometer;
        if ($left > self::SERVICE_KM_WARNING) {
            return null;
        }

        return $left < 0
            ? ['label' => 'Service overdue by '.number_format(-$left).' km', 'level' => 'danger']
            : ['label' => 'Service due in '.number_format($left).' km', 'level' => 'warning'];
    }

    /** The next service date and mileage after one done on $on at $km. */
    public static function nextService(CarbonImmutable $on, ?int $km, ?int $everyDays, ?int $everyKm): array
    {
        return [$everyDays ? $on->addDays($everyDays) : null, $everyKm && $km !== null ? $km + $everyKm : null];
    }

    public static function whenText(CarbonImmutable $due, CarbonImmutable $today, bool $isService = false): string
    {
        $days = (int) $today->startOfDay()->diffInDays($due->startOfDay(), false);
        $verb = $isService ? 'is due' : 'expires';

        return match (true) {
            $days < 0 => ($isService ? 'was due ' : 'expired ').abs($days).' '.(abs($days) === 1 ? 'day' : 'days').' ago',
            $days === 0 => "{$verb} today",
            default => "{$verb} in {$days} ".($days === 1 ? 'day' : 'days').' ('.$due->format('j M Y').')',
        };
    }

    // ---------------------------------------------------------------- helpers

    private function vehicle(int $operatorId, string $publicId): object
    {
        $v = DB::table('vehicles')->where(['operator_id' => $operatorId, 'public_id' => $publicId])->whereNull('deleted_at')->first();
        if (! $v) {
            throw new RuntimeException('No such vehicle.');
        }

        return $v;
    }

    /** Odometers only go up; a smaller reading is almost always a typo. */
    private function odometer(object $v, int $km): int
    {
        if ($km < 0 || $km > 5_000_000) {
            throw new RuntimeException('That odometer reading does not look right.');
        }
        if ($v->odometer_km !== null && $km < (int) $v->odometer_km) {
            throw new RuntimeException('The odometer reading ('.number_format($km).' km) is lower than the last one recorded ('.number_format((int) $v->odometer_km).' km).');
        }

        return $km;
    }
}
