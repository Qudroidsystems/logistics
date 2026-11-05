<?php

namespace Tests\Feature\Logistics;

use App\Models\User;
use App\Modules\Dispatch\ManualAssignmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Support\BuildsMarketplace;
use Tests\TestCase;

class DispatchBoardTest extends TestCase
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

    private function driver(string $name): User
    {
        $u = User::factory()->create(['name' => $name, 'must_change_password' => false]);
        DB::table('operator_members')->insert(['operator_id' => $this->op, 'user_id' => $u->id, 'role' => 'driver', 'status' => 'active', 'created_at' => now(), 'updated_at' => now()]);
        DB::table('users')->where('id', $u->id)->update(['current_operator_id' => $this->op]);
        DB::table('driver_profiles')->insert(['public_id' => (string) Str::ulid(), 'user_id' => $u->id, 'operator_id' => $this->op, 'status' => 'active', 'availability' => 'online', 'max_active_jobs' => 1, 'created_at' => now(), 'updated_at' => now()]);

        return $u;
    }

    public function test_waiting_job_shows_with_drivers_and_can_be_assigned_from_the_board(): void
    {
        $d = $this->driver('Bola Driver');
        $this->actingAs($this->owner)->get('/provider/dispatch')->assertOk()->assertSee('Needs a driver')->assertSee('1 Market Rd')->assertSee('Bola Driver');

        $this->actingAs($this->owner)->post("/provider/jobs/{$this->shipment}/assign", ['driver_user_id' => $d->id])->assertSessionHasNoErrors();
        $this->actingAs($this->owner)->get('/provider/dispatch')->assertOk()->assertSee('On the road')->assertSee('Nothing is waiting for a driver.');
    }

    public function test_handed_back_jobs_are_flagged_and_driver_problems_show(): void
    {
        $d = $this->driver('Bola Driver');
        app(ManualAssignmentService::class)->assign($this->op, $this->shipment, $d->id, $this->owner->id);
        app(\App\Modules\Dispatch\DriverService::class)->reportIssue((int) DB::table('driver_profiles')->where('user_id', $d->id)->value('id'), $this->shipment, 'wrong_address');
        $this->actingAs($this->owner)->get('/provider/dispatch')->assertSee('Driver reported a problem');

        app(\App\Modules\Dispatch\DriverService::class)->release((int) DB::table('driver_profiles')->where('user_id', $d->id)->value('id'), $this->shipment, 'vehicle_problem');
        $this->actingAs($this->owner)->get('/provider/dispatch')->assertSee('Handed back or no match');
    }

    public function test_jobs_from_the_same_pickup_are_grouped_and_given_out_together(): void
    {
        $customer = $this->makeUser('Shop');
        $this->fundCustomer($customer->id, 1_000_000);
        app(\App\Modules\Payments\PaymentService::class)->payWithWallet($this->lockedAgreement($customer->id, $this->op, $this->owner->id), $customer->id);
        $jobs = DB::table('shipments')->where('operator_id', $this->op)->pluck('public_id')->all();
        $this->assertCount(2, $jobs);
        $d = $this->driver('Bola Driver');

        $this->actingAs($this->owner)->get('/provider/dispatch')->assertOk()->assertSee('2 jobs from 1 Market Rd')->assertSee('Assign all');
        // Room for one job only: all or nothing.
        $this->actingAs($this->owner)->post('/provider/dispatch/assign-group', ['jobs' => $jobs, 'driver_user_id' => $d->id])->assertSessionHas('error');
        $this->assertSame(0, DB::table('assignments')->count());

        DB::table('driver_profiles')->where('user_id', $d->id)->update(['max_active_jobs' => 3]);
        $this->actingAs($this->owner)->post('/provider/dispatch/assign-group', ['jobs' => $jobs, 'driver_user_id' => $d->id])->assertSessionHas('success');
        $this->assertSame(2, DB::table('assignments')->whereIn('status', ['accepted'])->count());
        $this->assertSame(1, DB::table('notifications')->where(['notifiable_id' => $d->id, 'type' => 'delivery.jobs_assigned'])->count());
        $this->assertSame(0, DB::table('notifications')->where(['notifiable_id' => $d->id, 'type' => 'delivery.job_assigned'])->count());
        // The driver sees both pickups first, then the drop-offs, and each job's next stop.
        $me = $this->actingAs($d, 'sanctum')->getJson('/api/v1/driver/me')->assertOk()->json();
        $this->assertSame(['pickup', 'pickup'], array_slice(array_column($me['run'], 'type'), 0, 2));
        $this->assertCount(4, $me['run']);
        $this->assertSame([1, 2], array_column(array_column($me['jobs'], 'next_stop'), 'position'));
    }

    public function test_only_job_roles_may_open_the_board(): void
    {
        $driver = $this->driver('Bola Driver');
        $this->actingAs($driver)->get('/provider/dispatch')->assertForbidden();
    }
}
