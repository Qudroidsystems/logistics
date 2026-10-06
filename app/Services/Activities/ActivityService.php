<?php

namespace App\Services\Activities;

use App\Models\ActivityMembership;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Clubs and sports share one membership model (activity_memberships).
 */
class ActivityService
{
    public const TYPES = [
        'club' => [
            'table' => 'clubs', 'name' => 'club', 'lead' => 'patronid', 'lead_label' => 'Patron',
            'label' => 'Club', 'plural' => 'Clubs', 'icon' => 'ri-team-line',
            'view' => 'View club', 'manage' => 'Update club',
            'roles' => ['member' => 'Member', 'president' => 'President', 'vice_president' => 'Vice president',
                        'secretary' => 'Secretary', 'treasurer' => 'Treasurer', 'pro' => 'P.R.O.'],
        ],
        'sport' => [
            'table' => 'sports', 'name' => 'sport', 'lead' => 'coachid', 'lead_label' => 'Coach',
            'label' => 'Sport', 'plural' => 'Sports', 'icon' => 'ri-basketball-line',
            'view' => 'View sport', 'manage' => 'Update sport',
            'roles' => ['member' => 'Member', 'captain' => 'Captain', 'vice_captain' => 'Vice captain', 'reserve' => 'Reserve'],
        ],
    ];

    public static function cfg(string $type): array
    {
        abort_unless(isset(self::TYPES[$type]), 404);
        return self::TYPES[$type];
    }

    public static function available(): bool
    {
        static $ok = null;
        return $ok ??= Schema::hasTable('activity_memberships');
    }

    public function find(string $type, int $id): object
    {
        $c = self::cfg($type);
        $row = DB::table($c['table'] . ' as a')->leftJoin('users as u', 'u.id', '=', 'a.' . $c['lead'])
            ->where('a.id', $id)->first(['a.*', 'a.' . $c['name'] . ' as name', 'u.name as lead_name']);
        abort_unless($row, 404);
        return $row;
    }

    /** All clubs (or sports) with member counts for the session. */
    public function list(string $type, int $sessionId): Collection
    {
        $c = self::cfg($type);
        $counts = DB::table('activity_memberships')->where('type', $type)->where('session_id', $sessionId)
            ->groupBy('activity_id')->selectRaw('activity_id, COUNT(*) as n')->pluck('n', 'activity_id');

        return DB::table($c['table'] . ' as a')->leftJoin('users as u', 'u.id', '=', 'a.' . $c['lead'])
            ->orderBy('a.' . $c['name'])
            ->get(['a.*', 'a.' . $c['name'] . ' as name', 'u.name as lead_name'])
            ->map(function ($r) use ($counts) {
                $r->members = (int) ($counts[$r->id] ?? 0);
                $r->active  = !isset($r->is_active) || (bool) $r->is_active;
                return $r;
            });
    }

    public function members(string $type, int $id, int $sessionId): Collection
    {
        $teams = $type === 'sport' && Schema::hasTable('sport_teams');
        $classSql = "(SELECT TRIM(CONCAT(COALESCE(c.schoolclass,''), ' ', COALESCE(ar.arm,''))) FROM studentclass sc
                      JOIN schoolclass c ON c.id = sc.schoolclassid LEFT JOIN schoolarm ar ON ar.id = c.arm
                      WHERE sc.studentId = s.id AND sc.sessionid = m.session_id ORDER BY sc.id DESC LIMIT 1)";

        return DB::table('activity_memberships as m')
            ->join('studentRegistration as s', 's.id', '=', 'm.student_id')
            ->when($teams, fn ($q) => $q->leftJoin('sport_teams as t', 't.id', '=', 'm.team_id'))
            ->where('m.type', $type)->where('m.activity_id', $id)->where('m.session_id', $sessionId)
            ->orderByRaw("m.role = 'member'")->orderBy('s.lastname')->orderBy('s.firstname')
            ->get(array_merge(
                ['m.id', 'm.student_id', 'm.role', 'm.team_id', 'm.joined_on', 'm.source', 's.firstname', 's.lastname', 's.admissionNo', 's.gender',
                 DB::raw("$classSql as class_name")],
                $teams ? ['t.team_name'] : []
            ));
    }

    /** @return array{added:int, skipped:int, full:bool} */
    public function add(string $type, int $id, array $studentIds, int $sessionId, ?int $by, string $role = 'member'): array
    {
        $act = $this->find($type, $id);
        $existing = DB::table('activity_memberships')->where('type', $type)->where('activity_id', $id)->where('session_id', $sessionId)->count();
        $room = !empty($act->capacity) ? max(0, (int) $act->capacity - $existing) : PHP_INT_MAX;

        $added = 0; $skipped = 0;
        foreach (array_unique(array_map('intval', $studentIds)) as $sid) {
            if ($added >= $room) { $skipped++; continue; }
            $added += DB::table('activity_memberships')->insertOrIgnore([
                'type' => $type, 'activity_id' => $id, 'student_id' => $sid, 'session_id' => $sessionId,
                'role' => $role, 'joined_on' => now()->toDateString(), 'source' => 'manual', 'added_by' => $by,
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }
        return ['added' => $added, 'skipped' => $skipped, 'full' => $skipped > 0];
    }

    public function remove(ActivityMembership $m): void
    {
        // Keep the old one-pick column in step with the student form.
        $legacy = $m->type === 'club' ? ['studentclubs', 'clubid'] : ['studentsports', 'sportid'];
        if (Schema::hasTable($legacy[0])) {
            DB::table($legacy[0])->where('studentid', $m->student_id)->where($legacy[1], $m->activity_id)->update([$legacy[1] => null, 'updated_at' => now()]);
        }
        $m->delete();
    }

    /** A student's clubs & sports for a session: ['club' => [...], 'sport' => [...]] */
    public function forStudent(int $studentId, int $sessionId): array
    {
        $out = ['club' => collect(), 'sport' => collect()];
        if (!self::available()) return $out;
        foreach (self::TYPES as $type => $c) {
            $out[$type] = DB::table('activity_memberships as m')->join($c['table'] . ' as a', 'a.id', '=', 'm.activity_id')
                ->where('m.type', $type)->where('m.student_id', $studentId)->where('m.session_id', $sessionId)
                ->orderBy('a.' . $c['name'])
                ->get(['m.id', 'm.activity_id', 'm.role', 'a.' . $c['name'] . ' as name'])
                ->map(fn ($r) => tap($r, fn ($r) => $r->role_label = $c['roles'][$r->role] ?? ucfirst(str_replace('_', ' ', $r->role))));
        }
        return $out;
    }

    /** Replace a student's memberships of one type for a session with $ids. */
    public function syncStudent(string $type, int $studentId, int $sessionId, array $ids, ?int $by): array
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
        $current = DB::table('activity_memberships')->where('type', $type)->where('student_id', $studentId)->where('session_id', $sessionId)->pluck('activity_id')->map(fn ($v) => (int) $v)->all();

        $removed = 0;
        foreach (array_diff($current, $ids) as $gone) {
            $m = ActivityMembership::where(['type' => $type, 'student_id' => $studentId, 'session_id' => $sessionId, 'activity_id' => $gone])->first();
            if ($m) { $this->remove($m); $removed++; }
        }
        $added = 0; $full = [];
        foreach (array_diff($ids, $current) as $new) {
            $r = $this->add($type, $new, [$studentId], $sessionId, $by);
            $added += $r['added'];
            if ($r['full']) $full[] = $this->find($type, $new)->name;
        }
        return ['added' => $added, 'removed' => $removed, 'full' => $full];
    }
}
