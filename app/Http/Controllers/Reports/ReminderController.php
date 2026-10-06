<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Jobs\SendFeeReminderJob;
use App\Services\Notifications\ReminderService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ReminderController extends Controller
{
    public function __construct(
        protected ReminderService $reminders
    ) {
        $this->middleware('permission:View financial reports');
        // Or a dedicated permission: Send payment reminders
    }

    public function sendReminders(Request $request)
    {
        $data = $request->validate([
            'student_ids'   => 'required|array|min:1',
            'student_ids.*' => 'integer',
            'term_id'       => 'nullable|integer',
            'session_id'    => 'nullable|integer',
            'channels'      => 'required|array|min:1',
            'channels.*'    => 'in:email,sms,whatsapp',
        ]);

        $threshold = (int) config('reminders.sync_batch_limit', 15);

        // Small batch: send now, return a real summary the admin sees immediately.
        if (count($data['student_ids']) <= $threshold) {
            try {
                $result = $this->reminders->sendFeeReminders(
                    $data['student_ids'],
                    $data['channels'],
                    $data['term_id'] ?? null,
                    $data['session_id'] ?? null
                );

                return response()->json([
                    'success' => true,
                    'message' => $result['message'],
                    'summary' => $result['summary'],
                ]);
            } catch (\Throwable $e) {
                Log::error('Send reminders failed: ' . $e->getMessage());
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to send reminders: ' . $e->getMessage(),
                ], 500);
            }
        }

        // Large batch: queue one job per student so the request returns
        // instantly and no send is lost to a PHP execution-time cutoff.
        $sentBy = Auth::id();

        foreach ($data['student_ids'] as $studentId) {
            SendFeeReminderJob::dispatch(
                $studentId,
                $data['channels'],
                $data['term_id'] ?? null,
                $data['session_id'] ?? null,
                $sentBy
            );
        }

        return response()->json([
            'success' => true,
            'message' => count($data['student_ids']) . ' reminder(s) queued for sending — check the reminder logs shortly for delivery status.',
            'queued'  => true,
        ]);
    }
}