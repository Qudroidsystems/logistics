<?php

namespace Tests\Feature\Logistics;

use App\Jobs\SendPlainEmail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Tests\Support\BuildsMarketplace;
use Tests\TestCase;

/** "Drive with us": apply with documents, staff check and approve into the fleet, or decline. */
class DriveWithUsTest extends TestCase
{
    use BuildsMarketplace;
    use RefreshDatabase;

    private int $op;
    private User $staff;

    protected function setUp(): void
    {
        parent::setUp();
        $this->useRegion('CA');
        Storage::fake('local');
        Notification::fake();
        Bus::fake([SendPlainEmail::class]);
        $this->seedFoundation();
        [$this->op] = $this->makeProvider('company', 'CityHop Fleet');
        config(['booking.driver_operator_id' => $this->op, 'booking.vehicles' => ['car', 'van']]);
        $this->staff = $this->makeUser('Ops');
        Permission::findOrCreate('Approve driver', 'web');
        $this->staff->givePermissionTo('Approve driver');
    }

    private function form(array $o = []): array
    {
        return $o + [
            'name' => 'Jordan Lee', 'email' => 'jordan@example.test', 'phone' => '2265550177', 'city' => 'Kitchener', 'subdivision' => 'ON', 'postal_code' => 'n2g 1a1',
            'experience' => '1to2', 'availability' => ['weekend'], 'has_vehicle' => 'yes',
            'vehicle_type' => 'car', 'vehicle_make' => 'Toyota', 'vehicle_model' => 'Corolla', 'vehicle_year' => 2019, 'plate' => 'cdef 123',
            'licence_class' => 'G', 'licence_number' => 'L1234-56789-01234', 'licence_expires_on' => now()->addYears(2)->toDateString(),
            'licence_file' => UploadedFile::fake()->image('licence.jpg'),
            'insurance_company' => 'Acme Insurance', 'insurance_policy' => 'POL-998877', 'insurance_expires_on' => now()->addMonths(8)->toDateString(),
            'insurance_file' => UploadedFile::fake()->create('pink.pdf', 100, 'application/pdf'), 'registration_file' => UploadedFile::fake()->image('ownership.png'),
            'sms_consent' => '1', 'background_check_consent' => '1', 'terms' => '1',
        ];
    }

    public function test_apply_then_approve_into_the_fleet(): void
    {
        $this->get('/drive')->assertOk()->assertSee('Drive with us');
        $this->get('/drive/terms')->assertOk()->assertSee('Text messages');

        $this->post('/drive', $this->form(['terms' => null]))->assertSessionHasErrors('terms');
        $this->post('/drive', $this->form(['has_vehicle' => 'yes', 'insurance_file' => null]))->assertSessionHasErrors('insurance_file');
        $res = $this->post('/drive', $this->form());
        $a = DB::table('driver_applications')->first();
        $res->assertRedirect(route('drive.status', $a->public_id));
        $this->get(route('drive.status', $a->public_id))->assertOk()->assertSee("got your application");
        $this->assertSame('CDEF123', $a->plate);
        $this->assertSame('1234', $a->licence_number_last4);
        $this->assertStringNotContainsString('56789', json_encode($a));
        Storage::disk('local')->assertExists($a->licence_file);
        $this->assertTrue((bool) $a->sms_consent);

        // One open application per email.
        $this->post('/drive', $this->form())->assertSessionHas('error');

        // Staff: a stranger can't see it; staff can open documents; every check must be ticked.
        $this->actingAs($this->makeUser('Nobody'))->get('/ops/driver-applications')->assertForbidden();
        $this->actingAs($this->staff)->get('/ops/driver-applications')->assertOk()->assertSee('Jordan Lee');
        $this->actingAs($this->staff)->get("/ops/driver-applications/{$a->id}/files/licence")->assertOk();
        $this->actingAs($this->staff)->post("/ops/driver-applications/{$a->id}/approve", ['checks' => ['licence']])->assertSessionHas('error');
        $this->actingAs($this->staff)->post("/ops/driver-applications/{$a->id}/approve", ['checks' => ['licence', 'insurance', 'registration', 'call']])->assertSessionHas('success');

        $user = User::where('email', 'jordan@example.test')->firstOrFail();
        $profile = DB::table('driver_profiles')->where(['user_id' => $user->id, 'operator_id' => $this->op])->first();
        $this->assertSame('active', $profile->status);
        $this->assertNotNull($profile->current_vehicle_id);
        $this->assertSame('driver', DB::table('operator_members')->where(['user_id' => $user->id, 'operator_id' => $this->op])->value('role'));
        $this->assertSame('CDEF123', DB::table('vehicles')->where('id', $profile->current_vehicle_id)->value('plate'));
        $this->assertSame(3, DB::table('kyc_documents')->where('operator_id', $this->op)->where('status', 'approved')->count());
        $this->assertSame('approved', DB::table('driver_applications')->where('id', $a->id)->value('status'));
        $this->assertTrue(DB::table('notifications')->where(['notifiable_id' => $user->id, 'type' => 'driver.application_approved'])->exists());
        $this->actingAs($this->staff)->post("/ops/driver-applications/{$a->id}/approve", ['checks' => array_keys(\App\Modules\Drivers\DriverApplicationService::CHECKS)])->assertSessionHas('error');
    }

    public function test_without_a_vehicle_and_declined(): void
    {
        $this->post('/drive', $this->form(['email' => 'sam@example.test', 'has_vehicle' => 'no', 'insurance_file' => null, 'registration_file' => null, 'plate' => null]))->assertRedirect();
        $a = DB::table('driver_applications')->where('email', 'sam@example.test')->first();
        $this->assertNull($a->plate);
        $this->assertNull($a->insurance_file);
        $this->actingAs($this->staff)->post("/ops/driver-applications/{$a->id}/decline", ['note' => 'We need drivers with a car right now.', 'tell' => '1'])->assertSessionHas('success');
        $this->assertSame('declined', DB::table('driver_applications')->where('id', $a->id)->value('status'));
        Bus::assertDispatched(SendPlainEmail::class, fn ($j) => $j->to === 'sam@example.test');
        $this->get(route('drive.status', $a->public_id))->assertOk()->assertSee("can't go ahead", false);
    }

    public function test_closed_when_no_fleet_is_set(): void
    {
        config(['booking.driver_operator_id' => null]);
        $this->get('/drive')->assertOk()->assertSee("not taking new drivers");
    }
}
