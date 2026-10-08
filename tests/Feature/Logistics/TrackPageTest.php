<?php

namespace Tests\Feature\Logistics;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Support\BuildsMarketplace;
use Tests\TestCase;

class TrackPageTest extends TestCase
{
    use BuildsMarketplace;
    use RefreshDatabase;

    public function test_public_tracking_page_shows_progress_but_no_private_details(): void
    {
        $this->seedFoundation();
        [$op, $owner] = $this->makeProvider();
        $customer = $this->makeUser('Customer');
        $this->fundCustomer($customer->id, 1_000_000);
        $agreement = $this->lockedAgreement($customer->id, $op, $owner->id);
        app(\App\Modules\Payments\PaymentService::class)->payWithWallet($agreement, $customer->id);

        $shipment = DB::table('shipments')->first();
        $token = DB::table('tracking_links')->where(['shipment_id' => $shipment->id, 'audience' => 'recipient'])->value('token');

        $res = $this->get("/track/{$token}")->assertOk()->assertSee($shipment->tracking_code)->assertHeader('Referrer-Policy', 'no-referrer');
        $this->assertStringNotContainsString('payout', $res->getContent());

        $this->get('/track/'.str_repeat('a', 40))->assertNotFound();
        $this->get('/track/short')->assertNotFound();

        DB::table('tracking_links')->where('token', $token)->update(['revoked_at' => now()]);
        $this->get("/track/{$token}")->assertNotFound();
    }

    public function test_customer_order_page_links_to_tracking(): void
    {
        $this->seedFoundation();
        [$op, $owner] = $this->makeProvider();
        $customer = User::factory()->create(['must_change_password' => false]);
        $this->fundCustomer($customer->id, 1_000_000);
        $agreement = $this->lockedAgreement($customer->id, $op, $owner->id);
        app(\App\Modules\Payments\PaymentService::class)->payWithWallet($agreement, $customer->id);
        $shipment = DB::table('shipments')->value('public_id');
        $mine = DB::table('tracking_links')->where('audience', 'customer')->value('token');
        $theirs = DB::table('tracking_links')->where('audience', 'recipient')->value('token');

        $this->actingAs($customer)->get("/account/orders/{$shipment}")->assertOk()->assertSee("/track/{$mine}")->assertSee("/track/{$theirs}");
    }
}
