<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DeviceAttendanceLog;
use App\Services\DeviceAttendanceProcessor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DeviceAttendanceController extends Controller
{
    /**
     * Receive attendance records from the ZKTeco Python agent.
     *
     * The Python agent sends small batches, normally 25 records.
     *
     * Responsibilities:
     *
     * 1. Validate the incoming request.
     * 2. Store raw device punches.
     * 3. Prevent duplicate raw punches.
     * 4. Process newly inserted punches.
     * 5. Re-process previous error records when necessary.
     * 6. Return a compact response to the Python agent.
     */
    public function store(
        Request $request,
        DeviceAttendanceProcessor $processor
    ) {
        $validated = $request->validate([
            'device_serial' => [
                'required',
                'string',
                'max:100',
            ],

            'logs' => [
                'required',
                'array',
                'min:1',
                'max:100',
            ],

            'logs.*.pin' => [
                'required',
                'integer',
            ],

            'logs.*.timestamp' => [
                'required',
                'date',
            ],

            'logs.*.verify_mode' => [
                'nullable',
                'integer',
            ],

            'logs.*.status_code' => [
                'nullable',
                'integer',
            ],
        ]);

        $deviceSerial = $validated['device_serial'];

        $inserted = 0;
        $skipped = 0;
        $processed = 0;
        $errors = 0;

        /*
         * Keep detailed errors out of the normal response.
         * They are logged on the server.
         */
        $errorDetails = [];

        /*
         * Process the batch inside a transaction for the
         * database insertion stage.
         *
         * The processor itself handles attendance-specific
         * failures and updates processing_status.
         */
        DB::beginTransaction();

        try {

            foreach ($validated['logs'] as $logData) {

                try {

                    /*
                     * Find an existing raw punch first.
                     *
                     * This prevents duplicate records when
                     * the Python agent retries a batch.
                     */
                    $record = DeviceAttendanceLog::where(
                            'device_serial',
                            $deviceSerial
                        )
                        ->where(
                            'device_pin',
                            $logData['pin']
                        )
                        ->where(
                            'punch_time',
                            $logData['timestamp']
                        )
                        ->first();

                    /*
                     * --------------------------------------------------
                     * NEW RECORD
                     * --------------------------------------------------
                     */
                    if (!$record) {

                        $record = DeviceAttendanceLog::create([
                            'device_serial' => $deviceSerial,
                            'device_pin' => $logData['pin'],
                            'punch_time' => $logData['timestamp'],
                            'verify_mode' => $logData['verify_mode'] ?? null,
                            'status_code' => $logData['status_code'] ?? null,

                            /*
                             * If your model/database has a default,
                             * this can be omitted.
                             *
                             * Setting pending explicitly makes the
                             * processing state clear.
                             */
                            'processing_status' => 'pending',
                            'process_note' => null,
                        ]);

                        // FIXED: This was incorrectly written as $inserted = $inserted++;
                        $inserted++;

                        /*
                         * Process the newly created record.
                         */
                        $processor->process($record);

                        if (
                            $record->fresh()?->processing_status === 'processed'
                        ) {
                            $processed++;
                        } else {
                            $currentStatus = $record
                                ->fresh()
                                ?->processing_status;

                            if (
                                $currentStatus === 'error'
                                || $currentStatus === 'unmapped'
                            ) {
                                $errors++;

                                $errorDetails[] = [
                                    'pin' => $logData['pin'],
                                    'timestamp' => $logData['timestamp'],
                                    'status' => $currentStatus,
                                ];
                            }
                        }

                        continue;
                    }

                    /*
                     * --------------------------------------------------
                     * EXISTING RECORD
                     * --------------------------------------------------
                     *
                     * This normally happens because Python retried
                     * a batch that Laravel already received.
                     *
                     * Do NOT process an already successfully processed
                     * punch again.
                     */
                    if (
                        $record->processing_status === 'processed'
                    ) {
                        $skipped++;
                        continue;
                    }

                    /*
                     * --------------------------------------------------
                     * EXISTING BUT NOT SUCCESSFULLY PROCESSED
                     * --------------------------------------------------
                     *
                     * If the previous attempt resulted in an error,
                     * give the processor another chance.
                     *
                     * This is useful when a temporary database/config
                     * problem occurred during the first attempt.
                     */
                    if (
                        in_array(
                            $record->processing_status,
                            [
                                'pending',
                                'error',
                            ],
                            true
                        )
                    ) {

                        $processor->process(
                            $record->fresh()
                        );

                        $freshRecord = $record->fresh();

                        if (
                            $freshRecord?->processing_status === 'processed'
                        ) {
                            $processed++;
                        } else {
                            $errors++;

                            $errorDetails[] = [
                                'pin' => $logData['pin'],
                                'timestamp' => $logData['timestamp'],
                                'status' => $freshRecord?->processing_status,
                                'note' => $freshRecord?->process_note,
                            ];
                        }

                        continue;
                    }

                    /*
                     * --------------------------------------------------
                     * UNMAPPED
                     * --------------------------------------------------
                     *
                     * Leave unmapped punches alone.
                     *
                     * Your DeviceUserMappingController already has
                     * reprocessPendingLogs() for this purpose when an
                     * administrator maps the PIN later.
                     */
                    if (
                        $record->processing_status === 'unmapped'
                    ) {
                        $skipped++;
                        continue;
                    }

                    $skipped++;

                } catch (\Throwable $e) {

                    $errors++;

                    $errorDetails[] = [
                        'pin' => $logData['pin'] ?? null,
                        'timestamp' => $logData['timestamp'] ?? null,
                        'error' => $e->getMessage(),
                    ];

                    Log::error(
                        'Device attendance ingest error',
                        [
                            'device_serial' => $deviceSerial,
                            'device_pin' => $logData['pin'] ?? null,
                            'punch_time' => $logData['timestamp'] ?? null,
                            'error' => $e->getMessage(),
                        ]
                    );
                }
            }

            DB::commit();

        } catch (\Throwable $e) {

            DB::rollBack();

            Log::error(
                'Device attendance batch failed',
                [
                    'device_serial' => $deviceSerial,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]
            );

            return response()->json([
                'success' => false,
                'message' => 'Attendance batch could not be processed.',
            ], 500);
        }

        /*
         * Log a compact server-side summary.
         */
        Log::info(
            'Device attendance batch processed',
            [
                'device_serial' => $deviceSerial,
                'received' => count($validated['logs']),
                'inserted' => $inserted,
                'skipped' => $skipped,
                'processed' => $processed,
                'errors' => $errors,
            ]
        );

        /*
         * Return a small JSON response.
         *
         * The Python agent only needs the HTTP success status
         * to mark this batch as synchronized.
         */
        return response()->json([
            'success' => true,

            'device_serial' => $deviceSerial,

            'received' => count($validated['logs']),

            'inserted' => $inserted,

            'processed' => $processed,

            'skipped' => $skipped,

            'errors' => $errors,

            /*
             * Useful while debugging, but limited so that a bad
             * batch doesn't produce a huge response.
             */
            'error_details' => array_slice(
                $errorDetails,
                0,
                10
            ),
        ], 200);
    }
}