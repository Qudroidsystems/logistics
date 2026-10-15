<?php

namespace App\Modules\Backoffice;

use App\Modules\Insights\InsightsService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * Business reports for staff (the whole platform, or one provider) and for a provider's own workspace (its jobs only).
 *
 * Every report returns the same shape, so one page and one exporter serve them all:
 *   headings  column titles
 *   formats   per column: text, int, money (naira), pct, minutes, km, date, rating
 *   rows      plain values (money already in naira), safe to write straight into a spreadsheet
 *   summary   a few headline figures: [label, value, format]
 *   chart     optional: type, labels, series [{name, data}], format of the values
 *
 * Test orders never count. Dates follow the order's booking time, a delivery's delivered time, or the event's time,
 * as each report says.
 */
class ReportService
{
    public const REPORTS = [
        'daily' => 'Daily deliveries and revenue',
        'monthly' => 'Monthly revenue',
        'drivers' => 'Driver performance',
        'customers' => 'Customer analytics',
        'retention' => 'Customer retention',
        'routes' => 'Most popular routes',
        'failed' => 'Failed deliveries',
        'times' => 'Average delivery time',
        'profit' => 'Profit per job',
        'fleet' => 'Fleet use',
    ];

    /** Which staff permission (besides "View reports") opens each report in the staff console. */
    public const PERMISSION = [
        'daily' => 'View financial report', 'monthly' => 'View financial report', 'drivers' => 'View operations report',
        'customers' => 'View reports', 'retention' => 'View reports', 'routes' => 'View geographic report',
        'failed' => 'View operations report', 'times' => 'View operations report',
        'profit' => 'View financial report', 'fleet' => 'View operations report',
    ];

    private const DONE = ['delivered', 'completed'];

    public function table(string $report, ?int $operatorId, CarbonImmutable $from, CarbonImmutable $to): array
    {
        return match ($report) {
            'daily' => $this->daily($operatorId, $from, $to),
            'monthly' => $this->monthly($operatorId, $from, $to),
            'drivers' => $this->drivers($operatorId, $from, $to),
            'customers' => $this->customers($operatorId, $from, $to),
            'retention' => $this->retention($operatorId, $from, $to),
            'routes' => $this->routes($operatorId, $from, $to),
            'failed' => $this->failed($operatorId, $from, $to),
            'times' => $this->times($operatorId, $from, $to),
            'profit' => $this->profit($operatorId, $from, $to),
            'fleet' => $this->fleet($operatorId, $from, $to),
        };
    }

    // ------------------------------------------------------------------ money

    /** Bookings, deliveries and money per day, with empty days shown as zero. */
    public function daily(?int $op, CarbonImmutable $from, CarbonImmutable $to): array
    {
        $rows = $this->moneyBy('day', $op, $from, $to);
        $earn = $op ? 'Your earnings' : 'Provider earnings';

        return [
            'headings' => ['Date', 'Bookings', 'Delivered', 'Failed', 'Cancelled', 'Sales', 'Refunded', 'Platform fee', $earn],
            'formats' => ['date', 'int', 'int', 'int', 'int', 'money', 'money', 'money', 'money'],
            'rows' => array_map(fn ($r) => [$r->period, (int) $r->bookings, (int) $r->delivered, (int) $r->failed, (int) $r->cancelled,
                $this->naira($r->sales), $this->naira($r->refunded), $this->naira($r->fee), $this->naira($r->net)], $rows),
            'summary' => $this->moneySummary($rows, $earn),
            'chart' => ['type' => 'bar', 'format' => 'money', 'labels' => array_map(fn ($r) => CarbonImmutable::parse($r->period)->format('j M'), $rows),
                'series' => [['name' => 'Sales', 'data' => array_map(fn ($r) => $this->naira($r->sales), $rows)], ['name' => $earn, 'data' => array_map(fn ($r) => $this->naira($r->net), $rows)]]],
        ];
    }

    public function monthly(?int $op, CarbonImmutable $from, CarbonImmutable $to): array
    {
        $rows = $this->moneyBy('month', $op, $from->startOfMonth(), $to);
        $earn = $op ? 'Your earnings' : 'Provider earnings';
        $out = [];
        $prev = null;
        foreach ($rows as $r) {
            $net = (int) $r->sales - (int) $r->refunded;
            $out[] = [CarbonImmutable::parse($r->period)->format('M Y'), (int) $r->bookings, (int) $r->delivered, $this->naira($r->sales), $this->naira($r->refunded),
                $this->naira($net), $this->naira($r->fee), $this->naira($r->net), $r->bookings > 0 ? $this->naira(intdiv((int) $r->sales, (int) $r->bookings)) : 0,
                $prev !== null && $prev > 0 ? round(($net - $prev) * 100 / $prev, 1) : null];
            $prev = $net;
        }

        return [
            'headings' => ['Month', 'Bookings', 'Delivered', 'Sales', 'Refunded', 'Net sales', 'Platform fee', $earn, 'Average booking', 'Growth'],
            'formats' => ['text', 'int', 'int', 'money', 'money', 'money', 'money', 'money', 'money', 'pct'],
            'rows' => $out,
            'summary' => $this->moneySummary($rows, $earn),
            'chart' => ['type' => 'bar', 'format' => 'money', 'labels' => array_column($out, 0),
                'series' => [['name' => 'Net sales', 'data' => array_column($out, 5)], ['name' => $earn, 'data' => array_column($out, 7)]]],
        ];
    }

    /** @return object[] one row per day or month: bookings, delivered, failed, cancelled, sales, refunded, fee, net (kobo) */
    private function moneyBy(string $unit, ?int $op, CarbonImmutable $from, CarbonImmutable $to): array
    {
        $step = $unit === 'day' ? '1 day' : '1 month';
        $scope = $op ? 'AND o.operator_id = ?' : '';
        $b = [$from->toDateTimeString(), $to->toDateTimeString()];
        $sql = "
            WITH periods AS (SELECT generate_series(date_trunc('{$unit}', ?::timestamp), date_trunc('{$unit}', ?::timestamp), interval '{$step}')::date AS period),
            o AS (
                SELECT date_trunc('{$unit}', COALESCE(o.placed_at, o.created_at))::date AS period, o.total, s.status, COALESCE(h.refunded_amount, 0) AS refunded,
                       COALESCE(c.platform_fee, 0) AS fee, COALESCE(c.operator_net, 0) AS net
                FROM orders o
                LEFT JOIN shipments s ON s.order_id = o.id AND s.kind = 'forward'
                LEFT JOIN escrow_holds h ON h.agreement_id = o.agreement_id
                LEFT JOIN commissions c ON c.shipment_id = s.id
                WHERE o.is_test = false AND COALESCE(o.placed_at, o.created_at) BETWEEN ? AND ? {$scope}
            )
            SELECT p.period, COUNT(o.period) AS bookings,
                   COUNT(*) FILTER (WHERE o.status IN ('delivered','completed')) AS delivered,
                   COUNT(*) FILTER (WHERE o.status IN ('failed_attempt','returning','returned')) AS failed,
                   COUNT(*) FILTER (WHERE o.status = 'cancelled') AS cancelled,
                   COALESCE(SUM(o.total), 0) AS sales, COALESCE(SUM(o.refunded), 0) AS refunded,
                   COALESCE(SUM(o.fee), 0) AS fee, COALESCE(SUM(o.net), 0) AS net
            FROM periods p LEFT JOIN o ON o.period = p.period
            GROUP BY p.period ORDER BY p.period";

        return DB::select($sql, array_merge($b, $b, $op ? [$op] : []));
    }

    private function moneySummary(array $rows, string $earn): array
    {
        $sum = fn ($k) => array_sum(array_map(fn ($r) => (int) $r->{$k}, $rows));
        $bookings = $sum('bookings');

        return [
            ['Bookings', $bookings, 'int'], ['Delivered', $sum('delivered'), 'int'],
            ['Sales', $this->naira($sum('sales')), 'money'], ['Refunded', $this->naira($sum('refunded')), 'money'],
            ['Platform fee', $this->naira($sum('fee')), 'money'], [$earn, $this->naira($sum('net')), 'money'],
            ['Average booking', $bookings ? $this->naira(intdiv($sum('sales'), $bookings)) : 0, 'money'],
        ];
    }

    // ------------------------------------------------------------------ drivers

    /**
     * One row per driver: jobs finished and failed in the period (by the time they happened), offers taken, jobs
     * handed back, how long pickup to drop-off took, the customer's rating of the jobs they did, and their pay.
     */
    public function drivers(?int $op, CarbonImmutable $from, CarbonImmutable $to): array
    {
        $b = [$from->toDateTimeString(), $to->toDateTimeString()];
        $scope = $op ? 'AND d.operator_id = ?' : '';
        $rows = DB::select("
            WITH last_driver AS (
                SELECT DISTINCT ON (shipment_id) shipment_id, driver_profile_id FROM assignments
                WHERE status NOT IN ('reassigned', 'cancelled') ORDER BY shipment_id, id DESC
            ),
            done AS (
                SELECT ld.driver_profile_id, COUNT(*) AS n,
                       AVG(EXTRACT(EPOCH FROM (s.delivered_at - pu.at)) / 60) FILTER (WHERE pu.at IS NOT NULL AND s.delivered_at > pu.at) AS mins,
                       AVG(r.score) AS rating, COUNT(r.id) AS ratings
                FROM shipments s JOIN last_driver ld ON ld.shipment_id = s.id JOIN orders o ON o.id = s.order_id AND o.is_test = false
                LEFT JOIN LATERAL (SELECT MIN(e.created_at) AS at FROM shipment_events e WHERE e.shipment_id = s.id AND e.type = 'picked_up') pu ON true
                LEFT JOIN ratings r ON r.shipment_id = s.id AND r.ratee_type = 'operator' AND r.moderation_status = 'approved'
                WHERE s.delivered_at BETWEEN ? AND ? GROUP BY ld.driver_profile_id
            ),
            failed AS (SELECT e.actor_id AS driver_profile_id, COUNT(*) AS n FROM shipment_events e
                WHERE e.type = 'delivery_failed' AND e.actor_type = 'driver' AND e.created_at BETWEEN ? AND ? GROUP BY e.actor_id),
            released AS (SELECT e.actor_id AS driver_profile_id, COUNT(*) AS n FROM shipment_events e
                WHERE e.type = 'driver_released' AND e.actor_type = 'driver' AND e.created_at BETWEEN ? AND ? GROUP BY e.actor_id),
            offers AS (SELECT driver_profile_id, COUNT(*) FILTER (WHERE response = 'accepted') AS accepted,
                       COUNT(*) FILTER (WHERE response IN ('accepted','declined','timeout')) AS answered
                FROM dispatch_offers WHERE offered_at BETWEEN ? AND ? GROUP BY driver_profile_id),
            pay AS (SELECT driver_profile_id, SUM(payout_amount) AS amount FROM assignments WHERE pay_posted_at BETWEEN ? AND ? GROUP BY driver_profile_id)
            SELECT d.id, u.name, op.display_name AS company, d.status, d.availability,
                   COALESCE(done.n, 0) AS done, COALESCE(failed.n, 0) AS failed, COALESCE(released.n, 0) AS released,
                   offers.accepted, offers.answered, done.mins, done.rating, COALESCE(done.ratings, 0) AS ratings, COALESCE(pay.amount, 0) AS pay
            FROM driver_profiles d JOIN users u ON u.id = d.user_id LEFT JOIN operators op ON op.id = d.operator_id
            LEFT JOIN done ON done.driver_profile_id = d.id LEFT JOIN failed ON failed.driver_profile_id = d.id
            LEFT JOIN released ON released.driver_profile_id = d.id LEFT JOIN offers ON offers.driver_profile_id = d.id
            LEFT JOIN pay ON pay.driver_profile_id = d.id
            WHERE d.deleted_at IS NULL {$scope}
              AND (d.status = 'active' OR done.n IS NOT NULL OR failed.n IS NOT NULL OR offers.answered IS NOT NULL)
            ORDER BY COALESCE(done.n, 0) DESC, u.name
            LIMIT 500", array_merge($b, $b, $b, $b, $b, $op ? [$op] : []));

        // On time against the deadline, window or first promised arrival, and a 0-100 reliability score (InsightsService).
        $timing = app(InsightsService::class)->onTime($op, $from, $to);
        $out = array_map(function ($r) use ($op, $timing) {
            $attempts = (int) $r->done + (int) $r->failed;
            $t = $timing[(int) $r->id] ?? ['timed' => 0, 'on_time' => 0];
            $rel = InsightsService::reliabilityScore(['offers' => (int) $r->answered, 'offers_taken' => (int) $r->accepted, 'delivered' => (int) $r->done,
                'failed' => (int) $r->failed, 'dropped' => (int) $r->released] + $t);
            $row = [$r->name];
            if (! $op) {
                $row[] = $r->company;
            }

            return array_merge($row, [(int) $r->done, (int) $r->failed, $attempts ? round((int) $r->done * 100 / $attempts, 1) : null,
                $r->answered ? round((int) $r->accepted * 100 / (int) $r->answered, 1) : null, (int) $r->released,
                $r->mins !== null ? (int) round((float) $r->mins) : null, $r->rating !== null ? round((float) $r->rating, 2) : null, (int) $r->ratings,
                $this->naira($r->pay), $rel['on_time_rate'], $rel['enough'] ? $rel['score'] : null, str_replace('_', ' ', (string) $r->availability)]);
        }, $rows);

        $headings = array_merge(['Driver'], $op ? [] : ['Company'], ['Delivered', 'Failed', 'Success rate', 'Offers accepted', 'Handed back', 'Pickup to drop-off', 'Rating', 'Ratings', 'Pay', 'On time', 'Reliability', 'Now']);
        $formats = array_merge(['text'], $op ? [] : ['text'], ['int', 'int', 'pct', 'pct', 'int', 'minutes', 'rating', 'int', 'money', 'pct', 'int', 'text']);
        $done = array_sum(array_map(fn ($r) => (int) $r->done, $rows));
        $rated = array_filter($rows, fn ($r) => $r->rating !== null);
        $mins = array_filter($rows, fn ($r) => $r->mins !== null);
        $col = fn ($name) => array_search($name, $headings, true);
        $top = array_slice(array_filter($out, fn ($r) => $r[$col('Delivered')] > 0), 0, 12);

        return [
            'headings' => $headings, 'formats' => $formats, 'rows' => $out,
            'summary' => [
                ['Drivers', count($rows), 'int'], ['Deliveries', $done, 'int'],
                ['Failed', array_sum(array_map(fn ($r) => (int) $r->failed, $rows)), 'int'],
                ['Average rating', $rated ? round(array_sum(array_map(fn ($r) => (float) $r->rating * (int) $r->ratings, $rated)) / max(1, array_sum(array_map(fn ($r) => (int) $r->ratings, $rated))), 2) : null, 'rating'],
                ['Average pickup to drop-off', $mins ? (int) round(array_sum(array_map(fn ($r) => (float) $r->mins, $mins)) / count($mins)) : null, 'minutes'],
            ],
            'chart' => $top ? ['type' => 'bar', 'format' => 'int', 'labels' => array_column($top, 0),
                'series' => [['name' => 'Delivered', 'data' => array_column($top, $col('Delivered'))], ['name' => 'Failed', 'data' => array_column($top, $col('Failed'))]]] : null,
        ];
    }

    // ------------------------------------------------------------------ customers

    public function customers(?int $op, CarbonImmutable $from, CarbonImmutable $to): array
    {
        $scope = $op ? 'AND o.operator_id = ?' : '';
        $p = $op ? [$op] : [];
        $b = [$from->toDateTimeString(), $to->toDateTimeString()];
        $rows = DB::select("
            WITH firsts AS (SELECT o.customer_id, MIN(COALESCE(o.placed_at, o.created_at)) AS first_at FROM orders o WHERE o.is_test = false {$scope} GROUP BY o.customer_id)
            SELECT u.name, u.email, COUNT(o.id) AS orders, SUM(o.total) AS spend, f.first_at, MAX(COALESCE(o.placed_at, o.created_at)) AS last_at,
                   COUNT(o.id) FILTER (WHERE s.status IN ('delivered','completed')) AS delivered
            FROM orders o JOIN users u ON u.id = o.customer_id JOIN firsts f ON f.customer_id = o.customer_id
            LEFT JOIN shipments s ON s.order_id = o.id AND s.kind = 'forward'
            WHERE o.is_test = false AND COALESCE(o.placed_at, o.created_at) BETWEEN ? AND ? {$scope}
            GROUP BY u.id, u.name, u.email, f.first_at ORDER BY SUM(o.total) DESC, COUNT(o.id) DESC LIMIT 500", array_merge($p, $b, $p));

        $new = count(array_filter($rows, fn ($r) => $r->first_at >= $b[0]));
        $orders = array_sum(array_map(fn ($r) => (int) $r->orders, $rows));
        $spend = array_sum(array_map(fn ($r) => (int) $r->spend, $rows));
        $repeat = count(array_filter($rows, fn ($r) => (int) $r->orders > 1));

        return [
            'headings' => ['Customer', 'Email', 'Bookings', 'Delivered', 'Spent', 'Average booking', 'First booking', 'Latest booking', 'New in period'],
            'formats' => ['text', 'text', 'int', 'int', 'money', 'money', 'date', 'date', 'text'],
            'rows' => array_map(fn ($r) => [$r->name, str_ends_with((string) $r->email, '@phone.invalid') ? '' : $r->email, (int) $r->orders, (int) $r->delivered,
                $this->naira($r->spend), $this->naira(intdiv((int) $r->spend, max(1, (int) $r->orders))),
                substr((string) $r->first_at, 0, 10), substr((string) $r->last_at, 0, 10), $r->first_at >= $b[0] ? 'Yes' : 'No'], $rows),
            'summary' => [
                ['Customers who booked', count($rows), 'int'], ['New customers', $new, 'int'], ['Returning customers', count($rows) - $new, 'int'],
                ['Booked more than once', count($rows) ? round($repeat * 100 / count($rows), 1) : null, 'pct'],
                ['Bookings per customer', count($rows) ? round($orders / count($rows), 2) : null, 'text'],
                ['Spend per customer', count($rows) ? $this->naira(intdiv($spend, count($rows))) : 0, 'money'],
            ],
            'chart' => null,
        ];
    }

    /**
     * Customers grouped by the month of their first booking (cohorts starting inside the period), and the share who
     * booked again one, two and three months later.
     */
    public function retention(?int $op, CarbonImmutable $from, CarbonImmutable $to): array
    {
        $scope = $op ? 'AND o.operator_id = ?' : '';
        $p = $op ? [$op] : [];
        $rows = DB::select("
            WITH o AS (SELECT o.customer_id, date_trunc('month', COALESCE(o.placed_at, o.created_at))::date AS m FROM orders o WHERE o.is_test = false {$scope}),
            first AS (SELECT customer_id, MIN(m) AS cohort FROM o GROUP BY customer_id),
            act AS (SELECT DISTINCT o.customer_id, f.cohort,
                    (EXTRACT(YEAR FROM age(o.m, f.cohort)) * 12 + EXTRACT(MONTH FROM age(o.m, f.cohort)))::int AS k
                    FROM o JOIN first f ON f.customer_id = o.customer_id)
            SELECT cohort, COUNT(DISTINCT customer_id) AS customers,
                   COUNT(DISTINCT customer_id) FILTER (WHERE k = 1) AS m1, COUNT(DISTINCT customer_id) FILTER (WHERE k = 2) AS m2,
                   COUNT(DISTINCT customer_id) FILTER (WHERE k = 3) AS m3, COUNT(DISTINCT customer_id) FILTER (WHERE k >= 1) AS ever
            FROM act WHERE cohort BETWEEN ? AND ? GROUP BY cohort ORDER BY cohort",
            array_merge($p, [$from->startOfMonth()->toDateString(), $to->toDateString()]));

        $now = CarbonImmutable::now()->startOfMonth();
        $pct = function ($n, $total, $cohort, $k) use ($now) {
            // A month that has not happened yet is blank, not zero.
            return CarbonImmutable::parse($cohort)->addMonths($k)->gt($now) ? null : round((int) $n * 100 / max(1, (int) $total), 1);
        };
        $out = array_map(fn ($r) => [CarbonImmutable::parse($r->cohort)->format('M Y'), (int) $r->customers,
            $pct($r->m1, $r->customers, $r->cohort, 1), $pct($r->m2, $r->customers, $r->cohort, 2), $pct($r->m3, $r->customers, $r->cohort, 3),
            round((int) $r->ever * 100 / max(1, (int) $r->customers), 1)], $rows);
        $total = array_sum(array_map(fn ($r) => (int) $r->customers, $rows));
        $ever = array_sum(array_map(fn ($r) => (int) $r->ever, $rows));

        return [
            'headings' => ['First booked in', 'New customers', 'Back after 1 month', 'After 2 months', 'After 3 months', 'Came back at all'],
            'formats' => ['text', 'int', 'pct', 'pct', 'pct', 'pct'],
            'rows' => $out,
            'summary' => [['New customers', $total, 'int'], ['Came back at least once', $total ? round($ever * 100 / $total, 1) : null, 'pct']],
            'chart' => $out ? ['type' => 'line', 'format' => 'pct', 'labels' => array_column($out, 0),
                'series' => [['name' => 'Back after 1 month', 'data' => array_column($out, 2)], ['name' => 'Came back at all', 'data' => array_column($out, 5)]]] : null,
        ];
    }

    // ------------------------------------------------------------------ routes

    /**
     * Pickup area to drop-off area pairs, busiest first. An area is the smallest neighbourhood-sized zone (up to 60 km²) the
     * point falls in, so a whole-city zone does not swallow every route; outside those it is a square of about one
     * kilometre, named after the address seen there most often.
     */
    public function routes(?int $op, CarbonImmutable $from, CarbonImmutable $to): array
    {
        $scope = $op ? 'AND o.operator_id = ?' : '';
        $area = fn ($col) => "COALESCE((SELECT z.name FROM zones z WHERE z.active AND ST_Area(z.boundary) <= 60000000 AND ST_Covers(z.boundary, {$col}) ORDER BY z.priority DESC, ST_Area(z.boundary), z.id LIMIT 1),
            'cell:' || round(ST_Y({$col}::geometry)::numeric, 2) || ',' || round(ST_X({$col}::geometry)::numeric, 2))";
        $rows = DB::select("
            WITH j AS (
                SELECT s.id, s.distance_m, o.total, s.delivered_at, o.placed_at, p.line1 AS pl, d.line1 AS dl, {$area('p.point')} AS from_key, {$area('d.point')} AS to_key
                FROM shipments s JOIN orders o ON o.id = s.order_id
                JOIN LATERAL (SELECT line1, point FROM shipment_stops WHERE shipment_id = s.id AND type = 'pickup' ORDER BY seq LIMIT 1) p ON true
                JOIN LATERAL (SELECT line1, point FROM shipment_stops WHERE shipment_id = s.id AND type = 'dropoff' ORDER BY seq DESC LIMIT 1) d ON true
                WHERE o.is_test = false AND s.kind = 'forward' AND COALESCE(o.placed_at, o.created_at) BETWEEN ? AND ? {$scope}
            )
            SELECT from_key, to_key, COUNT(*) AS n, AVG(distance_m) AS dist, AVG(total) AS price,
                   AVG(EXTRACT(EPOCH FROM (delivered_at - placed_at)) / 60) FILTER (WHERE delivered_at IS NOT NULL) AS mins,
                   mode() WITHIN GROUP (ORDER BY pl) AS from_eg, mode() WITHIN GROUP (ORDER BY dl) AS to_eg
            FROM j GROUP BY from_key, to_key ORDER BY COUNT(*) DESC, AVG(total) DESC LIMIT 50",
            array_merge([$from->toDateTimeString(), $to->toDateTimeString()], $op ? [$op] : []));

        $name = fn ($key, $eg) => str_starts_with($key, 'cell:') ? 'Near '.$eg : $key;
        $out = array_map(fn ($r) => [$name($r->from_key, $r->from_eg), $name($r->to_key, $r->to_eg), (int) $r->n,
            $r->dist !== null ? round((float) $r->dist / 1000, 1) : null, $this->naira((int) round((float) $r->price)),
            $r->mins !== null ? (int) round((float) $r->mins) : null], $rows);
        $top = array_slice($out, 0, 10);

        return [
            'headings' => ['From', 'To', 'Deliveries', 'Average distance', 'Average price', 'Average booking to delivery'],
            'formats' => ['text', 'text', 'int', 'km', 'money', 'minutes'],
            'rows' => $out,
            'summary' => [['Routes', count($out), 'int'], ['Deliveries on the top 10', array_sum(array_column($top, 2)), 'int']],
            'chart' => $top ? ['type' => 'bar', 'horizontal' => true, 'format' => 'int', 'labels' => array_map(fn ($r) => mb_strimwidth($r[0], 0, 28, '…').' → '.mb_strimwidth($r[1], 0, 28, '…'), $top),
                'series' => [['name' => 'Deliveries', 'data' => array_column($top, 2)]]] : null,
        ];
    }

    // ------------------------------------------------------------------ failures and timing

    public function failed(?int $op, CarbonImmutable $from, CarbonImmutable $to): array
    {
        $scope = $op ? 'AND s.operator_id = ?' : '';
        $b = [$from->toDateTimeString(), $to->toDateTimeString()];
        $p = $op ? [$op] : [];
        $rows = DB::select("
            WITH days AS (SELECT generate_series(?::date, ?::date, interval '1 day')::date AS day),
            f AS (SELECT e.created_at::date AS day, e.meta FROM shipment_events e JOIN shipments s ON s.id = e.shipment_id JOIN orders o ON o.id = s.order_id
                  WHERE e.type = 'delivery_failed' AND o.is_test = false AND e.created_at BETWEEN ? AND ? {$scope}),
            ok AS (SELECT s.delivered_at::date AS day, COUNT(*) AS n FROM shipments s JOIN orders o ON o.id = s.order_id
                  WHERE o.is_test = false AND s.delivered_at BETWEEN ? AND ? {$scope} GROUP BY 1)
            SELECT d.day, COUNT(f.day) AS failed, COALESCE(MAX(ok.n), 0) AS delivered,
                   COALESCE(SUM((f.meta->>'charged')::bigint), 0) AS charged, COALESCE(SUM((f.meta->>'refunded')::bigint), 0) AS refunded,
                   mode() WITHIN GROUP (ORDER BY f.meta->>'reason') AS reason
            FROM days d LEFT JOIN f ON f.day = d.day LEFT JOIN ok ON ok.day = d.day GROUP BY d.day ORDER BY d.day",
            array_merge([$from->toDateString(), $to->toDateString()], $b, $p, $b, $p));

        $reasons = \App\Modules\Marketplace\FailedDeliveryService::REASONS;
        $label = fn ($k) => $k ? ($reasons[$k] ?? str_replace('_', ' ', $k)) : '';
        $byReason = DB::select("SELECT e.meta->>'reason' AS reason, COUNT(*) AS n FROM shipment_events e JOIN shipments s ON s.id = e.shipment_id JOIN orders o ON o.id = s.order_id
            WHERE e.type = 'delivery_failed' AND o.is_test = false AND e.created_at BETWEEN ? AND ? {$scope} GROUP BY 1 ORDER BY 2 DESC", array_merge($b, $p));
        $failed = array_sum(array_map(fn ($r) => (int) $r->failed, $rows));
        $delivered = array_sum(array_map(fn ($r) => (int) $r->delivered, $rows));

        $summary = [['Failed', $failed, 'int'], ['Failure rate', ($failed + $delivered) ? round($failed * 100 / ($failed + $delivered), 1) : null, 'pct'],
            ['Kept as charges', $this->naira(array_sum(array_map(fn ($r) => (int) $r->charged, $rows))), 'money'],
            ['Refunded', $this->naira(array_sum(array_map(fn ($r) => (int) $r->refunded, $rows))), 'money']];
        foreach (array_slice($byReason, 0, 4) as $r) {
            $summary[] = [ucfirst($label($r->reason)), (int) $r->n, 'int'];
        }

        return [
            'headings' => ['Date', 'Delivered', 'Failed', 'Failure rate', 'Kept as charges', 'Refunded', 'Most common reason'],
            'formats' => ['date', 'int', 'int', 'pct', 'money', 'money', 'text'],
            'rows' => array_map(fn ($r) => [$r->day, (int) $r->delivered, (int) $r->failed,
                ((int) $r->failed + (int) $r->delivered) ? round((int) $r->failed * 100 / ((int) $r->failed + (int) $r->delivered), 1) : null,
                $this->naira($r->charged), $this->naira($r->refunded), ucfirst($label($r->reason))], $rows),
            'summary' => $summary,
            'chart' => ['type' => 'bar', 'format' => 'int', 'stacked' => true, 'labels' => array_map(fn ($r) => CarbonImmutable::parse($r->day)->format('j M'), $rows),
                'series' => [['name' => 'Delivered', 'data' => array_map(fn ($r) => (int) $r->delivered, $rows)], ['name' => 'Failed', 'data' => array_map(fn ($r) => (int) $r->failed, $rows)]]],
        ];
    }

    /**
     * How long deliveries took, by the day they were delivered: from booking, and from pickup. On time means before the
     * job's deliver-by time when it has one, otherwise within the service's promised minutes.
     */
    public function times(?int $op, CarbonImmutable $from, CarbonImmutable $to): array
    {
        $scope = $op ? 'AND s.operator_id = ?' : '';
        $rows = DB::select("
            WITH days AS (SELECT generate_series(?::date, ?::date, interval '1 day')::date AS day),
            j AS (
                SELECT s.delivered_at::date AS day,
                       EXTRACT(EPOCH FROM (s.delivered_at - COALESCE(o.placed_at, o.created_at))) / 60 AS total_m,
                       EXTRACT(EPOCH FROM (s.delivered_at - pu.at)) / 60 AS ride_m,
                       CASE WHEN s.deliver_by IS NOT NULL THEN s.delivered_at <= s.deliver_by
                            WHEN st.sla_minutes IS NOT NULL THEN s.delivered_at <= COALESCE(o.placed_at, o.created_at) + make_interval(mins => st.sla_minutes) END AS on_time
                FROM shipments s JOIN orders o ON o.id = s.order_id LEFT JOIN service_types st ON st.id = s.service_type_id
                LEFT JOIN LATERAL (SELECT MIN(e.created_at) AS at FROM shipment_events e WHERE e.shipment_id = s.id AND e.type = 'picked_up') pu ON true
                WHERE o.is_test = false AND s.delivered_at BETWEEN ? AND ? {$scope}
            )
            SELECT d.day, COUNT(j.day) AS n, AVG(j.total_m) FILTER (WHERE j.total_m >= 0) AS total_m,
                   percentile_cont(0.5) WITHIN GROUP (ORDER BY j.total_m) FILTER (WHERE j.total_m >= 0) AS median_m,
                   AVG(j.ride_m) FILTER (WHERE j.ride_m >= 0) AS ride_m,
                   COUNT(*) FILTER (WHERE j.on_time) AS on_time, COUNT(*) FILTER (WHERE j.on_time IS NOT NULL) AS judged
            FROM days d LEFT JOIN j ON j.day = d.day GROUP BY d.day ORDER BY d.day",
            array_merge([$from->toDateString(), $to->toDateString(), $from->toDateTimeString(), $to->toDateTimeString()], $op ? [$op] : []));

        $mins = fn ($v) => $v !== null ? (int) round((float) $v) : null;
        $n = array_sum(array_map(fn ($r) => (int) $r->n, $rows));
        $weighted = fn ($k) => $n ? (int) round(array_sum(array_map(fn ($r) => (float) $r->{$k} * (int) $r->n, array_filter($rows, fn ($r) => $r->{$k} !== null))) / max(1, array_sum(array_map(fn ($r) => (int) $r->n, array_filter($rows, fn ($r) => $r->{$k} !== null))))) : null;
        $judged = array_sum(array_map(fn ($r) => (int) $r->judged, $rows));

        return [
            'headings' => ['Date', 'Delivered', 'Booking to delivery', 'Median', 'Pickup to drop-off', 'On time'],
            'formats' => ['date', 'int', 'minutes', 'minutes', 'minutes', 'pct'],
            'rows' => array_map(fn ($r) => [$r->day, (int) $r->n, $mins($r->total_m), $mins($r->median_m), $mins($r->ride_m),
                (int) $r->judged ? round((int) $r->on_time * 100 / (int) $r->judged, 1) : null], $rows),
            'summary' => [['Delivered', $n, 'int'], ['Booking to delivery', $weighted('total_m'), 'minutes'], ['Pickup to drop-off', $weighted('ride_m'), 'minutes'],
                ['On time', $judged ? round(array_sum(array_map(fn ($r) => (int) $r->on_time, $rows)) * 100 / $judged, 1) : null, 'pct']],
            'chart' => ['type' => 'line', 'format' => 'minutes', 'labels' => array_map(fn ($r) => CarbonImmutable::parse($r->day)->format('j M'), $rows),
                'series' => [['name' => 'Booking to delivery', 'data' => array_map(fn ($r) => $mins($r->total_m), $rows)], ['name' => 'Pickup to drop-off', 'data' => array_map(fn ($r) => $mins($r->ride_m), $rows)]]],
        ];
    }

    // ------------------------------------------------------------------ profit and fleet

    /**
     * What each delivered job earned the provider (after any platform fee) less the rider's pay and the running cost from
     * the provider's cost profile on the job's distance. Without a cost profile only the rider's pay counts as a cost.
     */
    public function profit(?int $op, CarbonImmutable $from, CarbonImmutable $to): array
    {
        $p = app(InsightsService::class)->profit($op, $from, $to);
        $t = $p['totals'];
        $flag = ['loss' => 'Loss', 'thin_margin' => 'Thin margin'];
        $rows = array_map(fn ($j) => array_merge([CarbonImmutable::parse($j['delivered_at'])->toDateString(), $j['order_number']], $op ? [] : [$j['company']], [
            $j['rider'] ?? '', round($j['distance_m'] / 1000, 1), $this->naira($j['earned']), $this->naira($j['rider_pay']), $this->naira($j['running_cost']),
            $this->naira($j['profit']), $j['margin_bp'] === null ? null : round($j['margin_bp'] / 100, 1), $flag[$j['flag']] ?? '', $j['profile'] ?? 'No cost profile',
        ]), $p['jobs']);

        $byDay = [];
        foreach ($p['jobs'] as $j) {
            $d = CarbonImmutable::parse($j['delivered_at'])->toDateString();
            $byDay[$d] = ($byDay[$d] ?? 0) + $j['profit'];
        }
        ksort($byDay);

        return [
            'headings' => array_merge(['Date', 'Order'], $op ? [] : ['Company'], ['Rider', 'Distance', 'Earned', 'Rider pay', 'Running cost', 'Profit', 'Margin', 'Flag', 'Cost profile']),
            'formats' => array_merge(['date', 'text'], $op ? [] : ['text'], ['text', 'km', 'money', 'money', 'money', 'money', 'pct', 'text', 'text']),
            'rows' => $rows,
            'summary' => [
                ['Delivered jobs', $t['jobs'], 'int'], ['Earned', $this->naira($t['earned']), 'money'], ['Costs', $this->naira($t['cost']), 'money'],
                ['Profit', $this->naira($t['profit']), 'money'], ['Margin', $t['margin_bp'] === null ? null : round($t['margin_bp'] / 100, 1), 'pct'],
                ['Profit per km', $t['profit_per_km'] === null ? null : $this->naira($t['profit_per_km']), 'money'],
                ['Jobs that lost money', $t['loss_jobs'], 'int'], ['On a thin margin', $t['thin_jobs'], 'int'],
            ],
            'chart' => $byDay ? ['type' => 'bar', 'format' => 'money', 'labels' => array_map(fn ($d) => CarbonImmutable::parse($d)->format('j M'), array_keys($byDay)),
                'series' => [['name' => 'Profit', 'data' => array_map(fn ($k) => $this->naira($k), array_values($byDay))]]] : null,
            'note' => $p['has_profile'] ? null : 'No cost profile yet, so only rider pay is counted as a cost. Add fuel and running costs in the Pricing calculator to see real profit.',
        ];
    }

    /** Every vehicle: jobs, kilometres, days on the road, earnings, and insurance or roadworthiness papers due. */
    public function fleet(?int $op, CarbonImmutable $from, CarbonImmutable $to): array
    {
        $f = app(InsightsService::class)->fleet($op, $from, $to);
        $s = $f['summary'];
        $rows = array_map(fn ($v) => array_merge([$v['plate']], $op ? [] : [$v['company']], [$v['name'], $v['rider'] ?? 'Not assigned', $v['jobs'], $v['km'], $v['days_used'],
            $v['use_rate'], $this->naira($v['earned']), implode('; ', array_column($v['papers'], 'label')), $v['status']]), $f['vehicles']);
        $used = array_values(array_filter($f['vehicles'], fn ($v) => $v['jobs'] > 0));
        usort($used, fn ($a, $b) => $b['jobs'] <=> $a['jobs']);
        $used = array_slice($used, 0, 12);

        return [
            'headings' => array_merge(['Vehicle'], $op ? [] : ['Company'], ['Make', 'Rider', 'Jobs', 'Distance', 'Days used', 'Share of days', 'Earned', 'Papers', 'Status']),
            'formats' => array_merge(['text'], $op ? [] : ['text'], ['text', 'text', 'int', 'km', 'int', 'pct', 'money', 'text', 'text']),
            'rows' => $rows,
            'summary' => [['Vehicles', $s['vehicles'], 'int'], ['Did no jobs', $s['idle'], 'int'], ['Papers to check', $s['papers_due'], 'int'], ['Distance', $s['km'], 'km']],
            'chart' => $used ? ['type' => 'bar', 'format' => 'int', 'labels' => array_column($used, 'plate'), 'series' => [['name' => 'Jobs', 'data' => array_column($used, 'jobs')]]] : null,
        ];
    }

    // ------------------------------------------------------------------ dashboard tiles

    /** Today against yesterday and the last 14 days of sales, for the revenue tiles on the dashboards. */
    public function revenueSnapshot(?int $op): array
    {
        $tz = config('app.timezone', 'Africa/Lagos');
        $today = CarbonImmutable::now($tz);
        $days = $this->moneyBy('day', $op, $today->subDays(13)->startOfDay(), $today->endOfDay());
        $last = end($days) ?: null;
        $prev = count($days) > 1 ? $days[count($days) - 2] : null;

        return [
            'today' => $last, 'yesterday' => $prev,
            'labels' => array_map(fn ($r) => CarbonImmutable::parse($r->period)->format('j M'), $days),
            'sales' => array_map(fn ($r) => $this->naira($r->sales), $days),
            'net' => array_map(fn ($r) => $this->naira($r->net), $days),
            'bookings' => array_map(fn ($r) => (int) $r->bookings, $days),
        ];
    }

    /** A value as the pages and the PDF show it. */
    public static function show($v, string $format): string
    {
        if ($v === null || $v === '') {
            return '—';
        }

        return match ($format) {
            'money' => '₦'.number_format((float) $v, ((float) $v == floor((float) $v)) ? 0 : 2),
            'int' => number_format((int) $v),
            'pct' => rtrim(rtrim(number_format((float) $v, 1), '0'), '.').'%',
            'minutes' => (int) $v >= 120 ? intdiv((int) $v, 60).' h '.((int) $v % 60).' min' : ((int) $v).' min',
            'km' => number_format((float) $v, 1).' km',
            'rating' => number_format((float) $v, 2).' ★',
            'date' => CarbonImmutable::parse((string) $v)->format('D j M Y'),
            default => (string) $v,
        };
    }

    private function naira($kobo): float
    {
        return round(((int) $kobo) / 100, 2);
    }
}
