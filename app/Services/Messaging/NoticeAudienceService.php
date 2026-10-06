<?php

namespace App\Services\Messaging;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Turns a notice audience into contacts per channel, one entry per phone
 * number / email address. A parent with three children appears once, with
 * all three children attached.
 *
 * audience = [
 *   'scope'         => 'school' | 'classes' | 'categories' | 'students' | 'none',
 *   'class_ids'     => [..], 'category_ids' => [..], 'student_ids' => [..],
 *   'include_staff' => bool,
 * ]
 */
class NoticeAudienceService
{
    /**
     * @return array{
     *   contacts: array<string, array<string, array>>,   // channel => contact => [to, name, type, students, classes]
     *   students: int, staff: int,
     *   missing: array<string, array>                    // channel => [student rows with no contact]
     * }
     */
    public function resolve(array $audience, array $channels): array
    {
        $channels = array_values(array_intersect($channels, ['sms', 'whatsapp', 'email']));
        $contacts = array_fill_keys($channels, []);
        $missing  = array_fill_keys($channels, []);

        $students = $this->students($audience);
        foreach ($students as $st) {
            foreach ($channels as $ch) {
                $found = $this->studentContacts($st, $ch);
                if (!$found) {
                    $missing[$ch][] = ['id' => $st->id, 'name' => $st->student_name, 'adm' => $st->admissionNo, 'class' => $st->class_name];
                    continue;
                }
                foreach ($found as $to => $name) {
                    $c = $contacts[$ch][$to] ?? ['to' => $to, 'name' => $name, 'type' => 'parent', 'students' => [], 'student_names' => [], 'classes' => []];
                    if (!in_array($st->id, $c['students'], true)) {
                        $c['students'][]      = $st->id;
                        $c['student_names'][] = $st->first_name;
                        if ($st->class_name) $c['classes'][] = $st->class_name;
                    }
                    $c['name'] = $c['name'] ?: $name;
                    $contacts[$ch][$to] = $c;
                }
            }
        }

        $staffCount = 0;
        if (!empty($audience['include_staff'])) {
            $staff = $this->staff();
            $staffCount = $staff->count();
            foreach ($staff as $u) {
                foreach ($channels as $ch) {
                    $to = $ch === 'email'
                        ? (filter_var($u->email, FILTER_VALIDATE_EMAIL) ? strtolower(trim($u->email)) : null)
                        : MessagingService::normalizePhone($u->phone_number);
                    if (!$to || isset($contacts[$ch][$to])) continue; // a staff parent already gets the parent copy
                    $contacts[$ch][$to] = ['to' => $to, 'name' => $u->name, 'type' => 'staff', 'students' => [], 'student_names' => [], 'classes' => []];
                }
            }
        }

        foreach ($contacts as $ch => $list) {
            foreach ($list as $to => $c) {
                $contacts[$ch][$to]['classes'] = array_values(array_unique($c['classes']));
            }
        }

        return ['contacts' => $contacts, 'students' => $students->count(), 'staff' => $staffCount, 'missing' => $missing];
    }

    /** Active student ids in an audience (for in-portal notifications). */
    public function studentIds(array $audience): array
    {
        return $this->students($audience)->pluck('id')->map(fn ($v) => (int) $v)->all();
    }

    protected function students(array $a)
    {
        $scope = $a['scope'] ?? 'school';
        if ($scope === 'none') return collect();

        $pr = Schema::hasTable('parentRegistration');
        $col = fn ($c) => $pr && Schema::hasColumn('parentRegistration', $c) ? "p.$c" : DB::raw("NULL as $c");

        $q = DB::table('studentRegistration as s')
            ->leftJoin('studentclass as scl', 'scl.studentId', '=', 's.id')
            ->leftJoin('schoolclass as c', 'c.id', '=', 'scl.schoolclassid')
            ->leftJoin('schoolarm as a', 'a.id', '=', 'c.arm')
            ->whereRaw("LOWER(COALESCE(s.student_status, 'active')) = 'active'");
        if ($pr) {
            $q->leftJoin('parentRegistration as p', 'p.studentId', '=', 's.id');
        }

        if ($scope === 'classes') {
            $q->whereIn('scl.schoolclassid', array_map('intval', $a['class_ids'] ?? [0]));
        } elseif ($scope === 'categories') {
            $cats = array_map('intval', $a['category_ids'] ?? [0]);
            $classIds = collect();
            if (Schema::hasTable('schoolclass_classcategory')) {
                $classIds = DB::table('schoolclass_classcategory')->whereIn('classcategory_id', $cats)->pluck('schoolclass_id');
            }
            $classIds = $classIds->merge(DB::table('schoolclass')->whereIn('classcategoryid', $cats)->pluck('id'))->unique();
            $q->whereIn('scl.schoolclassid', $classIds->all() ?: [0]);
        } elseif ($scope === 'students') {
            $q->whereIn('s.id', array_map('intval', $a['student_ids'] ?? [0]));
        }

        $rows = $q->get([
            's.id', 's.firstname', 's.lastname', 's.admissionNo',
            DB::raw("TRIM(CONCAT(COALESCE(c.schoolclass,''), ' ', COALESCE(a.arm,''))) as class_name"),
            $col('father'), $col('mother'), $col('guardian_name'),
            $col('father_phone'), $col('mother_phone'), $col('guardian_phone'), $col('whatsapp_number'), $col('parent_email'),
        ]);

        // One row per student (merge extra parent/placement rows).
        return $rows->groupBy('id')->map(function ($g) {
            $s = clone $g->first();
            foreach (['father', 'mother', 'guardian_name', 'father_phone', 'mother_phone', 'guardian_phone', 'whatsapp_number', 'parent_email', 'class_name'] as $f) {
                $s->$f = $g->pluck($f)->filter()->first();
            }
            $s->first_name   = trim((string) $s->firstname) ?: trim((string) $s->lastname);
            $s->student_name = trim($s->lastname . ' ' . $s->firstname);
            return $s;
        })->values();
    }

    /** [contact => display name] for one student on one channel. */
    protected function studentContacts(object $s, string $channel): array
    {
        $out = [];
        if ($channel === 'email') {
            foreach (preg_split('/[;,\s]+/', (string) $s->parent_email) as $e) {
                if (filter_var($e, FILTER_VALIDATE_EMAIL)) $out[strtolower($e)] = $this->parentName($s);
            }
            return $out;
        }

        $phones = $channel === 'whatsapp' && $s->whatsapp_number
            ? [[$s->whatsapp_number, $this->parentName($s)]]
            : [[$s->father_phone, $s->father], [$s->mother_phone, $s->mother], [$s->guardian_phone, $s->guardian_name]];

        foreach ($phones as [$p, $name]) {
            if ($n = MessagingService::normalizePhone($p)) {
                $out[$n] = $out[$n] ?? (trim((string) $name) ?: 'Parent');
            }
        }
        return $out;
    }

    protected function parentName(object $s): string
    {
        return trim((string) ($s->father ?: $s->mother ?: $s->guardian_name)) ?: 'Parent';
    }

    protected function staff()
    {
        return User::whereNull('student_id')
            ->when(method_exists(User::class, 'roles'), fn ($q) => $q->whereDoesntHave('roles', fn ($r) => $r->whereIn('name', ['Student', 'Parent', 'student', 'parent'])))
            ->get(['id', 'name', 'email', 'phone_number']);
    }
}
