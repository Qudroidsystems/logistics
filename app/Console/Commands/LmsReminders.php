<?php

namespace App\Console\Commands;

use App\Models\LmsAssignment;
use App\Models\LmsLiveClass;
use App\Models\LmsQuiz;
use App\Services\Messaging\MessagingService;
use App\Services\Messaging\PortalNotifier;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Reminds enrolled learners about assignments/quizzes due soon and upcoming live
 * classes — always via the portal bell, and optionally over SMS/WhatsApp/email
 * (--channels=sms,whatsapp) using the school's MessagingService.
 *
 *   php artisan lms:reminders            # bell only, 48h window
 *   php artisan lms:reminders --channels=sms,whatsapp --hours=24
 */
class LmsReminders extends Command
{
    protected $signature = 'lms:reminders {--hours=48 : Due-window in hours} {--live-hours=3 : Live-class window} {--channels= : Extra channels, comma-separated (sms,whatsapp,email)}';
    protected $description = 'Send LMS due-date and live-class reminders to learners';

    public function handle(): int
    {
        if (!Schema::hasTable('lms_enrollments')) { $this->warn('LMS not installed.'); return self::SUCCESS; }

        $hours = max(1, (int) $this->option('hours'));
        $liveHours = max(1, (int) $this->option('live-hours'));
        $channels = array_values(array_filter(array_map('trim', explode(',', (string) $this->option('channels')))));
        $now = now();

        $sent = 0;

        // ── Assignments due soon (to those who haven't submitted) ──────────
        $assignments = LmsAssignment::with('course')
            ->where('is_published', true)->whereNotNull('due_at')
            ->whereBetween('due_at', [$now, (clone $now)->addHours($hours)])->get();
        foreach ($assignments as $a) {
            if (!$a->course || !$a->course->is_published) continue;
            $enrolled = $this->enrolledIds($a->course_id);
            $submitted = DB::table('lms_assignment_submissions')->where('assignment_id', $a->id)
                ->pluck('student_id')->map(fn ($v) => (int) $v)->all();
            $targets = array_values(array_diff($enrolled, $submitted));
            $sent += $this->notify($a->course, $targets,
                'Assignment due soon',
                "\"{$a->title}\" is due {$a->due_at->format('D d M, H:i')}.",
                'lms-due-a-' . $a->id . '-' . $a->due_at->toDateString(), $channels);
        }

        // ── Quizzes closing soon ──────────────────────────────────────────
        $quizzes = LmsQuiz::with('course')
            ->where('is_published', true)->whereNotNull('available_until')
            ->whereBetween('available_until', [$now, (clone $now)->addHours($hours)])->get();
        foreach ($quizzes as $q) {
            if (!$q->course || !$q->course->is_published) continue;
            $sent += $this->notify($q->course, $this->enrolledIds($q->course_id),
                'Quiz closing soon',
                "\"{$q->title}\" closes {$q->available_until->format('D d M, H:i')}.",
                'lms-quiz-' . $q->id . '-' . $q->available_until->toDateString(), $channels);
        }

        // ── Live classes starting soon ────────────────────────────────────
        $lives = LmsLiveClass::with('course')
            ->where('status', 'scheduled')->whereNotNull('scheduled_at')
            ->whereBetween('scheduled_at', [$now, (clone $now)->addHours($liveHours)])->get();
        foreach ($lives as $lc) {
            if (!$lc->course || !$lc->course->is_published) continue;
            $sent += $this->notify($lc->course, $this->enrolledIds($lc->course_id),
                'Live class starting soon',
                "\"{$lc->title}\" starts {$lc->scheduled_at->format('D d M, H:i')}." . ($lc->join_url ? ' Join: ' . $lc->join_url : ''),
                'lms-live-' . $lc->id . '-' . $lc->scheduled_at->format('YmdHi'), $channels);
        }

        $this->info("LMS reminders processed. Notifications sent: {$sent}.");
        return self::SUCCESS;
    }

    protected function enrolledIds(int $courseId): array
    {
        return DB::table('lms_enrollments')->where('course_id', $courseId)->where('status', '!=', 'dropped')
            ->pluck('student_id')->map(fn ($v) => (int) $v)->all();
    }

    /** Bell to each learner's user, plus optional messaging channels. Returns count. */
    protected function notify($course, array $studentIds, string $title, string $body, string $key, array $channels): int
    {
        $studentIds = array_values(array_filter(array_unique(array_map('intval', $studentIds))));
        if (!$studentIds) return 0;

        $url = route('lms.learn.show', $course);
        $count = 0;

        // portal bell (idempotent per key)
        try {
            $userIds = DB::table('users')->whereIn('student_id', $studentIds)->pluck('id')->map(fn ($v) => (int) $v)->all();
            if ($userIds) $count += PortalNotifier::toUsers($userIds, $title, $body, $url, 'notice', $key);
        } catch (\Throwable $e) {}

        // optional SMS / WhatsApp / email to the students themselves
        if ($channels) {
            $students = DB::table('studentRegistration')->whereIn('id', $studentIds)
                ->get(['id', 'phone_number', 'email']);
            $msg = new MessagingService();
            foreach ($students as $s) {
                foreach ($channels as $ch) {
                    $to = in_array($ch, ['sms', 'whatsapp'], true) ? $s->phone_number : $s->email;
                    if (!$to) continue;
                    try { $msg->send($ch, $to, $body, ['type' => 'lms', 'title' => $title]); }
                    catch (\Throwable $e) {}
                }
            }
        }

        return $count;
    }
}
