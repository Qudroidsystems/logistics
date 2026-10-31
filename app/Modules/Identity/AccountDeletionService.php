<?php

namespace App\Modules\Identity;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Deletes an account at the user's request (Apple and Google require this in-app; privacy law requires it generally).
 *
 * What goes: name, email, phone, photo, password, saved addresses, devices and push tokens, sign-ins, team and store
 * memberships, a live business plan and recurring schedules. What stays, emptied of personal details: the user row
 * (as "Deleted user") so past orders, invoices, payouts and the ledger still balance, as the law on business records
 * requires. Blocked while something is still in progress, so nothing is left half done.
 */
class AccountDeletionService
{
    /** Shipment states after which nothing more can happen. */
    private const DONE = ['delivered', 'confirmed', 'completed', 'cancelled', 'returned', 'lost', 'damaged'];

    /** Why the account can't be deleted yet, or [] when it can. */
    public function blockers(User $u): array
    {
        $out = [];
        $openOrders = DB::table('orders as o')->join('shipments as s', 's.order_id', '=', 'o.id')
            ->where('o.customer_id', $u->id)->whereNotIn('s.status', self::DONE)->count();
        if ($openOrders) {
            $out[] = "You have {$openOrders} delivery(ies) still in progress. Wait for them to finish or cancel them first.";
        }
        $balance = app(\App\Modules\Payments\WalletService::class)->balance((int) $u->id);
        if ($balance > 0) {
            $out[] = 'Your wallet still has '.\App\Support\Money::format($balance, true).'. Withdraw it first, or contact us.';
        }
        $payouts = DB::table('payout_requests as p')->join('wallets as w', 'w.id', '=', 'p.wallet_id')
            ->where(['w.owner_type' => 'customer', 'w.owner_id' => $u->id])->whereIn('p.status', ['requested', 'approved', 'processing'])->exists();
        if ($payouts) {
            $out[] = 'A withdrawal is still being paid out. Try again once it arrives.';
        }
        $owner = DB::table('operator_members as m')->join('operators as o', 'o.id', '=', 'm.operator_id')
            ->where('m.user_id', $u->id)->where('m.status', 'active')->where('m.role', 'owner')->whereNotIn('o.status', ['closed', 'rejected', 'deleted'])->value('o.display_name');
        if ($owner) {
            $out[] = "You own the business {$owner}. Hand ownership to someone else or ask us to close it first.";
        }
        $driving = DB::table('assignments as a')->join('driver_profiles as d', 'd.id', '=', 'a.driver_profile_id')
            ->where('d.user_id', $u->id)->whereIn('a.status', ['assigned', 'accepted', 'en_route', 'active'])->exists();
        if ($driving) {
            $out[] = 'You have a delivery job in progress. Finish or hand it back first.';
        }
        if ($u->can('dashboard')) {
            $out[] = 'Staff accounts are removed by an administrator.';
        }

        return $out;
    }

    /** Checks the password, then deletes. Throws with the reasons when it can't be done yet. */
    public function delete(User $u, string $password, string $via, ?string $reason = null): void
    {
        if (! Hash::check($password, (string) $u->password)) {
            throw new RuntimeException('That password is not right.');
        }
        if ($blockers = $this->blockers($u)) {
            throw new RuntimeException(implode(' ', $blockers));
        }
        $avatar = $u->avatar;
        DB::transaction(function () use ($u, $via, $reason) {
            $id = $u->id;
            DB::table('subscriptions')->where('user_id', $id)->whereIn('status', ['active', 'past_due'])->update(['status' => 'cancelled', 'cancelled_at' => now(), 'updated_at' => now()]);
            DB::table('recurring_deliveries')->where('user_id', $id)->update(['status' => 'ended', 'updated_at' => now()]);
            // Saved places: emptied and hidden (past deliveries may point at them).
            DB::table('addresses')->where(['owner_type' => 'user', 'owner_id' => $id])->update([
                'label' => null, 'line2' => null, 'directions_note' => null, 'contact_name' => null, 'contact_phone' => null, 'deleted_at' => now(), 'updated_at' => now(),
            ]);
            DB::table('devices')->where('user_id', $id)->delete();
            DB::table('personal_access_tokens')->where('tokenable_type', User::class)->where('tokenable_id', $id)->delete();
            DB::table('sessions')->where('user_id', $id)->delete();
            DB::table('operator_members')->where('user_id', $id)->update(['status' => 'removed', 'updated_at' => now()]);
            DB::table('driver_profiles')->where('user_id', $id)->update(['status' => 'offboarded', 'availability' => 'offline', 'updated_at' => now()]);
            DB::table('dropoff_centre_users')->where('user_id', $id)->delete();
            DB::table('customer_profiles')->where('user_id', $id)->update(['marketing_opt_in' => false, 'updated_at' => now()]);
            DB::table('users')->where('id', $id)->update([
                'name' => 'Deleted user', 'username' => null, 'email' => "deleted-{$id}-".Str::lower(Str::random(8)).'@deleted.invalid',
                'phone_number' => null, 'avatar' => null, 'gender' => null, 'date_of_birth' => null, 'email_verified_at' => null,
                'password' => Hash::make(Str::random(48)), 'remember_token' => null, 'is_disabled' => true, 'deleted_at' => now(), 'updated_at' => now(),
            ]);
            DB::table('account_deletions')->insert(['user_id' => $id, 'via' => $via, 'reason' => $reason ? mb_substr($reason, 0, 300) : null, 'deleted_at' => now()]);
        });
        if ($avatar) {
            Storage::disk('public')->delete('avatars/'.$avatar);
        }
    }
}
