<?php

namespace Tests\Feature\Logistics;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Tests\Support\BuildsMarketplace;
use Tests\TestCase;

class SettingsTest extends TestCase
{
    use BuildsMarketplace;
    use RefreshDatabase;

    private function staff(array $perms): User
    {
        $u = User::factory()->create(['must_change_password' => false]);
        foreach ($perms as $p) {
            Permission::findOrCreate($p, 'web');
        }
        $u->givePermissionTo($perms);

        return $u;
    }

    public function test_page_needs_a_settings_permission(): void
    {
        $this->seedFoundation();
        $this->actingAs($this->staff([]))->get('/ops/settings')->assertForbidden();
        $this->actingAs($this->staff(['Manage commission']))->get('/ops/settings')->assertOk()->assertSee('12');
    }

    public function test_fee_can_be_changed_and_is_used_for_new_agreements(): void
    {
        $this->seedFoundation();
        $admin = $this->staff(['Manage commission']);
        $this->actingAs($admin)->post('/ops/settings/fee', ['percent' => 10, 'min_naira' => 50])->assertSessionHasNoErrors();
        $rule = DB::table('commission_rules')->whereNull('operator_id')->whereNull('operator_type')->first();
        $this->assertSame(1000, (int) $rule->rate_bp);
        $this->assertSame(5000, (int) $rule->min_fee);

        [$op, $owner] = $this->makeProvider();
        $customer = $this->makeUser('Customer');
        $id = $this->lockedAgreement($customer->id, $op, $owner->id, 300_000);
        $this->assertSame(30_000, (int) DB::table('agreements')->where('id', $id)->value('platform_fee'));

        $this->actingAs($admin)->post('/ops/settings/fee', ['percent' => 90, 'min_naira' => 50])->assertSessionHasErrors('percent');
        $this->actingAs($admin)->post('/ops/settings/rules', ['window_hours' => 48, 'advance_percent' => 30, 'tracking_seconds' => 10])->assertForbidden();
    }

    public function test_timing_settings_are_saved_and_read_back(): void
    {
        $this->seedFoundation();
        $admin = $this->staff(['Manage pricing rules']);
        $this->actingAs($admin)->post('/ops/settings/rules', ['window_hours' => 48, 'advance_percent' => 30, 'tracking_seconds' => 10])->assertSessionHasNoErrors();

        $val = fn ($k) => json_decode(DB::table('platform_settings')->whereNull('operator_id')->where('key', $k)->value('value'), true);
        $this->assertSame(48, $val('escrow.default_confirmation_window_hours'));
        $this->assertSame(3000, $val('shopper.advance_max_bp'));
        $this->assertSame(10, $val('tracking.location_interval_seconds'));
        $this->assertSame($admin->id, (int) DB::table('platform_settings')->where('key', 'tracking.location_interval_seconds')->value('updated_by'));

        $this->actingAs($admin)->get('/ops/settings')->assertOk()->assertSee('value="48"', false);
        $this->actingAs($admin)->post('/ops/settings/rules', ['window_hours' => 0, 'advance_percent' => 30, 'tracking_seconds' => 10])->assertSessionHasErrors('window_hours');
    }
}
