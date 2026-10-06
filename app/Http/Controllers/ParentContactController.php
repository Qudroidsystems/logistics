<?php

namespace App\Http\Controllers;

use App\Services\Messaging\MessagingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Parent contact clean-up: find students whose parents have missing or
 * invalid phone numbers / emails, fix them one by one, or in bulk via CSV.
 * Every SMS / WhatsApp / email feature depends on this data.
 */
class ParentContactController extends Controller
{
    public const FIELDS = [
        'father_name'     => ['col' => 'father',          'label' => 'Father name',     'type' => 'name'],
        'father_phone'    => ['col' => 'father_phone',    'label' => 'Father phone',    'type' => 'phone'],
        'mother_name'     => ['col' => 'mother',          'label' => 'Mother name',     'type' => 'name'],
        'mother_phone'    => ['col' => 'mother_phone',    'label' => 'Mother phone',    'type' => 'phone'],
        'guardian_name'   => ['col' => 'guardian_name',   'label' => 'Guardian name',   'type' => 'name'],
        'guardian_phone'  => ['col' => 'guardian_phone',  'label' => 'Guardian phone',  'type' => 'phone'],
        'whatsapp_number' => ['col' => 'whatsapp_number', 'label' => 'WhatsApp number', 'type' => 'phone'],
        'parent_email'    => ['col' => 'parent_email',    'label' => 'Parent email',    'type' => 'email'],
    ];

    public const ISSUES = [
        ''              => 'All students',
        'no_contact'    => 'No phone and no email',
        'no_phone'      => 'No valid phone',
        'invalid_phone' => 'A phone number is invalid',
        'no_email'      => 'No parent email',
        'invalid_email' => 'Email is invalid',
        'no_whatsapp'   => 'No WhatsApp number',
    ];

    public function __construct()
    {
        $this->middleware('permission:Manage parent contacts');
    }

    public function index(Request $request)
    {
        $rows  = $this->rows($request->integer('class_id') ?: null, trim((string) $request->get('q')));
        $all   = $rows;
        $issue = (string) $request->get('issue', '');
        if ($issue !== '' && isset(self::ISSUES[$issue])) {
            $rows = $rows->filter(fn ($r) => in_array($issue, $r->issues, true));
        }

        $page    = max(1, $request->integer('page', 1));
        $perPage = 50;
        $paged   = new \Illuminate\Pagination\LengthAwarePaginator(
            $rows->slice(($page - 1) * $perPage, $perPage)->values(), $rows->count(), $perPage, $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return view('parent-contacts.index', [
            'pagetitle' => 'Parent Contacts',
            'rows'      => $paged,
            'classes'   => $this->classes(),
            'issues'    => self::ISSUES,
            'fields'    => self::FIELDS,
            'stats'     => [
                'students'  => $all->count(),
                'phone'     => $all->filter(fn ($r) => $r->valid_phones > 0)->count(),
                'email'     => $all->filter(fn ($r) => $r->valid_email)->count(),
                'whatsapp'  => $all->filter(fn ($r) => $r->has_whatsapp)->count(),
                'none'      => $all->filter(fn ($r) => in_array('no_contact', $r->issues, true))->count(),
                'invalid'   => $all->filter(fn ($r) => in_array('invalid_phone', $r->issues, true) || in_array('invalid_email', $r->issues, true))->count(),
            ],
        ]);
    }

    /** Save one student's parent contacts (AJAX from the edit modal). */
    public function update(Request $request, int $student)
    {
        abort_unless(DB::table('studentRegistration')->where('id', $student)->exists(), 404);

        $data = [];
        $errors = [];
        foreach (self::FIELDS as $key => $f) {
            if (!$this->hasCol($f['col'])) continue;
            $v = trim((string) $request->input($key, ''));
            if ($v !== '' && ($err = $this->invalid($f['type'], $v))) {
                $errors[$key] = $f['label'] . ': ' . $err;
                continue;
            }
            $data[$f['col']] = $v === '' ? null : $this->clean($f['type'], $v);
        }
        if ($errors) {
            return response()->json(['success' => false, 'message' => implode(' ', $errors), 'errors' => $errors], 422);
        }

        $this->save($student, $data);
        $row = $this->rows(null, '', [$student])->first();

        return response()->json(['success' => true, 'message' => 'Contacts saved.', 'row' => $row]);
    }

    /** CSV of the current filter, ready to fill in and import back. */
    public function export(Request $request): StreamedResponse
    {
        $rows  = $this->rows($request->integer('class_id') ?: null, trim((string) $request->get('q')));
        $issue = (string) $request->get('issue', '');
        if ($issue !== '') $rows = $rows->filter(fn ($r) => in_array($issue, $r->issues, true));

        $name = 'parent-contacts-' . now()->format('Y-m-d') . '.csv';
        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // Excel-friendly UTF-8
            fputcsv($out, array_merge(['admission_no', 'student_name', 'class'], array_keys(self::FIELDS), ['issues']));
            foreach ($rows as $r) {
                $line = [$r->admission_no, $r->student_name, $r->class_name];
                foreach (self::FIELDS as $key => $f) $line[] = $r->{$key} ?? '';
                $line[] = implode('; ', array_map(fn ($i) => self::ISSUES[$i] ?? $i, $r->issues));
                fputcsv($out, $line);
            }
            fclose($out);
        }, $name, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * Import a CSV (same columns as the export). Matched by admission_no.
     * Empty cells keep the current value. Dry run shows what would change.
     */
    public function import(Request $request)
    {
        $request->validate(['file' => 'required|file|mimes:csv,txt|max:5120', 'dry_run' => 'nullable|boolean']);
        $dry = $request->boolean('dry_run');

        $fh = fopen($request->file('file')->getRealPath(), 'r');
        $header = fgetcsv($fh);
        if (!$header) {
            return back()->with('error', 'The file is empty.');
        }
        $header = array_map(fn ($h) => strtolower(trim(preg_replace('/^\xEF\xBB\xBF/', '', (string) $h))), $header);
        if (!in_array('admission_no', $header, true)) {
            return back()->with('error', 'The file needs an "admission_no" column. Download the CSV from this page to get the right columns.');
        }

        $students = DB::table('studentRegistration')->pluck('id', 'admissionNo')
            ->mapWithKeys(fn ($id, $adm) => [strtolower(trim((string) $adm)) => $id]);

        $report = [];
        $counts = ['updated' => 0, 'unchanged' => 0, 'not_found' => 0, 'invalid' => 0];
        $line = 1;
        while (($cells = fgetcsv($fh)) !== false) {
            $line++;
            if (count(array_filter($cells, fn ($c) => trim((string) $c) !== '')) === 0) continue;
            $row = array_combine(array_slice($header, 0, count($cells)), array_slice($cells, 0, count($header))) ?: [];
            $adm = trim((string) ($row['admission_no'] ?? ''));
            $sid = $students[strtolower($adm)] ?? null;

            if (!$sid) {
                $counts['not_found']++;
                $report[] = ['line' => $line, 'adm' => $adm, 'status' => 'not_found', 'note' => 'Admission number not found'];
                continue;
            }

            $current = (array) (DB::table('parentRegistration')->where('studentId', $sid)->first() ?? []);
            $data = []; $errs = []; $changes = [];
            foreach (self::FIELDS as $key => $f) {
                if (!array_key_exists($key, $row) || !$this->hasCol($f['col'])) continue;
                $v = trim((string) $row[$key]);
                if ($v === '') continue; // keep
                if ($err = $this->invalid($f['type'], $v)) { $errs[] = $f['label'] . ' ' . $err; continue; }
                $v = $this->clean($f['type'], $v);
                if ((string) ($current[$f['col']] ?? '') !== $v) {
                    $data[$f['col']] = $v;
                    $changes[] = $f['label'];
                }
            }

            if ($errs) {
                $counts['invalid']++;
                $report[] = ['line' => $line, 'adm' => $adm, 'status' => 'invalid', 'note' => implode('; ', $errs) . ($changes ? ' (other fields ' . ($dry ? 'would be' : 'were') . ' saved)' : '')];
            }
            if ($changes) {
                if (!$dry) $this->save($sid, $data);
                if (!$errs) $counts['updated']++;
                if (!$errs) $report[] = ['line' => $line, 'adm' => $adm, 'status' => 'updated', 'note' => implode(', ', $changes)];
            } elseif (!$errs) {
                $counts['unchanged']++;
            }
        }
        fclose($fh);

        return back()->with('import_report', ['dry' => $dry, 'counts' => $counts, 'rows' => array_slice($report, 0, 300)])
            ->with('success', $dry ? 'Dry run finished — nothing was saved.' : 'Import finished.');
    }

    // ── helpers ─────────────────────────────────────────────────────────

    protected function rows(?int $classId, string $q, array $ids = [])
    {
        $cols = collect(self::FIELDS)->map(fn ($f, $k) => $this->hasCol($f['col']) ? "p.{$f['col']} as {$k}" : DB::raw("NULL as {$k}"))->values()->all();

        $rows = DB::table('studentRegistration as s')
            ->leftJoin('parentRegistration as p', 'p.studentId', '=', 's.id')
            ->leftJoin('studentclass as sc', 'sc.studentId', '=', 's.id')
            ->leftJoin('schoolclass as c', 'c.id', '=', 'sc.schoolclassid')
            ->leftJoin('schoolarm as a', 'a.id', '=', 'c.arm')
            ->whereRaw("LOWER(COALESCE(s.student_status, 'active')) = 'active'")
            ->when($classId, fn ($w) => $w->where('sc.schoolclassid', $classId))
            ->when($ids, fn ($w) => $w->whereIn('s.id', $ids))
            ->when($q !== '', fn ($w) => $w->where(fn ($x) => $x->where('s.admissionNo', 'like', "%{$q}%")
                ->orWhere('s.firstname', 'like', "%{$q}%")->orWhere('s.lastname', 'like', "%{$q}%")))
            ->orderBy('c.schoolclass')->orderBy('a.arm')->orderBy('s.lastname')->orderBy('s.firstname')
            ->get(array_merge([
                's.id', 's.admissionNo as admission_no',
                DB::raw("TRIM(CONCAT(COALESCE(s.lastname,''), ' ', COALESCE(s.firstname,''))) as student_name"),
                DB::raw("TRIM(CONCAT(COALESCE(c.schoolclass,''), ' ', COALESCE(a.arm,''))) as class_name"),
            ], $cols));

        return $rows->unique('id')->map(function ($r) {
            $phones = collect([$r->father_phone, $r->mother_phone, $r->guardian_phone])->filter(fn ($p) => trim((string) $p) !== '');
            $valid  = $phones->filter(fn ($p) => MessagingService::normalizePhone($p));
            $emailOk = $r->parent_email && filter_var(trim($r->parent_email), FILTER_VALIDATE_EMAIL);
            $waOk    = $r->whatsapp_number ? (bool) MessagingService::normalizePhone($r->whatsapp_number) : $valid->isNotEmpty();

            $issues = [];
            if ($valid->isEmpty() && !$emailOk) $issues[] = 'no_contact';
            if ($valid->isEmpty()) $issues[] = 'no_phone';
            if ($phones->count() > $valid->count() || ($r->whatsapp_number && !MessagingService::normalizePhone($r->whatsapp_number))) $issues[] = 'invalid_phone';
            if (!$r->parent_email) $issues[] = 'no_email';
            elseif (!$emailOk) $issues[] = 'invalid_email';
            if (!$r->whatsapp_number) $issues[] = 'no_whatsapp';

            $r->issues = $issues;
            $r->valid_phones = $valid->count();
            $r->valid_email = (bool) $emailOk;
            $r->has_whatsapp = $waOk;
            return $r;
        })->values();
    }

    protected function save(int $studentId, array $data): void
    {
        if (!$data) return;
        $exists = DB::table('parentRegistration')->where('studentId', $studentId)->exists();
        if ($exists) {
            DB::table('parentRegistration')->where('studentId', $studentId)->update($data + ['updated_at' => now()]);
        } else {
            DB::table('parentRegistration')->insert($data + ['studentId' => $studentId, 'created_at' => now(), 'updated_at' => now()]);
        }
    }

    protected function invalid(string $type, string $v): ?string
    {
        return match ($type) {
            'phone' => MessagingService::normalizePhone($v) ? null : 'is not a valid phone number',
            'email' => filter_var($v, FILTER_VALIDATE_EMAIL) ? null : 'is not a valid email',
            default => mb_strlen($v) > 190 ? 'is too long' : null,
        };
    }

    /** Store phones as local numbers (0803…) and emails in lower case. */
    protected function clean(string $type, string $v): string
    {
        if ($type === 'email') return strtolower($v);
        if ($type === 'phone') {
            $n = MessagingService::normalizePhone($v);
            return ($n && str_starts_with($n, '234') && strlen($n) === 13) ? '0' . substr($n, 3) : ($n ?: $v);
        }
        return $v;
    }

    protected function hasCol(string $col): bool
    {
        static $cols = null;
        $cols ??= array_map('strtolower', Schema::getColumnListing('parentRegistration'));
        return in_array(strtolower($col), $cols, true);
    }

    protected function classes()
    {
        return DB::table('schoolclass')->leftJoin('schoolarm', 'schoolarm.id', '=', 'schoolclass.arm')
            ->orderBy('schoolclass.schoolclass')->orderBy('schoolarm.arm')
            ->get(['schoolclass.id', DB::raw("TRIM(CONCAT(schoolclass.schoolclass, ' ', COALESCE(schoolarm.arm, ''))) as name")]);
    }
}
