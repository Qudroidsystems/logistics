<?php

namespace App\Http\Controllers;

use App\Models\BrandSetting;
use App\Modules\Booking\InstantQuoteService;
use Illuminate\Support\Facades\DB;

/** Pages for search engines: a landing page per city, the sitemap and robots.txt. */
class SeoController extends Controller
{
    /** Cities customers can book in (live or beta), for the sitemap and the footer. */
    public static function cities()
    {
        return DB::table('cities')->whereIn('launch_status', ['live', 'beta'])->orderBy('name')
            ->get(['id', 'name', 'slug', 'region', DB::raw('ST_Y(centre::geometry) AS lat'), DB::raw('ST_X(centre::geometry) AS lng')]);
    }

    public function city(string $slug, InstantQuoteService $instant)
    {
        $city = self::cities()->firstWhere('slug', $slug);
        abort_unless($city, 404);
        $options = $instant->options();
        // Partner stores in and around this city first.
        $options['centres'] = app(\App\Modules\DropOff\DropOffService::class)->list((float) $city->lat, (float) $city->lng, 50);

        return view('public.city', [
            'brand' => BrandSetting::forApp('customer'), 'city' => $city, 'options' => $options, 'embed' => false,
            'mapCentre' => ['lat' => (float) $city->lat, 'lng' => (float) $city->lng, 'zoom' => 11],
            'stores' => array_values(array_filter($options['centres'], fn ($c) => ($c['distance_km'] ?? 999) <= 40)),
        ]);
    }

    public function sitemap()
    {
        $urls = [route('landing'), route('business'), route('drive'), route('partners'), route('track.lookup'), route('privacy'), route('terms')];
        foreach (self::cities() as $c) {
            $urls[] = route('city', $c->slug);
        }
        $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n".'<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n";
        foreach ($urls as $u) {
            $xml .= '  <url><loc>'.e($u).'</loc></url>'."\n";
        }

        return response($xml.'</urlset>'."\n", 200, ['Content-Type' => 'application/xml']);
    }

    public function robots()
    {
        $body = "User-agent: *\nDisallow: /account\nDisallow: /ops\nDisallow: /provider\nDisallow: /driver\nDisallow: /merchant\nDisallow: /counter\nDisallow: /book/\nDisallow: /pay/\n"
            ."\nSitemap: ".route('sitemap')."\n";

        return response($body, 200, ['Content-Type' => 'text/plain']);
    }
}
