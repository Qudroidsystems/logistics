<?php

namespace Tests\Feature\Logistics;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Spatie\Permission\Models\Permission;
use Tests\Support\BuildsMarketplace;
use Tests\TestCase;

/** Help requests (customer ↔ staff), deleting an account, the legal pages, address search and the System page. */
class SupportAndAccountTest extends TestCase
{
    use BuildsMarketplace;
    use RefreshDatabase;

    private User $customer;
    private User $staff;

    protected function setUp(): void
    {
        parent::setUp();
        $this->useRegion('CA');
        $this->seedFoundation();
        $this->customer = $this->makeUser('Ada');
        $this->customer->forceFill(['password' => Hash::make('secret-pass-1')])->save();
        $this->staff = $this->makeUser('Sam Support');
        foreach (['View support ticket', 'Reply support ticket', 'View system health'] as $p) {
            Permission::findOrCreate($p, 'web');
            $this->staff->givePermissionTo($p);
        }
    }

    public function test_help_request_round_trip(): void
    {
        $this->actingAs($this->customer)->get('/account/help')->assertOk()->assertSee('Ask for help');
        $this->actingAs($this->customer)->post('/account/help', ['category' => 'late', 'body' => 'Where is my parcel please?'])->assertRedirect();
        $t = DB::table('support_tickets')->first();
        $this->assertSame('high', $t->priority);
        $this->assertTrue(DB::table('notifications')->where(['notifiable_id' => $this->staff->id, 'type' => 'support.new'])->exists());

        // Staff: inbox, internal note (hidden from the customer), reply.
        $this->actingAs($this->customer)->get('/ops/support')->assertForbidden();
        $this->actingAs($this->staff)->get('/ops/support')->assertOk()->assertSee($t->number);
        $this->actingAs($this->staff)->post("/ops/support/{$t->public_id}/reply", ['body' => 'Check with the driver first', 'internal' => '1'])->assertSessionHas('success');
        $this->actingAs($this->staff)->post("/ops/support/{$t->public_id}/reply", ['body' => 'It is 10 minutes away.', 'status' => 'pending_customer'])->assertSessionHas('success');
        $t = DB::table('support_tickets')->first();
        $this->assertSame('pending_customer', $t->status);
        $this->assertNotNull($t->first_responded_at);
        $this->assertSame($this->staff->id, (int) $t->assignee_id);

        $this->actingAs($this->customer)->get("/account/help/{$t->public_id}")->assertOk()->assertSee('10 minutes away')->assertDontSee('Check with the driver');
        $this->actingAs($this->customer)->post("/account/help/{$t->public_id}", ['body' => 'Got it, thanks!'])->assertSessionHas('success');
        $this->assertSame('open', DB::table('support_tickets')->value('status'));

        // App API, and someone else can't read it.
        $this->actingAs($this->customer, 'sanctum')->getJson('/api/v1/customer/support')->assertOk()->assertJsonPath('data.0.number', $t->number);
        $this->actingAs($this->customer, 'sanctum')->getJson("/api/v1/customer/support/{$t->public_id}")->assertOk()->assertJsonCount(3, 'messages');
        $this->actingAs($this->makeUser('Eve'), 'sanctum')->getJson("/api/v1/customer/support/{$t->public_id}")->assertNotFound();
        $this->actingAs($this->customer, 'sanctum')->postJson('/api/v1/customer/support', ['category' => 'other', 'body' => 'Hi'])->assertStatus(422);
    }

    public function test_delete_account(): void
    {
        $this->get('/privacy')->assertOk()->assertSee('Privacy policy');
        $this->get('/terms')->assertOk();
        $this->get('/delete-account')->assertOk();

        $this->actingAs($this->customer, 'sanctum')->postJson('/api/v1/auth/delete-account', ['password' => 'wrong'])->assertStatus(422);
        $this->fundCustomer($this->customer->id, 500);
        $this->actingAs($this->customer, 'sanctum')->getJson('/api/v1/auth/delete-account/check')->assertOk()->assertJsonCount(1, 'blockers');

        $other = $this->makeUser('Bo');
        $other->forceFill(['password' => Hash::make('secret-pass-2')])->save();
        $email = $other->email;
        $other->createToken('phone');
        $this->actingAs($other, 'sanctum')->postJson('/api/v1/auth/delete-account', ['password' => 'secret-pass-2', 'reason' => 'Moving'])->assertOk();
        $row = DB::table('users')->where('id', $other->id)->first();
        $this->assertSame('Deleted user', $row->name);
        $this->assertNotSame($email, $row->email);
        $this->assertNull($row->phone_number);
        $this->assertTrue((bool) $row->is_disabled);
        $this->assertSame(0, DB::table('personal_access_tokens')->where('tokenable_id', $other->id)->count());
        $this->assertSame('app', DB::table('account_deletions')->where('user_id', $other->id)->value('via'));
        $this->postJson('/api/v1/auth/login', ['login' => $email, 'password' => 'secret-pass-2'])->assertStatus(401);
    }

    public function test_address_search_and_system_page(): void
    {
        config(['maps.geocoder' => 'nominatim', 'cache.default' => 'array']);
        Http::fake(['*/search*' => Http::response([['osm_type' => 'way', 'osm_id' => 1, 'display_name' => '1 King St W, Kitchener', 'lat' => '43.45', 'lon' => '-80.49']])]);
        $this->getJson('/geo/search?q=1+King+St')->assertOk()->assertJsonPath('type_ahead', false)->assertJsonPath('results.0.lat', 43.45);
        $this->getJson('/api/v1/app-config')->assertOk()->assertJsonStructure(['region' => ['tiles' => ['url']]]);

        $this->actingAs($this->customer)->get('/ops/system')->assertForbidden();
        $this->actingAs($this->staff)->get('/ops/system')->assertOk()->assertSee('The scheduler is not running');
        $this->artisan('system:watch')->assertSuccessful();
        $this->actingAs($this->staff)->get('/ops/system')->assertOk()->assertDontSee('The scheduler is not running')->assertSee('recurring:run');
    }

    public function test_errors_are_grouped_shown_and_resolved(): void
    {
        $reports = app(\App\Modules\System\ErrorReports::class);
        foreach (['order 1', 'order 2'] as $m) {
            $reports->record(new \RuntimeException("Payment for {$m} broke"));   // same line: one report, counted twice
        }
        $row = DB::table('error_reports')->first();
        $this->assertSame(1, DB::table('error_reports')->count());
        $this->assertSame(2, (int) $row->count);
        $this->assertSame(1, DB::table('notifications')->where(['notifiable_id' => $this->staff->id, 'type' => 'system.problem'])->count());

        $this->actingAs($this->staff)->get('/ops/system')->assertOk()->assertSee('Payment for order 2 broke');
        $this->actingAs($this->customer)->post("/ops/system/errors/{$row->id}/resolve")->assertForbidden();
        $this->actingAs($this->staff)->post("/ops/system/errors/{$row->id}/resolve")->assertSessionHas('success');
        $this->assertSame('resolved', DB::table('error_reports')->value('status'));
    }
}
