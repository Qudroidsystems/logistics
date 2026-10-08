<?php

namespace Tests\Feature\Logistics;

use App\Models\User;
use App\Modules\Dispatch\ManualAssignmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\Support\BuildsMarketplace;
use Tests\TestCase;

/** A driver goes online, runs an assigned job and is held to the rules at each stop. */
class DriverPagesTest extends TestCase
{
    use BuildsMarketplace;
    use RefreshDatabase;

    private int $op;
    private User $owner;
    private User $driver;
    private string $shipment;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedFoundation();
        [$this->op, $this->owner] = $this->makeProvider();
        $this->driver = $this->makeDriver('Dayo Driver');

        $customer = $this->makeUser('Customer');
        $this->fundCustomer($customer->id, 1_000_000);
        $agreement = $this->lockedAgreement($customer->id, $this->op, $this->owner->id);
        app(\App\Modules\Payments\PaymentService::class)->payWithWallet($agreement, $customer->id);
        $this->shipment = (string) DB::table('shipments')->where('operator_id', $this->op)->value('public_id');
    }

    private function makeDriver(string $name): User
    {
        $u = User::factory()->create(['name' => $name, 'must_change_password' => false]);
        DB::table('operator_members')->insert(['operator_id' => $this->op, 'user_id' => $u->id, 'role' => 'driver', 'status' => 'active', 'created_at' => now(), 'updated_at' => now()]);
        DB::table('users')->where('id', $u->id)->update(['current_operator_id' => $this->op]);
        DB::table('driver_profiles')->insert([
            'public_id' => (string) Str::ulid(), 'user_id' => $u->id, 'operator_id' => $this->op, 'status' => 'active', 'availability' => 'offline',
            'max_active_jobs' => 1, 'created_at' => now(), 'updated_at' => now(),
        ]);

        return $u->fresh();
    }

    public function test_only_active_drivers_get_in(): void
    {
        $this->get('/driver')->assertRedirect();
        $this->actingAs($this->makeUser('Nobody'))->get('/driver')->assertForbidden();
        $this->actingAs($this->driver)->get('/driver')->assertOk()->assertSee('Go online');
    }

    public function test_driver_can_change_status_until_a_job_starts(): void
    {
        $this->actingAs($this->driver)->post('/driver/availability', ['availability' => 'online'])->assertSessionHasNoErrors();
        $this->assertSame('online', DB::table('driver_profiles')->where('user_id', $this->driver->id)->value('availability'));

        app(ManualAssignmentService::class)->assign($this->op, $this->shipment, $this->driver->id, $this->owner->id);
        $this->actingAs($this->driver)->post('/driver/availability', ['availability' => 'offline'])->assertSessionHas('error');
        $this->assertSame('on_job', DB::table('driver_profiles')->where('user_id', $this->driver->id)->value('availability'));
    }

    public function test_running_a_job(): void
    {
        $this->actingAs($this->driver)->post('/driver/availability', ['availability' => 'online']);
        app(ManualAssignmentService::class)->assign($this->op, $this->shipment, $this->driver->id, $this->owner->id);

        $this->actingAs($this->driver)->get('/driver')->assertOk()->assertSee('1 Market Rd');
        $this->actingAs($this->driver)->get("/driver/jobs/{$this->shipment}")->assertOk()->assertSee('Start trip to pickup')->assertSee('9 Hill St');

        $this->actingAs($this->driver)->post("/driver/jobs/{$this->shipment}/start")->assertSessionHasNoErrors();
        $this->assertSame('heading_to_pickup', DB::table('shipments')->where('public_id', $this->shipment)->value('status'));
        $this->actingAs($this->driver)->post("/driver/jobs/{$this->shipment}/start")->assertSessionHas('error');

        $stops = DB::table('shipment_stops')->join('shipments', 'shipments.id', '=', 'shipment_stops.shipment_id')->where('shipments.public_id', $this->shipment)->orderBy('seq')->get(['shipment_stops.id', 'shipment_stops.type']);

        // Far from the pickup: refused.
        $this->actingAs($this->driver)->post("/driver/jobs/{$this->shipment}/stops/{$stops[0]->id}/complete", ['lat' => 8.5, 'lng' => 6.5])->assertSessionHas('error');
        $this->assertSame('heading_to_pickup', DB::table('shipments')->where('public_id', $this->shipment)->value('status'));

        // At the pickup (7.80, 5.90): accepted, job moves on.
        $this->actingAs($this->driver)->post("/driver/jobs/{$this->shipment}/stops/{$stops[0]->id}/complete", ['lat' => 7.80, 'lng' => 5.90, 'otp' => ''])->assertSessionHasNoErrors();
        $this->assertSame('in_transit', DB::table('shipments')->where('public_id', $this->shipment)->value('status'));

        // At the drop-off (7.82, 5.92) with a wrong code: refused.
        $this->actingAs($this->driver)->post("/driver/jobs/{$this->shipment}/stops/{$stops[1]->id}/complete", ['lat' => 7.82, 'lng' => 5.92, 'otp' => '000000'])->assertSessionHas('error');
        $this->assertSame('in_transit', DB::table('shipments')->where('public_id', $this->shipment)->value('status'));
    }

    public function test_other_drivers_cannot_see_or_touch_the_job(): void
    {
        app(ManualAssignmentService::class)->assign($this->op, $this->shipment, $this->driver->id, $this->owner->id);
        $other = $this->makeDriver('Other Driver');
        $this->actingAs($other)->get("/driver/jobs/{$this->shipment}")->assertNotFound();
        $this->actingAs($other)->post("/driver/jobs/{$this->shipment}/start")->assertSessionHas('error');
    }

    public function test_driver_api(): void
    {
        Sanctum::actingAs($this->driver);
        $this->postJson('/api/v1/driver/availability', ['availability' => 'online'])->assertOk()->assertJsonPath('availability', 'online');
        $this->getJson('/api/v1/driver/me')->assertOk()->assertJsonPath('driver.availability', 'online')->assertJsonCount(0, 'jobs');

        app(ManualAssignmentService::class)->assign($this->op, $this->shipment, $this->driver->id, $this->owner->id);
        $this->getJson('/api/v1/driver/jobs')->assertOk()->assertJsonCount(1, 'live');
        $this->getJson("/api/v1/driver/jobs/{$this->shipment}")->assertOk()->assertJsonCount(2, 'stops');
        $this->postJson("/api/v1/driver/jobs/{$this->shipment}/start")->assertOk();
        $this->postJson('/api/v1/driver/availability', ['availability' => 'offline'])->assertStatus(422);
    }

    public function test_offers_can_be_accepted_and_declined(): void
    {
        DB::table('driver_profiles')->where('user_id', $this->driver->id)->update(['availability' => 'online']);
        $driverId = DB::table('driver_profiles')->where('user_id', $this->driver->id)->value('id');
        $sid = DB::table('shipments')->where('public_id', $this->shipment)->value('id');
        DB::table('shipments')->where('id', $sid)->update(['status' => 'offered']);
        $runId = DB::table('dispatch_runs')->insertGetId(['shipment_id' => $sid, 'strategy' => 'scored']);
        $offer = DB::table('dispatch_offers')->insertGetId([
            'run_id' => $runId, 'shipment_id' => $sid, 'driver_profile_id' => $driverId, 'operator_id' => $this->op, 'sequence' => 1,
            'payout_offered' => 150_000, 'expires_at' => now()->addSeconds(60),
        ]);

        $this->actingAs($this->driver)->get('/driver')->assertOk()->assertSee('₦1,500')->assertSee('Accept');
        $this->actingAs($this->driver)->post("/driver/offers/{$offer}/accept")->assertRedirect(route('driver.job', $this->shipment));
        $this->assertSame('assigned', DB::table('shipments')->where('id', $sid)->value('status'));
        $this->assertSame('on_job', DB::table('driver_profiles')->where('id', $driverId)->value('availability'));
    }

    public function test_driver_can_report_a_problem_and_release_a_job_before_pickup(): void
    {
        app(ManualAssignmentService::class)->assign($this->op, $this->shipment, $this->driver->id, $this->owner->id);

        $this->actingAs($this->driver)->post("/driver/jobs/{$this->shipment}/issue", ['reason' => 'wrong_address', 'note' => 'No such street'])->assertSessionHasNoErrors();
        $this->assertSame(1, DB::table('shipment_events')->where('type', 'driver_issue')->count());
        $this->actingAs($this->driver)->post("/driver/jobs/{$this->shipment}/issue", ['reason' => 'nonsense'])->assertSessionHas('error');

        $this->actingAs($this->driver)->post("/driver/jobs/{$this->shipment}/release", ['reason' => 'vehicle_problem'])->assertRedirect(route('driver.home'));
        $s = DB::table('shipments')->where('public_id', $this->shipment)->first();
        $this->assertSame('awaiting_dispatch', $s->status);
        $this->assertTrue((bool) $s->needs_manual_dispatch);
        $this->assertSame('reassigned', DB::table('assignments')->where('shipment_id', $s->id)->value('status'));
        $this->assertSame('online', DB::table('driver_profiles')->where('user_id', $this->driver->id)->value('availability'));

        // The dispatcher can give it to someone else straight away.
        $other = $this->makeDriver('Second Driver');
        app(ManualAssignmentService::class)->assign($this->op, $this->shipment, $other->id, $this->owner->id);
        $this->assertSame('assigned', DB::table('shipments')->where('public_id', $this->shipment)->value('status'));
    }

    public function test_a_job_cannot_be_released_once_the_goods_are_picked_up(): void
    {
        app(ManualAssignmentService::class)->assign($this->op, $this->shipment, $this->driver->id, $this->owner->id);
        $this->actingAs($this->driver)->post("/driver/jobs/{$this->shipment}/start");
        $pickup = DB::table('shipment_stops')->join('shipments', 'shipments.id', '=', 'shipment_stops.shipment_id')->where('shipments.public_id', $this->shipment)->where('type', 'pickup')->value('shipment_stops.id');
        $this->actingAs($this->driver)->post("/driver/jobs/{$this->shipment}/stops/{$pickup}/complete", ['lat' => 7.80, 'lng' => 5.90]);

        $this->actingAs($this->driver)->post("/driver/jobs/{$this->shipment}/release", ['reason' => 'other'])->assertSessionHas('error');
        $this->assertSame('in_transit', DB::table('shipments')->where('public_id', $this->shipment)->value('status'));
        $this->actingAs($this->driver)->post("/driver/jobs/{$this->shipment}/issue", ['reason' => 'receiver_unreachable'])->assertSessionHasNoErrors();
    }

    public function test_earnings_add_up_finished_jobs(): void
    {
        $driverId = (int) DB::table('driver_profiles')->where('user_id', $this->driver->id)->value('id');
        $sid = (int) DB::table('shipments')->where('public_id', $this->shipment)->value('id');
        DB::table('assignments')->insert([
            'public_id' => (string) Str::ulid(), 'shipment_id' => $sid, 'driver_profile_id' => $driverId, 'operator_id' => $this->op, 'status' => 'completed',
            'payout_amount' => 120_000, 'completed_at' => now(), 'created_at' => now(), 'updated_at' => now(),
        ]);
        $this->actingAs($this->driver)->get('/driver/history')->assertOk()->assertSee('₦1,200');
        Sanctum::actingAs($this->driver);
        $this->getJson('/api/v1/driver/earnings')->assertOk()->assertJsonPath('today', 120000);
    }
}
