<?php

namespace App\Modules\Team;

use App\Jobs\SendPlainEmail;
use App\Modules\Notifications\NotificationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * A provider's team: invite people by email, accept, change roles, remove, and manage the drivers a company employs.
 * Owners can do everything; admins cannot create or change other admins; driver managers can only invite and manage drivers.
 */
class TeamService
{
    public const ROLES = ['admin', 'dispatcher', 'finance', 'support', 'driver_manager', 'driver'];

    public const INVITE_DAYS = 7;

    public function __construct(private NotificationService $notify)
    {
    }

    // ---------------------------------------------------------------- who may do what (pure, unit tested)

    /** Roles an actor may grant, change or remove. */
    public static function manageable(string $actorRole): array
    {
        return match ($actorRole) {
            'owner' => self::ROLES,
            'admin' => array_values(array_diff(self::ROLES, ['admin'])),
            'driver_manager' => ['driver'],
            default => [],
        };
    }

    public static function canManage(string $actorRole, string $targetRole): bool
    {
        return in_array($targetRole, self::manageable($actorRole), true);
    }

    // ---------------------------------------------------------------- read

    public function members(int $opId)
    {
        return DB::table('operator_members as m')->join('users as u', 'u.id', '=', 'm.user_id')->leftJoin('driver_profiles as d', function ($j) {
            $j->on('d.user_id', '=', 'm.user_id')->on('d.operator_id', '=', 'm.operator_id');
        })->where('m.operator_id', $opId)->where('m.status', 'active')->orderBy('m.id')
            ->get(['u.id as user_id', 'u.name', 'u.email', 'u.phone_number as phone', 'm.role', 'm.joined_at', 'd.status as driver_status', 'd.availability', 'd.current_vehicle_id', 'd.pay_share_bp']);
    }

    public function invitations(int $opId)
    {
        return DB::table('operator_invitations')->where('operator_id', $opId)->whereNull('accepted_at')->where('expires_at', '>', now())->orderByDesc('id')
            ->get(['public_id', 'email', 'role', 'expires_at', 'created_at']);
    }

    /** Pending invitations addressed to this user's email. */
    public function mine(int $userId)
    {
        $email = strtolower((string) DB::table('users')->where('id', $userId)->value('email'));

        return DB::table('operator_invitations as i')->join('operators as o', 'o.id', '=', 'i.operator_id')->whereRaw('LOWER(i.email) = ?', [$email])
            ->whereNull('i.accepted_at')->where('i.expires_at', '>', now())->orderByDesc('i.id')->get(['i.public_id', 'o.display_name as provider', 'i.role', 'i.expires_at']);
    }

    // ---------------------------------------------------------------- invite / accept

    public function invite(int $opId, int $actorId, string $actorRole, string $email, string $role): string
    {
        if (! self::canManage($actorRole, $role)) {
            throw new RuntimeException('You cannot invite someone as '.str_replace('_', ' ', $role).'.');
        }
        $email = strtolower(trim($email));
        $already = DB::table('operator_members as m')->join('users as u', 'u.id', '=', 'm.user_id')->where('m.operator_id', $opId)->where('m.status', 'active')->whereRaw('LOWER(u.email) = ?', [$email])->exists();
        if ($already) {
            throw new RuntimeException('That person is already on your team.');
        }

        $token = 'inv_'.Str::random(32);
        $publicId = DB::transaction(function () use ($opId, $actorId, $email, $role, $token) {
            DB::table('operator_invitations')->where(['operator_id' => $opId, 'email' => $email])->whereNull('accepted_at')->delete();   // a new invite replaces an old one
            $pid = (string) Str::ulid();
            DB::table('operator_invitations')->insert([
                'public_id' => $pid, 'operator_id' => $opId, 'email' => $email, 'role' => $role, 'token_hash' => hash('sha256', $token),
                'invited_by' => $actorId, 'expires_at' => now()->addDays(self::INVITE_DAYS), 'created_at' => now(), 'updated_at' => now(),
            ]);

            return $pid;
        });

        $provider = (string) DB::table('operators')->where('id', $opId)->value('display_name');
        if ($existingUser = DB::table('users')->whereRaw('LOWER(email) = ?', [$email])->value('id')) {
            $this->notify->notify((int) $existingUser, 'team.invited', ['provider' => $provider, 'role' => str_replace('_', ' ', $role)], '/invitations');
        }
        SendPlainEmail::dispatch($email, "{$provider} invited you to join their team",
            "{$provider} invited you to join as ".str_replace('_', ' ', $role).".\n\nSign in (or register with this email address) in the app and open Invitations, or enter this code:\n\n{$token}\n\nIt expires in ".self::INVITE_DAYS." days. If you were not expecting this, ignore this email.")->afterCommit();

        return $publicId;
    }

    public function revoke(int $opId, string $actorRole, string $invitationPublicId): void
    {
        $inv = DB::table('operator_invitations')->where(['operator_id' => $opId, 'public_id' => $invitationPublicId])->whereNull('accepted_at')->first();
        if (! $inv || ! self::canManage($actorRole, $inv->role)) {
            throw new RuntimeException('Invitation not found.');
        }
        DB::table('operator_invitations')->where('id', $inv->id)->delete();
    }

    /** Accept by emailed code or by invitation id. Either way the signed-in user's email must be the one invited. */
    public function accept(int $userId, ?string $token, ?string $invitationId): int
    {
        $q = DB::table('operator_invitations')->whereNull('accepted_at')->where('expires_at', '>', now());
        $inv = $token ? $q->where('token_hash', hash('sha256', $token))->first() : ($invitationId ? $q->where('public_id', $invitationId)->first() : null);
        $email = strtolower((string) DB::table('users')->where('id', $userId)->value('email'));
        // Same answer for "no such invitation" and "not yours", so codes cannot be probed.
        if (! $inv || strtolower($inv->email) !== $email) {
            throw new RuntimeException('That invitation is invalid or has expired.');
        }

        DB::transaction(function () use ($inv, $userId) {
            $existing = DB::table('operator_members')->where(['operator_id' => $inv->operator_id, 'user_id' => $userId])->first();
            if ($existing) {
                DB::table('operator_members')->where('id', $existing->id)->update(['role' => $inv->role, 'status' => 'active', 'invited_by' => $inv->invited_by, 'joined_at' => now(), 'updated_at' => now()]);
            } else {
                DB::table('operator_members')->insert([
                    'operator_id' => $inv->operator_id, 'user_id' => $userId, 'role' => $inv->role, 'status' => 'active', 'invited_by' => $inv->invited_by,
                    'joined_at' => now(), 'created_at' => now(), 'updated_at' => now(),
                ]);
            }
            DB::table('users')->where('id', $userId)->whereNull('current_operator_id')->update(['current_operator_id' => $inv->operator_id]);
            if ($inv->role === 'driver') {
                // A company's driver starts as "applied"; the company approves them before they can take work.
                $profile = ['user_id' => $userId, 'operator_id' => $inv->operator_id];
                if (DB::table('driver_profiles')->where($profile)->exists()) {
                    DB::table('driver_profiles')->where($profile)->update(['status' => 'applied', 'availability' => 'offline', 'updated_at' => now()]);
                } else {
                    DB::table('driver_profiles')->insert($profile + ['public_id' => (string) Str::ulid(), 'status' => 'applied', 'availability' => 'offline', 'created_at' => now(), 'updated_at' => now()]);
                }
            }
            DB::table('operator_invitations')->where('id', $inv->id)->update(['accepted_at' => now(), 'accepted_by' => $userId, 'updated_at' => now()]);
        });

        $name = (string) DB::table('users')->where('id', $userId)->value('name');
        $this->notify->notifyOperator((int) $inv->operator_id, 'team.joined', ['name' => $name, 'role' => str_replace('_', ' ', $inv->role)], '/provider/team', ['owner', 'admin', 'driver_manager']);

        return (int) $inv->operator_id;
    }

    // ---------------------------------------------------------------- manage members

    public function changeRole(int $opId, string $actorRole, int $memberUserId, string $newRole): void
    {
        $m = $this->member($opId, $memberUserId);
        if (! self::canManage($actorRole, $m->role) || ! self::canManage($actorRole, $newRole)) {
            throw new RuntimeException('You cannot change this person\'s role.');
        }
        if ($newRole === 'driver' && $m->role !== 'driver') {
            throw new RuntimeException('Invite them again as a driver instead.');
        }
        DB::table('operator_members')->where('id', $m->id)->update(['role' => $newRole, 'updated_at' => now()]);
    }

    public function remove(int $opId, string $actorRole, int $memberUserId): void
    {
        $m = $this->member($opId, $memberUserId);
        if (! self::canManage($actorRole, $m->role)) {
            throw new RuntimeException('You cannot remove this person.');
        }
        DB::transaction(function () use ($opId, $m, $memberUserId) {
            DB::table('operator_members')->where('id', $m->id)->update(['status' => 'removed', 'updated_at' => now()]);
            DB::table('users')->where('id', $memberUserId)->where('current_operator_id', $opId)->update(['current_operator_id' => null]);
            // A removed driver cannot receive offers; jobs already assigned stay with dispatch to reassign.
            DB::table('driver_profiles')->where(['user_id' => $memberUserId, 'operator_id' => $opId])->update(['status' => 'offboarded', 'availability' => 'offline', 'current_vehicle_id' => null, 'updated_at' => now()]);
            DB::table('vehicle_assignments')->whereNull('ended_at')->whereIn('driver_profile_id', DB::table('driver_profiles')->where(['user_id' => $memberUserId, 'operator_id' => $opId])->select('id'))->update(['ended_at' => now()]);
        });
    }

    /** Company decision on one of its drivers: active (can take work) or suspended. */
    public function setDriverStatus(int $opId, int $driverUserId, string $status): void
    {
        if (! in_array($status, ['active', 'suspended'], true)) {
            throw new RuntimeException('Unknown driver status.');
        }
        $this->member($opId, $driverUserId);
        $n = DB::table('driver_profiles')->where(['user_id' => $driverUserId, 'operator_id' => $opId])->whereNotIn('status', ['offboarded'])
            ->update(['status' => $status, 'kyc_status' => $status === 'active' ? 'company_verified' : DB::raw('kyc_status'), 'availability' => $status === 'active' ? DB::raw('availability') : 'offline',
                'onboarded_at' => $status === 'active' ? DB::raw('COALESCE(onboarded_at, now())') : DB::raw('onboarded_at'), 'updated_at' => now()]);
        if (! $n) {
            throw new RuntimeException('That person is not a driver on your team.');
        }
    }

    /** The share of each finished job's net that goes to this driver, as a percentage (0 to 100; 0 = paid outside the platform). */
    public function setDriverPay(int $opId, int $driverUserId, float $percent): void
    {
        if ($percent < 0 || $percent > 100) {
            throw new RuntimeException('Pay share must be between 0 and 100 percent.');
        }
        $this->member($opId, $driverUserId);
        $n = DB::table('driver_profiles')->where(['user_id' => $driverUserId, 'operator_id' => $opId])->whereNotIn('status', ['offboarded'])
            ->update(['pay_share_bp' => (int) round($percent * 100) ?: null, 'updated_at' => now()]);
        if (! $n) {
            throw new RuntimeException('That person is not a driver on your team.');
        }
    }

    public function assignVehicle(int $opId, int $driverUserId, string $vehiclePublicId): void
    {
        $vehicle = DB::table('vehicles')->where(['public_id' => $vehiclePublicId, 'operator_id' => $opId])->whereNull('deleted_at')->first();
        if (! $vehicle || $vehicle->status !== 'active') {
            throw new RuntimeException('Choose one of your approved vehicles.');
        }
        $driver = DB::table('driver_profiles')->where(['user_id' => $driverUserId, 'operator_id' => $opId])->where('status', 'active')->first();
        if (! $driver) {
            throw new RuntimeException('The driver must be approved first.');
        }
        DB::transaction(function () use ($vehicle, $driver) {
            // One driver per vehicle at a time, and one vehicle per driver.
            DB::table('vehicle_assignments')->whereNull('ended_at')->where(fn ($q) => $q->where('vehicle_id', $vehicle->id)->orWhere('driver_profile_id', $driver->id))->update(['ended_at' => now()]);
            DB::table('driver_profiles')->where('current_vehicle_id', $vehicle->id)->where('id', '!=', $driver->id)->update(['current_vehicle_id' => null]);
            DB::table('vehicle_assignments')->insert(['vehicle_id' => $vehicle->id, 'driver_profile_id' => $driver->id, 'started_at' => now()]);
            DB::table('driver_profiles')->where('id', $driver->id)->update(['current_vehicle_id' => $vehicle->id, 'updated_at' => now()]);
        });
    }

    private function member(int $opId, int $userId): object
    {
        $m = DB::table('operator_members')->where(['operator_id' => $opId, 'user_id' => $userId, 'status' => 'active'])->first();
        if (! $m) {
            throw new RuntimeException('That person is not on your team.');
        }

        return $m;
    }
}
