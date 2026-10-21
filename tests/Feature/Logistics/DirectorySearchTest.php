<?php

namespace Tests\Feature\Logistics;

use App\Models\User;
use App\Modules\Directory\DirectoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\Support\BuildsMarketplace;
use Tests\TestCase;

/** Customers find companies, riders and shoppers by typing @handle (or a name) and ask them for a delivery. */
class DirectorySearchTest extends TestCase
{
    use BuildsMarketplace;
    use RefreshDatabase;

    private int $swift;
    private User $swiftOwner;
    private int $musaProfile;
    private User $musa;
    private User $customer;
    private int $city;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedFoundation();
        DB::insert("INSERT INTO cities (name, slug, centre, created_at, updated_at) VALUES ('Testville', 'testville', ST_SetSRID(ST_MakePoint(5.9, 7.8), 4326)::geography, now(), now())");
        $this->city = (int) DB::table('cities')->value('id');

        [$this->swift, $this->swiftOwner] = $this->makeProvider('company', 'Swift Haulage');
        $this->profile($this->swift, 'swift', 'Same-day across the city', 4.8, 120);
        [$rider] = $this->makeProvider('independent_driver', 'Ada Okoro');
        $this->profile($rider, 'ada-rides', 'Fast bike rider', 4.6, 40);
        [$shop] = $this->makeProvider('market_shopper', 'Market Mama');
        $this->profile($shop, 'marketmama', 'I buy at Ojota market for you', null, 0);
        [$hidden] = $this->makeProvider('company', 'Secret Movers');
        $this->profile($hidden, 'secret', 'not listed', 5.0, 10, false);

        // Musa rides for Swift Haulage and is online near the customer.
        $this->musa = $this->makeUser('Musa Bello');
        $this->musaProfile = DB::table('driver_profiles')->insertGetId(['public_id' => (string) Str::ulid(), 'operator_id' => $this->swift, 'user_id' => $this->musa->id,
            'status' => 'active', 'availability' => 'online', 'last_seen_at' => now(), 'rating_avg' => 4.9, 'rating_count' => 30, 'created_at' => now(), 'updated_at' => now()]);
        DB::statement('UPDATE driver_profiles SET last_point = ST_SetSRID(ST_MakePoint(5.91, 7.81), 4326)::geography WHERE id = ?', [$this->musaProfile]);
        app(DirectoryService::class)->ensureDriverHandle($this->musaProfile);
        DB::table('operator_members')->insert(['operator_id' => $this->swift, 'user_id' => $this->musa->id, 'role' => 'driver', 'status' => 'active', 'created_at' => now(), 'updated_at' => now()]);

        $this->customer = $this->makeUser('Chidi Customer');
        DB::table('users')->where('id', $this->customer->id)->update(['must_change_password' => false]);
    }

    private function profile(int $op, string $slug, string $headline, ?float $rating, int $count, bool $listed = true): void
    {
        DB::table('operators')->where('id', $op)->update(['status' => 'active']);
        DB::table('provider_profiles')->insert(['public_id' => (string) Str::ulid(), 'operator_id' => $op, 'public_slug' => $slug, 'headline' => $headline, 'listed' => $listed,
            'rating_avg' => $rating, 'rating_count' => $count, 'jobs_completed' => $count, 'tier' => 'verified', 'service_types' => json_encode([$this->serviceTypeId()]), 'created_at' => now(), 'updated_at' => now()]);
    }

    private function find(string $q, array $extra = []): array
    {
        Sanctum::actingAs($this->customer->fresh());

        return $this->getJson('/api/v1/customer/directory?'.http_build_query(['q' => $q] + $extra))->assertOk()->json('results');
    }

    public function test_typing_at_and_a_handle_finds_companies_riders_and_shoppers(): void
    {
        $this->assertSame('musa', DB::table('driver_profiles')->where('id', $this->musaProfile)->value('handle'));

        $r = $this->find('@sw');
        $this->assertSame(['swift', 'company', 'Swift Haulage'], [$r[0]['handle'], $r[0]['kind'], $r[0]['name']]);
        $this->assertTrue($r[0]['online']); // Musa is out for them
        $this->assertSame($this->swift, $r[0]['book']['operator_id']);

        $r = $this->find('@musa', ['lat' => 7.80, 'lng' => 5.90]);
        $this->assertSame(['musa', 'rider', 'Musa', 'Swift Haulage'], [$r[0]['handle'], $r[0]['kind'], $r[0]['name'], $r[0]['company']]);
        $this->assertSame(['online' => true, 'distance_km' => 2], ['online' => $r[0]['online'], 'distance_km' => $r[0]['distance_km']]);
        $this->assertSame(['operator_id' => $this->swift, 'provider' => 'Swift Haulage', 'slug' => 'swift', 'driver_handle' => 'musa'], $r[0]['book']);
        $this->assertArrayNotHasKey('lat', $r[0]); // never where they are

        $this->assertSame('rider', $this->find('@ada')[0]['kind']);                 // independent rider
        $this->assertSame('shopper', $this->find('market')[0]['kind']);             // by name, no @
        $this->assertSame([], $this->find('@secret'));                              // not listed
        $this->assertSame(['rider', 'rider'], array_column($this->find('', ['kind' => 'rider']), 'kind'));

        // Suspended providers and their riders disappear.
        DB::table('operators')->where('id', $this->swift)->update(['status' => 'suspended']);
        $this->assertSame([], $this->find('@musa'));
        $this->assertSame([], $this->find('@swift'));
    }

    public function test_asking_a_rider_by_handle_sends_the_request_to_their_company(): void
    {
        Sanctum::actingAs($this->customer->fresh());
        $public = $this->postJson('/api/v1/customer/requests', [
            'type' => 'parcel', 'service_type_id' => $this->serviceTypeId(), 'city_id' => $this->city, 'driver_handle' => '@musa',
            'pickup' => ['line1' => '1 Market Rd', 'lat' => 7.80, 'lng' => 5.90], 'dropoff' => ['line1' => '9 Hill St', 'lat' => 7.82, 'lng' => 5.92],
        ])->assertCreated()->json('request_id');
        $req = DB::table('service_requests')->where('public_id', $public)->first();
        $this->assertSame(['direct', $this->musaProfile], [$req->visibility, (int) $req->preferred_driver_profile_id]);
        $this->assertSame([$this->swift], DB::table('request_invitations')->where('request_id', $req->id)->pluck('operator_id')->map(fn ($i) => (int) $i)->all());

        DB::table('users')->where('id', $this->swiftOwner->id)->update(['must_change_password' => false, 'current_operator_id' => $this->swift]);
        $this->actingAs($this->swiftOwner->fresh())->get("/provider/requests/{$public}")->assertOk()->assertSee('The customer asked for Musa Bello (@musa)', false);

        $this->postJson('/api/v1/customer/requests', ['type' => 'parcel', 'service_type_id' => $this->serviceTypeId(), 'city_id' => $this->city, 'driver_handle' => '@nobody',
            'pickup' => ['line1' => 'a', 'lat' => 7.80, 'lng' => 5.90], 'dropoff' => ['line1' => 'b', 'lat' => 7.82, 'lng' => 5.92]])->assertStatus(422);
    }

    public function test_handles_are_unique_and_can_be_changed_by_their_owners(): void
    {
        DB::table('users')->where('id', $this->swiftOwner->id)->update(['must_change_password' => false, 'current_operator_id' => $this->swift]);
        $owner = $this->swiftOwner->fresh();

        $this->actingAs($owner)->post('/provider/setup/handle', ['handle' => '@musa'])->assertSessionHas('error', '@musa is taken. Try another.');
        $this->actingAs($owner)->post('/provider/setup/handle', ['handle' => 'a!'])->assertSessionHas('error');
        $this->actingAs($owner)->post('/provider/setup/handle', ['handle' => '@SwiftNG'])->assertSessionHas('success');
        $this->assertSame('swiftng', DB::table('provider_profiles')->where('operator_id', $this->swift)->value('public_slug'));

        // The company renames its rider; the rider can change it too.
        $this->actingAs($owner)->put("/provider/team/members/{$this->musa->id}/handle", ['handle' => 'swiftng'])->assertSessionHas('error');
        $this->actingAs($owner)->put("/provider/team/members/{$this->musa->id}/handle", ['handle' => 'musa_b'])->assertSessionHas('success');
        DB::table('users')->where('id', $this->musa->id)->update(['must_change_password' => false, 'current_operator_id' => $this->swift]);
        $this->actingAs($this->musa->fresh())->get('/driver')->assertOk()->assertSee('Your @handle', false)->assertSee('musa_b');
        $this->actingAs($this->musa->fresh())->post('/driver/handle', ['handle' => 'musa-rides'])->assertSessionHas('success');
        $this->assertSame('musa-rides', DB::table('driver_profiles')->where('id', $this->musaProfile)->value('handle'));
    }

    public function test_customers_on_the_website_search_too(): void
    {
        $this->actingAs($this->customer->fresh())->get('/account/providers?q=@musa')->assertOk()
            ->assertSee('@musa', false)->assertSee('Rider at Swift Haulage')->assertSee('Ask @musa for a price', false);
        $this->actingAs($this->customer->fresh())->get('/account/requests/new?provider='.$this->swift.'&driver=musa')->assertOk()
            ->assertSee('asks for', false)->assertSee('name="driver" value="musa"', false);
    }

    public function test_the_driver_app_shows_and_changes_the_riders_handle(): void
    {
        DB::table('users')->where('id', $this->musa->id)->update(['must_change_password' => false, 'current_operator_id' => $this->swift]);
        Sanctum::actingAs($this->musa->fresh());
        $this->getJson('/api/v1/driver/handle')->assertOk()->assertJson(['handle' => 'musa', 'company' => 'Swift Haulage', 'findable' => true]);
        $this->putJson('/api/v1/driver/handle', ['handle' => '@swift'])->assertStatus(422)->assertJson(['message' => '@swift is taken. Try another.']);
        $this->putJson('/api/v1/driver/handle', ['handle' => '@Musa_Rides'])->assertOk()->assertJson(['handle' => 'musa_rides']);
        $this->assertSame('musa_rides', DB::table('driver_profiles')->where('id', $this->musaProfile)->value('handle'));
    }

    public function test_the_apps_are_told_when_the_phone_number_is_verified(): void
    {
        Sanctum::actingAs($this->customer->fresh());
        $this->getJson('/api/v1/auth/me')->assertOk()->assertJsonPath('user.phone_verified', false);
        DB::table('users')->where('id', $this->customer->id)->update(['phone_verified_at' => now()]);
        Sanctum::actingAs($this->customer->fresh());
        $this->getJson('/api/v1/auth/me')->assertOk()->assertJsonPath('user.phone_verified', true);
    }
}
