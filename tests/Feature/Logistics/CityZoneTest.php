<?php

namespace Tests\Feature\Logistics;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Tests\Support\BuildsMarketplace;
use Tests\TestCase;

/** Staff can add a city and zones; the first city comes with a working whole-city zone. */
class CityZoneTest extends TestCase
{
    use BuildsMarketplace;
    use RefreshDatabase;

    private function staff(array $perms): User
    {
        $u = User::factory()->create(['must_change_password' => false]);
        foreach ($perms as $p) {
            Permission::findOrCreate($p, 'web');
        }
        $u->givePermissionTo($perms);

        return $u;
    }

    public function test_pages_need_zone_permissions(): void
    {
        $this->seedFoundation();
        $this->actingAs($this->staff([]))->get('/ops/cities')->assertForbidden();
        $this->actingAs($this->staff(['View zone']))->get('/ops/cities')->assertOk();
    }

    public function test_adding_a_city_also_creates_a_service_zone(): void
    {
        $this->seedFoundation();
        $admin = $this->staff(['View zone', 'Create zone', 'Update zone']);

        $this->actingAs($admin)->post('/ops/cities', [
            'name' => 'Lokoja', 'region' => 'Kogi', 'lat' => 7.80, 'lng' => 6.73, 'radius_km' => 12, 'launch_status' => 'live',
        ])->assertRedirect();

        $city = DB::table('cities')->where('name', 'Lokoja')->first();
        $this->assertNotNull($city);
        $this->assertSame('live', $city->launch_status);
        $this->assertSame(1, DB::table('zones')->where('city_id', $city->id)->where('type', 'service')->where('active', true)->count());

        $inside = DB::selectOne('select ST_Covers(boundary, ST_SetSRID(ST_MakePoint(6.74, 7.81), 4326)::geography) as c from zones where city_id = ?', [$city->id]);
        $this->assertTrue((bool) $inside->c);
        $outside = DB::selectOne('select ST_Covers(boundary, ST_SetSRID(ST_MakePoint(7.5, 8.5), 4326)::geography) as c from zones where city_id = ?', [$city->id]);
        $this->assertFalse((bool) $outside->c);

        $this->actingAs($admin)->get('/ops/cities/'.$city->id)->assertOk()->assertSee('Lokoja (whole city)');
    }

    public function test_zone_can_be_added_and_switched_off(): void
    {
        $this->seedFoundation();
        $admin = $this->staff(['View zone', 'Create zone', 'Update zone']);
        $this->actingAs($admin)->post('/ops/cities', ['name' => 'Kabba', 'lat' => 7.83, 'lng' => 6.07, 'radius_km' => 10, 'launch_status' => 'live']);
        $cityId = DB::table('cities')->where('name', 'Kabba')->value('id');

        $this->actingAs($admin)->post("/ops/cities/{$cityId}/zones", [
            'name' => 'Market area', 'type' => 'service', 'lat' => 7.83, 'lng' => 6.07, 'radius_km' => 2,
        ])->assertRedirect();
        $zoneId = DB::table('zones')->where('name', 'Market area')->value('id');
        $this->assertNotNull($zoneId);

        $this->actingAs($admin)->post("/ops/zones/{$zoneId}/toggle")->assertRedirect();
        $this->assertFalse((bool) DB::table('zones')->where('id', $zoneId)->value('active'));
        $this->assertSame(2, (int) DB::table('zones')->where('id', $zoneId)->value('version'));
    }

    public function test_bad_input_is_rejected(): void
    {
        $this->seedFoundation();
        $admin = $this->staff(['View zone', 'Create zone']);
        $this->actingAs($admin)->post('/ops/cities', ['name' => 'X', 'lat' => 120, 'lng' => 6, 'radius_km' => 5, 'launch_status' => 'live'])
            ->assertSessionHasErrors('lat');
        $this->assertSame(0, DB::table('cities')->count());
    }
}
