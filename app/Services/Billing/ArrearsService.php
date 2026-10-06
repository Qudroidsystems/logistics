<?php

namespace App\Services\Billing;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ArrearsService
{
    /**
     * Outstanding balances for a student from previous terms/sessions.
     *
     * When $excludeTermId and $excludeSessionId are both provided and > 0,
     * rows belonging to that exact term+session pair are excluded (i.e. "past"
     * relative to the payment page the user is currently on).
     *
     * When both are 0 / null, every book row with amount_owed > 0 is returned
     * (full arrears view).
     */
    public function getStudentArrears(
        int $studentId,
        ?int $excludeTermId = null,
        ?int $excludeSessionId = null
    ): array {
        $query = DB::table('student_bill_payment_book as sbpb')
            ->leftJoin('school_bill as sb', 'sb.id', '=', 'sbpb.school_bill_id')
            ->leftJoin('schoolclass as sc', 'sc.id', '=', 'sbpb.class_id')
            ->leftJoin('schoolarm as sa', 'sa.id', '=', 'sc.arm')
            ->leftJoin('schoolterm as st', 'st.id', '=', 'sbpb.term_id')
            ->leftJoin('schoolsession as ss', 'ss.id', '=', 'sbpb.session_id')
            ->where('sbpb.student_id', $studentId)
            ->where('sbpb.amount_owed', '>', 0);

        $excludeTermId    = (int) ($excludeTermId ?? 0);
        $excludeSessionId = (int) ($excludeSessionId ?? 0);

        if ($excludeTermId > 0 && $excludeSessionId > 0) {
            // Past = anything that is NOT the currently selected term+session
            $query->where(function ($q) use ($excludeTermId, $excludeSessionId) {
                $q->where('sbpb.term_id', '!=', $excludeTermId)
                  ->orWhere('sbpb.session_id', '!=', $excludeSessionId);
            });
        }

        $rows = $query
            ->select([
                'sbpb.id as book_id',
                'sbpb.school_bill_id',
                'sbpb.class_id',
                'sbpb.term_id',
                'sbpb.session_id',
                'sbpb.original_amount',
                'sbpb.adjusted_amount',
                'sbpb.amount_paid',
                'sbpb.amount_owed',
                'sbpb.scholarship_deduction',
                'sbpb.discount_deduction',
                'sb.title as bill_title',
                'sb.description as bill_description',
                DB::raw("TRIM(CONCAT(COALESCE(sc.schoolclass,''), ' ', COALESCE(sa.arm,''))) as class_name"),
                'st.term as term_name',
                'ss.session as session_name',
            ])
            ->orderBy('ss.session', 'desc')
            ->orderBy('st.id', 'desc')
            ->get();

        return $this->formatArrearsResult($rows);
    }

    /**
     * Quick check: does this student have any outstanding past balance
     * relative to the given term/session?
     */
    public function hasArrears(
        int $studentId,
        ?int $excludeTermId = null,
        ?int $excludeSessionId = null
    ): bool {
        $result = $this->getStudentArrears($studentId, $excludeTermId, $excludeSessionId);
        return $result['has_arrears'];
    }

    /**
     * Total outstanding amount only (lightweight).
     */
    public function getTotalArrears(
        int $studentId,
        ?int $excludeTermId = null,
        ?int $excludeSessionId = null
    ): float {
        $result = $this->getStudentArrears($studentId, $excludeTermId, $excludeSessionId);
        return $result['total_arrears'];
    }

    /**
     * Group raw book rows into term/session/class groups + totals.
     */
    protected function formatArrearsResult(Collection $rows): array
    {
        if ($rows->isEmpty()) {
            return [
                'has_arrears'   => false,
                'total_arrears' => 0.0,
                'groups'        => [],
                'bills'         => [],
            ];
        }

        $groups = $rows
            ->groupBy(fn ($r) => $r->term_id . '_' . $r->session_id . '_' . $r->class_id)
            ->map(function ($bills) {
                $first = $bills->first();
                return [
                    'class_id'     => (int) $first->class_id,
                    'class_name'   => $first->class_name ?: '—',
                    'term_id'      => (int) $first->term_id,
                    'term_name'    => $first->term_name ?: '—',
                    'session_id'   => (int) $first->session_id,
                    'session_name' => $first->session_name ?: '—',
                    'bill_count'   => $bills->count(),
                    'amount_paid'  => round((float) $bills->sum('amount_paid'), 2),
                    'outstanding'  => round((float) $bills->sum('amount_owed'), 2),
                    'bills'        => $bills->map(fn ($b) => [
                        'school_bill_id'        => (int) $b->school_bill_id,
                        'title'                 => $b->bill_title,
                        'description'           => $b->bill_description,
                        'original_amount'       => round((float) $b->original_amount, 2),
                        'adjusted_amount'       => round((float) $b->adjusted_amount, 2),
                        'amount_paid'           => round((float) $b->amount_paid, 2),
                        'outstanding'           => round((float) $b->amount_owed, 2),
                        'scholarship_deduction' => round((float) $b->scholarship_deduction, 2),
                        'discount_deduction'    => round((float) $b->discount_deduction, 2),
                    ])->values()->all(),
                ];
            })
            ->sortByDesc('outstanding')
            ->values()
            ->all();

        $flatBills = $rows->map(fn ($b) => [
            'school_bill_id' => (int) $b->school_bill_id,
            'title'          => $b->bill_title,
            'class_name'     => $b->class_name ?: '—',
            'term_name'      => $b->term_name ?: '—',
            'session_name'   => $b->session_name ?: '—',
            'outstanding'    => round((float) $b->amount_owed, 2),
            'class_id'       => (int) $b->class_id,
            'term_id'        => (int) $b->term_id,
            'session_id'     => (int) $b->session_id,
        ])->values()->all();

        return [
            'has_arrears'   => true,
            'total_arrears' => round((float) $rows->sum('amount_owed'), 2),
            'groups'        => $groups,
            'bills'         => $flatBills,
        ];
    }
}