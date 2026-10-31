<?php

namespace Tests\Unit;

use App\Modules\Maps\Geocoder;
use App\Modules\Routing\RoutingEngine;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/** Paid address search and road routing: which provider is used, what each answer means, and what may be cached. */
class MapsAndRoutingTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['cache.default' => 'array', 'region.country' => 'CA', 'maps.google.key' => null, 'maps.mapbox.token' => null, 'maps.mapbox.public_token' => null,
            'maps.tiles.url' => null, 'routing.google.key' => null]);
    }

    public function test_routing_follows_the_address_provider_unless_chosen(): void
    {
        config(['routing.driver' => 'auto', 'maps.geocoder' => 'nominatim']);
        $this->assertSame('straight', RoutingEngine::activeDriver());
        config(['maps.geocoder' => 'google', 'maps.google.key' => 'k']);
        $this->assertSame('google', RoutingEngine::activeDriver());
        config(['maps.geocoder' => 'mapbox', 'maps.google.key' => null, 'maps.mapbox.token' => 't']);
        $this->assertSame('mapbox', RoutingEngine::activeDriver());
        config(['routing.driver' => 'osrm']);
        $this->assertSame('osrm', RoutingEngine::activeDriver());
        config(['routing.driver' => 'google', 'maps.google.key' => null]);
        $this->assertSame('straight', RoutingEngine::activeDriver());
    }

    public function test_parsing_route_answers(): void
    {
        $this->assertSame(['distance_m' => 12345, 'duration_s' => 900], RoutingEngine::parseGoogle(['routes' => [['distanceMeters' => 12345, 'duration' => '900s']]]));
        $this->assertSame(['distance_m' => 8001, 'duration_s' => 601], RoutingEngine::parseMapbox(['code' => 'Ok', 'routes' => [['distance' => 8000.6, 'duration' => 600.7]]]));
        $this->expectException(\RuntimeException::class);
        RoutingEngine::parseMapbox(['code' => 'NoRoute', 'routes' => []]);
    }

    public function test_google_suggestions_are_not_kept_and_need_a_place_lookup(): void
    {
        config(['maps.geocoder' => 'google', 'maps.google.key' => 'k']);
        Http::fake(['places.googleapis.com/v1/places:autocomplete' => Http::response(['suggestions' => [['placePrediction' => ['placeId' => 'abc', 'text' => ['text' => '1 King St W, Kitchener, ON']]]]])]);
        $geo = app(Geocoder::class);
        $this->assertTrue($geo->typeAhead());
        $r = $geo->search('1 King St', 43.45, -80.49, 'sess');
        $this->assertSame([['id' => 'abc', 'label' => '1 King St W, Kitchener, ON', 'lat' => null, 'lng' => null]], $r);
        $geo->search('1 King St', 43.45, -80.49, 'sess');
        Http::assertSentCount(2);
    }

    public function test_nominatim_is_search_on_enter_only_and_cached(): void
    {
        config(['maps.geocoder' => 'nominatim']);
        Http::fake(['*/search*' => Http::response([['osm_type' => 'way', 'osm_id' => 5, 'display_name' => 'King St', 'lat' => '43.4', 'lon' => '-80.5']])]);
        $geo = app(Geocoder::class);
        $this->assertFalse($geo->typeAhead());
        $this->assertSame(43.4, $geo->search('King St')[0]['lat']);
        $geo->search('King St');
        Http::assertSentCount(1);
        $this->assertSame([], $geo->search('Ki'));
    }

    public function test_tiles(): void
    {
        $this->assertStringContainsString('tile.openstreetmap.org', Geocoder::tiles()['url']);
        config(['maps.mapbox.public_token' => 'pk.x']);
        $this->assertStringContainsString('api.mapbox.com', Geocoder::tiles()['url']);
        config(['maps.tiles.url' => 'https://tiles.example/{z}/{x}/{y}.png']);
        $this->assertSame('https://tiles.example/{z}/{x}/{y}.png', Geocoder::tiles()['url']);
    }
}
