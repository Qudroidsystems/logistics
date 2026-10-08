<?php

namespace Tests\Feature\Logistics;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Support\BuildsMarketplace;
use Tests\TestCase;

class ProviderPageTest extends TestCase
{
    use BuildsMarketplace;
    use RefreshDatabase;

    private function listed(bool $listed = true): int
    {
        [$op] = $this->makeProvider('company', 'Swift Haulage');
        DB::table('provider_profiles')->insert([
            'public_id' => (string) Str::ulid(), 'operator_id' => $op, 'public_slug' => 'swift-haulage', 'headline' => 'Careful and quick', 'about' => "We move things.\nAll over town.",
            'listed' => $listed, 'tier' => 'trusted', 'rating_avg' => 4.5, 'rating_count' => 2, 'jobs_completed' => 40,
            'service_types' => json_encode([$this->serviceTypeId()]), 'vehicle_types' => json_encode([]), 'verified_badges' => json_encode(['kyc_complete']),
            'created_at' => now(), 'updated_at' => now(),
        ]);

        return $op;
    }

    public function test_listed_provider_page_is_public(): void
    {
        $this->seedFoundation();
        $op = $this->listed();
        $customer = User::factory()->create(['name' => 'Ada Obi']);
        $this->fundCustomer($customer->id, 1_000_000);
        $owner = User::find(DB::table('operator_members')->where('operator_id', $op)->value('user_id'));
        $agreement = $this->lockedAgreement($customer->id, $op, $owner->id);
        app(\App\Modules\Payments\PaymentService::class)->payWithWallet($agreement, $customer->id);
        $sid = (int) DB::table('shipments')->where('operator_id', $op)->value('id');
        DB::table('ratings')->insert([
            'shipment_id' => $sid, 'rater_type' => 'customer', 'rater_id' => $customer->id, 'ratee_type' => 'operator', 'ratee_id' => $op,
            'score' => 5, 'comment' => 'Arrived early', 'is_public' => true, 'moderation_status' => 'approved', 'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->get('/p/swift-haulage')->assertOk()->assertSee('Swift Haulage')->assertSee('Careful and quick')->assertSee('Arrived early')
            ->assertSee('Ada')->assertDontSee('Obi')->assertSee('Sign in to ask for a price');
        $this->actingAs($customer)->get('/p/swift-haulage')->assertOk()->assertSee('Ask Swift Haulage for a price');
    }

    public function test_unlisted_or_unknown_providers_are_not_found(): void
    {
        $this->seedFoundation();
        $this->listed(false);
        $this->get('/p/swift-haulage')->assertNotFound();
        $this->get('/p/nobody-here')->assertNotFound();
    }
}
