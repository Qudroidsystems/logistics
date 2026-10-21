<?php

namespace Tests\Feature\Logistics;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\Support\BuildsMarketplace;
use Tests\TestCase;

/** The live fleet map: a provider sees its own drivers only, staff see everyone, and speeding is flagged. */
class LiveFleetTest extends TestCase
{
    use BuildsMarketplace;
    use RefreshDatabase;

    private int $op;
    private User $owner;
    private User $rider;
    private int $profile;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedFoundation();
        [$this->op, $this->owner] = $this->makeProvider();
        $this->rider = $this->makeUser('Musa Rider');
        $this->profile = $this->addDriver($this->op, $this->rider, 6.52, 3.37);
    }

    private function addDriver(int $op, User $u, float $lat, float $lng): int
    {
        $id = DB::table('driver_profiles')->insertGetId(['public_id' => (string) Str::ulid(), 'operator_id' => $op, 'user_id' => $u->id,
            'status' => 'active', 'availability' => 'online', 'last_seen_at' => now(), 'created_at' => now(), 'updated_at' => now()]);
        DB::statement('UPDATE driver_profiles SET last_point = ST_SetSRID(ST_MakePoint(?, ?), 4326)::geography WHERE id = ?', [$lng, $lat, $id]);
        DB::table('operator_members')->insert(['operator_id' => $op, 'user_id' => $u->id, 'role' => 'driver', 'status' => 'active', 'created_at' => now(), 'updated_at' => now()]);

        return $id;
    }

    private function member(string $role): User
    {
        $u = $this->makeUser(ucfirst($role));
        DB::table('operator_members')->insert(['operator_id' => $this->op, 'user_id' => $u->id, 'role' => $role, 'status' => 'active', 'created_at' => now(), 'updated_at' => now()]);
        DB::table('users')->where('id', $u->id)->update(['current_operator_id' => $this->op]);

        return $u->fresh();
    }

    public function test_the_provider_sees_its_own_drivers_on_the_map(): void
    {
        [$other] = $this->makeProvider('company', 'Other Couriers');
        $this->addDriver($other, $this->makeUser('Not Mine'), 6.6, 3.4);

        $this->actingAs($this->owner)->get('/provider/fleet')->assertOk()->assertSee('Live fleet');
        $res = $this->actingAs($this->owner)->getJson('/provider/fleet/live')->assertOk();

        $this->assertSame(['Musa Rider'], array_column($res->json('drivers'), 'name'));
        $this->assertSame('online', $res->json('drivers.0.state'));
        $this->assertEqualsWithDelta(6.52, $res->json('drivers.0.lat'), 0.0001);
    }

    public function test_dispatchers_and_driver_managers_may_look_but_finance_may_not(): void
    {
        $this->actingAs($this->member('dispatcher'))->getJson('/provider/fleet/live')->assertOk();
        $this->actingAs($this->member('driver_manager'))->getJson('/provider/fleet/live')->assertOk();
        $this->actingAs($this->member('finance'))->getJson('/provider/fleet/live')->assertForbidden();
    }

    public function test_a_drivers_trail_is_scoped_to_the_provider(): void
    {
        $pid = DB::table('driver_profiles')->where('id', $this->profile)->value('public_id');
        Sanctum::actingAs($this->rider);
        $this->postJson('/api/v1/driver/location', ['pings' => [
            ['lat' => 6.52, 'lng' => 3.37, 'at' => now()->subMinutes(2)->toIso8601String(), 'accuracy' => 10],
            ['lat' => 6.53, 'lng' => 3.38, 'at' => now()->subMinute()->toIso8601String(), 'accuracy' => 10],
        ]])->assertOk();

        $this->actingAs($this->owner)->getJson("/provider/fleet/drivers/{$pid}/trail")->assertOk()->assertJsonCount(2, 'points');

        [, $otherOwner] = $this->makeProvider('company', 'Other Couriers');
        DB::table('users')->where('id', $otherOwner->id)->update(['current_operator_id' => DB::table('operator_members')->where('user_id', $otherOwner->id)->value('operator_id')]);
        $this->actingAs($otherOwner->fresh())->getJson("/provider/fleet/drivers/{$pid}/trail")->assertNotFound();
    }

    public function test_speeding_is_flagged_once_and_the_provider_is_told(): void
    {
        config(['tracking.speed_limit_kmh' => 80]);
        Sanctum::actingAs($this->rider);
        foreach ([2, 1] as $ago) {
            $this->postJson('/api/v1/driver/location', ['pings' => [['lat' => 6.52, 'lng' => 3.37, 'at' => now()->subSeconds($ago * 20)->toIso8601String(), 'accuracy' => 10, 'speed' => 30]]])->assertOk();
        }

        $this->assertSame(1, DB::table('risk_events')->where(['subject_type' => 'driver', 'subject_id' => $this->profile, 'type' => 'speeding'])->count(), 'one flag per cool-down');
        $this->assertTrue(DB::table('notifications')->where(['notifiable_id' => $this->owner->id, 'type' => 'driver.speeding'])->exists());

        $live = $this->actingAs($this->owner)->getJson('/provider/fleet/live')->assertOk();
        $this->assertTrue($live->json('drivers.0.speeding'));
        $this->assertSame(108, $live->json('drivers.0.speed_kmh'));
    }

    public function test_the_team_page_shows_who_is_online(): void
    {
        $this->actingAs($this->owner)->get('/provider/team')->assertOk()->assertSee('Online')->assertSee('see on map');
    }
}
