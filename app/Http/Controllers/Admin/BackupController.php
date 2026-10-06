<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BackupSetting;
use App\Models\DatabaseBackup;
use App\Services\Backup\DatabaseBackupService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class BackupController extends Controller
{
    public function __construct(protected DatabaseBackupService $svc)
    {
        $this->middleware('auth');
        $this->middleware('permission:Manage backups');
    }

    public function index()
    {
        $settings = BackupSetting::current();
        $backups = DatabaseBackup::orderByDesc('created_at')->paginate(15);
        $successful = DatabaseBackup::where('status', 'success')->get();

        return view('admin.backups.index', [
            'pagetitle' => 'Database Backups',
            'settings'  => $settings,
            'backups'   => $backups,
            'stats'     => [
                'count'     => $successful->count(),
                'total'     => $successful->sum('size'),
                'last'      => $successful->sortByDesc('created_at')->first(),
                'next'      => $this->nextRun($settings),
            ],
        ]);
    }

    public function run(Request $request)
    {
        @set_time_limit(600);
        $result = $this->svc->run('manual', (int) $request->user()->id, $request->boolean('email'));
        return back()->with($result['ok'] ? 'success' : 'error', $result['message']);
    }

    public function download(DatabaseBackup $backup)
    {
        abort_unless($backup->exists(), 404, 'This backup file is no longer on the server.');
        return Storage::disk($backup->disk)->download($backup->path, $backup->filename);
    }

    public function destroy(DatabaseBackup $backup)
    {
        try { Storage::disk($backup->disk)->delete($backup->path); } catch (\Throwable $e) {}
        $backup->delete();
        return back()->with('success', 'Backup deleted.');
    }

    public function saveSettings(Request $request)
    {
        $d = $request->validate([
            'enabled'      => 'nullable|boolean',
            'frequency'    => 'required|in:daily,weekly,monthly',
            'day_of_week'  => 'nullable|integer|min:0|max:6',
            'day_of_month' => 'nullable|integer|min:1|max:28',
            'run_time'     => 'required|date_format:H:i',
            'email'        => 'nullable|email',
            'email_attach' => 'nullable|boolean',
            'keep_last'    => 'required|integer|min:1|max:365',
        ]);

        $s = BackupSetting::current();
        $s->update([
            'enabled'      => $request->boolean('enabled'),
            'frequency'    => $d['frequency'],
            'day_of_week'  => (int) ($d['day_of_week'] ?? 1),
            'day_of_month' => (int) ($d['day_of_month'] ?? 1),
            'run_time'     => $d['run_time'],
            'email'        => $d['email'] ?? null,
            'email_attach' => $request->boolean('email_attach'),
            'keep_last'    => (int) $d['keep_last'],
            'updated_by'   => $request->user()->id,
        ]);

        return back()->with('success', 'Backup schedule saved.');
    }

    protected function nextRun(BackupSetting $s): ?string
    {
        if (!$s->enabled) return null;
        [$h, $m] = array_pad(explode(':', $s->run_time), 2, 0);
        $next = Carbon::now()->setTime((int) $h, (int) $m, 0);
        if ($s->frequency === 'daily') {
            if ($next->isPast()) $next->addDay();
        } elseif ($s->frequency === 'weekly') {
            $next = $next->next($s->day_of_week);
        } else {
            $next->day = min((int) $s->day_of_month, 28);
            if ($next->isPast()) $next->addMonthNoOverflow();
        }
        return $next->toDayDateTimeString();
    }
}
