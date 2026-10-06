<?php

namespace App\Services\Payment;

use App\Models\OnlineFeePayment;
use App\Models\OnlineFeePaymentItem;
use App\Models\Student;
use App\Models\User;
use App\Services\Billing\ArrearsService;
use App\Services\Billing\FeeLedgerService;
use App\Services\Billing\PaymentAuditService;
use App\Services\Billing\StudentFeeStatementService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Online school-fee checkout.
 *
 *  quote()    what the student owes: the chosen term's bills + older arrears,
 *             each recomputed live (scholarship/discount + ledger payments).
 *  start()    validates the payer's selection against a fresh quote, saves
 *             the checkout and opens a Paystack transaction.
 *  finalize() confirms with Paystack (amount, currency, reference) and posts
 *             every item to the fee ledger exactly once.
 *
 * Money is handled in kobo (integers) end to end.
 *
 * Rules:
 *  - Any amount from ₦1 up to a bill's outstanding balance; checkout total ≥ ₦100.
 *  - While older arrears are unpaid, a current-term bill can only be paid if
 *    every arrear is paid IN FULL in the same checkout (arrears are posted first).
 *  - The school absorbs the Paystack fee: the payer is charged exactly the
 *    selected total and the bills are credited with that total.
 */
class OnlineFeeCheckoutService
{
    public const METHOD_LABEL = 'Online (Paystack)';
    public const MIN_TOTAL_KOBO = 10000; // ₦100

    public function __construct(
        protected StudentFeeStatementService $statements,
        protected ArrearsService $arrears,
        protected FeeLedgerService $ledger,
        protected PaystackGateway $paystack,
        protected PaymentAuditService $audit,
        protected OpayGateway $opay,
    ) {}

    /** Gateways that can take school fees, keyed by provider. */
    public function gateways(): array
    {
        return ['paystack' => $this->paystack, 'opay' => $this->opay];
    }

    public function gateway(?string $key): PaystackGateway|OpayGateway
    {
        return $this->gateways()[$key ?: 'paystack'] ?? $this->paystack;
    }

    /** [key => label] of gateways that are switched on and have keys. */
    public function readyGateways(): array
    {
        $out = [];
        foreach ($this->gateways() as $k => $g) if ($g->isReady()) $out[$k] = $k === 'opay' ? 'OPay' : 'Paystack';
        return $out;
    }

    public static function methodLabel(?string $gateway): string
    {
        return $gateway === 'opay' ? 'Online (OPay)' : self::METHOD_LABEL;
    }

    public static function kobo(float|int|string $naira): int
    {
        return (int) round(((float) $naira) * 100);
    }

    public static function naira(int $kobo): float
    {
        return round($kobo / 100, 2);
    }

    public function gatewayReady(): bool
    {
        return (bool) $this->readyGateways();
    }

    public function gatewayProblem(): ?string
    {
        return $this->readyGateways() ? null : ($this->paystack->problem() . ' ' . $this->opay->problem());
    }

    // ─────────────────────────────────────────────────────────────────────
    // QUOTE
    // ─────────────────────────────────────────────────────────────────────

    public function quote(Student $student, ?int $termId, ?int $sessionId): array
    {
        $studentId = (int) $student->id;
        $statement = $this->statements->buildStatement($student, $termId, $sessionId);
        $classId   = (int) ($statement['class']->id ?? 0);

        $current = [];
        if ($classId && $termId && $sessionId) {
            foreach ($statement['bills'] as $b) {
                $pos = $this->ledger->position($studentId, (int) $b['id'], $classId, $termId, $sessionId);
                $current[] = $this->line('c:' . $b['id'], $b, $pos, $classId, $termId, $sessionId, false, [
                    'term_name'    => $statement['term']->term ?? '',
                    'session_name' => $statement['session']->session ?? '',
                    'class_name'   => $statement['class']->schoolclass ?? '',
                ]);
            }
        }

        // Arrears: unpaid bills from EARLIER terms (ids are chronological).
        $arrears = [];
        if ($termId && $sessionId) {
            $raw = $this->arrears->getStudentArrears($studentId, $termId, $sessionId)['bills'] ?? [];
            foreach ($raw as $a) {
                $earlier = $a['session_id'] < $sessionId || ($a['session_id'] == $sessionId && $a['term_id'] < $termId);
                if (!$earlier) continue;

                $key = 'a:' . $a['school_bill_id'] . ':' . $a['class_id'] . ':' . $a['term_id'] . ':' . $a['session_id'];
                if (isset($arrears[$key])) continue;

                $pos = $this->ledger->position($studentId, $a['school_bill_id'], $a['class_id'], $a['term_id'], $a['session_id']);
                if ($pos['balance'] <= 0) continue;

                $arrears[$key] = $this->line($key, ['id' => $a['school_bill_id'], 'title' => $a['title']], $pos,
                    $a['class_id'], $a['term_id'], $a['session_id'], true, [
                        'term_name' => $a['term_name'], 'session_name' => $a['session_name'], 'class_name' => $a['class_name'],
                    ]);
            }
        }
        $arrears = array_values($arrears);
        usort($arrears, fn ($x, $y) => [$x['session_id'], $x['term_id']] <=> [$y['session_id'], $y['term_id']]);

        $openCurrent = array_values(array_filter($current, fn ($l) => $l['balance_kobo'] > 0));

        return [
            'class'          => $statement['class'],
            'term'           => $statement['term'],
            'session'        => $statement['session'],
            'statementError' => $statement['statementError'] ?? null,
            'current'        => $current,
            'arrears'        => $arrears,
            'totals'         => [
                'current_payable_kobo' => array_sum(array_column($current, 'payable_kobo')),
                'current_paid_kobo'    => array_sum(array_column($current, 'paid_kobo')),
                'current_kobo'         => array_sum(array_column($openCurrent, 'balance_kobo')),
                'arrears_kobo'         => array_sum(array_column($arrears, 'balance_kobo')),
            ],
            'has_arrears'    => !empty($arrears),
        ];
    }

    protected function line(string $key, array $bill, array $pos, int $classId, int $termId, int $sessionId, bool $isArrear, array $labels): array
    {
        return [
            'key'          => $key,
            'bill_id'      => (int) $bill['id'],
            'title'        => $bill['title'] ?? ($pos['bill']->title ?? 'Bill'),
            'description'  => $bill['description'] ?? null,
            'class_id'     => $classId,
            'term_id'      => $termId,
            'session_id'   => $sessionId,
            'is_arrear'    => $isArrear,
            'original_kobo'=> self::kobo($pos['original']),
            'savings_kobo' => self::kobo($pos['adjustment']['total_savings'] ?? 0),
            'payable_kobo' => self::kobo($pos['payable']),
            'paid_kobo'    => self::kobo($pos['paid']),
            'balance_kobo' => self::kobo($pos['balance']),
        ] + $labels;
    }

    // ─────────────────────────────────────────────────────────────────────
    // VALIDATE A SELECTION
    // ─────────────────────────────────────────────────────────────────────

    /**
     * @param array<string,string|float> $selection  key => amount in naira
     * @return array{quote: array, lines: array, arrears_kobo: int, current_kobo: int, total_kobo: int}
     */
    public function build(Student $student, int $termId, int $sessionId, array $selection): array
    {
        $quote = $this->quote($student, $termId, $sessionId);
        $all   = collect(array_merge($quote['arrears'], $quote['current']))->keyBy('key');

        $errors = [];
        $lines  = [];
        foreach ($selection as $key => $raw) {
            $line = $all->get($key);
            if (!$line) {
                $errors[] = 'One of the selected bills is no longer available. Please refresh the page.';
                continue;
            }
            $amount = trim(str_replace([',', '₦', ' '], '', (string) $raw));
            if (!preg_match('/^\d+(\.\d{1,2})?$/', $amount)) {
                $errors[] = "Enter a valid amount for {$line['title']}.";
                continue;
            }
            $kobo = self::kobo($amount);
            if ($kobo <= 0) {
                continue; // zero = not selected
            }
            if ($line['balance_kobo'] <= 0) {
                $errors[] = "{$line['title']} is already fully paid.";
                continue;
            }
            if ($kobo > $line['balance_kobo']) {
                $errors[] = "{$line['title']}: ₦" . number_format($kobo / 100, 2) . ' is more than the outstanding ₦' . number_format($line['balance_kobo'] / 100, 2) . '.';
                continue;
            }
            $lines[$key] = $line + ['amount_kobo' => $kobo];
        }

        $picked         = collect($lines);
        $currentPicked  = $picked->where('is_arrear', false);
        $arrearsPicked  = $picked->where('is_arrear', true);

        if ($currentPicked->isNotEmpty() && !empty($quote['arrears'])) {
            $short = collect($quote['arrears'])->filter(
                fn ($a) => ($lines[$a['key']]['amount_kobo'] ?? 0) !== $a['balance_kobo']
            );
            if ($short->isNotEmpty()) {
                $errors[] = 'Outstanding arrears (₦' . number_format($quote['totals']['arrears_kobo'] / 100, 2)
                    . ') must be paid in full before this term\'s fees. Select every arrear at its full balance, or pay only arrears for now.';
            }
        }

        $total = (int) $picked->sum('amount_kobo');
        if (empty($errors) && $total <= 0) {
            $errors[] = 'Select at least one bill to pay.';
        } elseif (empty($errors) && $total < self::MIN_TOTAL_KOBO) {
            $errors[] = 'The minimum online payment is ₦' . number_format(self::MIN_TOTAL_KOBO / 100, 2) . '.';
        }

        if ($errors) {
            throw ValidationException::withMessages(['selection' => array_values(array_unique($errors))]);
        }

        // Arrears first (oldest first), then this term's bills.
        $ordered = $arrearsPicked->sortBy(fn ($l) => sprintf('%010d%010d', $l['session_id'], $l['term_id']))
            ->concat($currentPicked)->values()->all();

        return [
            'quote'        => $quote,
            'lines'        => $ordered,
            'arrears_kobo' => (int) $arrearsPicked->sum('amount_kobo'),
            'current_kobo' => (int) $currentPicked->sum('amount_kobo'),
            'total_kobo'   => $total,
        ];
    }

    // ─────────────────────────────────────────────────────────────────────
    // START
    // ─────────────────────────────────────────────────────────────────────

    public function start(Student $student, User $payer, int $termId, int $sessionId, array $selection, string $callbackUrl, string $gatewayKey = 'paystack'): OnlineFeePayment
    {
        $gw = $this->gateway($gatewayKey);
        if (!$gw->isReady()) {
            throw ValidationException::withMessages(['selection' => ['Online payment is not available right now. Please contact the school bursary.']]);
        }

        $built = $this->build($student, $termId, $sessionId, $selection);
        $isStudentPayer = (int) ($payer->student_id ?? 0) === (int) $student->id;
        $email = $this->payerEmail($student, $payer, $isStudentPayer);

        $payment = DB::transaction(function () use ($student, $payer, $termId, $sessionId, $built, $email, $isStudentPayer, $gw) {
            // Earlier unfinished checkouts are superseded (a late webhook can still complete them).
            OnlineFeePayment::where('student_id', $student->id)->where('status', 'pending')
                ->update(['status' => 'abandoned', 'failure_reason' => 'Replaced by a newer checkout', 'updated_at' => now()]);

            $payment = OnlineFeePayment::create([
                'reference'     => $this->newReference(),
                'student_id'    => $student->id,
                'payer_user_id' => $payer->id,
                'payer_type'    => $isStudentPayer ? 'student' : 'staff',
                'email'         => $email,
                'class_id'      => $built['quote']['class']->id ?? null,
                'term_id'       => $termId,
                'session_id'    => $sessionId,
                'amount_kobo'   => $built['total_kobo'],
                'arrears_kobo'  => $built['arrears_kobo'],
                'current_kobo'  => $built['current_kobo'],
                'gateway'       => $gw->key(),
                'mode'          => $gw->mode(),
                'status'        => 'pending',
            ]);

            foreach ($built['lines'] as $l) {
                OnlineFeePaymentItem::create([
                    'online_fee_payment_id' => $payment->id,
                    'school_bill_id' => $l['bill_id'],
                    'title'          => $l['title'],
                    'class_id'       => $l['class_id'],
                    'term_id'        => $l['term_id'],
                    'session_id'     => $l['session_id'],
                    'is_arrear'      => $l['is_arrear'],
                    'payable_kobo'   => $l['payable_kobo'],
                    'balance_kobo'   => $l['balance_kobo'],
                    'amount_kobo'    => $l['amount_kobo'],
                ]);
            }

            return $payment;
        });

        $init = $gw->initialize($email, $payment->amount_kobo, $payment->reference, $callbackUrl, [
            'product_name'          => 'School fees — ' . trim($student->firstname . ' ' . $student->lastname),
            'product_description'   => 'Admission No ' . $student->admissionNo . ' · ' . $payment->reference,
            'user_name'             => trim($student->firstname . ' ' . $student->lastname),
            'online_fee_payment_id' => $payment->id,
            'student_id'            => $student->id,
            'admission_no'          => $student->admissionNo,
            'custom_fields'         => [
                ['display_name' => 'Student', 'variable_name' => 'student', 'value' => trim($student->firstname . ' ' . $student->lastname)],
                ['display_name' => 'Admission No', 'variable_name' => 'admission_no', 'value' => (string) $student->admissionNo],
            ],
        ]);

        if (!$init['ok']) {
            $payment->update(['status' => 'failed', 'failure_reason' => $init['message']]);
            throw ValidationException::withMessages(['selection' => [$init['message']]]);
        }

        $payment->update(['access_code' => $init['access_code'], 'authorization_url' => $init['authorization_url']]);

        return $payment;
    }

    protected function payerEmail(Student $student, User $payer, bool $isStudentPayer): string
    {
        $candidates = $isStudentPayer
            ? [$payer->email, $student->email]
            : [$payer->email, $student->email];

        foreach ($candidates as $e) {
            if ($e && filter_var($e, FILTER_VALIDATE_EMAIL)) {
                return strtolower(trim($e));
            }
        }
        $host = parse_url(config('app.url'), PHP_URL_HOST) ?: 'school.ng';
        return 'student' . $student->id . '@' . preg_replace('/^www\./', '', $host);
    }

    protected function newReference(): string
    {
        do {
            $ref = 'CSK-' . now()->format('ymd') . '-' . strtoupper(Str::random(10));
        } while (OnlineFeePayment::where('reference', $ref)->exists());

        return $ref;
    }

    // ─────────────────────────────────────────────────────────────────────
    // FINALIZE (callback, webhook, admin re-verify)
    // ─────────────────────────────────────────────────────────────────────

    public function finalize(string $reference, ?array $data = null, string $source = 'callback'): ?OnlineFeePayment
    {
        $payment = OnlineFeePayment::where('reference', $reference)->first();
        if (!$payment) {
            return null;
        }
        if ($payment->posted_at) {
            return $payment;
        }

        // Always confirm with the gateway directly (never trust a browser redirect or a callback body).
        $gw = $this->gateway($payment->gateway);
        if ($data === null || $source === 'webhook') {
            $verify = $gw->verify($reference);
            if (!$verify['ok']) {
                $payment->update(['last_verified_at' => now()]);
                return $payment->fresh();
            }
            $data = $verify['data'];
        }

        $justPosted = false;
        $result = DB::transaction(function () use ($payment, $data, $source, &$justPosted, $gw) {
            /** @var OnlineFeePayment $p */
            $p = OnlineFeePayment::whereKey($payment->id)->lockForUpdate()->first();
            if ($p->posted_at) {
                return $p;
            }

            $gwStatus = (string) ($data['status'] ?? '');
            $common = [
                'last_verified_at' => now(),
                'channel'          => $data['channel'] ?? $p->channel,
                'gateway_response' => array_intersect_key($data, array_flip([
                    'id', 'status', 'reference', 'amount', 'currency', 'channel', 'fees',
                    'paid_at', 'gateway_response', 'ip_address',
                ])) + ['source' => $source],
            ];

            if ($gwStatus !== 'success') {
                $map = ['failed' => 'failed', 'abandoned' => 'abandoned', 'reversed' => 'failed'];
                if ($gwStatus === 'pending') {
                    $p->update($common);
                    return $p->fresh();
                }
                $p->update($common + [
                    'status'         => $map[$gwStatus] ?? $p->status,
                    'failure_reason' => $data['gateway_response'] ?? $p->failure_reason,
                ]);
                return $p->fresh();
            }

            $paidKobo = (int) ($data['amount'] ?? 0);
            $okAmount = $paidKobo === (int) $p->amount_kobo;
            $okCurr   = strtoupper((string) ($data['currency'] ?? 'NGN')) === $p->currency;
            $okRef    = (string) ($data['reference'] ?? '') === $p->reference;

            if (!$okAmount || !$okCurr || !$okRef) {
                $p->update($common + [
                    'status'         => 'amount_mismatch',
                    'paid_kobo'      => $paidKobo,
                    'needs_review'   => true,
                    'failure_reason' => $gw->name() . ' confirmed ' . ($data['currency'] ?? '') . ' ' . number_format($paidKobo / 100, 2)
                        . ' but ₦' . number_format($p->amount_kobo / 100, 2) . ' was expected. Nothing was posted.',
                ]);
                Log::warning('Online fee payment mismatch', ['reference' => $p->reference, 'data' => $data]);
                return $p->fresh();
            }

            $applied = 0;
            foreach ($p->items()->orderByDesc('is_arrear')->orderBy('session_id')->orderBy('term_id')->orderBy('id')->get() as $item) {
                $res = $this->ledger->post(
                    (int) $p->student_id, (int) $item->school_bill_id, (int) $item->class_id,
                    (int) $item->term_id, (int) $item->session_id,
                    self::naira((int) $item->amount_kobo), self::methodLabel($p->gateway), $p->payer_user_id, $p->reference
                );
                $itemApplied = self::kobo($res['applied']);
                $applied += $itemApplied;
                $item->update([
                    'applied_kobo' => $itemApplied,
                    'student_bill_payment_record_id' => $res['record_id'],
                ]);

                if ($itemApplied > 0) {
                    try {
                        $this->audit->log('recorded', [
                            'student_id'     => $p->student_id,
                            'school_bill_id' => $item->school_bill_id,
                            'student_bill_payment_id'        => $res['shell_id'] ?? null,
                            'student_bill_payment_record_id' => $res['record_id'],
                            'class_id'   => $item->class_id,
                            'term_id'    => $item->term_id,
                            'session_id' => $item->session_id,
                            'amount'     => $res['applied'],
                            'payment_method' => self::methodLabel($p->gateway),
                            'entity_type'    => 'payment',
                        ], null, ['reference' => $p->reference, 'new_balance' => $res['balance']], 'Online payment (' . $gw->name() . ')');
                    } catch (\Throwable $e) {
                        Log::warning('Audit log failed for online payment', ['reference' => $p->reference, 'error' => $e->getMessage()]);
                    }
                }
            }

            $unapplied = max(0, $paidKobo - $applied);
            $p->update($common + [
                'status'           => 'success',
                'paid_kobo'        => $paidKobo,
                'gateway_fee_kobo' => (int) ($data['fees'] ?? 0),
                'applied_kobo'     => $applied,
                'unapplied_kobo'   => $unapplied,
                'needs_review'     => $unapplied > 0,
                'failure_reason'   => $unapplied > 0
                    ? '₦' . number_format($unapplied / 100, 2) . ' could not be applied because the bill(s) had been settled before this payment was confirmed.'
                    : null,
                'paid_at'          => !empty($data['paid_at']) ? Carbon::parse($data['paid_at']) : now(),
                'posted_at'        => now(),
            ]);
            $justPosted = true;

            return $p->fresh();
        });

        if ($justPosted && $result && $result->applied_kobo > 0) {
            \App\Services\Messaging\PaymentReceiptNotifier::queue(
                (int) $result->student_id, self::naira((int) $result->applied_kobo),
                $result->gateway === 'opay' ? 'Online (OPay)' : 'Online (' . ($result->channel ? ucwords(str_replace('_', ' ', $result->channel)) : 'Paystack') . ')',
                $result->reference, $result->term_id ? (int) $result->term_id : null, $result->session_id ? (int) $result->session_id : null
            );
        }

        return $result;
    }
}
