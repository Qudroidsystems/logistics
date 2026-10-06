<?php

namespace App\Services\Messaging;

use App\Models\MessagingSetting;
use App\Models\SchoolInformation;
use App\Models\Schoolsession;
use App\Models\Schoolterm;
use App\Models\Student;
use App\Services\Billing\StudentFeeStatementService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Sends a payment receipt to the student's parents after a payment is
 * recorded (bursar screen or Paystack). Settings live in Notices › Settings ›
 * Payment receipts (messaging_settings row "receipts").
 *
 * Each (reference, channel, recipient) is sent at most once, so a payment
 * confirmed twice (callback + webhook) never produces two receipts.
 */
class PaymentReceiptNotifier
{
    public const PLACEHOLDERS = [
        '{parent_name}'  => 'Parent name',
        '{student_name}' => 'Student name',
        '{class}'        => 'Class and arm',
        '{amount}'       => 'Amount paid',
        '{method}'       => 'Payment method',
        '{reference}'    => 'Receipt reference',
        '{date}'         => 'Payment date',
        '{term}'         => 'Term',
        '{session}'      => 'Session',
        '{balance}'      => 'Balance left for the term',
        '{school_name}'  => 'School name',
    ];

    public const DEFAULT_MESSAGE = "Dear {parent_name},\n\nWe have received {amount} for {student_name} ({class}) on {date} via {method}.\nReference: {reference}\nBalance for {term}, {session}: {balance}\n\nThank you.\n{school_name}";
    public const DEFAULT_SMS     = "Dear {parent_name}, we received {amount} for {student_name} on {date}. Ref: {reference}. {term} balance: {balance}. Thank you. - {school_name}";

    public function __construct(
        protected MessagingService $messaging,
        protected NoticeAudienceService $audience,
        protected StudentFeeStatementService $statements,
    ) {}

    public static function settings(): MessagingSetting
    {
        return MessagingSetting::firstOrCreate(
            ['channel' => 'receipts'],
            ['driver' => 'auto', 'is_active' => false, 'config' => [
                'channels' => ['sms'], 'message' => self::DEFAULT_MESSAGE, 'sms_text' => self::DEFAULT_SMS,
            ]]
        );
    }

    /** Queue a receipt to be sent after the current response (never slows the payment). */
    public static function queue(int $studentId, float $amount, string $method, string $reference, ?int $termId, ?int $sessionId): void
    {
        try {
            if ($amount > 0) {
                PortalNotifier::toStudents([$studentId], 'Payment received: ₦' . number_format($amount, 2),
                    'A payment of ₦' . number_format($amount, 2) . ' (' . $method . ') was recorded. Reference: ' . $reference . '.',
                    route('student.payments'), 'payment', 'payment:' . $reference);
            }
            if (!self::settings()->is_active || $amount <= 0) return;
            dispatch(function () use ($studentId, $amount, $method, $reference, $termId, $sessionId) {
                app(self::class)->send($studentId, $amount, $method, $reference, $termId, $sessionId);
            })->afterResponse();
        } catch (\Throwable $e) {
            Log::warning('Could not queue payment receipt', ['student' => $studentId, 'error' => $e->getMessage()]);
        }
    }

    public function send(int $studentId, float $amount, string $method, string $reference, ?int $termId, ?int $sessionId): array
    {
        $cfg = self::settings();
        if (!$cfg->is_active) return [];

        $channels = array_values(array_filter((array) ($cfg->config['channels'] ?? []), fn ($c) => $this->messaging->enabled($c)));
        if (!$channels) return [];

        $student = Student::find($studentId);
        if (!$student) return [];

        $vars = $this->vars($student, $amount, $method, $reference, $termId, $sessionId);
        $res  = $this->audience->resolve(['scope' => 'students', 'student_ids' => [$studentId]], $channels);
        $out  = [];

        foreach ($res['contacts'] as $ch => $list) {
            foreach ($list as $c) {
                $tpl  = $ch === 'sms' && trim((string) ($cfg->config['sms_text'] ?? '')) !== '' ? $cfg->config['sms_text'] : ($cfg->config['message'] ?? self::DEFAULT_MESSAGE);
                $body = strtr($tpl, $vars + ['{parent_name}' => $c['name'] ?: 'Parent']);

                // Claim (dedupe) before sending.
                $inserted = DB::table('payment_receipt_messages')->insertOrIgnore([
                    'student_id' => $studentId, 'reference' => mb_substr($reference, 0, 80), 'amount' => round($amount, 2),
                    'method' => mb_substr($method, 0, 60), 'channel' => $ch, 'recipient' => $c['to'],
                    'recipient_name' => mb_substr((string) $c['name'], 0, 190), 'body' => $body, 'status' => 'queued',
                    'created_at' => now(), 'updated_at' => now(),
                ]);
                if (!$inserted) continue;

                $r = $this->messaging->send($ch, $c['to'], $body, [
                    'name' => $c['name'] ?: 'Parent', 'subject' => 'Payment received — ' . $vars['{student_name}'],
                ]);
                DB::table('payment_receipt_messages')
                    ->where(['reference' => mb_substr($reference, 0, 80), 'channel' => $ch, 'recipient' => $c['to']])
                    ->update(['status' => $r['status'], 'error' => $r['error'] ? mb_substr($r['error'], 0, 490) : null,
                              'provider_message_id' => $r['id'] ?: null, 'updated_at' => now()]);
                $out[] = ['channel' => $ch, 'to' => $c['to'], 'status' => $r['status'], 'error' => $r['error']];
            }
        }
        return $out;
    }

    protected function vars(Student $s, float $amount, string $method, string $reference, ?int $termId, ?int $sessionId): array
    {
        $money = fn ($v) => '₦' . number_format((float) $v, 2);
        $balance = null; $class = '';
        try {
            $st = $this->statements->buildStatement($s, $termId, $sessionId);
            $balance = (float) ($st['totals']['outstanding'] ?? 0);
            $class = $st['class']->schoolclass ?? '';
        } catch (\Throwable $e) {
            Log::warning('Receipt balance lookup failed', ['student' => $s->id, 'error' => $e->getMessage()]);
        }
        $school = SchoolInformation::getActiveSchool() ?? SchoolInformation::first();

        return [
            '{student_name}' => trim($s->firstname . ' ' . $s->lastname),
            '{class}'        => $class,
            '{amount}'       => $money($amount),
            '{method}'       => $method,
            '{reference}'    => $reference,
            '{date}'         => now()->format('j M Y, g:i a'),
            '{term}'         => $termId ? (Schoolterm::where('id', $termId)->value('term') ?? '') : '',
            '{session}'      => $sessionId ? (Schoolsession::where('id', $sessionId)->value('session') ?? '') : '',
            '{balance}'      => $balance === null ? 'see portal' : ($balance <= 0 ? '₦0.00 (fully paid)' : $money($balance)),
            '{school_name}'  => $school->school_name ?? config('app.name'),
        ];
    }
}
