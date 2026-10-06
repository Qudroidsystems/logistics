<?php

namespace App\Jobs;

use App\Services\Notifications\ReminderService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendFeeReminderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    /**
     * Backoff in seconds between retry attempts. Only kicks in for exceptions
     * thrown out of handle() — provider-level failures (bad template, invalid
     * key) are caught inside ReminderService and logged as 'failed' rows in
     * reminder_logs without throwing, so they are NOT retried here.
     */
    public function backoff(): array
    {
        return [30, 120, 300];
    }

    public function __construct(
        public int $studentId,
        public array $channels,
        public ?int $termId = null,
        public ?int $sessionId = null,
        public ?int $sentBy = null,
    ) {}

    public function handle(ReminderService $reminders): void
    {
        $reminders->sendFeeReminders(
            [$this->studentId],
            $this->channels,
            $this->termId,
            $this->sessionId,
            $this->sentBy
        );
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('SendFeeReminderJob permanently failed', [
            'student_id' => $this->studentId,
            'channels'   => $this->channels,
            'error'      => $exception->getMessage(),
        ]);
    }
}