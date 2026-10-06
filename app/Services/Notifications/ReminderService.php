<?php

namespace App\Services\Notifications;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Auth;

class ReminderService
{
    /**
     * Send fee reminders for selected students on chosen channels.
     * Fans out to every valid parent contact found per student (e.g. both
     * father_phone and mother_phone if both are on file), not just the first.
     *
     * @param  array<int>  $studentIds
     * @param  array<string>  $channels  email|sms|whatsapp
     * @return array{message:string,summary:array}
     */
    public function sendFeeReminders(
        array $studentIds,
        array $channels,
        ?int $termId = null,
        ?int $sessionId = null,
        ?int $sentBy = null
    ): array {
        $channels = $this->normalizeChannels($channels);

        if (empty($channels)) {
            return [
                'message' => 'No channels selected.',
                'summary' => [],
            ];
        }

        $students = $this->loadStudentsWithDebt($studentIds, $termId, $sessionId);

        $summary = [];
        foreach ($channels as $channel) {
            $summary[$channel] = ['sent' => 0, 'skipped' => 0, 'failed' => 0];
        }

        // Explicit $sentBy (passed by queued jobs, where there's no session)
        // takes priority; falls back to the currently authenticated user for
        // the synchronous request path.
        $sentBy = $sentBy ?? Auth::id();

        foreach ($students as $student) {
            $contacts = $this->resolveContacts($student);
            $message  = $this->buildMessage($student);

            foreach ($channels as $channel) {
                $recipients = $channel === 'email' ? $contacts['emails'] : $contacts['phones'];

                if (empty($recipients)) {
                    $summary[$channel]['skipped']++;

                    DB::table('reminder_logs')->insert([
                        'student_id'        => $student->student_id,
                        'term_id'           => $termId,
                        'session_id'        => $sessionId,
                        'channel'           => $channel,
                        'recipient'         => null,
                        'status'            => 'skipped',
                        'reason'            => $channel === 'email' ? 'No valid email on file' : 'No phone number on file',
                        'outstanding'       => $student->outstanding,
                        'sent_by'           => $sentBy,
                        'provider_response' => null,
                        'created_at'        => now(),
                        'updated_at'        => now(),
                    ]);
                    continue;
                }

                // Fan out to every parent contact found (father + mother, etc.)
                foreach ($recipients as $recipient) {
                    $result = $this->sendOnChannel($channel, $student, $recipient, $message);

                    $summary[$channel][$result['status'] === 'sent' ? 'sent' : ($result['status'] === 'failed' ? 'failed' : 'skipped')]++;

                    DB::table('reminder_logs')->insert([
                        'student_id'        => $student->student_id,
                        'term_id'           => $termId,
                        'session_id'        => $sessionId,
                        'channel'           => $channel,
                        'recipient'         => $result['recipient'],
                        'status'            => $result['status'],
                        'reason'            => $result['reason'],
                        'outstanding'       => $student->outstanding,
                        'sent_by'           => $sentBy,
                        'provider_response' => $result['provider_response'] ?? null,
                        'created_at'        => now(),
                        'updated_at'        => now(),
                    ]);
                }
            }
        }

        $message = $this->formatSummaryMessage(count($students), $summary);

        return [
            'message' => $message,
            'summary' => $summary,
        ];
    }

    protected function normalizeChannels(array $channels): array
    {
        $allowed = ['email', 'sms', 'whatsapp'];
        $enabled = config('reminders.channels', []);

        return collect($channels)
            ->map(fn ($c) => strtolower(trim((string) $c)))
            ->filter(fn ($c) => in_array($c, $allowed, true))
            ->filter(fn ($c) => ($enabled[$c] ?? false) === true)
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Loads debtor students joined with parentRegistration for real contact
     * fields (parent_email, father_phone, mother_phone), falling back to the
     * student's own email/phone_number when parent record is missing.
     */
    protected function loadStudentsWithDebt(array $studentIds, ?int $termId, ?int $sessionId): Collection
    {
        $query = DB::table('student_bill_payment_book as sbpb')
            ->join('studentRegistration as s', 's.id', '=', 'sbpb.student_id')
            ->leftJoin('schoolclass as sc', 'sc.id', '=', 'sbpb.class_id')
            ->leftJoin('schoolarm as sa', 'sa.id', '=', 'sc.arm')
            ->leftJoin('schoolterm as st', 'st.id', '=', 'sbpb.term_id')
            ->leftJoin('schoolsession as ss', 'ss.id', '=', 'sbpb.session_id')
            ->leftJoin('parentRegistration as pr', 'pr.studentId', '=', 's.id')
            ->whereIn('sbpb.student_id', $studentIds)
            ->where('sbpb.amount_owed', '>', 0)
            ->select(
                's.id as student_id',
                's.firstname',
                's.lastname',
                's.admissionNo',
                's.email as student_email',
                's.phone_number as student_phone',
                'pr.parent_email',
                'pr.father_phone',
                'pr.mother_phone',
                DB::raw("TRIM(CONCAT(COALESCE(sc.schoolclass,''), ' ', COALESCE(sa.arm,''))) as class_name"),
                'st.term as term_name',
                'ss.session as session_name',
                DB::raw('SUM(sbpb.amount_owed) as outstanding'),
                DB::raw('SUM(sbpb.amount_paid) as amount_paid')
            )
            ->groupBy(
                's.id',
                's.firstname',
                's.lastname',
                's.admissionNo',
                's.email',
                's.phone_number',
                'pr.parent_email',
                'pr.father_phone',
                'pr.mother_phone',
                'sc.schoolclass',
                'sa.arm',
                'st.term',
                'ss.session'
            );

        if ($termId) {
            $query->where('sbpb.term_id', $termId);
        }
        if ($sessionId) {
            $query->where('sbpb.session_id', $sessionId);
        }

        $students = $query->get();

        // Attach a per-bill breakdown (the bills still owed) to each student.
        $bookBills = DB::table('student_bill_payment_book as sbpb')
            ->join('school_bill as sb', 'sb.id', '=', 'sbpb.school_bill_id')
            ->whereIn('sbpb.student_id', $studentIds)
            ->where('sbpb.amount_owed', '>', 0)
            ->when($termId, fn ($q) => $q->where('sbpb.term_id', $termId))
            ->when($sessionId, fn ($q) => $q->where('sbpb.session_id', $sessionId))
            ->select('sbpb.student_id', 'sb.title', 'sbpb.amount_owed as owed')
            ->get()->groupBy('student_id');
        foreach ($students as $st) {
            $st->bills = ($bookBills->get($st->student_id) ?? collect())
                ->map(fn ($b) => ['title' => $b->title, 'owed' => (float) $b->owed])->values()->all();
        }

        // Include never-payers in the selection (no payment-book row) via the roster.
        $found = $students->pluck('student_id')->map(fn ($v) => (int) $v)->all();
        $missing = array_values(array_diff(array_map('intval', $studentIds), $found));
        if ($missing) {
            $students = $students->concat($this->rosterDebtors($missing, $termId, $sessionId));
        }

        return $students;
    }

    /** Debtors with no payment-book row yet: owed = full billed for their class/term/session. */
    protected function rosterDebtors(array $studentIds, ?int $termId, ?int $sessionId): Collection
    {
        if (!$sessionId) {
            $sessionId = (int) (DB::table('schoolsession')->where('status', 'Current')->value('id') ?? 0) ?: null;
        }

        $roster = DB::table('studentclass as scl')
            ->join('studentRegistration as s', 's.id', '=', 'scl.studentId')
            ->leftJoin('schoolclass as sc', 'sc.id', '=', 'scl.schoolclassid')
            ->leftJoin('schoolarm as sa', 'sa.id', '=', 'sc.arm')
            ->leftJoin('schoolterm as st', 'st.id', '=', 'scl.termid')
            ->leftJoin('schoolsession as ss', 'ss.id', '=', 'scl.sessionid')
            ->leftJoin('parentRegistration as pr', 'pr.studentId', '=', 's.id')
            ->whereIn('scl.studentId', $studentIds)
            ->when($termId, fn ($q) => $q->where('scl.termid', $termId))
            ->when($sessionId, fn ($q) => $q->where('scl.sessionid', $sessionId))
            ->select(
                's.id as student_id', 's.firstname', 's.lastname', 's.admissionNo',
                's.email as student_email', 's.phone_number as student_phone',
                'pr.parent_email', 'pr.father_phone', 'pr.mother_phone',
                DB::raw("TRIM(CONCAT(COALESCE(sc.schoolclass,''), ' ', COALESCE(sa.arm,''))) as class_name"),
                'st.term as term_name', 'ss.session as session_name',
                'scl.schoolclassid as class_id', 'scl.termid as term_id', 'scl.sessionid as session_id'
            )->get()
            ->unique(fn ($r) => $r->student_id . '_' . $r->class_id . '_' . $r->term_id . '_' . $r->session_id)
            ->values();

        if ($roster->isEmpty()) return collect();

        $bills = DB::table('school_bill_class_term_session as sbcts')
            ->join('school_bill as sb', 'sb.id', '=', 'sbcts.bill_id');
        if (Schema::hasColumn('school_bill_class_term_session', 'deleted_at')) $bills->whereNull('sbcts.deleted_at');
        if (Schema::hasColumn('school_bill_class_term_session', 'is_active')) $bills->where('sbcts.is_active', 1);
        $billsByGroup = $bills->select('sbcts.class_id', 'sbcts.termid_id as term_id', 'sbcts.session_id', 'sb.title', 'sb.bill_amount')
            ->get()->groupBy(fn ($b) => ((int) $b->class_id) . '_' . ((int) $b->term_id) . '_' . ((int) $b->session_id));

        $out = collect();
        foreach ($roster as $r) {
            $gk = ((int) $r->class_id) . '_' . ((int) $r->term_id) . '_' . ((int) $r->session_id);
            $gb = $billsByGroup->get($gk);
            if (!$gb) continue;
            $total = (float) $gb->sum('bill_amount');
            if ($total <= 0) continue;
            $r->outstanding = $total;
            $r->amount_paid = 0;
            $r->bills = $gb->map(fn ($b) => ['title' => $b->title, 'owed' => (float) $b->bill_amount])->values()->all();
            $out->push($r);
        }
        return $out;
    }

    /**
     * Returns EVERY valid, deduplicated email/phone found for a student
     * (parent + student's own contacts), not just the first match.
     */
    protected function resolveContacts(object $student): array
    {
        $emailFields = config('reminders.contacts.email_fields', ['parent_email', 'student_email']);
        $phoneFields = config('reminders.contacts.phone_fields', ['father_phone', 'mother_phone', 'student_phone']);

        $emails = collect($emailFields)
            ->map(fn ($f) => $student->{$f} ?? null)
            ->filter()
            ->map(fn ($e) => trim((string) $e))
            ->filter(fn ($e) => filter_var($e, FILTER_VALIDATE_EMAIL))
            ->unique()
            ->values()
            ->all();

        $phones = collect($phoneFields)
            ->map(fn ($f) => $student->{$f} ?? null)
            ->filter()
            ->map(fn ($p) => $this->normalizePhone((string) $p))
            ->filter()
            ->unique()
            ->values()
            ->all();

        return ['emails' => $emails, 'phones' => $phones];
    }

    /**
     * Nigeria-friendly: 080... → 23480...
     */
    protected function normalizePhone(string $phone): ?string
    {
        $digits = preg_replace('/\D+/', '', $phone);
        if ($digits === null || $digits === '') {
            return null;
        }

        if (str_starts_with($digits, '234') && strlen($digits) >= 13) {
            return $digits;
        }
        if (str_starts_with($digits, '0') && strlen($digits) === 11) {
            return '234' . substr($digits, 1);
        }
        if (strlen($digits) === 10) {
            return '234' . $digits;
        }

        return $digits;
    }

    protected function buildMessage(object $student): array
    {
        $name = trim(($student->firstname ?? '') . ' ' . ($student->lastname ?? ''));
        $school = config('reminders.school_name', config('app.name'));
        $outstanding = number_format((float) $student->outstanding, 2);
        $term = $student->term_name ?: 'current term';
        $session = $student->session_name ?: 'current session';
        $class = $student->class_name ?: '';

        $subject = "Fee payment reminder – {$name}";

        $breakdown = '';
        if (!empty($student->bills)) {
            foreach ($student->bills as $b) {
                $bt = is_array($b) ? ($b['title'] ?? 'Bill') : ($b->title ?? 'Bill');
                $bo = is_array($b) ? ($b['owed'] ?? 0) : ($b->owed ?? 0);
                $breakdown .= "  - {$bt}: ₦" . number_format((float) $bo, 2) . "\n";
            }
        }

        $body = "Dear Parent/Guardian,\n\n"
            . "This is a reminder that {$name} ({$student->admissionNo})"
            . ($class ? " in {$class}" : '')
            . " has an outstanding school fee balance of ₦{$outstanding}"
            . " for {$term}, {$session}.\n\n"
            . ($breakdown ? "Breakdown of what is owed:\n{$breakdown}\n" : '')
            . "Please arrange payment at your earliest convenience.\n\n"
            . "Thank you.\n{$school}";

        $sms = "Fee reminder: {$name} owes ₦{$outstanding} for {$term}. Please pay promptly. – {$school}";

        return [
            'subject' => $subject,
            'body'    => $body,
            'sms'     => mb_substr($sms, 0, 320),
            'name'    => $name,
            'outstanding' => $outstanding,
            'term'    => $term,
            'session' => $session,
        ];
    }

    protected function sendOnChannel(string $channel, object $student, string $recipient, array $message): array
    {
        return match ($channel) {
            'email'    => $this->sendEmail($recipient, $message),
            'sms'      => $this->sendSms($recipient, $message),
            'whatsapp' => $this->sendWhatsapp($recipient, $message),
            default    => [
                'status' => 'skipped',
                'recipient' => null,
                'reason' => 'Unknown channel',
            ],
        };
    }

    protected function sendEmail(?string $email, array $message): array
    {
        if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return [
                'status' => 'skipped',
                'recipient' => $email,
                'reason' => 'No valid email',
            ];
        }

        try {
            Mail::raw($message['body'], function ($mail) use ($email, $message) {
                $mail->to($email)->subject($message['subject']);
            });

            return [
                'status' => 'sent',
                'recipient' => $email,
                'reason' => null,
            ];
        } catch (\Throwable $e) {
            Log::error('Reminder email failed: ' . $e->getMessage());
            return [
                'status' => 'failed',
                'recipient' => $email,
                'reason' => $e->getMessage(),
                'provider_response' => $e->getMessage(),
            ];
        }
    }

    protected function sendSms(?string $phone, array $message): array
    {
        if (!$phone) {
            return [
                'status' => 'skipped',
                'recipient' => null,
                'reason' => 'No phone number',
            ];
        }

        $driver = config('reminders.sms.driver', 'log');

        try {
            if ($driver === 'termii') {
                return $this->sendSmsTermii($phone, $message['sms']);
            }
            if ($driver === 'africastalking') {
                return $this->sendSmsAfricaTalking($phone, $message['sms']);
            }

            // log driver – always "sent" for testing
            Log::info('SMS reminder (log driver)', ['to' => $phone, 'text' => $message['sms']]);
            return [
                'status' => 'sent',
                'recipient' => $phone,
                'reason' => 'Logged only (SMS_DRIVER=log)',
                'provider_response' => 'log',
            ];
        } catch (\Throwable $e) {
            Log::error('Reminder SMS failed: ' . $e->getMessage());
            return [
                'status' => 'failed',
                'recipient' => $phone,
                'reason' => $e->getMessage(),
                'provider_response' => $e->getMessage(),
            ];
        }
    }

    protected function sendSmsTermii(string $phone, string $text): array
    {
        $apiKey = config('reminders.sms.termii.api_key');
        $sender = config('reminders.sms.termii.sender');
        $url    = config('reminders.sms.termii.url');

        if (!$apiKey) {
            return [
                'status' => 'skipped',
                'recipient' => $phone,
                'reason' => 'Termii not configured',
            ];
        }

        $response = Http::post($url, [
            'to' => $phone,
            'from' => $sender,
            'sms' => $text,
            'type' => 'plain',
            'channel' => 'generic',
            'api_key' => $apiKey,
        ]);

        $body = $response->json() ?? [];

        if ($response->successful() && strtolower((string) ($body['code'] ?? '')) === 'ok') {
            return [
                'status' => 'sent',
                'recipient' => $phone,
                'reason' => null,
                'provider_response' => $response->body(),
            ];
        }

        $interpreted = $this->interpretTermiiError($body, $response->status());

        Log::warning('Termii SMS send failed', [
            'phone' => $phone,
            'status' => $response->status(),
            'reason' => $interpreted['reason'],
            'raw' => $response->body(),
        ]);

        return [
            'status' => 'failed',
            'recipient' => $phone,
            'reason' => $interpreted['reason'],
            'provider_response' => $response->body(),
            'retryable' => $interpreted['retryable'],
        ];
    }

    protected function sendSmsAfricaTalking(string $phone, string $text): array
    {
        // Placeholder – implement with AT SDK or HTTP when ready
        Log::info('Africa\'s Talking SMS stub', ['to' => $phone, 'text' => $text]);
        return [
            'status' => 'sent',
            'recipient' => $phone,
            'reason' => 'Stub – implement AT API',
            'provider_response' => 'stub',
        ];
    }

    protected function sendWhatsapp(?string $phone, array $message): array
    {
        if (!$phone) {
            return [
                'status' => 'skipped',
                'recipient' => null,
                'reason' => 'No phone number',
            ];
        }

        $driver = config('reminders.whatsapp.driver', 'log');

        try {
            if ($driver === 'meta') {
                return $this->sendWhatsappMeta($phone, $message);
            }
            if ($driver === 'twilio') {
                return $this->sendWhatsappTwilio($phone, $message);
            }

            Log::info('WhatsApp reminder (log driver)', ['to' => $phone, 'text' => $message['sms']]);
            return [
                'status' => 'sent',
                'recipient' => $phone,
                'reason' => 'Logged only (WHATSAPP_DRIVER=log)',
                'provider_response' => 'log',
            ];
        } catch (\Throwable $e) {
            Log::error('Reminder WhatsApp failed: ' . $e->getMessage());
            return [
                'status' => 'failed',
                'recipient' => $phone,
                'reason' => $e->getMessage(),
                'provider_response' => $e->getMessage(),
            ];
        }
    }

    protected function sendWhatsappMeta(string $phone, array $message): array
    {
        $token   = config('reminders.whatsapp.meta.token');
        $phoneId = config('reminders.whatsapp.meta.phone_number_id');
        $template = config('reminders.whatsapp.meta.template_name');
        $lang    = config('reminders.whatsapp.meta.template_language', 'en');

        if (!$token || !$phoneId) {
            return [
                'status' => 'skipped',
                'recipient' => $phone,
                'reason' => 'WhatsApp Meta not configured',
            ];
        }

        $payload = [
            'messaging_product' => 'whatsapp',
            'to' => $phone,
            'type' => 'template',
            'template' => [
                'name' => $template,
                'language' => ['code' => $lang],
                'components' => [
                    [
                        'type' => 'body',
                        'parameters' => [
                            ['type' => 'text', 'text' => $message['name']],
                            ['type' => 'text', 'text' => $message['outstanding']],
                            ['type' => 'text', 'text' => $message['term']],
                        ],
                    ],
                ],
            ],
        ];

        $response = Http::withToken($token)
            ->post("https://graph.facebook.com/v19.0/{$phoneId}/messages", $payload);

        if ($response->successful()) {
            return [
                'status' => 'sent',
                'recipient' => $phone,
                'reason' => null,
                'provider_response' => $response->body(),
            ];
        }

        $interpreted = $this->interpretMetaError($response->json());

        Log::warning('WhatsApp Meta send failed', [
            'phone' => $phone,
            'status' => $response->status(),
            'reason' => $interpreted['reason'],
            'raw' => $response->body(),
        ]);

        return [
            'status' => 'failed',
            'recipient' => $phone,
            'reason' => $interpreted['reason'],
            'provider_response' => $response->body(),
            'retryable' => $interpreted['retryable'],
        ];
    }

    protected function sendWhatsappTwilio(string $phone, array $message): array
    {
        $sid   = config('reminders.whatsapp.twilio.sid');
        $token = config('reminders.whatsapp.twilio.token');
        $from  = config('reminders.whatsapp.twilio.from');

        if (!$sid || !$token || !$from) {
            return [
                'status' => 'skipped',
                'recipient' => $phone,
                'reason' => 'Twilio WhatsApp not configured',
            ];
        }

        $response = Http::withBasicAuth($sid, $token)
            ->asForm()
            ->post("https://api.twilio.com/2010-04-01/Accounts/{$sid}/Messages.json", [
                'From' => $from,
                'To'   => 'whatsapp:+' . ltrim($phone, '+'),
                'Body' => $message['body'],
            ]);

        if ($response->successful()) {
            return [
                'status' => 'sent',
                'recipient' => $phone,
                'reason' => null,
                'provider_response' => $response->body(),
            ];
        }

        return [
            'status' => 'failed',
            'recipient' => $phone,
            'reason' => 'Twilio error',
            'provider_response' => $response->body(),
        ];
    }

    /**
     * Translate Meta's WhatsApp Cloud API error payload into an actionable
     * message. Codes grouped by Meta's own categories: Authorization,
     * Throttling, Integrity, Template, Phone Registration, Account/Policy, Flow.
     */
    protected function interpretMetaError(?array $body): array
    {
        $error = $body['error'] ?? null;

        if (!$error) {
            return ['reason' => 'Meta API error (no error detail returned)', 'retryable' => true];
        }

        $code    = $error['code'] ?? null;
        $subcode = $error['error_subcode'] ?? null;
        $raw     = $error['message'] ?? 'Unknown Meta error';

        $map = [
            // --- Authorization Errors ---
            0   => ['msg' => 'App/user authentication failed — check the access token is valid for this app.', 'retry' => false],
            190 => ['msg' => 'Access token expired or invalid — regenerate a permanent System User token.', 'retry' => false],
            10  => ['msg' => 'Permission denied — the token is missing the whatsapp_business_messaging permission.', 'retry' => false],
            200 => ['msg' => 'Permission error — check the app has been granted the required WhatsApp permissions.', 'retry' => false],
            131005 => ['msg' => 'Access denied — permission not granted or has been removed. Re-check token scopes.', 'retry' => false],

            // --- Throttling / Rate-limit Errors ---
            4      => ['msg' => 'API call rate limit hit (too many management calls). Slow down or batch requests.', 'retry' => true],
            80007  => ['msg' => 'WhatsApp Business Account hourly rate limit hit (200-5000 calls/hr depending on tier). Wait for reset.', 'retry' => true],
            130429 => ['msg' => 'Message throughput limit hit for this phone number. Reduce send rate or queue with delay.', 'retry' => true],
            131048 => ['msg' => 'Spam/quality rate limit hit — too many messages blocked or flagged as spam. Check quality rating in WhatsApp Manager.', 'retry' => false],
            131056 => ['msg' => 'Pair rate limit hit — too many messages sent to this same recipient in a short window. Wait before retrying this number; other numbers are unaffected.', 'retry' => true],

            // --- Integrity / Message-send Errors ---
            131000 => ['msg' => 'Unknown send error from Meta. Safe to retry once; if it persists, contact Meta support.', 'retry' => true],
            131008 => ['msg' => 'Required parameter missing from the request payload — check the API call structure.', 'retry' => false],
            131009 => ['msg' => 'Invalid parameter value, or recipient number is not a valid WhatsApp number, or sender number not added to the WABA.', 'retry' => false],
            131016 => ['msg' => 'Meta service temporarily unavailable. Check WhatsApp Platform Status page, then retry.', 'retry' => true],
            131021 => ['msg' => 'Sender and recipient phone numbers are the same — not allowed.', 'retry' => false],
            131026 => ['msg' => 'Message undeliverable — recipient may not be on WhatsApp, may have blocked the business, or has not accepted WhatsApp\'s terms.', 'retry' => false],
            131031 => ['msg' => 'Business account restricted — check WhatsApp Manager for policy/account status.', 'retry' => false],
            131047 => ['msg' => 'Outside the 24-hour session window for a free-form message. This should not occur for template sends — verify you are actually sending type=template.', 'retry' => false],
            131049 => ['msg' => 'Message blocked due to a business policy or per-user marketing message limit.', 'retry' => false],
            131051 => ['msg' => 'Unsupported message type for this recipient device or API version.', 'retry' => false],
            131053 => ['msg' => 'Meta could not download attached media from the given URL.', 'retry' => false],

            // --- Template Errors ---
            132000 => ['msg' => 'Template parameter count/type mismatch — number or type of {{1}}, {{2}}, {{3}} sent does not match what was approved.', 'retry' => false],
            132001 => ['msg' => 'Template does not exist / not approved / name-language mismatch. Recently approved templates can take a few minutes to become usable — check WHATSAPP_TEMPLATE_NAME and WHATSAPP_TEMPLATE_LANG exactly match WhatsApp Manager.', 'retry' => false],
            132005 => ['msg' => 'Translated template text exceeds the allowed character limit once variables are substituted — shorten the values you are passing in.', 'retry' => false],
            132007 => ['msg' => 'Template is paused due to low quality/negative feedback.', 'retry' => false],
            132012 => ['msg' => 'Template parameter format does not match the component type (e.g. sending text where a currency/date object was expected).', 'retry' => false],
            132015 => ['msg' => 'Template is currently disabled by Meta.', 'retry' => false],
            132016 => ['msg' => 'A required example value is missing for a template parameter used in the request.', 'retry' => false],

            // --- Phone Registration Errors ---
            133000 => ['msg' => 'Generic phone registration failure — check number is not registered elsewhere on WhatsApp/WhatsApp Business app.', 'retry' => false],
            133004 => ['msg' => 'Service unavailable during phone number registration. Retry shortly.', 'retry' => true],
            133005 => ['msg' => 'Incorrect PIN provided during two-step verification for the phone number.', 'retry' => false],
            133006 => ['msg' => 'Phone number needs to complete two-step verification setup before it can send.', 'retry' => false],
            133008 => ['msg' => 'Too many PIN attempts for this phone number\'s two-step verification — locked temporarily.', 'retry' => false],
            133009 => ['msg' => 'Two-step verification PIN was entered too soon after a previous attempt; wait before retrying.', 'retry' => true],
            133010 => ['msg' => 'Phone number is not registered on the WhatsApp Business Platform yet — finish registration in WhatsApp Manager.', 'retry' => false],
            133015 => ['msg' => 'Number was recently deleted from WhatsApp Business Platform and deletion has not finished propagating — wait ~5 minutes and retry registration.', 'retry' => true],
            133016 => ['msg' => 'Phone number registration attempts rate-limited (max 10 per 72 hrs).', 'retry' => false],

            // --- Account / Policy Errors ---
            368    => ['msg' => 'WhatsApp Business Account temporarily blocked for a policy violation — check WhatsApp Manager for the specific violation and appeal if needed.', 'retry' => false],
            130472 => ['msg' => 'Recipient number is part of a Meta marketing-message experiment; message intentionally not sent.', 'retry' => false],
            130497 => ['msg' => 'Business account restricted from messaging users in this recipient\'s country.', 'retry' => false],
            134011 => ['msg' => 'Account-level error — check Business Manager for restrictions or unresolved requirements.', 'retry' => false],

            // --- Flow Errors ---
            132068 => ['msg' => 'WhatsApp Flow error — the referenced Flow is not published or is misconfigured.', 'retry' => false],
            132069 => ['msg' => 'WhatsApp Flow token/response validation failed.', 'retry' => false],
        ];

        if (isset($map[$code])) {
            return [
                'reason'    => $map[$code]['msg'],
                'retryable' => $map[$code]['retry'],
            ];
        }

        return [
            'reason'    => "Meta error {$code}" . ($subcode ? "/{$subcode}" : '') . ": {$raw}",
            'retryable' => false,
        ];
    }

    /**
     * Termii doesn't publish numeric error codes like Meta does — only HTTP
     * status ranges (2xx/4xx/5xx) plus free-text messages. This matches on
     * both. Source: https://developers.termii.com/error
     */
    protected function interpretTermiiError(array $body, int $httpStatus): array
    {
        $msg = strtolower((string) ($body['message'] ?? $body['error'] ?? ''));

        if ($httpStatus === 401) {
            if (str_contains($msg, 'not active') || str_contains($msg, 'deactivated') || str_contains($msg, 'disabled')) {
                return ['reason' => 'Termii account is deactivated/disabled — contact Termii to reactivate.', 'retryable' => false];
            }
            return ['reason' => 'Unauthorized — check TERMII_API_KEY and that TERMII_URL uses https (not http).', 'retryable' => false];
        }

        if ($httpStatus >= 400 && $httpStatus < 500) {
            if (str_contains($msg, 'sender id') || str_contains($msg, 'senderid')) {
                return ['reason' => 'Invalid Sender ID — not registered/approved, or misspelled. Check the dashboard.', 'retryable' => false];
            }
            if (str_contains($msg, 'device not found') || str_contains($msg, 'device')) {
                return ['reason' => 'Device not registered/recognized — register the sending device on the Termii dashboard.', 'retryable' => false];
            }
            if (str_contains($msg, 'insufficient') || str_contains($msg, 'balance')) {
                return ['reason' => 'Insufficient Termii wallet balance — top up.', 'retryable' => false];
            }
            if (str_contains($msg, 'invalid') && (str_contains($msg, 'number') || str_contains($msg, 'phone') || str_contains($msg, 'msisdn'))) {
                return ['reason' => 'Invalid destination phone number format.', 'retryable' => false];
            }
            if ($httpStatus === 429 || str_contains($msg, 'rate limit') || str_contains($msg, 'too many')) {
                return ['reason' => 'Termii rate limit hit — slow send frequency.', 'retryable' => true];
            }
            if ($httpStatus === 403) {
                return ['reason' => 'Forbidden — account restricted or lacks permission for this endpoint/channel.', 'retryable' => false];
            }
            return ['reason' => 'Termii rejected the request (4xx): ' . ($msg ?: 'invalid parameters supplied'), 'retryable' => false];
        }

        if ($httpStatus >= 500) {
            return ['reason' => 'Termii server error (5xx) — rare per their docs; safe to retry.', 'retryable' => true];
        }

        return ['reason' => 'Termii error: ' . ($msg ?: 'unknown'), 'retryable' => false];
    }

    protected function formatSummaryMessage(int $studentCount, array $summary): string
    {
        $parts = ["Reminders processed for {$studentCount} student(s)."];
        foreach ($summary as $channel => $stats) {
            $parts[] = strtoupper($channel) . ": {$stats['sent']} sent, {$stats['skipped']} skipped, {$stats['failed']} failed";
        }
        return implode("\n", $parts);
    }
}