<?php

namespace App\Services\Houses;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * School houses. A student's house is their latest studenthouses row.
 */
class HouseService
{
    public const ROLES = ['member' => 'Member', 'captain' => 'House captain', 'vice_captain' => 'Vice captain', 'prefect' => 'House prefect'];
    public const CATEGORIES = ['sports' => 'Sports', 'academics' => 'Academics', 'conduct' => 'Conduct', 'cultural' => 'Cultural', 'other' => 'Other'];

    /** Subquery: latest studenthouses row per student. */
    public function currentQuery()
    {
        $latest = DB::table('studenthouses')->selectRaw('MAX(id) as id')->groupBy('studentid');
        return DB::table('studenthouses as sh')->joinSub($latest, 'lh', 'lh.id', '=', 'sh.id');
    }

    public function houses(): Collection
    {
        $hasPatron = Schema::hasColumn('schoolhouses', 'patron_id');
        return DB::table('schoolhouses as h')->leftJoin('users as u', 'u.id', '=', 'h.housemasterid')
            ->when($hasPatron, fn ($q) => $q->leftJoin('users as pu', 'pu.id', '=', 'h.patron_id')->leftJoin('users as au', 'au.id', '=', 'h.assistant_master_id'))
            ->orderBy('h.house')
            ->get(array_merge(['h.*', 'u.name as master_name'], $hasPatron ? ['pu.name as patron_name', 'au.name as assistant_name'] : []))
            ->map(fn ($h) => tap($h, fn ($h) => $h->active = !isset($h->is_active) || (bool) $h->is_active));
    }

    public function houseOf(int $studentId): ?object
    {
        return DB::table('studenthouses as sh')->join('schoolhouses as h', 'h.id', '=', 'sh.schoolhouse')
            ->where('sh.studentid', $studentId)->orderByDesc('sh.id')->first(['h.id', 'h.house', 'h.housecolour', 'sh.role']);
    }

    /** Per-house figures for active students placed in the session. */
    public function summary(int $sessionId): Collection
    {
        $placed = DB::table('studentclass')->where('sessionid', $sessionId)->select('studentId')->distinct();

        $rows = $this->currentQuery()
            ->join('studentRegistration as s', 's.id', '=', 'sh.studentid')
            ->whereIn('s.id', $placed)
            ->whereRaw("LOWER(COALESCE(s.student_status, 'active')) = 'active'")
            ->groupBy('sh.schoolhouse')
            ->get(['sh.schoolhouse as house_id', DB::raw('COUNT(*) as members'),
                   DB::raw("SUM(LOWER(COALESCE(s.gender,'')) IN ('male','m')) as boys"),
                   DB::raw("SUM(LOWER(COALESCE(s.gender,'')) IN ('female','f')) as girls")])
            ->keyBy('house_id');

        $points = Schema::hasTable('house_points')
            ? DB::table('house_points')->where('session_id', $sessionId)->groupBy('house_id')->selectRaw('house_id, SUM(points) as pts')->pluck('pts', 'house_id')
            : collect();

        $captains = $this->currentQuery()->join('studentRegistration as s', 's.id', '=', 'sh.studentid')
            ->when(Schema::hasColumn('studenthouses', 'role'), fn ($q) => $q->where('sh.role', 'captain'), fn ($q) => $q->whereRaw('1=0'))
            ->get(['sh.schoolhouse', DB::raw("CONCAT(s.firstname,' ',s.lastname) as name")])->groupBy('schoolhouse')
            ->map(fn ($g) => $g->pluck('name')->implode(', '));

        return $this->houses()->map(function ($h) use ($rows, $points, $captains) {
            $r = $rows[$h->id] ?? null;
            $h->members = (int) ($r->members ?? 0);
            $h->boys    = (int) ($r->boys ?? 0);
            $h->girls   = (int) ($r->girls ?? 0);
            $h->points  = (int) ($points[$h->id] ?? 0);
            $h->captain = $captains[$h->id] ?? null;
            return $h;
        });
    }

    public function unassignedCount(int $sessionId): int
    {
        return DB::table('studentclass as sc')->join('studentRegistration as s', 's.id', '=', 'sc.studentId')
            ->where('sc.sessionid', $sessionId)->whereRaw("LOWER(COALESCE(s.student_status, 'active')) = 'active'")
            ->whereNotExists(fn ($q) => $q->from('studenthouses as sh')->join('schoolhouses as h', 'h.id', '=', 'sh.schoolhouse')->whereColumn('sh.studentid', 's.id'))
            ->distinct()->count('s.id');
    }

    public function members(int $houseId, int $sessionId, ?int $classId = null, ?string $search = null): Collection
    {
        $classSql = "(SELECT CONCAT(sc.schoolclassid, '|', TRIM(CONCAT(COALESCE(c.schoolclass,''), ' ', COALESCE(ar.arm,'')))) FROM studentclass sc
                      JOIN schoolclass c ON c.id = sc.schoolclassid LEFT JOIN schoolarm ar ON ar.id = c.arm
                      WHERE sc.studentId = s.id AND sc.sessionid = " . (int) $sessionId . " ORDER BY sc.id DESC LIMIT 1)";

        $q = $this->currentQuery()->join('studentRegistration as s', 's.id', '=', 'sh.studentid')
            ->where('sh.schoolhouse', $houseId)
            ->whereRaw("LOWER(COALESCE(s.student_status, 'active')) = 'active'")
            ->select('sh.id as link_id', 's.id', 's.firstname', 's.lastname', 's.admissionNo', 's.gender',
                     Schema::hasColumn('studenthouses', 'role') ? 'sh.role' : DB::raw("'member' as role"),
                     DB::raw("$classSql as class_info"));
        if ($search) $q->where(fn ($w) => $w->where('s.admissionNo', 'like', "%$search%")->orWhereRaw("CONCAT(s.firstname,' ',s.lastname) LIKE ?", ["%$search%"]));

        $rows = $q->orderByRaw("CASE COALESCE(" . (Schema::hasColumn('studenthouses', 'role') ? 'sh.role' : "'member'") . ",'member') WHEN 'captain' THEN 0 WHEN 'vice_captain' THEN 1 WHEN 'prefect' THEN 2 ELSE 3 END")
            ->orderBy('s.lastname')->get()
            ->map(function ($r) {
                [$cid, $cname] = array_pad(explode('|', (string) $r->class_info, 2), 2, null);
                $r->class_id = $cid ? (int) $cid : null; $r->class_name = $cname;
                return $r;
            });
        return $classId ? $rows->where('class_id', $classId)->values() : $rows;
    }

    /** Put students in a house (keeps one current row per student). */
    public function assign(array $studentIds, int $houseId, ?int $termId, ?int $sessionId, string $role = 'member'): int
    {
        $n = 0;
        foreach (array_unique(array_map('intval', $studentIds)) as $sid) {
            $row = ['schoolhouse' => $houseId, 'termid' => $termId, 'sessionid' => $sessionId, 'updated_at' => now()];
            if (Schema::hasColumn('studenthouses', 'role')) $row['role'] = $role;
            $latest = DB::table('studenthouses')->where('studentid', $sid)->orderByDesc('id')->value('id');
            if ($latest) DB::table('studenthouses')->where('id', $latest)->update($row);
            else DB::table('studenthouses')->insert($row + ['studentid' => $sid, 'created_at' => now()]);
            $n++;
        }
        return $n;
    }

    public function setRole(int $studentId, string $role): void
    {
        $latest = DB::table('studenthouses')->where('studentid', $studentId)->orderByDesc('id')->value('id');
        if ($latest) DB::table('studenthouses')->where('id', $latest)->update(['role' => $role, 'updated_at' => now()]);
    }

    /** Sibling groups as arrays of student ids (if the school uses sibling groups). */
    public function siblingGroups(): array
    {
        $groups = [];
        if (Schema::hasTable('sibling_group_students')) {
            foreach (DB::table('sibling_group_students')->get(['sibling_group_id as g', 'student_id']) as $r) $groups[$r->g][] = (int) $r->student_id;
        } elseif (Schema::hasColumn('studentRegistration', 'sibling_group_id')) {
            foreach (DB::table('studentRegistration')->whereNotNull('sibling_group_id')->get(['sibling_group_id as g', 'id']) as $r) $groups[$r->g][] = (int) $r->id;
        }
        return array_values(array_filter($groups, fn ($g) => count($g) > 1));
    }

    /**
     * Balanced auto-assignment of students without a house: spreads each
     * class and gender evenly across houses and keeps siblings together.
     * @return array{plan: array<int,int>, per_house: array<int,int>}
     */
    public function autoPlan(int $sessionId): array
    {
        $houses = $this->houses()->where('active', true)->pluck('id')->map(fn ($v) => (int) $v)->all();
        if (!$houses) return ['plan' => [], 'per_house' => []];

        $students = DB::table('studentclass as sc')->join('studentRegistration as s', 's.id', '=', 'sc.studentId')
            ->where('sc.sessionid', $sessionId)->whereRaw("LOWER(COALESCE(s.student_status, 'active')) = 'active'")
            ->groupBy('s.id', 's.gender')->get(['s.id', 's.gender', DB::raw('MAX(sc.schoolclassid) as class_id')])
            ->keyBy('id');

        $current = $this->currentQuery()->whereIn('sh.schoolhouse', $houses)->pluck('sh.schoolhouse', 'sh.studentid')->map(fn ($v) => (int) $v);

        $cell = []; $classTot = []; $tot = array_fill_keys($houses, 0);
        $g = fn ($s) => in_array(strtolower((string) $s->gender), ['male', 'm'], true) ? 'm' : (in_array(strtolower((string) $s->gender), ['female', 'f'], true) ? 'f' : 'x');
        $bump = function ($s, $h) use (&$cell, &$classTot, &$tot, $g) {
            $cell[$s->class_id][$g($s)][$h] = ($cell[$s->class_id][$g($s)][$h] ?? 0) + 1;
            $classTot[$s->class_id][$h] = ($classTot[$s->class_id][$h] ?? 0) + 1;
            $tot[$h]++;
        };
        foreach ($current as $sid => $h) if (isset($students[$sid])) $bump($students[$sid], $h);

        // Units: sibling groups first (together), then singles.
        $units = []; $inUnit = [];
        foreach ($this->siblingGroups() as $grp) {
            $ids = array_values(array_filter($grp, fn ($i) => isset($students[$i])));
            if (!$ids) continue;
            $units[] = $ids;
            foreach ($ids as $i) $inUnit[$i] = true;
        }
        foreach ($students as $sid => $s) if (!isset($inUnit[$sid])) $units[] = [(int) $sid];
        usort($units, fn ($a, $b) => count($b) <=> count($a));

        $plan = [];
        foreach ($units as $ids) {
            $todo = array_values(array_filter($ids, fn ($i) => !isset($current[$i])));
            if (!$todo) continue;
            $known = array_values(array_unique(array_map(fn ($i) => $current[$i], array_filter($ids, fn ($i) => isset($current[$i])))));
            if ($known) {
                $house = $known[0]; // join the sibling already placed
            } else {
                $first = $students[$todo[0]];
                $best = null; $house = $houses[0];
                foreach ($houses as $h) {
                    $score = [0, 0, 0];
                    foreach ($todo as $i) { $s = $students[$i]; $score[0] += $cell[$s->class_id][$g($s)][$h] ?? 0; $score[1] += $classTot[$s->class_id][$h] ?? 0; }
                    $score[2] = $tot[$h];
                    if ($best === null || $score < $best) { $best = $score; $house = $h; }
                }
            }
            foreach ($todo as $i) { $plan[$i] = $house; $bump($students[$i], $house); }
        }

        return ['plan' => $plan, 'per_house' => array_count_values($plan)];
    }

    public function awardPoints(array $data, ?int $by): void
    {
        DB::table('house_points')->insert([
            'house_id' => (int) $data['house_id'], 'points' => (int) $data['points'], 'category' => $data['category'] ?? 'other',
            'reason' => $data['reason'], 'event_date' => $data['event_date'] ?? now()->toDateString(),
            'student_id' => $data['student_id'] ?? null, 'session_id' => (int) $data['session_id'], 'term_id' => $data['term_id'] ?? null,
            'awarded_by' => $by, 'created_at' => now(), 'updated_at' => now(),
        ]);
    }
}
