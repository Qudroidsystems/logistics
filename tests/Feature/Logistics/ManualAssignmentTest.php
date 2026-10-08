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

class ManualAssignmentTest extends TestCase
{
    use BuildsMarketplace;
    use RefreshDatabase;

    private int $op;
    private User $owner;
    private string $shipment;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedFoundation();
        [$this->op, $this->owner] = $this->makeProvider();
        $customer = $this->makeUser('Customer');
        $this->fundCustomer($customer->id, 1_000_000);
        $agreement = $this->lockedAgreement($customer->id, $this->op, $this->owner->id);
        app(\App\Modules\Payments\PaymentService::class)->payWithWallet($agreement, $customer->id);
        $this->shipment = (string) DB::table('shipments')->where('operator_id', $this->op)->value('public_id');
    }

    private function driver(string $name, string $status = 'active', int $max = 1): User
    {
        $u = User::factory()->create(['name' => $name]);
        DB::table('operator_members')->insert(['operator_id' => $this->op, 'user_id' => $u->id, 'role' => 'driver', 'status' => 'active', 'created_at' => now(), 'updated_at' => now()]);
        DB::table('driver_profiles')->insert([
            'public_id' => (string) Str::ulid(), 'user_id' => $u->id, 'operator_id' => $this->op, 'status' => $status, 'availability' => 'online',
            'max_active_jobs' => $max, 'created_at' => now(), 'updated_at' => now(),
        ]);

        return $u;
    }

    public function test_which_states_can_be_assigned(): void
    {
        $this->assertTrue(ManualAssignmentService::canAssign('awaiting_dispatch'));
        $this->assertTrue(ManualAssignmentService::canAssign('assigned'));
        $this->assertFalse(ManualAssignmentService::canAssign('picked_up'));
        $this->assertFalse(ManualAssignmentService::canAssign('delivered'));
    }

    public function test_assigning_a_driver_to_a_paid_job(): void
    {
        $d = $this->driver('Tunde');
        Sanctum::actingAs($this->owner);

        $this->postJson("/api/v1/provider/shipments/{$this->shipment}/assign", ['driver_user_id' => $d->id])->assertOk();

        $this->assertSame('assigned', DB::table('shipments')->where('public_id', $this->shipment)->value('status'));
        $a = DB::table('assignments')->first();
        $this->assertSame('dispatcher', $a->assigned_by);
        $this->assertSame('accepted', $a->status);
        $this->assertSame('on_job', DB::table('driver_profiles')->where('user_id', $d->id)->value('availability'));
        $this->assertTrue(DB::table('notifications')->where(['notifiable_id' => $d->id, 'type' => 'delivery.job_assigned'])->exists());
    }

    public function test_only_approved_drivers_on_the_team_and_not_overbooked(): void
    {
        $pending = $this->driver('New', 'applied');
        $busy = $this->driver('Busy');
        $stranger = User::factory()->create();
        Sanctum::actingAs($this->owner);

        $this->postJson("/api/v1/provider/shipments/{$this->shipment}/assign", ['driver_user_id' => $pending->id])->assertStatus(422);
        $this->postJson("/api/v1/provider/shipments/{$this->shipment}/assign", ['driver_user_id' => $stranger->id])->assertStatus(422);

        // A driver who cannot take any more jobs is refused.
        DB::table('driver_profiles')->where('user_id', $busy->id)->update(['max_active_jobs' => 0]);
        $this->postJson("/api/v1/provider/shipments/{$this->shipment}/assign", ['driver_user_id' => $busy->id])->assertStatus(422);
        $this->assertSame(0, DB::table('assignments')->count());
    }

    public function test_reassigning_frees_the_first_driver_and_keeps_one_live_assignment(): void
    {
        $a = $this->driver('Ade');
        $b = $this->driver('Bayo');
        Sanctum::actingAs($this->owner);

        $this->postJson("/api/v1/provider/shipments/{$this->shipment}/assign", ['driver_user_id' => $a->id])->assertOk();
        $this->postJson("/api/v1/provider/shipments/{$this->shipment}/assign", ['driver_user_id' => $a->id])->assertStatus(422);   // already theirs
        $this->postJson("/api/v1/provider/shipments/{$this->shipment}/assign", ['driver_user_id' => $b->id, 'reason' => 'bike broke down'])->assertOk();

        $this->assertSame(1, DB::table('assignments')->whereIn('status', ['assigned', 'accepted', 'en_route', 'active'])->count());
        $this->assertSame('reassigned', DB::table('assignments')->where('status', 'reassigned')->value('status'));
        $this->assertSame('online', DB::table('driver_profiles')->where('user_id', $a->id)->value('availability'));
        $this->assertSame('on_job', DB::table('driver_profiles')->where('user_id', $b->id)->value('availability'));
    }

    public function test_no_change_once_the_goods_are_collected(): void
    {
        $d = $this->driver('Late');
        DB::table('shipments')->where('public_id', $this->shipment)->update(['status' => 'picked_up']);
        Sanctum::actingAs($this->owner);

        $this->postJson("/api/v1/provider/shipments/{$this->shipment}/assign", ['driver_user_id' => $d->id])->assertStatus(422);
    }

    public function test_another_provider_cannot_assign_this_job(): void
    {
        [$otherOp, $otherOwner] = $this->makeProvider('company', 'Rival Haulage');
        Sanctum::actingAs($otherOwner);

        $this->postJson("/api/v1/provider/shipments/{$this->shipment}/assign", ['driver_user_id' => $otherOwner->id])->assertStatus(422);
        $this->assertSame(0, DB::table('assignments')->count());
    }

    public function test_offers_still_open_are_withdrawn(): void
    {
        $d = $this->driver('Offer');
        $sid = (int) DB::table('shipments')->value('id');
        $run = DB::table('dispatch_runs')->insertGetId(['shipment_id' => $sid, 'strategy' => 'scored', 'started_at' => now()]);
        $offer = DB::table('dispatch_offers')->insertGetId([
            'run_id' => $run, 'shipment_id' => $sid, 'driver_profile_id' => DB::table('driver_profiles')->where('user_id', $d->id)->value('id'), 'operator_id' => $this->op,
            'sequence' => 1, 'offered_at' => now(), 'expires_at' => now()->addMinute(),
        ]);
        Sanctum::actingAs($this->owner);

        $this->postJson("/api/v1/provider/shipments/{$this->shipment}/assign", ['driver_user_id' => $d->id])->assertOk();

        $this->assertSame('cancelled_by_system', DB::table('dispatch_offers')->where('id', $offer)->value('response'));
        $this->assertSame('assigned', DB::table('dispatch_runs')->where('id', $run)->value('outcome'));
    }
}
