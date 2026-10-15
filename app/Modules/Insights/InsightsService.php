<?php

namespace App\Modules\Insights;

use App\Modules\Pricing\CostCalculator;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * What a provider learns from the jobs already done: which jobs made money, which riders can be relied on, and how the
 * vehicles are used. Read-only, worked out from the records each job already leaves behind. The Reports pages
 * (ReportService: profit, drivers, fleet) show these figures.
 *
 *   profit       per delivered job: what the provider earned (their share after any platform fee, or the delivery and
 *                service fee in a company install) less the rider's pay and the running cost from the provider's cost
 *                profile (fuel, maintenance, other per-job costs, the empty return trip) on the job's real distance
 *   reliability  per rider: offers taken, jobs finished, failed or dropped, and drop-offs on time against the first
 *                arrival time the receiver was given (or the delivery deadline or window when one was set)
 *   fleet        per vehicle: jobs, kilometres, days on the road, earnings, and papers expiring
 */
class InsightsService
{
    /** A drop-off this long after the time promised still counts as on time. */
    public const ON_TIME_GRACE_S = 600;

    /** Below this many jobs a rider's score is shown as "not enough jobs yet". */
    public const MIN_JOBS = 5;

    /** Papers due to expire within this many days are flagged. */
    public const PAPERS_WARN_DAYS = 30;

    public function __construct(private CostCalculator $calculator) {}

    // ---------------------------------------------------------------- profit

    /** Delivered jobs with what each earned and cost. $opId null = every provider (the staff console). */
    public function profit(?int $opId, CarbonImmutable $from, CarbonImmutable $to): array
    {
        $profiles = DB::table('provider_cost_profiles')->where('active', true)->when($opId, fn ($q) => $q->where('operator_id', $opId))
            ->orderByDesc('is_default')->orderBy('id')->get()->groupBy('operator_id');
        $scope = $opId ? 'AND s.operator_id = ?' : '';
        $rows = DB::select(
            "SELECT s.id, s.public_id, s.operator_id, s.tracking_code, s.delivered_at, COALESCE(s.distance_m, ag.distance_m, 0) AS distance_m, COALESCE(s.duration_s, 0) AS duration_s,
                    o.order_number, o.agreement_id, o.delivery_fee, o.service_fee, ag.provider_net, ag.platform_fee, op.display_name AS company,
                    a.payout_amount, u.name AS rider, v.plate, v.vehicle_type_id
             FROM shipments s
             JOIN orders o ON o.id = s.order_id AND o.is_test = false
             JOIN operators op ON op.id = s.operator_id
             LEFT JOIN agreements ag ON ag.id = o.agreement_id
             LEFT JOIN LATERAL (SELECT * FROM assignments x WHERE x.shipment_id = s.id AND x.status = 'completed' ORDER BY x.id DESC LIMIT 1) a ON true
             LEFT JOIN driver_profiles p ON p.id = a.driver_profile_id
             LEFT JOIN users u ON u.id = p.user_id
             LEFT JOIN vehicles v ON v.id = a.vehicle_id
             WHERE s.status IN ('delivered', 'completed') AND s.is_test = false AND s.delivered_at BETWEEN ? AND ? {$scope}
             ORDER BY s.delivered_at DESC LIMIT 5000",
            array_merge([$from->toDateTimeString(), $to->toDateTimeString()], $opId ? [$opId] : [])
        );

        $jobs = [];
        foreach ($rows as $r) {
            $own = $profiles->get($r->operator_id) ?? collect();
            $profile = $own->firstWhere('vehicle_type_id', $r->vehicle_type_id) ?? $own->first();
            $earned = $r->agreement_id ? (int) $r->provider_net : (int) $r->delivery_fee + (int) $r->service_fee;
            $jobs[] = [
                'id' => $r->public_id, 'tracking_code' => $r->tracking_code, 'order_number' => $r->order_number, 'delivered_at' => $r->delivered_at,
                'company' => $r->company, 'rider' => $r->rider, 'plate' => $r->plate, 'distance_m' => (int) $r->distance_m, 'platform_fee' => (int) $r->platform_fee,
                'profile' => $profile?->name,
            ] + $this->jobProfit($earned, $r->payout_amount === null ? null : (int) $r->payout_amount, $profile ? (array) $profile : null, (int) $r->distance_m, (int) $r->duration_s);
        }

        return ['jobs' => $jobs, 'totals' => self::totals($jobs), 'has_profile' => $profiles->isNotEmpty()];
    }

    /**
     * Pure apart from the calculator. The rider's actual pay replaces the profile's labour figure when we know it, so
     * labour is never counted twice; without either, labour is left out and the job looks better than it is.
     *
     * @return array{earned:int, rider_pay:int, running_cost:int, cost:int, profit:int, margin_bp:?int, flag:?string, costed:bool}
     */
    public function jobProfit(int $earned, ?int $riderPay, ?array $profile, int $distanceM, int $durationS): array
    {
        $running = 0;
        $labour = $riderPay ?? 0;
        if ($profile) {
            $c = $this->calculator->calculate($profile, ['distance_m' => $distanceM, 'duration_s' => $durationS]);
            $lines = $c['costs'];
            $running = array_sum(array_diff_key($lines, ['labour' => 0]));
            $labour = $riderPay ?? (int) ($lines['labour'] ?? 0);
        }
        $cost = $running + $labour;
        $profit = $earned - $cost;
        $marginBp = $earned > 0 ? intdiv($profit * 10_000, $earned) : null;

        return ['earned' => $earned, 'rider_pay' => $labour, 'running_cost' => $running, 'cost' => $cost, 'profit' => $profit, 'margin_bp' => $marginBp,
            'flag' => $profit < 0 ? 'loss' : ($marginBp !== null && $marginBp < CostCalculator::THIN_MARGIN_BP ? 'thin_margin' : null), 'costed' => $profile !== null];
    }

    /** Pure. */
    public static function totals(array $jobs): array
    {
        $sum = fn (string $k) => array_sum(array_column($jobs, $k));
        $earned = $sum('earned');
        $profit = $sum('profit');
        $km = $sum('distance_m') / 1000;

        return ['jobs' => count($jobs), 'earned' => $earned, 'rider_pay' => $sum('rider_pay'), 'running_cost' => $sum('running_cost'), 'cost' => $sum('cost'),
            'profit' => $profit, 'platform_fee' => $sum('platform_fee'), 'margin_bp' => $earned > 0 ? intdiv($profit * 10_000, $earned) : null,
            'loss_jobs' => count(array_filter($jobs, fn ($j) => $j['flag'] === 'loss')), 'thin_jobs' => count(array_filter($jobs, fn ($j) => $j['flag'] === 'thin_margin')),
            'km' => round($km, 1), 'profit_per_km' => $km > 0 ? (int) round($profit / $km) : null, 'profit_per_job' => $jobs ? intdiv($profit, count($jobs)) : null];
    }

    // ---------------------------------------------------------------- rider reliability

    /**
     * Per rider: drop-offs they completed that had a time to meet, and how many met it. The time is the job's deadline,
     * its delivery window, or the first arrival time the receiver was given, with ON_TIME_GRACE_S to spare.
     *
     * @return array<int, array{timed:int, on_time:int}> keyed by driver profile id
     */
    public function onTime(?int $opId, CarbonImmutable $from, CarbonImmutable $to): array
    {
        $grace = self::ON_TIME_GRACE_S;

        return DB::table('shipment_stops as st')->join('shipments as s', 's.id', '=', 'st.shipment_id')
            ->join('assignments as a', fn ($j) => $j->on('a.shipment_id', '=', 's.id')->where('a.status', 'completed'))
            ->leftJoin('stop_etas as e', 'e.stop_id', '=', 'st.id')
            ->when($opId, fn ($q) => $q->where('a.operator_id', $opId))
            ->where('s.is_test', false)->where('st.type', 'dropoff')->where('st.status', 'completed')
            ->whereBetween('st.completed_at', [$from->toDateTimeString(), $to->toDateTimeString()])
            ->whereRaw('COALESCE(s.deliver_by, st.window_end, e.promised_at) IS NOT NULL')
            ->groupBy('a.driver_profile_id')
            ->selectRaw("a.driver_profile_id, COUNT(*) AS timed,
                COUNT(*) FILTER (WHERE st.completed_at <= COALESCE(s.deliver_by, st.window_end, e.promised_at) + make_interval(secs => {$grace})) AS on_time")
            ->get()->mapWithKeys(fn ($r) => [(int) $r->driver_profile_id => ['timed' => (int) $r->timed, 'on_time' => (int) $r->on_time]])->all();
    }

    /**
     * Pure. On time 40, finishing what they take 40, taking offers 20. A part with no data counts a neutral 0.7, and the
     * score is marked as not settled until the rider has MIN_JOBS jobs.
     *
     * @param  array{offers:int, offers_taken:int, delivered:int, failed:int, dropped:int, timed:int, on_time:int}  $m
     * @return array{score:int, enough:bool, on_time_rate:?float, finish_rate:?float, accept_rate:?float}
     */
    public static function reliabilityScore(array $m): array
    {
        $taken = $m['delivered'] + $m['failed'] + $m['dropped'];
        $onTime = $m['timed'] > 0 ? $m['on_time'] / $m['timed'] : null;
        $finish = $taken > 0 ? $m['delivered'] / $taken : null;
        $accept = $m['offers'] > 0 ? $m['offers_taken'] / $m['offers'] : null;
        $score = (int) round(100 * (0.4 * ($onTime ?? 0.7) + 0.4 * ($finish ?? 0.7) + 0.2 * ($accept ?? 0.7)));

        return ['score' => $score, 'enough' => $taken >= self::MIN_JOBS, 'on_time_rate' => $onTime === null ? null : round($onTime * 100, 1),
            'finish_rate' => $finish === null ? null : round($finish * 100, 1), 'accept_rate' => $accept === null ? null : round($accept * 100, 1)];
    }

    // ---------------------------------------------------------------- fleet

    /** Every vehicle with its use in the period. $opId null = every provider. */
    public function fleet(?int $opId, CarbonImmutable $from, CarbonImmutable $to): array
    {
        $range = [$from->toDateTimeString(), $to->toDateTimeString()];
        $use = DB::table('assignments as a')->join('shipments as s', 's.id', '=', 'a.shipment_id')->join('orders as o', 'o.id', '=', 's.order_id')
            ->leftJoin('agreements as ag', 'ag.id', '=', 'o.agreement_id')
            ->when($opId, fn ($q) => $q->where('a.operator_id', $opId))->where('o.is_test', false)->where('a.status', 'completed')->whereNotNull('a.vehicle_id')->whereBetween('a.completed_at', $range)
            ->groupBy('a.vehicle_id')
            ->selectRaw("a.vehicle_id, COUNT(*) AS jobs, COALESCE(SUM(COALESCE(s.distance_m, ag.distance_m, 0)), 0) AS distance_m,
                COUNT(DISTINCT (a.completed_at AT TIME ZONE 'Africa/Lagos')::date) AS days,
                COALESCE(SUM(CASE WHEN s.status = 'delivered' THEN CASE WHEN o.agreement_id IS NOT NULL THEN ag.provider_net ELSE o.delivery_fee + o.service_fee END ELSE 0 END), 0) AS earned,
                MAX(a.completed_at) AS last_job_at")
            ->get()->keyBy('vehicle_id');

        $days = max(1, (int) ceil(($to->getTimestamp() - $from->getTimestamp()) / 86400));
        $today = CarbonImmutable::today();
        $vehicles = DB::table('vehicles as v')->join('vehicle_types as t', 't.id', '=', 'v.vehicle_type_id')
            ->leftJoin('driver_profiles as p', 'p.current_vehicle_id', '=', 'v.id')->leftJoin('users as u', 'u.id', '=', 'p.user_id')
            ->join('operators as op', 'op.id', '=', 'v.operator_id')
            ->when($opId, fn ($q) => $q->where('v.operator_id', $opId))->whereNull('v.deleted_at')->orderBy('op.display_name')->orderBy('v.plate')->limit(2000)
            ->get(['v.id', 'v.plate', 'v.make', 'v.model', 'v.status', 'v.insurance_expires_on', 'v.roadworthy_expires_on', 't.name as type', 'u.name as rider', 'op.display_name as company']);

        $out = [];
        foreach ($vehicles as $v) {
            $u = $use[$v->id] ?? null;
            $out[] = [
                'plate' => $v->plate, 'company' => $v->company, 'name' => trim("{$v->make} {$v->model}") ?: $v->type, 'type' => $v->type, 'status' => $v->status, 'rider' => $v->rider,
                'jobs' => (int) ($u->jobs ?? 0), 'km' => round(((int) ($u->distance_m ?? 0)) / 1000, 1), 'days_used' => (int) ($u->days ?? 0),
                'use_rate' => round(100 * ((int) ($u->days ?? 0)) / $days, 1), 'earned' => (int) ($u->earned ?? 0), 'last_job_at' => $u->last_job_at ?? null,
                'papers' => array_values(array_filter([
                    self::paper('Insurance', $v->insurance_expires_on, $today),
                    self::paper('Roadworthiness', $v->roadworthy_expires_on, $today),
                ])),
            ];
        }

        $summary = ['vehicles' => count($out), 'idle' => count(array_filter($out, fn ($v) => $v['jobs'] === 0)),
            'papers_due' => count(array_filter($out, fn ($v) => $v['papers'] !== [])), 'km' => round(array_sum(array_column($out, 'km')), 1), 'days' => $days];

        return ['vehicles' => $out, 'summary' => $summary];
    }

    /** Pure. "Insurance expired 3 days ago", "Roadworthiness expires in 12 days", or null when it is not due soon. */
    public static function paper(string $label, ?string $expires, CarbonImmutable $today): ?array
    {
        if (! $expires) {
            return ['label' => "{$label} date not recorded", 'level' => 'info'];
        }
        $d = (int) $today->diffInDays(CarbonImmutable::parse($expires)->startOfDay(), false);
        if ($d < 0) {
            return ['label' => "{$label} expired ".abs($d).' '.(abs($d) === 1 ? 'day' : 'days').' ago', 'level' => 'danger'];
        }
        if ($d <= self::PAPERS_WARN_DAYS) {
            return ['label' => $d === 0 ? "{$label} expires today" : "{$label} expires in {$d} ".($d === 1 ? 'day' : 'days'), 'level' => 'warning'];
        }

        return null;
    }
}
