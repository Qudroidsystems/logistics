<?php

namespace App\Http\Controllers;

use App\Models\Exam;
use App\Models\ExamAttempt;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Broadcast;

class ExamPauseController extends Controller
{
    public function pause(Exam $exam)
    {
        try {
            // Pause all active (non-submitted) attempts for this exam
            $activeAttempts = ExamAttempt::active()
                ->where('exam_id', $exam->id)
                ->whereNull('paused_at') // Not already paused
                ->get();

            foreach ($activeAttempts as $attempt) {
                $attempt->update(['paused_at' => now()]);
            }

            $count = $activeAttempts->count();
            Log::info("Exam paused by admin: Exam ID {$exam->id}, attempts affected: {$count}");

            // Notify clients (WebSocket or email)
            $this->notifyPausedAttempts($activeAttempts, $exam);

            return response()->json([
                'success' => true,
                'message' => "Exam paused for {$count} active attempts"
            ]);
        } catch (\Exception $e) {
            Log::error("Pause exam failed: {$e->getMessage()}", ['exam_id' => $exam->id]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to pause exam: ' . $e->getMessage()
            ], 500);
        }
    }

    public function resume(Exam $exam)
    {
        try {
            // Resume all paused attempts for this exam
            $pausedAttempts = ExamAttempt::paused()
                ->where('exam_id', $exam->id)
                ->get();

            $totalPauseSeconds = 0;
            foreach ($pausedAttempts as $attempt) {
                $pauseStart = $attempt->paused_at;
                $pauseEnd = now();
                $pauseSeconds = $pauseStart->diffInSeconds($pauseEnd);

                $attempt->update([
                    'resumed_at' => $pauseEnd,
                    'pause_duration' => $attempt->pause_duration + $pauseSeconds
                ]);

                $totalPauseSeconds += $pauseSeconds;
            }

            $count = $pausedAttempts->count();
            Log::info("Exam resumed by admin: Exam ID {$exam->id}, attempts affected: {$count}, added pause time: {$totalPauseSeconds}s");

            // Notify clients
            $this->notifyResumedAttempts($pausedAttempts, $exam);

            return response()->json([
                'success' => true,
                'message' => "Exam resumed for {$count} attempts. Added {$totalPauseSeconds}s pause time."
            ]);
        } catch (\Exception $e) {
            Log::error("Resume exam failed: {$e->getMessage()}", ['exam_id' => $exam->id]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to resume exam: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get exam status (for client polling)
     */
    public function status(Exam $exam)
    {
        $isPaused = ExamAttempt::active()
            ->where('exam_id', $exam->id)
            ->paused()
            ->exists();

        // For current user (assume auth()->user()->activeAttempt or similar)
        $userAttempt = auth()->user()->activeExamAttempt(); // Add this method to User model if needed
        $remainingTime = $userAttempt ? $userAttempt->effective_remaining_time : 0;

        return response()->json([
            'paused' => $isPaused,
            'remaining_time' => $remainingTime
        ]);
    }

    private function notifyPausedAttempts($attempts, $exam)
    {
        // Broadcast via Laravel Echo/WebSockets (optional)
        if (class_exists(Broadcast::class)) {
            broadcast(new \App\Events\ExamPaused($exam->id))->toOthers();
        }

        // Optional: Email students
        // foreach ($attempts as $attempt) {
        //     Mail::to($attempt->student->email)->send(new \App\Mail\ExamPausedMail($exam));
        // }
    }

    private function notifyResumedAttempts($attempts, $exam)
    {
        if (class_exists(Broadcast::class)) {
            broadcast(new \App\Events\ExamResumed($exam->id))->toOthers();
        }

        // Optional: Email
        // foreach ($attempts as $attempt) {
        //     Mail::to($attempt->student->email)->send(new \App\Mail\ExamResumedMail($exam));
        // }
    }
}
