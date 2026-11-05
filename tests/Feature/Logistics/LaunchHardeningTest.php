<?php

namespace Tests\Feature\Logistics;

use App\Modules\Partner\RouteEstimator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\Support\BuildsMarketplace;
use Tests\TestCase;

/** Security headers, the price-form bot check, idle app sign-ins and old quotes. */
class LaunchHardeningTest extends TestCase
{
    use BuildsMarketplace;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->useRegion('CA');
        $this->seedFoundation();
    }

    public function test_security_headers(): void
    {
        $this->get('/privacy')->assertHeader('X-Content-Type-Options', 'nosniff')->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        $embed = $this->get('/embed/quote');
        $embed->assertHeaderMissing('X-Frame-Options');
        $this->assertStringContainsString('frame-ancestors *', (string) $embed->headers->get('Content-Security-Policy'));
    }

    public function test_bot_check_after_five_prices(): void
    {
        $this->mock(RouteEstimator::class, fn ($m) => $m->shouldReceive('estimate')->andReturn(['distance_m' => 5_000, 'duration_s' => 600]));
        DB::insert("INSERT INTO cities (name, slug, centre, launch_status, created_at, updated_at) VALUES ('Waterloo', 'waterloo', ST_SetSRID(ST_MakePoint(-80.52, 43.46), 4326)::geography, 'live', now(), now())");
        [$op] = $this->makeProvider('company', 'CityHop');
        DB::table('pricing_plans')->insert(['public_id' => (string) Str::ulid(), 'operator_id' => $op, 'city_id' => DB::table('cities')->value('id'),
            'service_type_id' => (int) DB::table('service_types')->where('code', 'same_day')->value('id'), 'name' => 'S', 'min_fee' => 1_000, 'rounding_kobo' => 1,
            'status' => 'active', 'created_at' => now(), 'updated_at' => now()]);
        config(['booking.operator_id' => $op, 'booking.vehicles' => [], 'services.turnstile.site_key' => 'site', 'services.turnstile.secret_key' => 'secret']);
        Http::fake(['challenges.cloudflare.com/*' => Http::sequence()->push(['success' => false])->push(['success' => true])]);
        $body = ['pickup' => ['lat' => 43.45, 'lng' => -80.49], 'dropoff' => ['lat' => 43.47, 'lng' => -80.54], 'service' => 'same_day', 'size' => 'small'];
        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/v1/public/quote', $body)->assertOk();
        }
        $this->postJson('/api/v1/public/quote', $body)->assertStatus(429)->assertJsonPath('error', 'challenge');
        $this->postJson('/api/v1/public/quote', $body + ['cf_token' => 'bad'])->assertStatus(429);
        $this->postJson('/api/v1/public/quote', $body + ['cf_token' => 'good'])->assertOk();
        // Signed-in customers are never asked.
        $this->actingAs($this->makeUser('Ada'), 'sanctum')->postJson('/api/v1/public/quote', $body)->assertOk();

        // Old unbooked quotes go; recent ones stay.
        DB::table('quotes')->limit(3)->update(['expires_at' => now()->subDays(10)]);
        $before = DB::table('quotes')->count();
        $this->artisan('quotes:prune')->assertSuccessful();
        $this->assertSame($before - 3, DB::table('quotes')->count());
    }

    public function test_city_pages_sitemap_and_robots(): void
    {
        DB::insert("INSERT INTO cities (name, slug, centre, launch_status, created_at, updated_at) VALUES ('Kitchener', 'kitchener', ST_SetSRID(ST_MakePoint(-80.49, 43.45), 4326)::geography, 'live', now(), now())");
        DB::insert("INSERT INTO cities (name, slug, centre, launch_status, created_at, updated_at) VALUES ('Later Town', 'later-town', ST_SetSRID(ST_MakePoint(-80, 43), 4326)::geography, 'planned', now(), now())");
        $this->get('/delivery/kitchener')->assertOk()->assertSee('Same-day delivery in Kitchener')->assertSee('<meta name="description"', false);
        $this->get('/delivery/later-town')->assertNotFound();
        $this->get('/sitemap.xml')->assertOk()->assertSee('/delivery/kitchener')->assertDontSee('later-town');
        $this->get('/robots.txt')->assertOk()->assertSee('Sitemap: ')->assertSee('Disallow: /account');
        $this->get('/')->assertOk()->assertSee('/delivery/kitchener');
        $this->get('/embed/quote')->assertSee('noindex', false);
    }

    public function test_idle_app_sign_ins_are_removed(): void
    {
        $u = $this->makeUser('Ada');
        $old = $u->createToken('old phone');
        $u->createToken('this phone');
        DB::table('personal_access_tokens')->where('id', $old->accessToken->id)->update(['last_used_at' => now()->subDays(61)]);
        $this->artisan('tokens:prune-idle')->assertSuccessful();
        $this->assertSame(['this phone'], DB::table('personal_access_tokens')->where('tokenable_id', $u->id)->pluck('name')->all());
    }
}
