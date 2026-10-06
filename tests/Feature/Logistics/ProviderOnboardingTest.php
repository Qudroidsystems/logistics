<?php

namespace Tests\Feature\Logistics;

use App\Models\User;
use App\Modules\Marketplace\NegotiationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Tests\Support\BuildsMarketplace;
use Tests\TestCase;

/**
 * Provider onboarding end to end over HTTP: register, fill in the profile, upload documents, submit,
 * staff review, approval, listing, suspension. Needs PostgreSQL with PostGIS (see phpunit.xml).
 */
class ProviderOnboardingTest extends TestCase
{
    use BuildsMarketplace;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedFoundation();
        Storage::fake('local');
    }

    private function staff(array $permissions): User
    {
        $u = $this->makeUser('Staff Member');
        foreach ($permissions as $p) {
            Permission::findOrCreate($p, 'web');
        }
        $u->givePermissionTo($permissions);

        return $u;
    }

    private function zoneId(): int
    {
        $city = (int) (DB::table('cities')->value('id') ?? DB::table('cities')->insertGetId([
            'name' => 'Lokoja', 'slug' => 'lokoja', 'created_at' => now(), 'updated_at' => now(),
        ]));

        return (int) DB::table('zones')->insertGetId([
            'city_id' => $city, 'name' => 'Central', 'type' => 'service', 'active' => true,
            'boundary' => DB::raw("ST_GeogFromText('MULTIPOLYGON(((5.8 7.7, 6.0 7.7, 6.0 7.9, 5.8 7.9, 5.8 7.7)))')"), 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function doc(User $u, string $type, ?string $vehicle = null)
    {
        Sanctum::actingAs($u);

        return $this->post('/api/v1/provider/documents', array_filter([
            'doc_type' => $type, 'number' => 'ABC123456', 'vehicle_id' => $vehicle, 'file' => UploadedFile::fake()->image($type.'.jpg'),
        ]));
    }

    /** Registers a company and does everything up to (not including) submit. */
    private function readyCompany(): array
    {
        $owner = $this->makeUser('Chidi Owner');
        Sanctum::actingAs($owner);
        $res = $this->postJson('/api/v1/provider/register', ['type' => 'company', 'legal_name' => 'Swift Haulage Ltd', 'display_name' => 'Swift Haulage'])->assertCreated();
        $opPublic = $res->json('operator.public_id');
        $opId = (int) DB::table('operators')->where('public_id', $opPublic)->value('id');

        $this->doc($owner, 'cac_certificate')->assertCreated();
        $this->doc($owner, 'national_id')->assertCreated();
        Sanctum::actingAs($owner);
        $this->postJson('/api/v1/provider/rate-cards', [
            'service_type_id' => $this->serviceTypeId(), 'base' => 100_000, 'per_km' => 20_000, 'min_fee' => 150_000,
        ])->assertCreated();
        $this->putJson('/api/v1/provider/service-areas', ['zone_ids' => [$this->zoneId()]])->assertOk();

        return [$owner, $opId, $opPublic];
    }

    public function test_registration_creates_a_pending_unlisted_provider(): void
    {
        $owner = $this->makeUser();
        Sanctum::actingAs($owner);

        $this->postJson('/api/v1/provider/register', ['type' => 'independent_driver', 'legal_name' => 'Musa Bello', 'display_name' => 'Musa Rider'])
            ->assertCreated()->assertJsonPath('application.status', 'draft')->assertJsonPath('operator.status', 'pending');

        $op = DB::table('operators')->where('type', 'independent_driver')->first();
        $this->assertSame($op->id, (int) DB::table('users')->where('id', $owner->id)->value('current_operator_id'));
        $this->assertFalse((bool) DB::table('provider_profiles')->where('operator_id', $op->id)->value('listed'));
        $this->assertTrue(DB::table('driver_profiles')->where('operator_id', $op->id)->exists(), 'a rider gets a driver profile');
        $this->assertTrue(DB::table('operator_capabilities')->where(['operator_id' => $op->id, 'capability' => 'list_in_directory'])->exists());
    }

    public function test_one_provider_account_per_user(): void
    {
        $owner = $this->makeUser();
        Sanctum::actingAs($owner);
        $body = ['type' => 'company', 'legal_name' => 'A Ltd', 'display_name' => 'A'];
        $this->postJson('/api/v1/provider/register', $body)->assertCreated();
        $this->postJson('/api/v1/provider/register', $body)->assertStatus(422);
    }

    public function test_submit_lists_exactly_what_is_missing(): void
    {
        $owner = $this->makeUser();
        Sanctum::actingAs($owner);
        $this->postJson('/api/v1/provider/register', ['type' => 'company', 'legal_name' => 'B Ltd', 'display_name' => 'B Logistics'])->assertCreated();

        $res = $this->postJson('/api/v1/provider/submit')->assertStatus(422);
        $msg = $res->json('message');
        foreach (['cac_certificate', 'national_id', 'rate card', 'service area'] as $needle) {
            $this->assertStringContainsString($needle, $msg);
        }
    }

    public function test_full_flow_from_registration_to_listing(): void
    {
        [$owner, $opId, $opPublic] = $this->readyCompany();
        $staff = $this->staff(['View kyc', 'Approve kyc', 'Reject kyc']);

        Sanctum::actingAs($owner);
        $this->postJson('/api/v1/provider/submit')->assertOk()->assertJsonPath('application.status', 'submitted');
        $this->assertFalse((bool) DB::table('provider_profiles')->where('operator_id', $opId)->value('listed'), 'not listed before approval');

        // Cannot be approved until the documents are.
        Sanctum::actingAs($staff);
        $this->postJson("/api/v1/admin/provider-applications/{$opPublic}/approve")->assertStatus(422);

        foreach (DB::table('kyc_documents')->where('operator_id', $opId)->pluck('public_id') as $doc) {
            $this->postJson("/api/v1/admin/kyc-documents/{$doc}/approve")->assertOk();
        }
        $this->postJson("/api/v1/admin/provider-applications/{$opPublic}/approve")->assertOk();

        $this->assertSame('active', DB::table('operators')->where('id', $opId)->value('status'));
        $profile = DB::table('provider_profiles')->where('operator_id', $opId)->first();
        $this->assertTrue((bool) $profile->listed);
        $this->assertSame('verified', $profile->tier);
        $this->assertContains($this->serviceTypeId(), json_decode($profile->service_types, true), 'search matches on the priced service');
        $this->assertContains($opId, app(NegotiationService::class)->matchProviders($this->serviceTypeId(), 10), 'now found by customers');

        $this->assertTrue(DB::table('notifications')->where(['notifiable_id' => $owner->id, 'type' => 'provider.approved'])->exists(), 'the owner was told');
    }

    public function test_changes_requested_unlocks_editing_and_resubmission(): void
    {
        [$owner, $opId, $opPublic] = $this->readyCompany();
        $staff = $this->staff(['View kyc', 'Approve kyc', 'Reject kyc']);

        Sanctum::actingAs($owner);
        $this->postJson('/api/v1/provider/submit')->assertOk();
        $this->putJson('/api/v1/provider/profile', ['headline' => 'Edited while under review'])->assertStatus(422);

        Sanctum::actingAs($staff);
        $this->postJson("/api/v1/admin/provider-applications/{$opPublic}/request-changes", ['note' => 'National ID is blurry'])->assertOk();

        Sanctum::actingAs($owner);
        $this->putJson('/api/v1/provider/profile', ['headline' => 'Reliable city deliveries'])->assertOk();
        $this->doc($owner, 'national_id')->assertCreated();
        Sanctum::actingAs($owner);
        $this->postJson('/api/v1/provider/submit')->assertOk();
        $this->assertSame(2, (int) DB::table('provider_applications')->where('operator_id', $opId)->value('submissions'));
        $this->assertTrue(DB::table('notifications')->where(['notifiable_id' => $owner->id, 'type' => 'provider.changes_requested'])->exists());
    }

    public function test_a_rider_needs_a_vehicle_with_registration(): void
    {
        $owner = $this->makeUser();
        Sanctum::actingAs($owner);
        $this->postJson('/api/v1/provider/register', ['type' => 'independent_driver', 'legal_name' => 'Musa Bello', 'display_name' => 'Musa Rider'])->assertCreated();
        $this->doc($owner, 'national_id')->assertCreated();
        $this->doc($owner, 'drivers_licence')->assertCreated();
        Sanctum::actingAs($owner);
        $this->postJson('/api/v1/provider/rate-cards', ['service_type_id' => $this->serviceTypeId(), 'base' => 50_000, 'per_km' => 10_000])->assertCreated();
        $this->putJson('/api/v1/provider/service-areas', ['zone_ids' => [$this->zoneId()]])->assertOk();

        $this->postJson('/api/v1/provider/submit')->assertStatus(422)->assertSee('vehicle');

        $vehicleType = (int) DB::table('vehicle_types')->where('code', 'motorbike')->value('id');
        $v = $this->postJson('/api/v1/provider/vehicles', ['vehicle_type_id' => $vehicleType, 'plate' => 'kgl 123 ab'])->assertCreated()->json('vehicle');
        $this->postJson('/api/v1/provider/vehicles', ['vehicle_type_id' => $vehicleType, 'plate' => 'KGL123AB'])->assertStatus(422);   // same plate
        $this->doc($owner, 'vehicle_registration', $v)->assertCreated();
        Sanctum::actingAs($owner);
        $this->postJson('/api/v1/provider/submit')->assertOk();
    }

    public function test_suspending_removes_the_provider_from_search_and_reinstating_restores_it(): void
    {
        [$owner, $opId, $opPublic] = $this->readyCompany();
        $staff = $this->staff(['View kyc', 'Approve kyc', 'Reject kyc', 'Suspend vendor']);
        Sanctum::actingAs($owner);
        $this->postJson('/api/v1/provider/submit')->assertOk();
        Sanctum::actingAs($staff);
        foreach (DB::table('kyc_documents')->where('operator_id', $opId)->pluck('public_id') as $doc) {
            $this->postJson("/api/v1/admin/kyc-documents/{$doc}/approve")->assertOk();
        }
        $this->postJson("/api/v1/admin/provider-applications/{$opPublic}/approve")->assertOk();

        $this->postJson("/api/v1/admin/providers/{$opPublic}/suspend", ['reason' => 'Complaints under review'])->assertOk();
        $this->assertNotContains($opId, app(NegotiationService::class)->matchProviders($this->serviceTypeId(), 10));

        $this->postJson("/api/v1/admin/providers/{$opPublic}/reinstate")->assertOk();
        $this->assertContains($opId, app(NegotiationService::class)->matchProviders($this->serviceTypeId(), 10));
    }

    public function test_staff_endpoints_need_the_permission(): void
    {
        [, , $opPublic] = $this->readyCompany();
        Sanctum::actingAs($this->makeUser('No Permissions'));

        $this->getJson('/api/v1/admin/provider-applications')->assertForbidden();
        $this->postJson("/api/v1/admin/provider-applications/{$opPublic}/approve")->assertForbidden();
    }

    public function test_a_provider_cannot_read_another_providers_documents_through_the_staff_route(): void
    {
        [$owner, $opId] = $this->readyCompany();
        $doc = DB::table('kyc_documents')->where('operator_id', $opId)->value('public_id');
        Sanctum::actingAs($owner);

        $this->get("/api/v1/admin/kyc-documents/{$doc}/file")->assertForbidden();
    }
}
