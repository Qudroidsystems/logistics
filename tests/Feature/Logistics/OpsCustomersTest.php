<?php

namespace Tests\Feature\Logistics;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Tests\Support\BuildsMarketplace;
use Tests\TestCase;

/** Ops > Customers: search, the customer page, staff notes and suspension. */
class OpsCustomersTest extends TestCase
{
    use BuildsMarketplace;
    use RefreshDatabase;

    public function test_search_page_notes_and_suspension(): void
    {
        $this->useRegion('CA');
        $this->seedFoundation();
        $customer = $this->makeUser('Grace Hopper');
        $pid = (string) Str::ulid();
        DB::table('customer_profiles')->insert(['public_id' => $pid, 'user_id' => $customer->id, 'created_at' => now(), 'updated_at' => now()]);
        $customer->createToken('app');

        $agent = $this->makeUser('Sam Support');
        $boss = $this->makeUser('Ops Lead');
        foreach (['View customer', 'Suspend customer', 'View customer wallet'] as $p) {
            Permission::findOrCreate($p, 'web');
        }
        $agent->givePermissionTo('View customer');
        $boss->givePermissionTo(['View customer', 'Suspend customer', 'View customer wallet']);

        $this->actingAs($customer)->get('/ops/customers')->assertForbidden();
        $this->actingAs($agent)->get('/ops/customers?q=grace')->assertOk()->assertSee('Grace Hopper');
        $this->actingAs($agent)->get("/ops/customers/{$pid}")->assertOk()->assertSee('Grace Hopper')->assertDontSee('Suspend</button>', false);

        $this->actingAs($agent)->post("/ops/customers/{$pid}/notes", ['body' => 'Prefers calls after 5pm', 'pinned' => '1'])->assertSessionHas('success');
        $this->assertTrue(DB::table('customer_notes')->where(['user_id' => $customer->id, 'pinned' => true])->exists());

        $this->actingAs($agent)->post("/ops/customers/{$pid}/suspend", ['suspend' => '1', 'reason' => 'Fraud'])->assertForbidden();
        $this->actingAs($boss)->post("/ops/customers/{$pid}/suspend", ['suspend' => '1', 'reason' => 'Chargebacks'])->assertSessionHas('success');
        $this->assertTrue((bool) $customer->fresh()->is_disabled);
        $this->assertSame(0, DB::table('personal_access_tokens')->where('tokenable_id', $customer->id)->count());
        $this->actingAs($boss)->post("/ops/customers/{$pid}/suspend", ['suspend' => '0'])->assertSessionHas('success');
        $this->assertFalse((bool) $customer->fresh()->is_disabled);
    }
}
