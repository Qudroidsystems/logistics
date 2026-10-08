<?php

namespace Tests\Feature\Logistics;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Support\BuildsMarketplace;
use Tests\TestCase;

/** The provider's browser workspace: who may see which page, and the sign-up flow. */
class ProviderWorkspaceTest extends TestCase
{
    use BuildsMarketplace;
    use RefreshDatabase;

    private function memberWithRole(int $op, string $role): User
    {
        $u = User::factory()->create(['must_change_password' => false, 'current_operator_id' => $op]);
        DB::table('operator_members')->insert(['operator_id' => $op, 'user_id' => $u->id, 'role' => $role, 'status' => 'active', 'created_at' => now(), 'updated_at' => now()]);

        return $u;
    }

    public function test_a_user_without_a_provider_is_sent_to_sign_up(): void
    {
        $this->seedFoundation();
        $u = User::factory()->create(['must_change_password' => false]);

        $this->actingAs($u)->get('/provider')->assertRedirect(route('provider.start'));
        $this->actingAs($u)->get('/provider/start')->assertOk()->assertSee('Become a provider');
        $this->actingAs($u)->get('/provider/wallet')->assertForbidden();
    }

    public function test_signing_up_creates_the_account_and_opens_setup(): void
    {
        $this->seedFoundation();
        $u = User::factory()->create(['must_change_password' => false]);

        $this->actingAs($u)->post('/provider/start', ['type' => 'independent_driver', 'legal_name' => 'Ade Rider', 'display_name' => 'Ade Express'])
            ->assertRedirect(route('provider.onboarding'));

        $this->assertSame('pending', DB::table('operators')->where('display_name', 'Ade Express')->value('status'));
        $this->actingAs($u->fresh())->get('/provider/setup')->assertOk()->assertSee('Checklist');
        $this->actingAs($u->fresh())->get('/provider')->assertOk()->assertSee('Ade Express');
    }

    public function test_pages_follow_the_team_role(): void
    {
        $this->seedFoundation();
        [$op, $owner] = $this->makeProvider();
        $owner->forceFill(['must_change_password' => false])->save();
        $dispatcher = $this->memberWithRole($op, 'dispatcher');
        $finance = $this->memberWithRole($op, 'finance');

        $this->actingAs($owner)->get('/provider/jobs')->assertOk();
        $this->actingAs($owner)->get('/provider/wallet')->assertOk();
        $this->actingAs($owner)->get('/provider/team')->assertOk();

        $this->actingAs($dispatcher)->get('/provider/jobs')->assertOk();
        $this->actingAs($dispatcher)->get('/provider/wallet')->assertForbidden();
        $this->actingAs($dispatcher)->get('/provider/team')->assertForbidden();
        $this->actingAs($dispatcher)->get('/provider/setup')->assertForbidden();

        $this->actingAs($finance)->get('/provider/wallet')->assertOk();
        $this->actingAs($finance)->get('/provider/jobs')->assertForbidden();
    }

    public function test_inviting_from_the_team_page_creates_an_invitation(): void
    {
        $this->seedFoundation();
        [$op, $owner] = $this->makeProvider();
        $owner->forceFill(['must_change_password' => false])->save();

        $this->actingAs($owner)->post('/provider/team/invite', ['email' => 'new@example.com', 'role' => 'dispatcher'])->assertSessionHas('success');
        $this->assertTrue(DB::table('operator_invitations')->where(['operator_id' => $op, 'email' => 'new@example.com'])->exists());

        // A rule broken in the form comes back as a message, not a crash.
        $this->actingAs($owner)->post('/provider/team/invite', ['email' => 'new@example.com', 'role' => 'owner'])->assertSessionHasErrors('role');
    }

    public function test_a_provider_cannot_open_another_providers_job(): void
    {
        $this->seedFoundation();
        [$op, $owner] = $this->makeProvider();
        $owner->forceFill(['must_change_password' => false])->save();

        $this->actingAs($owner)->get('/provider/jobs/01HZZZZZZZZZZZZZZZZZZZZZZZ')->assertNotFound();
    }
}
