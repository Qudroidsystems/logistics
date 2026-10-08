<?php

namespace Tests\Feature\Logistics;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Support\BuildsMarketplace;
use Tests\TestCase;

class SeedDemoTest extends TestCase
{
    use BuildsMarketplace;
    use RefreshDatabase;

    public function test_demo_world_works_and_is_repeatable(): void
    {
        $this->seedFoundation();
        $this->artisan('logistics:demo')->assertSuccessful();
        $this->artisan('logistics:demo')->assertSuccessful();

        $this->assertSame(1, DB::table('cities')->where('slug', 'demo-lokoja')->count());
        $this->assertSame(1, DB::table('zones')->count());
        $this->assertSame(3, User::whereIn('email', ['demo.customer@example.test', 'demo.provider@example.test', 'demo.driver@example.test'])->count());

        $customer = User::where('email', 'demo.customer@example.test')->first();
        $this->assertSame(5_000_000, $this->walletBalance('customer', $customer->id));

        $this->actingAs($customer)->get('/account/providers?service_type_id='.$this->serviceTypeId())->assertOk()->assertSee('Demo Haulage');
        $this->actingAs(User::where('email', 'demo.provider@example.test')->first())->get('/provider')->assertOk();
        $this->actingAs(User::where('email', 'demo.driver@example.test')->first())->get('/driver')->assertOk()->assertSee('Go online');
    }
}
