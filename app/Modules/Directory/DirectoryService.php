<?php

namespace App\Modules\Directory;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * Find providers, riders and shoppers by @handle or name.
 *
 * One handle namespace for everyone: a business's handle is its public page slug, a company rider's is
 * driver_profiles.handle. Only listed, active businesses (and the active riders of those businesses) can be found.
 * Riders never show where they are: only whether they are online and, when the customer shares their position,
 * a rounded distance.
 */
class DirectoryService
{
    public const KINDS = ['company' => 'Delivery company', 'rider' => 'Rider', 'shopper' => 'Shopper'];

    /** Operator type → what customers see. */
    public const TYPE_KIND = ['company' => 'company', 'franchise' => 'company', 'independent_driver' => 'rider', 'market_shopper' => 'shopper'];

    public const HANDLE_RULE = '/^[a-z0-9][a-z0-9_-]{2,29}$/';

    public static function normalize(?string $h): string
    {
        return strtolower(trim(ltrim(trim((string) $h), '@')));
    }

    /** Whether a handle is free across businesses and riders (optionally ignoring the one being changed). */
    public function available(string $handle, ?int $exceptDriverId = null, ?int $exceptOperatorId = null): bool
    {
        $h = self::normalize($handle);

        return ! DB::table('provider_profiles')->whereRaw('lower(public_slug) = ?', [$h])->when($exceptOperatorId, fn ($q) => $q->where('operator_id', '!=', $exceptOperatorId))->exists()
            && ! DB::table('driver_profiles')->where('handle', $h)->when($exceptDriverId, fn ($q) => $q->where('id', '!=', $exceptDriverId))->exists();
    }

    /** Checks and normalises a handle someone typed; throws with a message people can act on. */
    public function validate(string $handle, ?int $exceptDriverId = null, ?int $exceptOperatorId = null): string
    {
        $h = self::normalize($handle);
        if (! preg_match(self::HANDLE_RULE, $h)) {
            throw new InvalidArgumentException('Use 3 to 30 letters, numbers, - or _, starting with a letter or number.');
        }
        if (in_array($h, ['admin', 'support', 'help', 'ops', 'staff', 'system', 'everyone', 'all'], true)) {
            throw new InvalidArgumentException('That handle is reserved.');
        }
        if (! $this->available($h, $exceptDriverId, $exceptOperatorId)) {
            throw new InvalidArgumentException("@{$h} is taken. Try another.");
        }

        return $h;
    }

    /** A free handle built from a name, e.g. "musa" or "musa482". */
    public function suggest(string $name): string
    {
        $base = substr(preg_replace('/[^a-z0-9]/', '', strtolower(Str::ascii(strtok($name, ' ') ?: 'rider'))) ?: 'rider', 0, 20);
        $h = strlen($base) >= 3 ? $base : $base.'rider';
        while (! $this->available($h)) {
            $h = $base.random_int(10, 9999);
        }

        return $h;
    }

    /** Give a rider a handle if they have none yet (riders added after the handles feature). */
    public function ensureDriverHandle(int $driverProfileId): ?string
    {
        $d = DB::table('driver_profiles as d')->join('users as u', 'u.id', '=', 'd.user_id')->where('d.id', $driverProfileId)->first(['d.handle', 'u.name']);
        if (! $d) {
            return null;
        }
        if ($d->handle) {
            return $d->handle;
        }
        $h = $this->suggest((string) $d->name);
        DB::table('driver_profiles')->where('id', $driverProfileId)->whereNull('handle')->update(['handle' => $h, 'updated_at' => now()]);

        return $h;
    }

    /**
     * @param  string  $q  "@musa", "@swift", "swift haulage"…
     * @param  string|null  $kind  company | rider | shopper (null: everyone)
     * @return array<int, array>
     */
    public function search(string $q, ?string $kind = null, ?float $lat = null, ?float $lng = null, int $limit = 20): array
    {
        $raw = trim($q);
        $handleOnly = str_starts_with($raw, '@');
        $term = self::normalize($raw);
        $kind = isset(self::KINDS[$kind ?? '']) ? $kind : null;
        $like = '%'.str_replace(['%', '_'], ['\%', '\_'], $term).'%';
        $prefix = str_replace(['%', '_'], ['\%', '\_'], $term).'%';
        $types = $kind ? array_keys(array_filter(self::TYPE_KIND, fn ($k) => $k === $kind)) : array_keys(self::TYPE_KIND);
        $out = [];

        // Businesses: companies, independent riders and shoppers.
        $biz = DB::table('provider_profiles as p')->join('operators as o', 'o.id', '=', 'p.operator_id')
            ->where('p.listed', true)->where('o.status', 'active')->whereIn('o.type', $types)
            ->when($term !== '', fn ($w) => $w->where(fn ($x) => $x->whereRaw('lower(p.public_slug) LIKE ?', [$prefix])
                ->when(! $handleOnly, fn ($y) => $y->orWhere('o.display_name', 'ilike', $like)->orWhere('p.headline', 'ilike', $like))))
            ->orderByRaw('lower(p.public_slug) = ? DESC', [$term])
            ->orderByRaw("CASE p.tier WHEN 'preferred' THEN 0 WHEN 'trusted' THEN 1 WHEN 'verified' THEN 2 ELSE 3 END")->orderByDesc('p.rating_avg')
            ->limit($limit)->get(['o.id', 'o.type', 'o.display_name', 'p.public_slug', 'p.headline', 'p.logo', 'p.tier', 'p.rating_avg', 'p.rating_count', 'p.jobs_completed', 'p.availability_status']);
        foreach ($biz as $b) {
            $online = $this->onlineRiders((int) $b->id, $lat, $lng);
            $out[] = [
                'kind' => self::TYPE_KIND[$b->type] ?? 'company', 'kind_label' => self::KINDS[self::TYPE_KIND[$b->type] ?? 'company'],
                'handle' => $b->public_slug, 'name' => $b->display_name,
                'subtitle' => $b->headline ?: ($b->type === 'independent_driver' ? 'Independent rider' : ($b->type === 'market_shopper' ? 'Market shopper' : 'Delivery company')),
                'rating' => $b->rating_avg !== null ? round((float) $b->rating_avg, 1) : null, 'rating_count' => (int) $b->rating_count,
                'jobs' => (int) $b->jobs_completed, 'tier' => $b->tier,
                'accepting' => $b->availability_status === 'accepting', 'online' => $online['count'] > 0, 'riders_online' => $online['count'],
                'distance_km' => $online['nearest_km'],
                'book' => ['operator_id' => (int) $b->id, 'provider' => $b->display_name, 'slug' => $b->public_slug, 'driver_handle' => null],
                'exact' => strtolower($b->public_slug) === $term,
            ];
        }

        // Riders who work for a company, found by their own handle or first name.
        if ($kind === null || $kind === 'rider') {
            $riders = DB::table('driver_profiles as d')->join('users as u', 'u.id', '=', 'd.user_id')->join('operators as o', 'o.id', '=', 'd.operator_id')
                ->join('provider_profiles as p', 'p.operator_id', '=', 'o.id')
                ->where('d.status', 'active')->whereNull('d.deleted_at')->whereNotNull('d.handle')
                ->where('o.status', 'active')->where('p.listed', true)->whereIn('o.type', ['company', 'franchise'])
                ->when($term !== '', fn ($w) => $w->where(fn ($x) => $x->where('d.handle', 'like', $prefix)
                    ->when(! $handleOnly, fn ($y) => $y->orWhere('u.name', 'ilike', $term.'%'))))
                ->orderByRaw('d.handle = ? DESC', [$term])->orderByDesc('d.rating_avg')->limit($limit)
                ->selectRaw('d.id, d.handle, d.availability, d.last_seen_at, d.rating_avg, d.rating_count, u.name, o.id AS operator_id, o.display_name AS company, p.public_slug AS company_slug')
                ->when($lat !== null && $lng !== null, fn ($q) => $q->selectRaw('ST_Distance(d.last_point, ST_SetSRID(ST_MakePoint(?, ?), 4326)::geography) AS dist_m', [$lng, $lat]))
                ->get();
            foreach ($riders as $r) {
                $online = in_array($r->availability, ['online', 'on_job'], true) && $r->last_seen_at && now()->diffInMinutes($r->last_seen_at, true) <= 15;
                $out[] = [
                    'kind' => 'rider', 'kind_label' => 'Rider', 'handle' => $r->handle, 'name' => Str::before((string) $r->name, ' ') ?: 'Rider',
                    'subtitle' => "Rider at {$r->company}", 'company' => $r->company, 'company_handle' => $r->company_slug,
                    'rating' => $r->rating_avg !== null ? round((float) $r->rating_avg, 1) : null, 'rating_count' => (int) $r->rating_count, 'jobs' => null, 'tier' => null,
                    'accepting' => true, 'online' => $online, 'busy' => $r->availability === 'on_job',
                    'distance_km' => $online && isset($r->dist_m) && $r->dist_m !== null ? $this->roundKm((float) $r->dist_m) : null,
                    'book' => ['operator_id' => (int) $r->operator_id, 'provider' => $r->company, 'slug' => $r->company_slug, 'driver_handle' => $r->handle],
                    'exact' => $r->handle === $term,
                ];
            }
        }

        // Exact handle first, then people online, then nearest, then best rated.
        usort($out, fn ($a, $b) => [! $a['exact'], ! $a['online'], $a['distance_km'] ?? 9999, -($a['rating'] ?? 0)]
            <=> [! $b['exact'], ! $b['online'], $b['distance_km'] ?? 9999, -($b['rating'] ?? 0)]);

        return array_map(fn ($r) => collect($r)->except('exact')->all(), array_slice($out, 0, $limit));
    }

    /** A rider a customer asked for by handle, with the company that will be asked to do the job. */
    public function rider(string $handle): ?object
    {
        return DB::table('driver_profiles as d')->join('operators as o', 'o.id', '=', 'd.operator_id')->join('users as u', 'u.id', '=', 'd.user_id')
            ->where('d.handle', self::normalize($handle))->where('d.status', 'active')->whereNull('d.deleted_at')->where('o.status', 'active')
            ->first(['d.id', 'd.handle', 'd.operator_id', 'u.name', 'o.display_name AS company']);
    }

    /** How many of a business's riders are online, and roughly how far the nearest is. */
    private function onlineRiders(int $operatorId, ?float $lat, ?float $lng): array
    {
        $q = DB::table('driver_profiles')->where('operator_id', $operatorId)->where('status', 'active')->whereNull('deleted_at')
            ->whereIn('availability', ['online', 'on_job'])->where('last_seen_at', '>=', now()->subMinutes(15));
        $count = (clone $q)->count();
        $near = null;
        if ($count && $lat !== null && $lng !== null) {
            $m = (clone $q)->whereNotNull('last_point')->selectRaw('MIN(ST_Distance(last_point, ST_SetSRID(ST_MakePoint(?, ?), 4326)::geography)) AS m', [$lng, $lat])->value('m');
            $near = $m !== null ? $this->roundKm((float) $m) : null;
        }

        return ['count' => $count, 'nearest_km' => $near];
    }

    /** Never precise: under a kilometre shows as 1, otherwise whole kilometres. */
    private function roundKm(float $metres): int
    {
        return max(1, (int) round($metres / 1000));
    }
}
