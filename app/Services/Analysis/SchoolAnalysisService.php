<?php
// app/Services/Analysis/SchoolAnalysisService.php

namespace App\Services\Analysis;

use Illuminate\Support\Facades\DB;

class SchoolAnalysisService
{
    /**
     * Get class analysis - FIXED: No StudentClass relationship
     */
    public function getClassAnalysis($classId, $termId, $sessionId)
    {
        // Get students using direct DB query instead of relationship
        $students = DB::table('studentRegistration as s')
            ->join('studentclass as sc', 'sc.studentId', '=', 's.id')
            ->leftJoin('studentpicture as sp', 'sp.studentid', '=', 's.id')
            ->where('sc.schoolclassid', $classId)
            ->where('sc.termid', $termId)
            ->where('sc.sessionid', $sessionId)
            ->select(
                's.id as student_id',
                's.firstname',
                's.lastname',
                's.othername',
                's.admissionNo',
                'sp.picture as avatar'
            )
            ->get();

        $analysis = [];
        $totalBilledSum = 0;
        $totalPaidSum = 0;
        $totalOutstandingSum = 0;

        foreach ($students as $student) {
            // Get payment book
            $paymentBook = DB::table('student_bill_payment_book')
                ->where('student_id', $student->student_id)
                ->where('class_id', $classId)
                ->where('term_id', $termId)
                ->where('session_id', $sessionId)
                ->first();

            // Get total bills
            $totalBilled = DB::table('school_bill_class_term_session as sbcts')
                ->where('sbcts.class_id', $classId)
                ->where('sbcts.termid_id', $termId)
                ->where('sbcts.session_id', $sessionId)
                ->join('school_bill as sb', 'sb.id', '=', 'sbcts.bill_id')
                ->sum('sb.bill_amount');

            $totalSavings = $paymentBook ? (($paymentBook->scholarship_deduction ?? 0) + ($paymentBook->discount_deduction ?? 0)) : 0;
            $adjustedBilled = max(0, $totalBilled - $totalSavings);
            $totalPaid = $paymentBook ? $paymentBook->amount_paid : 0;
            $outstanding = max(0, $adjustedBilled - $totalPaid);

            $studentName = trim($student->firstname . ' ' . $student->lastname);
            if (!empty($student->othername)) {
                $studentName .= ' (' . $student->othername . ')';
            }

            $analysis[] = [
                'student_id' => $student->student_id,
                'name' => $studentName,
                'admission_no' => $student->admissionNo ?? 'N/A',
                'total_billed' => $adjustedBilled,
                'total_paid' => $totalPaid,
                'total_outstanding' => $outstanding,
                'savings' => $totalSavings,
            ];

            $totalBilledSum += $adjustedBilled;
            $totalPaidSum += $totalPaid;
            $totalOutstandingSum += $outstanding;
        }

        // Sort by outstanding amount
        usort($analysis, function($a, $b) {
            return $b['total_outstanding'] - $a['total_outstanding'];
        });

        // Get class name
        $class = DB::table('schoolclass')
            ->leftJoin('schoolarm', 'schoolclass.arm', '=', 'schoolarm.id')
            ->where('schoolclass.id', $classId)
            ->select(DB::raw("CONCAT(schoolclass.schoolclass, ' ', COALESCE(schoolarm.arm, '')) as class_name"))
            ->first();

        $term = DB::table('schoolterm')->where('id', $termId)->value('term');
        $session = DB::table('schoolsession')->where('id', $sessionId)->value('session');
        $collectionRate = $totalBilledSum > 0 ? round(($totalPaidSum / $totalBilledSum) * 100, 1) : 0;

        return [
            'students' => $analysis,
            'class_name' => $class->class_name ?? 'N/A',
            'term' => $term,
            'session' => $session,
            'totals' => [
                'total_students' => $students->count(),
                'total_billed_amount' => $totalBilledSum,
                'total_paid_amount' => $totalPaidSum,
                'total_outstanding' => $totalOutstandingSum,
                'collection_rate' => $collectionRate,
            ]
        ];
    }

    /**
     * Get school financial summary - FIXED
     */
    public function getSchoolFinancialSummary($sessionId = null, $termId = null)
    {
        $query = DB::table('student_bill_payment_book');

        if ($sessionId) {
            $query->where('session_id', $sessionId);
        }
        if ($termId) {
            $query->where('term_id', $termId);
        }

        $totalBilled = $query->sum('adjusted_amount');
        $totalPaid = $query->sum('amount_paid');
        $totalOutstanding = $totalBilled - $totalPaid;
        $totalSavings = $query->sum(DB::raw('scholarship_deduction + discount_deduction'));
        $totalStudents = $query->distinct('student_id')->count('student_id');
        $collectionRate = $totalBilled > 0 ? round(($totalPaid / $totalBilled) * 100, 1) : 0;

        // Monthly trend for the last 12 months
        $monthlyTrend = [];
        for ($i = 11; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $startDate = $date->copy()->startOfMonth()->format('Y-m-d');
            $endDate = $date->copy()->endOfMonth()->format('Y-m-d');

            $monthlyCollected = DB::table('student_bill_payment_book')
                ->when($sessionId, fn($q) => $q->where('session_id', $sessionId))
                ->when($termId, fn($q) => $q->where('term_id', $termId))
                ->whereBetween('updated_at', [$startDate, $endDate])
                ->sum('amount_paid');

            $monthlyTrend['labels'][] = $date->format('M Y');
            $monthlyTrend['values'][] = $monthlyCollected;
        }

        // Payment methods distribution
        $paymentMethods = DB::table('student_bill_payment')
            ->select('payment_method', DB::raw('COUNT(*) as count'))
            ->when($sessionId, fn($q) => $q->where('session_id', $sessionId))
            ->when($termId, fn($q) => $q->where('term_id', $termId))
            ->whereNotNull('payment_method')
            ->groupBy('payment_method')
            ->get();

        // Class performance
        $classPerformance = DB::table('student_bill_payment_book as sbpb')
            ->join('schoolclass as sc', 'sc.id', '=', 'sbpb.class_id')
            ->leftJoin('schoolarm as sa', 'sa.id', '=', 'sc.arm')
            ->select(
                DB::raw("CONCAT(sc.schoolclass, ' ', COALESCE(sa.arm, '')) as class_name"),
                DB::raw('SUM(sbpb.amount_paid) as total_collected'),
                DB::raw('SUM(sbpb.adjusted_amount) as total_billed'),
                DB::raw('COUNT(DISTINCT sbpb.student_id) as student_count')
            )
            ->when($sessionId, fn($q) => $q->where('sbpb.session_id', $sessionId))
            ->when($termId, fn($q) => $q->where('sbpb.term_id', $termId))
            ->groupBy('sbpb.class_id', 'sc.schoolclass', 'sa.arm')
            ->get()
            ->map(function($item) {
                $item->collection_rate = $item->total_billed > 0
                    ? round(($item->total_collected / $item->total_billed) * 100, 1)
                    : 0;
                return $item;
            });

        return [
            'total_revenue' => $totalBilled,
            'total_collected' => $totalPaid,
            'total_outstanding' => $totalOutstanding,
            'total_savings' => $totalSavings,
            'total_students' => $totalStudents,
            'collection_rate' => $collectionRate,
            'monthly_trend' => $monthlyTrend,
            'payment_methods' => [
                'labels' => $paymentMethods->pluck('payment_method'),
                'values' => $paymentMethods->pluck('count'),
            ],
            'class_performance' => $classPerformance,
        ];
    }

    /**
     * Get scholarship impact analysis - FIXED
     */
    public function getScholarshipImpactAnalysis($termId = null, $sessionId = null)
    {
        // Get active scholarships
        $scholarships = DB::table('scholarship_assignments as sa')
            ->join('scholarships as s', 's.id', '=', 'sa.scholarship_id')
            ->where('sa.status', 'active')
            ->where('sa.effective_from', '<=', now())
            ->where(function($q) {
                $q->whereNull('sa.effective_to')->orWhere('sa.effective_to', '>=', now());
            })
            ->select('s.title as scholarship_name', 'sa.value', 'sa.value_type', 'sa.student_id')
            ->get();

        // Get active discounts
        $discounts = DB::table('discount_assignments as da')
            ->join('discounts as d', 'd.id', '=', 'da.discount_id')
            ->where('da.status', 'active')
            ->where('da.effective_from', '<=', now())
            ->where(function($q) {
                $q->whereNull('da.effective_to')->orWhere('da.effective_to', '>=', now());
            })
            ->select('d.title as discount_name', 'da.value', 'da.value_type', 'da.student_id')
            ->get();

        // Calculate actual savings from payments
        $savingsQuery = DB::table('student_bill_payment_book');
        if ($termId) $savingsQuery->where('term_id', $termId);
        if ($sessionId) $savingsQuery->where('session_id', $sessionId);

        $actualSavings = $savingsQuery->select(
            DB::raw('SUM(scholarship_deduction) as scholarship_savings'),
            DB::raw('SUM(discount_deduction) as discount_savings')
        )->first();

        return [
            'scholarship_count' => $scholarships->count(),
            'discount_count' => $discounts->count(),
            'total_beneficiaries' => $scholarships->pluck('student_id')->merge($discounts->pluck('student_id'))->unique()->count(),
            'scholarship_by_type' => $scholarships->groupBy('scholarship_name')->map(fn($items) => $items->count()),
            'discount_by_type' => $discounts->groupBy('discount_name')->map(fn($items) => $items->count()),
            'actual_scholarship_savings' => $actualSavings->scholarship_savings ?? 0,
            'actual_discount_savings' => $actualSavings->discount_savings ?? 0,
            'total_savings' => ($actualSavings->scholarship_savings ?? 0) + ($actualSavings->discount_savings ?? 0),
        ];
    }

    /**
     * Get all classes for dropdown
     */
    public function getAllClasses()
    {
        return DB::table('schoolclass')
            ->leftJoin('schoolarm', 'schoolclass.arm', '=', 'schoolarm.id')
            ->select(
                'schoolclass.id',
                DB::raw("CONCAT(schoolclass.schoolclass, ' ', COALESCE(schoolarm.arm, '')) as display_name")
            )
            ->orderBy('schoolclass.schoolclass')
            ->get();
    }

    /**
     * Get all terms for dropdown
     */
    public function getAllTerms()
    {
        return DB::table('schoolterm')->orderBy('id')->get();
    }

    /**
     * Get all sessions for dropdown
     */
    public function getAllSessions()
    {
        return DB::table('schoolsession')->orderBy('session', 'desc')->get();
    }
}
