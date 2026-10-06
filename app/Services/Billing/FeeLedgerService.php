<?php

namespace App\Services\Billing;

use App\Models\SchoolBillModel;
use App\Models\StudentBillPayment;
use App\Models\StudentBillPaymentBook;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Writes a payment into the school-fee ledger exactly the way the bursar's
 * screen does (SchoolPaymentController@store):
 *
 *   student_bill_payment         one "shell" per bill/class/term/session cycle
 *   student_bill_payment_record  one row per payment (the ledger)
 *   student_bill_payment_book    summary row, recomputed from the ledger SUM
 *
 * The payable amount is always recomputed with BillAdjustmentService and the
 * amount already paid always comes from the ledger, so a stale screen or a
 * tampered request can never over-credit a bill.
 *
 * Callers must wrap calls in a DB transaction.
 */
class FeeLedgerService
{
    protected static array $columns = [];

    public function __construct(protected BillAdjustmentService $billAdjustment) {}

    /** Payable, paid and outstanding for one bill in one class/term/session. */
    public function position(int $studentId, int $billId, int $classId, int $termId, int $sessionId): array
    {
        $bill = SchoolBillModel::find($billId);
        $original = (float) ($bill->bill_amount ?? 0);
        $adj  = $this->billAdjustment->buildBillAdjustment($studentId, $billId, $original);
        $paid = $this->ledgerPaid($studentId, $billId, $classId, $termId, $sessionId);

        return [
            'bill'        => $bill,
            'original'    => round($original, 2),
            'adjustment'  => $adj,
            'payable'     => (float) $adj['adjusted_amount'],
            'paid'        => $paid,
            'balance'     => round(max(0, (float) $adj['adjusted_amount'] - $paid), 2),
        ];
    }

    public function ledgerPaid(int $studentId, int $billId, int $classId, int $termId, int $sessionId): float
    {
        $q = DB::table('student_bill_payment_record as r')
            ->join('student_bill_payment as p', 'p.id', '=', 'r.student_bill_payment_id')
            ->where('p.student_id', $studentId)
            ->where('p.school_bill_id', $billId)
            ->where('p.class_id', $classId)
            ->where('p.termid_id', $termId)
            ->where('p.session_id', $sessionId);
        if ($this->has('student_bill_payment', 'deleted_at')) $q->whereNull('p.deleted_at');
        if ($this->has('student_bill_payment_record', 'deleted_at')) $q->whereNull('r.deleted_at');

        $ledger = (float) $q->sum('r.amount_paid');
        if ($ledger > 0) {
            return round($ledger, 2);
        }

        // Legacy data with no ledger rows: fall back to the book (same as the bursar's screen).
        return round((float) DB::table('student_bill_payment_book')
            ->where('student_id', $studentId)->where('school_bill_id', $billId)
            ->where('class_id', $classId)->where('term_id', $termId)->where('session_id', $sessionId)
            ->value('amount_paid'), 2);
    }

    /**
     * Post a payment of up to $amount against one bill. Never posts more than
     * the bill's outstanding balance at this moment.
     *
     * @return array{applied: float, excess: float, record_id: ?int, balance: float}
     */
    public function post(
        int $studentId, int $billId, int $classId, int $termId, int $sessionId,
        float $amount, string $method, ?int $userId, ?string $reference = null
    ): array {
        $pos = $this->position($studentId, $billId, $classId, $termId, $sessionId);
        $apply = round(min($amount, $pos['balance']), 2);

        if ($apply <= 0) {
            return ['applied' => 0.0, 'excess' => round($amount, 2), 'record_id' => null, 'balance' => $pos['balance']];
        }

        $balanceAfter = round($pos['balance'] - $apply, 2);
        $complete     = $balanceAfter <= 0 ? 1 : 0;
        $status       = $complete ? 'Completed' : 'Pending';

        // Same cycle rule as the bursar: add to the shell that is still waiting
        // for its invoice (delete_status '1'), otherwise start a new shell.
        $shell = StudentBillPayment::where([
            'student_id' => $studentId, 'school_bill_id' => $billId, 'class_id' => $classId,
            'termid_id'  => $termId,    'session_id'     => $sessionId,
        ])->where('delete_status', '1')->orderByDesc('id')->first();

        if ($shell) {
            DB::table('student_bill_payment')->where('id', $shell->id)->update([
                'payment_method' => $method, 'status' => $status,
                'generated_by'   => $userId, 'updated_at' => now(),
            ]);
        } else {
            $shell = StudentBillPayment::create([
                'student_id' => $studentId, 'school_bill_id' => $billId, 'class_id' => $classId,
                'termid_id'  => $termId,    'session_id'     => $sessionId,
                'payment_method' => $method, 'status' => $status,
                'generated_by'   => $userId, 'delete_status' => '1',
            ]);
        }

        $row = [
            'student_bill_payment_id' => $shell->id,
            'class_id'         => $classId,
            'termid_id'        => $termId,
            'session_id'       => $sessionId,
            'amount_paid'      => $apply,
            'amount_owed'      => $balanceAfter,
            'total_bill'       => $pos['payable'],
            'complete_payment' => $complete,
            'generated_by'     => $userId,
            'created_at'       => now(),
            'updated_at'       => now(),
        ];
        if ($this->has('student_bill_payment_record', 'last_payment'))          $row['last_payment'] = $apply;
        if ($reference && $this->has('student_bill_payment_record', 'transaction_reference')) $row['transaction_reference'] = $reference;

        $recordId = DB::table('student_bill_payment_record')->insertGetId($row);

        $this->syncBook($studentId, $billId, $classId, $termId, $sessionId, $pos, $userId);

        return [
            'applied'   => $apply,
            'excess'    => round($amount - $apply, 2),
            'record_id' => (int) $recordId,
            'balance'   => $balanceAfter,
            'shell_id'  => (int) $shell->id,
        ];
    }

    /** Recompute the book row from the ledger (never by adding a delta). */
    public function syncBook(int $studentId, int $billId, int $classId, int $termId, int $sessionId, ?array $pos = null, ?int $userId = null): void
    {
        $pos  = $pos ?? $this->position($studentId, $billId, $classId, $termId, $sessionId);
        $paid = $this->ledgerPaid($studentId, $billId, $classId, $termId, $sessionId);
        $owed = round(max(0, $pos['payable'] - $paid), 2);

        $payload = [
            'amount_paid'           => $paid,
            'amount_owed'           => $owed,
            'payment_status'        => ($owed <= 0 && $paid > 0) ? 'Completed' : 'Pending',
            'scholarship_deduction' => $pos['adjustment']['scholarship_deduction'],
            'discount_deduction'    => $pos['adjustment']['discount_deduction'],
            'adjusted_amount'       => $pos['payable'],
            'updated_at'            => now(),
        ];

        $book = StudentBillPaymentBook::where([
            'student_id' => $studentId, 'school_bill_id' => $billId, 'class_id' => $classId,
            'term_id'    => $termId,    'session_id'     => $sessionId,
        ])->first();

        if ($book) {
            DB::table('student_bill_payment_book')->where('id', $book->id)->update($payload);
        } else {
            StudentBillPaymentBook::create($payload + [
                'student_id' => $studentId, 'school_bill_id' => $billId, 'class_id' => $classId,
                'term_id'    => $termId,    'session_id'     => $sessionId,
                'original_amount' => $pos['original'], 'generated_by' => $userId,
            ]);
        }
    }

    protected function has(string $table, string $column): bool
    {
        if (!isset(self::$columns[$table])) {
            try {
                self::$columns[$table] = array_map('strtolower', Schema::getColumnListing($table));
            } catch (\Throwable $e) {
                self::$columns[$table] = [];
            }
        }
        return in_array(strtolower($column), self::$columns[$table], true);
    }
}
