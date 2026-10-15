<?php

namespace App\Http\Controllers\Provider;

use App\Http\Controllers\Concerns\HandlesBackoffice;
use App\Modules\Backoffice\JobEditService;
use App\Modules\Backoffice\ManualBookingService;
use App\Modules\Backoffice\ReportService;
use App\Modules\Dispatch\ManualAssignmentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * The provider's own back office: take a booking for a customer, correct a job's details, and read reports on its own
 * jobs, drivers and money. Dispatch roles may book and edit; reports follow the money and jobs roles.
 */
class BackofficeController extends WorkspaceController
{
    use HandlesBackoffice;

    // ---------------------------------------------------------------- manual booking

    public function newBooking(Request $request)
    {
        [$op] = $this->ctx($request, 'jobs');

        return $this->view('backoffice.booking', $request, $this->bookingFormData() + [
            'action' => route('provider.booking.store'), 'priceUrl' => route('provider.booking.price'), 'back' => route('provider.dispatch'),
            'drivers' => app(ManualAssignmentService::class)->drivers($op->id), 'providers' => null, 'pagetitle' => 'New booking',
        ], 'jobs');
    }

    public function price(Request $request)
    {
        [$op] = $this->ctx($request, 'jobs');

        return $this->priceJson($request, (int) $op->id);
    }

    public function storeBooking(Request $request, ManualBookingService $svc)
    {
        [$op] = $this->ctx($request, 'jobs');
        $in = $this->bookingInput($request);
        try {
            $r = $svc->book((int) $op->id, $in, $request->user()->id);
        } catch (RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return $r['shipment']
            ? redirect()->route('provider.job', $r['shipment'])->with('success', $r['message'])
            : redirect()->route('provider.booking.new')->with('success', $r['message'])->with('payment_url', $r['payment_url'])->with('booked', $r['agreement']);
    }

    // ---------------------------------------------------------------- editing a job

    public function editJob(Request $request, string $shipment, JobEditService $svc)
    {
        [$op] = $this->ctx($request, 'jobs');
        $s = DB::table('shipments')->where('public_id', $shipment)->where('operator_id', $op->id)->first();
        abort_unless($s, 404);

        return $this->view('backoffice.edit-job', $request, $svc->load((int) $s->id) + [
            's' => $s, 'can' => JobEditService::editable($s->status), 'order' => DB::table('orders')->where('id', $s->order_id)->value('order_number'),
            'action' => route('provider.job.update', $s->public_id), 'back' => route('provider.job', $s->public_id), 'pagetitle' => 'Edit job',
        ], 'jobs');
    }

    public function updateJob(Request $request, string $shipment)
    {
        [$op] = $this->ctx($request, 'jobs');
        $s = DB::table('shipments')->where('public_id', $shipment)->where('operator_id', $op->id)->first(['id', 'public_id']);
        abort_unless($s, 404);

        return $this->saveEdit($request, (int) $s->id, 'dispatcher', route('provider.job', $s->public_id));
    }

    // ---------------------------------------------------------------- reports

    /** Owners and admins see everything; finance sees the money reports, dispatchers the operational ones. */
    private function allowedReports(string $role): array
    {
        $money = ['daily', 'monthly', 'customers', 'retention', 'profit'];
        $ops = ['drivers', 'routes', 'failed', 'times', 'fleet'];

        return array_values(array_intersect(array_keys(ReportService::REPORTS), match ($role) {
            'owner', 'admin' => array_merge($money, $ops), 'finance' => $money, 'dispatcher', 'driver_manager' => array_merge($ops, ['daily']), default => [],
        }));
    }

    public function reports(Request $request, ReportService $reports, ?string $report = null)
    {
        [$op, $role] = $this->ctx($request);
        $allowed = $this->allowedReports($role);
        abort_unless($allowed, 403, 'Your role does not include reports.');
        $report ??= $allowed[0];
        abort_unless(in_array($report, $allowed, true), isset(ReportService::REPORTS[$report]) ? 403 : 404);
        $range = $this->reportRange($request);

        return $this->view('backoffice.reports', $request, [
            'report' => $report, 'allowed' => $allowed, 'table' => $reports->table($report, (int) $op->id, ...$range), 'from' => $range[0], 'to' => $range[1],
            'routeName' => 'provider.reports', 'exportRoute' => 'provider.reports.export', 'canExport' => true,
            'providers' => null, 'provider' => null, 'scope' => $op->display_name, 'pagetitle' => 'Reports',
        ]);
    }

    public function export(Request $request, ReportService $reports, string $report, string $format)
    {
        [$op, $role] = $this->ctx($request);
        abort_unless(in_array($report, $this->allowedReports($role), true), 403);

        return $this->exportReport($report, $format, $reports, (int) $op->id, $this->reportRange($request), $op->display_name);
    }
}
