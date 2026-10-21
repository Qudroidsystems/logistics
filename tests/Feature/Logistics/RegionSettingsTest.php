<?php

namespace Tests\Feature\Logistics;

use App\Models\MessagingSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Tests\Support\BuildsMarketplace;
use Tests\TestCase;

/** Canada settings in practice: city sales tax, Canadian phone sign-up, the app-config region block and Messaging settings. */
class RegionSettingsTest extends TestCase
{
    use BuildsMarketplace;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedFoundation();
        $p = (require base_path('config/region-presets.php'))['CA'];
        config(['region' => array_merge(config('region'), $p)]);
    }

    private function staff(string ...$permissions): User
    {
        $u = $this->makeUser('Staff');
        foreach ($permissions as $perm) {
            Permission::findOrCreate($perm, 'web');
            $u->givePermissionTo($perm);
        }

        return $u;
    }

    public function test_a_new_ontario_city_gets_13_percent_hst(): void
    {
        $this->actingAs($this->staff('Create zone', 'View zone'))->post('/ops/cities', [
            'name' => 'Kitchener', 'region' => 'Ontario', 'lat' => 43.4516, 'lng' => -80.4925, 'radius_km' => 15, 'launch_status' => 'live',
        ])->assertRedirect();

        $this->assertSame(1300, (int) DB::table('cities')->where('name', 'Kitchener')->value('tax_rate_bp'));
    }

    public function test_sign_up_with_a_canadian_number(): void
    {
        $this->postJson('/api/v1/auth/register', ['name' => 'Ada', 'email' => 'ada@example.ca', 'phone' => '(226) 339-8404', 'password' => 'secret-pass'])->assertCreated();
        $this->assertSame('+12263398404', DB::table('users')->where('email', 'ada@example.ca')->value('phone_number'));
        $this->postJson('/api/v1/auth/login', ['login' => '226 339 8404', 'password' => 'secret-pass'])->assertOk();
    }

    public function test_the_apps_get_the_currency(): void
    {
        $this->getJson('/api/v1/app-config?app=customer')->assertOk()->assertJsonPath('region.currency', 'CAD')->assertJsonPath('region.symbol', '$');
    }

    public function test_messaging_settings_keep_secrets_encrypted(): void
    {
        $admin = $this->staff('Manage notifications settings');
        $this->actingAs($admin)->get('/admin/messaging')->assertOk()->assertSee('Twilio');
        $this->actingAs($admin)->post('/admin/messaging/sms', [
            'driver' => 'twilio', 'is_active' => 1, 'config' => ['account_sid' => 'AC123', 'auth_token' => 'secret-token', 'messaging_service_sid' => 'MG1'],
        ])->assertRedirect();

        $s = MessagingSetting::for('sms');
        $this->assertSame('secret-token', $s->value('auth_token'));
        $this->assertStringStartsWith('enc:', $s->config['auth_token']);
        $this->assertTrue($s->isLive());

        // Saving again with the secret left blank keeps it.
        $this->actingAs($admin)->post('/admin/messaging/sms', ['driver' => 'twilio', 'is_active' => 1, 'config' => ['account_sid' => 'AC123', 'auth_token' => '', 'messaging_service_sid' => 'MG1']]);
        $this->assertSame('secret-token', MessagingSetting::for('sms')->value('auth_token'));
    }
}
