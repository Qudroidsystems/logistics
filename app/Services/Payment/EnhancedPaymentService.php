<?php
// app/Services/Payment/EnhancedPaymentService.php

namespace App\Services\Payment;

use App\Models\StudentBillPayment;
use App\Models\StudentBillPaymentRecord;
use App\Models\StudentBillPaymentBook;
use App\Models\PaymentBatch;
use App\Models\PaymentBatchItem;
use App\Models\SchoolBillTermSession;
use App\Models\SchoolBillModel;
use App\Models\Student;
use App\Services\Scholarship\ScholarshipService;
use App\Services\Discount\DiscountService;
use Illuminate\Support\Facades\DB;

class EnhancedPaymentService
{
    protected $scholarshipService;
    protected $discountService;

    public function __construct(
        ScholarshipService $scholarshipService,
        DiscountService $discountService
    ) {
        $this->scholarshipService = $scholarshipService;
        $this->discountService = $discountService;
    }

    /**
     * Get student payment details
     */
    public function getStudentPaymentDetails($studentId, $classId, $termId, $sessionId)
    {
        $student = Student::findOrFail($studentId);

        $assignedBills = SchoolBillTermSession::where('class_id', $classId)
            ->where('termid_id', $termId)
            ->where('session_id', $sessionId)
            ->with('schoolBill')
            ->get();

        $bills = [];
        foreach ($assignedBills as $assignment) {
            $bill = $assignment->schoolBill;
            $payment = StudentBillPayment::where('student_id', $studentId)
                ->where('school_bill_id', $bill->id)
                ->first();

            $bills[] = [
                'bill_id' => $bill->id,
                'title' => $bill->title,
                'description' => $bill->description,
                'original_amount' => $bill->bill_amount,
                'adjusted_amount' => $bill->bill_amount,
                'paid_amount' => $payment ? $payment->total_paid : 0,
                'outstanding' => $bill->bill_amount - ($payment ? $payment->total_paid : 0),
                'savings' => 0,
            ];
        }

        return [
            'student' => $student,
            'bills' => $bills,
            'summary' => [
                'total_original' => 0,
                'total_savings' => 0,
                'total_paid' => 0,
                'total_outstanding' => 0,
            ],
            'has_outstanding' => true
        ];
    }

    /**
     * Process payment
     */
    public function processPayment($studentId, $classId, $termId, $sessionId, $paymentData)
    {
        return DB::transaction(function () use ($studentId, $classId, $termId, $sessionId, $paymentData) {
            $batch = PaymentBatch::create([
                'batch_no' => PaymentBatch::generateBatchNumber(),
                'student_id' => $studentId,
                'payment_date' => now(),
                'total_amount' => array_sum(array_column($paymentData['items'], 'amount')),
                'payment_method' => $paymentData['payment_method'],
                'reference_no' => $paymentData['reference_no'] ?? null,
                'status' => 'completed',
                'notes' => $paymentData['notes'] ?? null,
                'created_by' => auth()->id(),
            ]);

            foreach ($paymentData['items'] as $item) {
                $payment = StudentBillPayment::firstOrCreate(
                    [
                        'student_id' => $studentId,
                        'school_bill_id' => $item['bill_id'],
                        'class_id' => $classId,
                        'termid_id' => $termId,
                        'session_id' => $sessionId,
                    ],
                    [
                        'payment_method' => $paymentData['payment_method'],
                        'payment_status' => 'pending',
                        'total_paid' => 0,
                        'total_balance' => $item['amount'],
                        'generated_by' => auth()->id(),
                        'delete_status' => '1',
                    ]
                );

                $payment->update([
                    'total_paid' => $payment->total_paid + $item['amount'],
                    'total_balance' => max(0, $payment->total_balance - $item['amount']),
                    'last_payment_date' => now(),
                ]);

                PaymentBatchItem::create([
                    'payment_batch_id' => $batch->id,
                    'school_bill_id' => $item['bill_id'],
                    'class_id' => $classId,
                    'termid_id' => $termId,
                    'session_id' => $sessionId,
                    'amount_paid' => $item['amount'],
                ]);
            }

            return [
                'success' => true,
                'batch' => $batch,
                'total_paid' => $batch->total_amount,
                'total_savings' => 0,
                'receipt' => ['receipt_no' => 'RCP-' . $batch->batch_no],
            ];
        });
    }

    /**
     * Reverse payment
     */
    public function reversePayment($batchId, $reason)
    {
        $batch = PaymentBatch::findOrFail($batchId);
        $batch->update(['status' => 'reversed', 'reversal_reason' => $reason]);

        return $batch;
    }
}
