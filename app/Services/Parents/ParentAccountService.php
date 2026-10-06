<?php

namespace App\Services\Parents;

use App\Models\SchoolInformation;
use App\Models\User;
use App\Services\Messaging\MessagingService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

/**
 * Parent accounts: one per parent phone number found in the student records
 * (father / mother / guardian phone). Children sharing a number are linked to
 * the same account. Staff who are also parents keep their own login and get
 * the Parent role added.
 */
class ParentAccountService
{
    public const ROLE = 'Parent';

    public function __construct(protected MessagingService $messaging) {}

    public static function role(): Role
    {
        return Role::firstOrCreate(['name' => self::ROLE, 'guard_name' => 'web']);
    }

    /** Phone spellings to look a user up by (0803…, 234803…, +234803…, 803…). */
    public static function phoneVariants(?string $phone): array
    {
        $n = MessagingService::normalizePhone($phone);
        if (!$n) return [];
        $v = [$n, '+' . $n];
        if (str_starts_with($n, '234') && strlen($n) === 13) {
            $v[] = '0' . substr($n, 3);
            $v[] = substr($n, 3);
        }
        return array_values(array_unique($v));
    }

    public static function localPhone(string $phone): string
    {
        $n = MessagingService::normalizePhone($phone) ?? $phone;
        return (str_starts_with($n, '234') && strlen($n) === 13) ? '0' . substr($n, 3) : $n;
    }

    public static function findByPhone(?string $phone): ?User
    {
        $v = self::phoneVariants($phone);
        return $v ? User::whereIn('phone_number', $v)->orderBy('id')->first() : null;
    }

    /**
     * Create/update accounts from the records and link children.
     * @return array{created:int, linked:int, unlinked:int, accounts:int}
     */
    public function sync(): array
    {
        $role  = self::role();
        $stats = ['created' => 0, 'linked' => 0, 'unlinked' => 0, 'accounts' => 0];
        $cols  = array_map('strtolower', Schema::getColumnListing('parentRegistration'));
        $has   = fn ($c) => in_array(strtolower($c), $cols, true);

        $rows = DB::table('parentRegistration as p')
            ->join('studentRegistration as s', 's.id', '=', 'p.studentId')
            ->whereRaw("LOWER(COALESCE(s.student_status, 'active')) = 'active'")
            ->get(array_merge(['p.studentId as student_id'], array_map(
                fn ($c) => $has($c) ? "p.$c" : DB::raw("NULL as $c"),
                ['father', 'mother', 'guardian_name', 'father_phone', 'mother_phone', 'guardian_phone', 'parent_email']
            )));

        // phone → [name, email, students[id => relationship]]
        $groups = [];
        foreach ($rows as $r) {
            foreach ([['father_phone', 'father', 'father'], ['mother_phone', 'mother', 'mother'], ['guardian_phone', 'guardian_name', 'guardian']] as [$pc, $nc, $rel]) {
                $n = MessagingService::normalizePhone($r->$pc);
                if (!$n) continue;
                $g = $groups[$n] ?? ['name' => null, 'email' => null, 'students' => []];
                $g['name']  = $g['name'] ?: (trim((string) $r->$nc) ?: null);
                $g['email'] = $g['email'] ?: ($r->parent_email && filter_var(trim($r->parent_email), FILTER_VALIDATE_EMAIL) ? strtolower(trim($r->parent_email)) : null);
                $g['students'][(int) $r->student_id] = $g['students'][(int) $r->student_id] ?? $rel;
                $groups[$n] = $g;
            }
        }

        $host = preg_replace('/^www\./', '', parse_url(config('app.url'), PHP_URL_HOST) ?: 'school.ng');

        foreach ($groups as $phone => $g) {
            DB::transaction(function () use ($phone, $g, $role, $host, &$stats) {
                $user = self::findByPhone($phone);
                if (!$user) {
                    $email = $g['email'] && !User::where('email', $g['email'])->exists() ? $g['email'] : 'p' . $phone . '@parents.' . $host;
                    if (User::where('email', $email)->exists()) $email = 'p' . $phone . '.' . Str::lower(Str::random(4)) . '@parents.' . $host;
                    $user = User::create([
                        'name'         => $g['name'] ?: 'Parent',
                        'email'        => $email,
                        'password'     => Hash::make(Str::random(32)), // real password is sent later
                        'phone_number' => self::localPhone($phone),
                    ]);
                    $stats['created']++;
                }
                if (!$user->hasRole(self::ROLE)) $user->assignRole($role);
                $stats['accounts']++;

                foreach ($g['students'] as $sid => $rel) {
                    $stats['linked'] += DB::table('parent_student')->insertOrIgnore([
                        'user_id' => $user->id, 'student_id' => $sid, 'relationship' => $rel, 'source' => 'auto',
                        'created_at' => now(), 'updated_at' => now(),
                    ]);
                }
                // Auto links whose phone was removed from the records.
                $stats['unlinked'] += DB::table('parent_student')->where('user_id', $user->id)->where('source', 'auto')
                    ->whereNotIn('student_id', array_keys($g['students']))->delete();
            });
        }

        return $stats;
    }

    /**
     * New temporary password + login details by SMS / WhatsApp / email.
     * @return array{password: string, results: array}
     */
    public function sendCredentials(User $user, array $channels = ['sms', 'whatsapp', 'email']): array
    {
        $password = self::tempPassword();
        $user->forceFill([
            'password' => Hash::make($password), 'must_change_password' => true, 'credentials_sent_at' => now(),
        ])->save();

        $school = (SchoolInformation::getActiveSchool() ?? SchoolInformation::first())->school_name ?? config('app.name');
        $url    = rtrim(config('app.url'), '/') . '/login';
        $text   = "Dear {$user->name}, your {$school} parent portal account is ready. Login: {$url} Phone: {$user->phone_number} Password: {$password} (change it at first login).";

        $results = [];
        foreach ($channels as $ch) {
            if (!$this->messaging->enabled($ch)) continue;
            $to = $ch === 'email'
                ? (filter_var($user->email, FILTER_VALIDATE_EMAIL) && !str_contains($user->email, '@parents.') ? $user->email : null)
                : MessagingService::normalizePhone($user->phone_number);
            if (!$to) continue;
            $r = $this->messaging->send($ch, $to, $text, ['name' => $user->name, 'subject' => 'Your parent portal login']);
            $results[$ch] = $r['status'] . ($r['error'] ? ': ' . $r['error'] : '');
        }
        Log::info('Parent credentials sent', ['user' => $user->id, 'results' => $results]);

        return ['password' => $password, 'results' => $results];
    }

    public static function tempPassword(): string
    {
        $alphabet = 'abcdefghjkmnpqrstuvwxyz23456789';
        $p = '';
        for ($i = 0; $i < 8; $i++) $p .= $alphabet[random_int(0, strlen($alphabet) - 1)];
        return $p;
    }

    /** Children linked to a parent (active students). */
    public static function children(User $user): Collection
    {
        return DB::table('parent_student as ps')
            ->join('studentRegistration as s', 's.id', '=', 'ps.student_id')
            ->leftJoin('studentclass as sc', 'sc.studentId', '=', 's.id')
            ->leftJoin('schoolclass as c', 'c.id', '=', 'sc.schoolclassid')
            ->leftJoin('schoolarm as a', 'a.id', '=', 'c.arm')
            ->leftJoin('studentpicture as pic', 'pic.studentid', '=', 's.id')
            ->where('ps.user_id', $user->id)
            ->orderBy('s.firstname')
            ->get(['s.id', 's.firstname', 's.lastname', 's.othername', 's.admissionNo', 's.statusId', 'ps.relationship',
                   'sc.schoolclassid as class_id', 'sc.termid as term_id', 'sc.sessionid as session_id', 'pic.picture',
                   DB::raw("TRIM(CONCAT(COALESCE(c.schoolclass,''), ' ', COALESCE(a.arm,''))) as class_name")])
            ->unique('id')->values();
    }

    public static function isParentOf(User $user, int $studentId): bool
    {
        return DB::table('parent_student')->where('user_id', $user->id)->where('student_id', $studentId)->exists();
    }

    /** Parent user ids for these students (for notifications). */
    public static function parentUserIds(array $studentIds): array
    {
        if (!$studentIds || !Schema::hasTable('parent_student')) return [];
        return DB::table('parent_student')->whereIn('student_id', $studentIds)->pluck('user_id')->unique()->values()->all();
    }
}
