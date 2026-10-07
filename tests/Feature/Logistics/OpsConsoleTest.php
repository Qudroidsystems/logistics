<?php

namespace Tests\Feature\Logistics;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Tests\Support\BuildsMarketplace;
use Tests\TestCase;

/** The staff console pages render and are guarded by the same permissions as the admin API. */
class OpsConsoleTest extends TestCase
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

    public function test_pages_need_the_right_permission(): void
    {
        $this->seedFoundation();
        $plain = $this->staff([]);
        $this->actingAs($plain)->get('/ops/orders')->assertForbidden();
        $this->actingAs($plain)->get('/ops/disputes')->assertForbidden();
        $this->actingAs($plain)->get('/ops/applications')->assertForbidden();
        auth()->logout();
        $this->get('/ops')->assertRedirect();
    }

    public function test_console_pages_render_for_staff(): void
    {
        $this->seedFoundation();
        $u = $this->staff(['dashboard', 'View delivery', 'View dispute', 'View kyc']);
        $this->actingAs($u);

        $this->get('/ops')->assertOk()->assertSee('Operations');
        $this->get('/ops/orders')->assertOk()->assertSee('Orders');
        $this->get('/ops/disputes')->assertOk()->assertSee('Disputes');
        $this->get('/ops/applications')->assertOk()->assertSee('Provider applications');
    }

    public function test_only_approvers_can_approve_an_application(): void
    {
        $this->seedFoundation();
        [$op] = $this->makeProvider('independent_driver', 'Solo Rider');
        $public = DB::table('operators')->where('id', $op)->value('public_id');
        $viewer = $this->staff(['View kyc']);

        $this->actingAs($viewer)->post("/ops/applications/{$public}/decide", ['action' => 'approve'])->assertForbidden();
    }
}
