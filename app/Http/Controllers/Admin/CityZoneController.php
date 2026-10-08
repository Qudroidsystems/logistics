<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Staff pages for the places the service runs in. A city has a centre point; a zone is a round service area drawn
 * from a centre and a radius (no polygon editor needed to get started). Customers can only post requests in a city,
 * and providers can only pick service areas from zones, so nothing works until one of each exists.
 */
class CityZoneController extends Controller
{
    private const STATUSES = ['planned', 'beta', 'live', 'paused'];

    private const ZONE_TYPES = ['service', 'pricing', 'surge', 'restricted', 'hub_catchment', 'no_pickup', 'no_dropoff'];

    public function index()
    {
        $cities = DB::table('cities as c')
            ->selectRaw('c.id, c.name, c.region, c.slug, c.launch_status, ST_Y(c.centre::geometry) as lat, ST_X(c.centre::geometry) as lng,
                (select count(*) from zones z where z.city_id = c.id and z.operator_id is null) as zone_count,
                (select count(*) from zones z where z.city_id = c.id and z.operator_id is null and z.active) as active_zones')
            ->orderBy('c.name')
            ->get();

        return view('ops.cities', ['cities' => $cities, 'statuses' => self::STATUSES, 'pagetitle' => 'Cities']);
    }

    public function storeCity(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'region' => ['nullable', 'string', 'max:80'],
            'lat' => ['required', 'numeric', 'between:-90,90'],
            'lng' => ['required', 'numeric', 'between:-180,180'],
            'radius_km' => ['required', 'numeric', 'between:1,200'],
            'launch_status' => ['required', Rule::in(self::STATUSES)],
        ]);

        $slug = Str::slug($data['name']);
        if (DB::table('cities')->where('slug', $slug)->exists()) {
            $slug .= '-'.Str::lower(Str::random(4));
        }

        $cityId = DB::transaction(function () use ($data, $slug) {
            $id = DB::table('cities')->insertGetId([
                'region' => $data['region'] ?? null,
                'name' => $data['name'],
                'slug' => $slug,
                'launch_status' => $data['launch_status'],
                'centre' => DB::raw(sprintf('ST_SetSRID(ST_MakePoint(%F, %F), 4326)::geography', (float) $data['lng'], (float) $data['lat'])),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // One whole-city service zone so providers and customers have something to pick straight away.
            $this->insertZone($id, $data['name'].' (whole city)', 'service', (float) $data['lat'], (float) $data['lng'], (float) $data['radius_km'] * 1000);

            return $id;
        });

        return redirect()->route('ops.city', $cityId)->with('success', $data['name'].' added with one service zone.');
    }

    public function city(int $city)
    {
        $row = $this->cityRow($city);
        abort_unless($row, 404);

        $zones = DB::table('zones')
            ->selectRaw('id, name, type, active, version, ST_Y(ST_Centroid(boundary::geometry)) as lat, ST_X(ST_Centroid(boundary::geometry)) as lng, ROUND((ST_Area(boundary) / 1000000)::numeric, 1) as area_km2')
            ->where('city_id', $city)->whereNull('operator_id')
            ->orderBy('name')->get();

        return view('ops.city', [
            'city' => $row, 'zones' => $zones, 'statuses' => self::STATUSES, 'types' => self::ZONE_TYPES,
            'pagetitle' => $row->name,
        ]);
    }

    public function updateCity(Request $request, int $city)
    {
        abort_unless($this->cityRow($city), 404);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'region' => ['nullable', 'string', 'max:80'],
            'launch_status' => ['required', Rule::in(self::STATUSES)],
        ]);

        DB::table('cities')->where('id', $city)->update([
            'name' => $data['name'], 'region' => $data['region'] ?? null,
            'launch_status' => $data['launch_status'], 'updated_at' => now(),
        ]);

        return back()->with('success', 'City saved.');
    }

    public function storeZone(Request $request, int $city)
    {
        abort_unless($this->cityRow($city), 404);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'type' => ['required', Rule::in(self::ZONE_TYPES)],
            'shape' => ['nullable', Rule::in(['circle', 'polygon'])],
            'lat' => ['required_unless:shape,polygon', 'nullable', 'numeric', 'between:-90,90'],
            'lng' => ['required_unless:shape,polygon', 'nullable', 'numeric', 'between:-180,180'],
            'radius_km' => ['required_unless:shape,polygon', 'nullable', 'numeric', 'between:0.1,200'],
            'points' => ['required_if:shape,polygon', 'nullable', 'string', 'max:20000'],
        ]);

        if (($data['shape'] ?? 'circle') === 'polygon') {
            $ring = $this->parsePoints($data['points'] ?? '');
            if (! $ring) {
                return back()->withInput()->with('error', 'Click at least 3 points on the map to outline the zone.');
            }
            if (! $this->insertPolygonZone($city, $data['name'], $data['type'], $ring)) {
                return back()->withInput()->with('error', 'That outline crosses itself or is too small or too large. Redraw it without crossing lines.');
            }

            return back()->with('success', 'Zone added.');
        }

        $this->insertZone($city, $data['name'], $data['type'], (float) $data['lat'], (float) $data['lng'], (float) $data['radius_km'] * 1000);

        return back()->with('success', 'Zone added.');
    }

    public function toggleZone(int $zone)
    {
        $z = DB::table('zones')->where('id', $zone)->whereNull('operator_id')->first();
        abort_unless($z, 404);

        DB::table('zones')->where('id', $zone)->update([
            'active' => ! $z->active, 'version' => $z->version + 1, 'updated_at' => now(),
        ]);

        return back()->with('success', $z->active ? 'Zone switched off.' : 'Zone switched on.');
    }

    // ----------------------------------------------------------------

    private function cityRow(int $id): ?object
    {
        return DB::table('cities')
            ->selectRaw('id, name, region, slug, launch_status, ST_Y(centre::geometry) as lat, ST_X(centre::geometry) as lng')
            ->where('id', $id)->first();
    }

    /**
     * Pure: the JSON the map sends ([[lat,lng],...]) as a closed ring of [lng,lat] pairs, or null when it is not a usable outline
     * (fewer than 3 distinct points, more than 300, or coordinates out of range).
     *
     * @return array<int,array{0:float,1:float}>|null
     */
    public static function parsePoints(string $json): ?array
    {
        $raw = json_decode($json, true);
        if (! is_array($raw) || count($raw) < 3 || count($raw) > 300) {
            return null;
        }
        $ring = [];
        foreach ($raw as $p) {
            if (! is_array($p) || count($p) !== 2 || ! is_numeric($p[0]) || ! is_numeric($p[1])) {
                return null;
            }
            [$lat, $lng] = [(float) $p[0], (float) $p[1]];
            if ($lat < -90 || $lat > 90 || $lng < -180 || $lng > 180) {
                return null;
            }
            $pair = [$lng, $lat];
            if ($ring === [] || end($ring) !== $pair) {
                $ring[] = $pair;
            }
        }
        if (count($ring) < 3) {
            return null;
        }
        if ($ring[0] !== end($ring)) {
            $ring[] = $ring[0];
        }

        return count($ring) >= 4 ? $ring : null;
    }

    /** A drawn zone. Refused (false) when the outline is not a valid polygon or is outside 0.01 to 40,000 km2. */
    private function insertPolygonZone(int $cityId, string $name, string $type, array $ring): bool
    {
        $wkt = 'POLYGON(('.implode(',', array_map(fn ($p) => sprintf('%F %F', $p[0], $p[1]), $ring)).'))';
        $check = DB::selectOne('select ST_IsValid(g) as ok, ST_Area(g::geography) / 1000000 as km2 from (select ST_GeomFromText(?, 4326) as g) t', [$wkt]);
        if (! $check || ! $check->ok || $check->km2 < 0.01 || $check->km2 > 40000) {
            return false;
        }
        DB::table('zones')->insert([
            'city_id' => $cityId, 'name' => $name, 'type' => $type,
            'boundary' => DB::raw(sprintf("ST_Multi(ST_GeomFromText('%s', 4326))::geography", $wkt)),
            'created_at' => now(), 'updated_at' => now(),
        ]);

        return true;
    }

    /** A round zone: the circle around a point, stored as a one-part multipolygon on the geography type. */
    private function insertZone(int $cityId, string $name, string $type, float $lat, float $lng, float $radiusMetres): void
    {
        DB::table('zones')->insert([
            'city_id' => $cityId,
            'name' => $name,
            'type' => $type,
            'boundary' => DB::raw(sprintf(
                'ST_Multi(ST_Buffer(ST_SetSRID(ST_MakePoint(%F, %F), 4326)::geography, %F)::geometry)::geography',
                $lng, $lat, $radiusMetres
            )),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
