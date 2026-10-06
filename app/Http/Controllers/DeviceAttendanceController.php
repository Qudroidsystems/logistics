<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DeviceAttendanceLog;
use App\Services\DeviceAttendanceProcessor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Ingestion endpoint the Python agent (running on the school LAN) POSTs to,
 * in batches of ~25 records at a time.
 * Protected by the 'device.auth' middleware (X-Device-Key header).
 *
 * Route (routes/api.php):
 *   Route::middleware('device.auth')->post('/device/attendance', [DeviceAttendanceController::class, 'store']);
 */
class DeviceAttendanceController extends Controller
{
    public function store(Request $request, DeviceAttendanceProcessor $processor)
    {
        $validated = $request->validate([
            'device_serial'       => 'required|string',
            'logs'                => 'required|array|min:1',
            'logs.*.pin'          => 'required|integer',
            'logs.*.timestamp'    => 'required|date',
            'logs.*.verify_mode'  => 'nullable|integer',
            'logs.*.status_code'  => 'nullable|integer',
        ]);

        $inserted = 0;
        $skipped  = 0;
        $errors   = 0;

        foreach ($validated['logs'] as $log) {
            try {
                $record = DeviceAttendanceLog::firstOrCreate(
                    [
                        'device_serial' => $validated['device_serial'],
                        'device_pin'    => $log['pin'],
                        'punch_time'    => $log['timestamp'],
                    ],
                    [
                        'verify_mode' => $log['verify_mode'] ?? null,
                        'status_code' => $log['status_code'] ?? null,
                    ]
                );

                if ($record->wasRecentlyCreated) {
                    $inserted++;
                    // Synchronous at current volume (~1,200 mapped users). Move to a
                    // queued Job (ProcessDeviceAttendanceLog) if punch volume grows.
                    $processor->process($record);
                } else {
                    $skipped++;
                }
            } catch (\Exception $e) {
                $errors++;
                Log::error('Device attendance ingest error: ' . $e->getMessage());
            }
        }

        return response()->json([
            'success'  => true,
            'inserted' => $inserted,
            'skipped'  => $skipped,
            'errors'   => $errors,
        ]);
    }
}