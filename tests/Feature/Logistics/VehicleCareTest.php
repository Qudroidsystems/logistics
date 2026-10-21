<?php

namespace Tests\Feature\Logistics;

use App\Models\User;
use App\Modules\Fleet\VehicleCareService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Support\BuildsMarketplace;
use Tests\TestCase;

/** The Vehicles page (papers, servicing, fuel) and the daily fleet reminders. */
class VehicleCareTest extends TestCase
{
    use BuildsMarketplace;
    use RefreshDatabase;

    private int $op;
    private User $owner;
    private string $vehicle;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedFoundation();
        [$this->op, $this->owner] = $this->makeProvider();
        $this->vehicle = (string) Str::ulid();
        DB::table('vehicles')->insert([
            'public_id' => $this->vehicle, 'operator_id' => $this->op, 'vehicle_type_id' => DB::table('vehicle_types')->value('id'), 'plate' => 'LAG123XY',
            'status' => 'active', 'insurance_expires_on' => now()->addDays(5)->toDateString(), 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    public function test_the_page_shows_what_is_due(): void
    {
        $this->actingAs($this->owner)->get('/provider/vehicles')->assertOk()->assertSee('LAG123XY')->assertSee('Insurance expires in 5 days');
    }

    public function test_a_service_moves_the_next_one_on(): void
    {
        $this->actingAs($this->owner)->put("/provider/vehicles/{$this->vehicle}/care", ['service_interval_days' => 90, 'service_interval_km' => 5000, 'odometer_km' => 40000])->assertRedirect();
        $this->actingAs($this->owner)->post("/provider/vehicles/{$this->vehicle}/service", ['serviced_on' => now()->toDateString(), 'odometer_km' => 41000, 'cost_naira' => 25000])->assertRedirect();

        $v = DB::table('vehicles')->where('public_id', $this->vehicle)->first();
        $this->assertSame(now()->addDays(90)->toDateString(), $v->next_service_on);
        $this->assertSame(46000, (int) $v->next_service_km);
        $this->assertSame(2_500_000, (int) DB::table('vehicle_service_logs')->value('cost_kobo'));
    }

    public function test_fuel_is_recorded_and_odometers_only_go_up(): void
    {
        $this->actingAs($this->owner)->post("/provider/vehicles/{$this->vehicle}/fuel", ['filled_on' => now()->toDateString(), 'litres' => 20, 'cost_naira' => 18000, 'odometer_km' => 1000])
            ->assertSessionHas('success');
        $this->actingAs($this->owner)->post("/provider/vehicles/{$this->vehicle}/fuel", ['filled_on' => now()->toDateString(), 'litres' => 10, 'cost_naira' => 9000, 'odometer_km' => 900])
            ->assertSessionHas('error');

        $this->assertSame(1, DB::table('vehicle_fuel_logs')->count());
        $this->actingAs($this->owner)->get('/provider/vehicles')->assertSee('₦18,000');
    }

    public function test_reminders_go_out_once_per_stage(): void
    {
        $care = app(VehicleCareService::class);
        $today = CarbonImmutable::today();

        $this->assertSame(1, $care->sendReminders($today), 'insurance due in 5 days: the 7-day reminder');
        $this->assertSame(0, $care->sendReminders($today), 'not again the same day');
        $this->assertTrue(DB::table('notifications')->where(['notifiable_id' => $this->owner->id, 'type' => 'fleet.reminder'])->exists());

        $this->assertSame(1, $care->sendReminders($today->addDays(5)), 'on the day');
        $this->assertSame(1, $care->sendReminders($today->addDays(6)), 'once overdue');
        $this->assertSame(0, $care->sendReminders($today->addDays(7)));
    }

    public function test_a_driver_is_told_about_their_own_licence(): void
    {
        $rider = $this->makeUser('Musa Rider');
        $dp = DB::table('driver_profiles')->insertGetId(['public_id' => (string) Str::ulid(), 'operator_id' => $this->op, 'user_id' => $rider->id, 'status' => 'active', 'created_at' => now(), 'updated_at' => now()]);
        DB::table('kyc_documents')->insert([
            'public_id' => (string) Str::ulid(), 'operator_id' => $this->op, 'subject_type' => 'driver', 'subject_id' => $dp, 'doc_type' => 'drivers_licence',
            'file_path' => 'kyc/x.pdf', 'status' => 'approved', 'expires_on' => now()->addDays(20)->toDateString(), 'created_at' => now(), 'updated_at' => now(),
        ]);

        app(VehicleCareService::class)->sendReminders();

        $this->assertTrue(DB::table('notifications')->where(['notifiable_id' => $rider->id, 'type' => 'fleet.reminder'])->exists());
    }
}
