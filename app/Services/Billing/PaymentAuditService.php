<?php
// app/Services/Billing/PaymentAuditService.php

namespace App\Services\Billing;

use App\Models\PaymentAuditLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class PaymentAuditService
{
    /**
     * Record a business-level audit event.
     *
     * @param string     $action     recorded | bulk_recorded | updated | deleted | invoice_confirmed | reversed
     * @param array      $context    student_id, school_bill_id, student_bill_payment_id,
     *                               student_bill_payment_record_id, class_id, term_id, session_id,
     *                               amount, payment_method, entity_type
     * @param array|null $oldValues  snapshot before the change (null for creates)
     * @param array|null $newValues  snapshot after the change
     * @param string|null $notes     human-readable description
     */
    public function log(
        string $action,
        array $context = [],
        ?array $oldValues = null,
        ?array $newValues = null,
        ?string $notes = null
    ): PaymentAuditLog {
        return PaymentAuditLog::create([
            'student_id'                     => $context['student_id'] ?? null,
            'school_bill_id'                 => $context['school_bill_id'] ?? null,
            'student_bill_payment_id'        => $context['student_bill_payment_id'] ?? null,
            'student_bill_payment_record_id' => $context['student_bill_payment_record_id'] ?? null,
            'class_id'                       => $context['class_id'] ?? null,
            'term_id'                        => $context['term_id'] ?? null,
            'session_id'                     => $context['session_id'] ?? null,
            'action'                         => $action,
            'entity_type'                    => $context['entity_type'] ?? null,
            'old_values'                     => $oldValues,
            'new_values'                     => $newValues,
            'amount'                         => $context['amount'] ?? null,
            'payment_method'                 => $context['payment_method'] ?? null,
            'performed_by'                   => Auth::id(),
            'ip_address'                     => Request::ip(),
            'notes'                          => $notes,
        ]);
    }
}