<?php

namespace App\Modules\Maps;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

/**
 * Address search for every form (price form, partner and driver forms, recurring deliveries, the apps), through one
 * provider chosen in config/maps.php. Browsers never call the provider directly: the key stays on the server, results
 * are cached, and the provider's rules (rate limits, no type-ahead on Nominatim) are kept in one place.
 *
 * search() returns suggestions; a suggestion without coordinates (Google) is resolved with place().
 */
class Geocoder
{
    public function driver(): string
    {
        $d = (string) config('maps.geocoder', 'nominatim');
        if ($d === 'google' && ! config('maps.google.key')) {
            return 'nominatim';
        }
        if ($d === 'mapbox' && ! config('maps.mapbox.token')) {
            return 'nominatim';
        }

        return in_array($d, ['google', 'mapbox', 'nominatim'], true) ? $d : 'nominatim';
    }

    /** Whether forms may search as the person types (Nominatim forbids it). */
    public function typeAhead(): bool
    {
        return $this->driver() !== 'nominatim';
    }

    /** @return array<int, array{id:string, label:string, lat:?float, lng:?float}> */
    public function search(string $q, ?float $nearLat = null, ?float $nearLng = null, ?string $session = null): array
    {
        $q = trim(preg_replace('/\s+/', ' ', $q));
        if (mb_strlen($q) < 3) {
            return [];
        }
        $driver = $this->driver();
        $country = strtolower((string) config('region.country', 'ca'));
        $key = 'geo:s:'.$driver.':'.md5(mb_strtolower($q).'|'.$country.'|'.round((float) $nearLat, 1).','.round((float) $nearLng, 1));
        $fetch = function () use ($driver, $q, $country, $nearLat, $nearLng, $session) {
            try {
                return match ($driver) {
                    'google' => $this->google($q, $country, $nearLat, $nearLng, $session),
                    'mapbox' => $this->mapbox($q, $country, $nearLat, $nearLng),
                    default => $this->nominatim($q, $country),
                };
            } catch (Throwable $e) {
                Log::warning("Address search ({$driver}) failed: {$e->getMessage()}");

                return [];
            }
        };

        // Providers' terms decide what may be kept: Google suggestions belong to one person's typing session, and
        // Mapbox results may be stored only on a permanent-geocoding plan. Nominatim results may be cached.
        if (! $this->mayCacheSearch()) {
            return $fetch();
        }

        return Cache::remember($key, now()->addMinutes((int) config('maps.cache_minutes', 1440)), $fetch);
    }

    private function mayCacheSearch(): bool
    {
        return match ($this->driver()) {
            'google' => false,
            'mapbox' => (bool) config('maps.mapbox.permanent'),
            default => true,
        };
    }

    /** Coordinates for a suggestion that came without them (Google place ids). @return array{id:string,label:string,lat:float,lng:float,postal_code:?string,city:?string} */
    public function place(string $id, ?string $session = null): array
    {
        if ($this->driver() !== 'google') {
            throw new RuntimeException('Pick the address again.');
        }

        return Cache::remember('geo:p:'.md5($id), now()->addMinutes((int) config('maps.cache_minutes', 1440)), function () use ($id, $session) {
            $r = Http::timeout(6)->withHeaders([
                'X-Goog-Api-Key' => (string) config('maps.google.key'),
                'X-Goog-FieldMask' => 'id,formattedAddress,location,addressComponents',
            ])->get('https://places.googleapis.com/v1/places/'.rawurlencode($id), array_filter(['sessionToken' => $session]));
            if (! $r->successful() || ! $r->json('location')) {
                throw new RuntimeException('We could not find that address. Try typing it again.');
            }
            $parts = collect((array) $r->json('addressComponents'));
            $find = fn (string $type) => $parts->first(fn ($c) => in_array($type, (array) ($c['types'] ?? []), true))['longText'] ?? null;

            return ['id' => $id, 'label' => (string) $r->json('formattedAddress'), 'lat' => (float) $r->json('location.latitude'), 'lng' => (float) $r->json('location.longitude'),
                'postal_code' => $find('postal_code'), 'city' => $find('locality') ?? $find('postal_town')];
        });
    }

    /** The street address at a point ("Use my location"). Never cached: it is one person's position. Null when nothing is found. */
    public function reverse(float $lat, float $lng): ?string
    {
        $driver = $this->driver();
        try {
            if ($driver === 'google') {
                $r = Http::timeout(6)->get('https://maps.googleapis.com/maps/api/geocode/json', [
                    'latlng' => "{$lat},{$lng}", 'result_type' => 'street_address|premise', 'key' => (string) config('maps.google.key'),
                ]);

                return $r->successful() ? ($r->json('results.0.formatted_address') ?: null) : null;
            }
            if ($driver === 'mapbox') {
                $r = Http::timeout(6)->get('https://api.mapbox.com/search/geocode/v6/reverse', [
                    'longitude' => $lng, 'latitude' => $lat, 'types' => 'address', 'limit' => 1, 'access_token' => (string) config('maps.mapbox.token'),
                ]);

                return $r->successful() ? ($r->json('features.0.properties.full_address') ?: null) : null;
            }
            $lock = Cache::lock('geo:nominatim', 5);
            $lock->block(5);
            try {
                $wait = 1.05 - (microtime(true) - (float) Cache::get('geo:nominatim:last', 0));
                if ($wait > 0) {
                    usleep((int) ($wait * 1_000_000));
                }
                $r = Http::timeout(6)->withHeaders(['User-Agent' => config('app.name').' ('.(config('booking.contact_email') ?: config('mail.from.address')).')'])
                    ->get(config('maps.nominatim.url').'/reverse', ['format' => 'jsonv2', 'lat' => $lat, 'lon' => $lng, 'zoom' => 18]);
                Cache::put('geo:nominatim:last', microtime(true), 60);
            } finally {
                $lock->release();
            }

            return $r->successful() ? ($r->json('display_name') ?: null) : null;
        } catch (Throwable $e) {
            Log::warning("Reverse geocoding ({$driver}) failed: {$e->getMessage()}");

            return null;
        }
    }

    private function google(string $q, string $country, ?float $lat, ?float $lng, ?string $session): array
    {
        $body = array_filter([
            'input' => $q, 'includedRegionCodes' => [$country], 'sessionToken' => $session,
            'locationBias' => $lat !== null && $lng !== null ? ['circle' => ['center' => ['latitude' => $lat, 'longitude' => $lng], 'radius' => 50000.0]] : null,
        ]);
        $r = Http::timeout(5)->withHeaders(['X-Goog-Api-Key' => (string) config('maps.google.key')])->post('https://places.googleapis.com/v1/places:autocomplete', $body);
        if (! $r->successful()) {
            throw new RuntimeException('Google Places '.$r->status().': '.mb_substr($r->body(), 0, 200));
        }

        return collect((array) $r->json('suggestions'))->pluck('placePrediction')->filter()->take(6)
            ->map(fn ($p) => ['id' => (string) $p['placeId'], 'label' => (string) ($p['text']['text'] ?? ''), 'lat' => null, 'lng' => null])->values()->all();
    }

    private function mapbox(string $q, string $country, ?float $lat, ?float $lng): array
    {
        $r = Http::timeout(5)->get('https://api.mapbox.com/search/geocode/v6/forward', array_filter([
            'q' => $q, 'country' => $country, 'autocomplete' => 'true', 'limit' => 6, 'language' => 'en',
            'proximity' => $lat !== null && $lng !== null ? "{$lng},{$lat}" : null,
            'permanent' => config('maps.mapbox.permanent') ? 'true' : null,
            'access_token' => (string) config('maps.mapbox.token'),
        ]));
        if (! $r->successful()) {
            throw new RuntimeException('Mapbox '.$r->status());
        }

        return collect((array) $r->json('features'))->map(fn ($f) => [
            'id' => (string) ($f['id'] ?? $f['properties']['mapbox_id'] ?? md5(json_encode($f))),
            'label' => (string) ($f['properties']['full_address'] ?? $f['properties']['name'] ?? ''),
            'lat' => (float) $f['geometry']['coordinates'][1], 'lng' => (float) $f['geometry']['coordinates'][0],
        ])->values()->all();
    }

    /** OpenStreetMap's server: at most one request a second for the whole app, identified, and never type-ahead. */
    private function nominatim(string $q, string $country): array
    {
        $lock = Cache::lock('geo:nominatim', 5);
        $lock->block(5);
        try {
            $last = (float) Cache::get('geo:nominatim:last', 0);
            $wait = 1.05 - (microtime(true) - $last);
            if ($wait > 0) {
                usleep((int) ($wait * 1_000_000));
            }
            $r = Http::timeout(6)->withHeaders(['User-Agent' => config('app.name').' ('.(config('booking.contact_email') ?: config('mail.from.address')).')'])
                ->get(config('maps.nominatim.url').'/search', ['format' => 'jsonv2', 'limit' => 5, 'countrycodes' => $country, 'q' => $q]);
            Cache::put('geo:nominatim:last', microtime(true), 60);
        } finally {
            $lock->release();
        }
        if (! $r->successful()) {
            throw new RuntimeException('Nominatim '.$r->status());
        }

        return collect((array) $r->json())->map(fn ($x) => ['id' => 'osm:'.($x['osm_type'] ?? '').($x['osm_id'] ?? ''), 'label' => (string) $x['display_name'],
            'lat' => (float) $x['lat'], 'lng' => (float) $x['lon']])->values()->all();
    }

    /** Map tiles for Leaflet (website) and flutter_map (apps). @return array{url:string, attribution:string, subdomains:array} */
    public static function tiles(): array
    {
        if ($url = config('maps.tiles.url')) {
            return ['url' => $url, 'attribution' => (string) (config('maps.tiles.attribution') ?: '&copy; OpenStreetMap contributors'), 'subdomains' => []];
        }
        if ($t = config('maps.mapbox.public_token')) {
            return ['url' => 'https://api.mapbox.com/styles/v1/mapbox/streets-v12/tiles/256/{z}/{x}/{y}@2x?access_token='.$t,
                'attribution' => '&copy; Mapbox &copy; OpenStreetMap contributors', 'subdomains' => []];
        }

        return ['url' => 'https://tile.openstreetmap.org/{z}/{x}/{y}.png', 'attribution' => '&copy; OpenStreetMap contributors', 'subdomains' => []];
    }
}
