<?php

namespace App\Http\Controllers;

use App\Models\JobProgress;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class JobStatusController extends Controller
{
    public function show($jobId): JsonResponse
    {
        try {
            $jobProgress = JobProgress::where('job_id', $jobId)->first();

            if (!$jobProgress) {
                Log::warning('Job progress not found', ['job_id' => $jobId]);
                return response()->json([
                    'success' => false,
                    'message' => 'Job not found',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'job_id' => $jobProgress->job_id,
                    'status' => $jobProgress->status,
                    'completed_operations' => $jobProgress->completed_operations,
                    'total_operations' => $jobProgress->total_operations,
                    'progress' => $jobProgress->total_operations > 0 
                        ? round(($jobProgress->completed_operations / $jobProgress->total_operations) * 100, 2) 
                        : 0,
                    'errors' => $jobProgress->errors ? json_decode($jobProgress->errors, true) : null,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching job status', [
                'job_id' => $jobId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch job status: ' . $e->getMessage(),
            ], 500);
        }
    }
}