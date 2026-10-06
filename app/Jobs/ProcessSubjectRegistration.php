<?php

namespace App\Jobs;

use App\Models\JobProgress;
use App\Models\SubjectRegistration;
use App\Models\SubjectRegistrationStatus;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProcessSubjectRegistration implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    protected $validated;
    protected $chunkSize;
    protected $jobId;

    public function __construct(array $validated, int $chunkSize = 100)
    {
        $this->validated = $validated;
        $this->chunkSize = $chunkSize;
        $this->jobId = uniqid('job_');
        $this->onQueue('registrations');
    }

    public function getJobId()
    {
        return $this->jobId;
    }

    public function handle()
    {
        Log::info('Starting ProcessSubjectRegistration job', [
            'job_id' => $this->jobId,
            'validated' => $this->validated,
            'chunk_size' => $this->chunkSize,
        ]);

        $results = [];
        $successCount = 0;
        $skippedCount = 0;
        $errors = [];
        $now = now();

        $totalOperations = count($this->validated['studentid']) * count($this->validated['subjectclassid']) * count($this->validated['termid']);

        Log::info('Calculated total operations', [
            'job_id' => $this->jobId,
            'total_operations' => $totalOperations,
        ]);

        try {
            Log::info('Attempting to create JobProgress record', ['job_id' => $this->jobId]);
            JobProgress::create([
                'job_id' => $this->jobId,
                'total_operations' => $totalOperations,
                'completed_operations' => 0,
                'status' => 'pending',
            ]);
            Log::info('JobProgress record created successfully', ['job_id' => $this->jobId]);
        } catch (\Exception $e) {
            Log::error('Failed to create JobProgress record', [
                'job_id' => $this->jobId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e; // Re-throw to mark job as failed
        }

        DB::beginTransaction();
        try {
            foreach ($this->validated['studentid'] as $studentId) {
                foreach ($this->validated['termid'] as $termId) {
                    foreach ($this->validated['subjectclassid'] as $index => $subjectClassId) {
                        $staffId = $this->validated['staffid'][$index] ?? null;

                        $exists = SubjectRegistration::where([
                            ['studentid', '=', $studentId],
                            ['subjectclassid', '=', $subjectClassId],
                            ['termid', '=', $termId],
                            ['sessionid', '=', $this->validated['sessionid']],
                        ])->exists();

                        if ($exists) {
                            $skippedCount++;
                            $errors[] = "Registration already exists for student {$studentId}, subject {$subjectClassId}, term {$termId}";
                            continue;
                        }

                        SubjectRegistration::create([
                            'studentid' => $studentId,
                            'subjectclassid' => $subjectClassId,
                            'termid' => $termId,
                            'staffid' => $staffId,
                            'sessionid' => $this->validated['sessionid'],
                            'status' => SubjectRegistrationStatus::ENROLLED,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ]);

                        $successCount++;
                        $completedOperations = $successCount + $skippedCount;

                        if ($completedOperations % $this->chunkSize === 0 || $completedOperations === $totalOperations) {
                            JobProgress::where('job_id', $this->jobId)->update([
                                'completed_operations' => $completedOperations,
                                'status' => $completedOperations === $totalOperations ? 'completed' : 'processing',
                                'errors' => !empty($errors) ? json_encode($errors) : null,
                            ]);
                        }
                    }
                }
            }

            JobProgress::where('job_id', $this->jobId)->update([
                'completed_operations' => $totalOperations,
                'status' => 'completed',
                'errors' => !empty($errors) ? json_encode($errors) : null,
            ]);

            DB::commit();
            Log::info('ProcessSubjectRegistration job completed', [
                'job_id' => $this->jobId,
                'success_count' => $successCount,
                'skipped_count' => $skippedCount,
                'errors' => $errors,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            JobProgress::where('job_id', $this->jobId)->update([
                'status' => 'failed',
                'errors' => json_encode(array_merge($errors, ['Exception: ' . $e->getMessage()])),
            ]);
            Log::error('ProcessSubjectRegistration job failed', [
                'job_id' => $this->jobId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }
}