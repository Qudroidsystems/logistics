<?php

namespace App\Services\Billing;

use App\Models\DiscountAssignment;
use App\Models\ScholarshipAssignment;
use App\Models\Schoolsession;
use App\Models\Schoolterm;
use App\Models\Student;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * A student's fee statement for one term: bills, payable amounts after
 * scholarships/discounts, amount paid (from the payment ledger), balances,
 * transaction history and arrears.
 *
 * Single source of truth for the student-facing money figures -- used by
 * the My Payments portal AND the result-access gate, so "you owe X" is the
 * same number everywhere, and matches the bursar's payment screen
 * (same BillAdjustmentService, same ledger sums).
 */
class StudentFeeStatementService
{
    public function __construct(
        protected BillAdjustmentService $billAdjustment,
        protected ArrearsService $arrears
    ) {}

    /** Column lists per table, so optional columns (added by later migrations
     *  that may not have run on every server) never break a query. */
    protected static array $columnCache = [];

    protected function hasColumn(string $table, string $column): bool
    {
        if (!isset(self::$columnCache[$table])) {
            try {
                self::$columnCache[$table] = array_map('strtolower', Schema::getColumnListing($table));
            } catch (\Throwable $e) {
                self::$columnCache[$table] = [];
            }
        }
        return in_array(strtolower($column), self::$columnCache[$table], true);
    }

    /** whereNull(alias.deleted_at) only when the table has soft deletes. */
    protected function notDeleted($query, string $table, ?string $alias = null)
    {
        return $this->hasColumn($table, 'deleted_at')
            ? $query->whereNull(($alias ? $alias . '.' : '') . 'deleted_at')
            : $query;
    }

    public function buildStatement(Student $student, ?int $termId, ?int $sessionId): array
    {
        $studentId = (int) $student->id;
        $empty = [
            'class' => null, 'term' => null, 'session' => null,
            'bills' => collect(), 'paymentHistory' => collect(),
            'totals' => ['original' => 0, 'adjusted' => 0, 'paid' => 0, 'outstanding' => 0, 'savings' => 0],
            'scholarshipAssignment' => null, 'discountAssignments' => collect(),
            'arrears' => ['has_arrears' => false, 'total_arrears' => 0.0, 'groups' => [], 'bills' => []],
            'statementError' => null,
        ];

        $term    = $termId ? Schoolterm::find($termId, ['id', 'term']) : null;
        $session = $sessionId ? Schoolsession::find($sessionId, ['id', 'session']) : null;
        if (!$term || !$session) {
            return ['statementError' => 'Select a term and session to see your bills.'] + $empty;
        }

        $classId = $this->resolveClassId($studentId, $termId, $sessionId);
        $arrears = $this->arrears->getStudentArrears($studentId, $termId, $sessionId);

        if (!$classId) {
            return array_merge($empty, [
                'term' => $term, 'session' => $session, 'arrears' => $arrears,
                'statementError' => 'No class or fee record was found for you in ' . $term->term . ', ' . $session->session . '.',
            ]);
        }

        $classRow = DB::table('schoolclass')->leftJoin('schoolarm', 'schoolarm.id', '=', 'schoolclass.arm')
            ->where('schoolclass.id', $classId)->first(['schoolclass.id', 'schoolclass.schoolclass', 'schoolarm.arm']);
        $class = (object) [
            'id'          => $classId,
            'schoolclass' => trim(($classRow->schoolclass ?? '') . ' ' . ($classRow->arm ?? '')) ?: '—',
        ];

        // ── Bills: same filters as the bursar's screen ──────────────────────
        $rawBills = DB::table('school_bill_class_term_session as bcts')
            ->join('school_bill as sb', 'sb.id', '=', 'bcts.bill_id')
            ->where('bcts.class_id', $classId)
            ->where('bcts.termid_id', $termId)
            ->where('bcts.session_id', $sessionId)
            ->tap(fn ($q) => $this->notDeleted($q, 'school_bill_class_term_session', 'bcts'))
            ->where(function ($q) use ($student) {
                $q->whereNull('sb.statusId')
                  ->orWhere('sb.statusId', '')
                  ->orWhere('sb.statusId', 0)
                  ->orWhere('sb.statusId', $student->statusId);
            })
            ->orderBy('bcts.display_order')
            ->orderBy('sb.title')
            ->get([
                'sb.id', 'sb.title', 'sb.description', 'sb.bill_amount', 'sb.due_date', 'sb.category',
            ]);

        $now = now();
        $scholarshipAssignment = ScholarshipAssignment::where('student_id', $studentId)
            ->where('status', 'active')
            ->where('effective_from', '<=', $now)
            ->where(fn ($q) => $q->whereNull('effective_to')->orWhere('effective_to', '>=', $now))
            ->with('scholarship')
            ->first();
        $discountAssignments = DiscountAssignment::where('student_id', $studentId)
            ->where('status', 'active')
            ->where('effective_from', '<=', $now)
            ->where(fn ($q) => $q->whereNull('effective_to')->orWhere('effective_to', '>=', $now))
            ->with('discount')
            ->get();

        // ── Amount paid per bill: from the ledger ───────────────────────────
        $paidByBill = DB::table('student_bill_payment_record as r')
            ->join('student_bill_payment as p', 'p.id', '=', 'r.student_bill_payment_id')
            ->where('p.student_id', $studentId)
            ->where('p.class_id', $classId)
            ->where('p.termid_id', $termId)
            ->where('p.session_id', $sessionId)
            ->tap(fn ($q) => $this->notDeleted($q, 'student_bill_payment', 'p'))
            ->tap(fn ($q) => $this->notDeleted($q, 'student_bill_payment_record', 'r'))
            ->groupBy('p.school_bill_id')
            ->select('p.school_bill_id', DB::raw('SUM(r.amount_paid) as total_paid'))
            ->pluck('total_paid', 'school_bill_id');

        // Legacy fallback only for bills with no ledger rows (same as admin).
        $bookPaid = DB::table('student_bill_payment_book')
            ->where('student_id', $studentId)
            ->where('class_id', $classId)
            ->where('term_id', $termId)
            ->where('session_id', $sessionId)
            ->pluck('amount_paid', 'school_bill_id');

        $bills  = collect();
        $totals = ['original' => 0, 'adjusted' => 0, 'paid' => 0, 'outstanding' => 0, 'savings' => 0];

        foreach ($rawBills as $bill) {
            $adj = $this->billAdjustment->buildBillAdjustment(
                $studentId, (int) $bill->id, (float) $bill->bill_amount, $scholarshipAssignment, $discountAssignments
            );

            $paid = isset($paidByBill[$bill->id])
                ? (float) $paidByBill[$bill->id]
                : (float) ($bookPaid[$bill->id] ?? 0);

            $payable  = (float) $adj['adjusted_amount'];
            $balance  = round(max(0, $payable - $paid), 2);
            $progress = $payable > 0 ? min(100, $paid / $payable * 100) : 100;

            $status = $payable <= 0 ? 'covered'
                : ($balance <= 0 ? 'paid' : ($paid > 0 ? 'partial' : 'unpaid'));

            $bills->push([
                'id'                    => (int) $bill->id,
                'title'                 => $bill->title,
                'description'           => $bill->description,
                'category'              => $bill->category,
                'due_date'              => $bill->due_date ? Carbon::parse($bill->due_date)->format('d M Y') : null,
                'is_overdue'            => $bill->due_date && $balance > 0 && Carbon::parse($bill->due_date)->endOfDay()->isPast(),
                'original_amount'       => (float) $adj['original_amount'],
                'adjusted_amount'       => $payable,
                'scholarship_deduction' => (float) $adj['scholarship_deduction'],
                'scholarship_label'     => $adj['scholarship_label'],
                'discount_deduction'    => (float) $adj['discount_deduction'],
                'discount_labels'       => $adj['discount_labels'],
                'total_savings'         => (float) $adj['total_savings'],
                'savings'               => (float) $adj['total_savings'],
                'amount_paid'           => round($paid, 2),
                'balance'               => $balance,
                'progress'              => round($progress, 1),
                'status'                => $status,
                'is_paid'               => in_array($status, ['paid', 'covered'], true),
                'is_partial'            => $status === 'partial',
            ]);

            $totals['original']    += (float) $adj['original_amount'];
            $totals['adjusted']    += $payable;
            $totals['paid']        += $paid;
            $totals['outstanding'] += $balance;
            $totals['savings']     += (float) $adj['total_savings'];
        }
        $totals = array_map(fn ($v) => round($v, 2), $totals);

        return [
            'class'                 => $class,
            'term'                  => $term,
            'session'               => $session,
            'bills'                 => $bills,
            'totals'                => $totals,
            'paymentHistory'        => $this->paymentHistory($studentId, $termId, $sessionId),
            'scholarshipAssignment' => $scholarshipAssignment,
            'discountAssignments'   => $discountAssignments,
            'arrears'               => $arrears,
            'statementError'        => $rawBills->isEmpty() ? 'No fee bills have been set for your class this term yet.' : null,
        ];
    }
    public function resolveClassId(int $studentId, int $termId, int $sessionId): ?int
    {
        $id = DB::table('studentclass')->where('studentId', $studentId)->where('sessionid', $sessionId)
                ->orderByRaw('termid = ? DESC', [$termId])->orderByDesc('id')->value('schoolclassid')
            ?: DB::table('student_bill_payment_book')->where('student_id', $studentId)
                ->where('term_id', $termId)->where('session_id', $sessionId)->value('class_id')
            ?: DB::table('student_bill_payment')->where('student_id', $studentId)
                ->where('termid_id', $termId)->where('session_id', $sessionId)->tap(fn ($q) => $this->notDeleted($q, 'student_bill_payment'))->value('class_id')
            ?: DB::table('broadsheet_records')->where('student_id', $studentId)
                ->where('session_id', $sessionId)->orderByDesc('id')->value('schoolclass_id');

        return $id ? (int) $id : null;
    }
    /** One row per ledger transaction for the term (newest first). */
    public function paymentHistory(int $studentId, int $termId, int $sessionId): Collection
    {
        return DB::table('student_bill_payment_record as r')
            ->join('student_bill_payment as p', 'p.id', '=', 'r.student_bill_payment_id')
            ->leftJoin('school_bill as sb', 'sb.id', '=', 'p.school_bill_id')
            ->leftJoin('users as u', 'u.id', '=', 'r.generated_by')
            ->where('p.student_id', $studentId)
            ->where('p.termid_id', $termId)
            ->where('p.session_id', $sessionId)
            ->tap(fn ($q) => $this->notDeleted($q, 'student_bill_payment', 'p'))
            ->tap(fn ($q) => $this->notDeleted($q, 'student_bill_payment_record', 'r'))
            ->orderByDesc('r.created_at')
            ->orderByDesc('r.id')
            ->get($this->historyColumns())
            ->map(function ($r) {
                $r->paid_at = $r->paid_at ? Carbon::parse($r->paid_at) : null;
                foreach (['is_reversal' => 0, 'invoice_no' => null, 'reference' => null, 'payment_channel' => null, 'payment_method' => null] as $k => $v) {
                    if (!property_exists($r, $k)) $r->$k = $v;
                }
                $r->method  = ($r->payment_channel ?? null) ?: ($r->payment_method ?? null) ?: '—';
                $r->status  = !empty($r->is_reversal) ? 'reversal' : ((int) ($r->complete_payment ?? 0) === 1 ? 'completed' : 'part');
                return $r;
            });
    }
    /** Select list for paymentHistory(); optional columns only if they exist. */
    protected function historyColumns(): array
    {
        $cols = [
            'r.id',
            'r.created_at as paid_at',
            'r.amount_paid',
            'r.amount_owed as balance_after',
            'r.complete_payment',
            'sb.title as bill_title',
            'u.name as received_by',
        ];
        $optional = [
            ['student_bill_payment_record', 'is_reversal',           'r.is_reversal'],
            ['student_bill_payment_record', 'invoiceNo',             'r.invoiceNo as invoice_no'],
            ['student_bill_payment_record', 'transaction_reference', 'r.transaction_reference as reference'],
            ['student_bill_payment_record', 'payment_channel',       'r.payment_channel'],
            ['student_bill_payment',        'payment_method',        'p.payment_method'],
        ];
        foreach ($optional as [$table, $column, $select]) {
            if ($this->hasColumn($table, $column)) {
                $cols[] = $select;
            }
        }
        return $cols;
    }

    /** Amount paid per term for the session, from the ledger. */
    public function buildPaymentTrend(int $studentId, ?int $sessionId): array
    {
        if (!$sessionId) return [];

        $sums = DB::table('student_bill_payment_record as r')
            ->join('student_bill_payment as p', 'p.id', '=', 'r.student_bill_payment_id')
            ->where('p.student_id', $studentId)
            ->where('p.session_id', $sessionId)
            ->tap(fn ($q) => $this->notDeleted($q, 'student_bill_payment', 'p'))
            ->tap(fn ($q) => $this->notDeleted($q, 'student_bill_payment_record', 'r'))
            ->groupBy('p.termid_id')
            ->select('p.termid_id', DB::raw('SUM(r.amount_paid) as total_paid'))
            ->pluck('total_paid', 'termid_id');

        $trend = [];
        foreach (Schoolterm::orderBy('id')->get(['id', 'term']) as $t) {
            if (isset($sums[$t->id]) && (float) $sums[$t->id] != 0.0) {
                $trend[$t->term] = round((float) $sums[$t->id], 2);
            }
        }
        return $trend;
    }
}
