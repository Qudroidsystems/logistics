<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Modules\System\SystemHealth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

/** Operations > System: is background work running, the queue, failed jobs, and which services are set up. */
class SystemController extends Controller
{
    private function check(Request $request): void
    {
        abort_unless($request->user()->can('View system health'), 403);
    }

    public function index(Request $request, SystemHealth $health)
    {
        $this->check($request);

        return view('ops.system', $health->page() + ['errorReports' => app(\App\Modules\System\ErrorReports::class)->open(), 'pagetitle' => 'System']);
    }

    public function retry(Request $request, string $uuid)
    {
        $this->check($request);
        abort_unless(DB::table('failed_jobs')->where('uuid', $uuid)->exists(), 404);
        Artisan::call('queue:retry', ['id' => [$uuid]]);

        return back()->with('success', 'Sent back to the queue.');
    }

    public function resolveError(Request $request, int $error)
    {
        $this->check($request);
        app(\App\Modules\System\ErrorReports::class)->resolve($error);

        return back()->with('success', 'Marked as fixed. You will be told if it happens again.');
    }

    public function forget(Request $request, string $uuid)
    {
        $this->check($request);
        DB::table('failed_jobs')->where('uuid', $uuid)->delete();

        return back()->with('success', 'Removed.');
    }
}
