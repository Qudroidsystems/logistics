<?php

namespace Tests\Feature\Logistics;

use App\Jobs\SendNotificationEmail;
use App\Models\User;
use App\Modules\Notifications\NotificationPreferences;
use App\Modules\Notifications\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class NotificationPreferencesTest extends TestCase
{
    use RefreshDatabase;

    public function test_categories(): void
    {
        $this->assertSame('delivery', NotificationPreferences::categoryOf('delivery.assigned'));
        $this->assertSame('offer', NotificationPreferences::categoryOf('request.invited'));
        $this->assertSame('offer', NotificationPreferences::categoryOf('offer.received'));
        $this->assertFalse(NotificationPreferences::canMute('payout'));
        $this->assertFalse(NotificationPreferences::canMute('provider'));
    }

    public function test_defaults_then_muting(): void
    {
        $u = User::factory()->create();
        Sanctum::actingAs($u);
        $all = $this->getJson('/api/v1/notifications/preferences')->assertOk()->json('preferences');
        $this->assertCount(6, $all);

        $this->putJson('/api/v1/notifications/preferences', ['category' => 'rating', 'in_app' => false])->assertOk();
        $this->putJson('/api/v1/notifications/preferences', ['category' => 'rating', 'email' => false])->assertOk();   // keeps in_app false
        $rating = collect($this->getJson('/api/v1/notifications/preferences')->json('preferences'))->firstWhere('category', 'rating');
        $this->assertFalse($rating['in_app']);
        $this->assertFalse($rating['email']);

        $this->putJson('/api/v1/notifications/preferences', ['category' => 'payout', 'email' => false])->assertStatus(422);
        $this->putJson('/api/v1/notifications/preferences', ['category' => 'nonsense', 'email' => false])->assertStatus(422);
    }

    public function test_muted_in_app_is_not_stored_and_critical_always_is(): void
    {
        Bus::fake([SendNotificationEmail::class]);
        $u = User::factory()->create();
        NotificationPreferences::set($u->id, 'delivery', false, false);
        $svc = app(NotificationService::class);

        $svc->notify($u->id, 'delivery.assigned', ['order' => 'A1']);
        $this->assertSame(0, DB::table('notifications')->where('notifiable_id', $u->id)->count());

        $svc->notify($u->id, 'payout.paid', ['amount' => '₦1,000']);
        $this->assertSame(1, DB::table('notifications')->where('notifiable_id', $u->id)->where('type', 'payout.paid')->count());
    }

    public function test_email_can_be_muted_while_in_app_stays(): void
    {
        Bus::fake([SendNotificationEmail::class]);
        $u = User::factory()->create();
        $svc = app(NotificationService::class);

        $svc->notify($u->id, 'delivery.delivered', ['order' => 'A2']);   // emails by default
        Bus::assertDispatchedTimes(SendNotificationEmail::class, 1);

        NotificationPreferences::set($u->id, 'delivery', true, false);
        $svc->notify($u->id, 'delivery.delivered', ['order' => 'A3']);
        Bus::assertDispatchedTimes(SendNotificationEmail::class, 1);
        $this->assertSame(2, DB::table('notifications')->where('notifiable_id', $u->id)->count());
    }
}
