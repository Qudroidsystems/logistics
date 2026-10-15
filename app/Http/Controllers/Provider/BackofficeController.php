<?php

namespace App\Http\Controllers\Provider;

use App\Http\Controllers\Concerns\HandlesBackoffice;
use App\Modules\Backoffice\BulkBookingService;
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
            'pickupPoints' => array_filter(app(\App\Modules\Returns\PickupPointService::class)->all((int) $op->id), fn ($p) => $p->status === 'active'),
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

    // ---------------------------------------------------------------- bulk booking

    public function bulk(Request $request)
    {
        [$op] = $this->ctx($request, 'jobs');

        return $this->view('backoffice.bulk', $request, $this->bookingFormData() + [
            'batches' => DB::table('bulk_batches')->where('operator_id', $op->id)->where('status', '!=', 'discarded')->orderByDesc('id')->limit(20)->get(),
            'columns' => BulkBookingService::COLUMNS, 'maxRows' => BulkBookingService::MAX_ROWS, 'pagetitle' => 'Bulk booking',
        ], 'jobs');
    }

    public function bulkTemplate(Request $request)
    {
        $this->ctx($request, 'jobs');

        return response(BulkBookingService::template(), 200, ['Content-Type' => 'text/csv', 'Content-Disposition' => 'attachment; filename="bulk-booking-template.csv"']);
    }

    public function bulkCheck(Request $request, BulkBookingService $svc)
    {
        [$op] = $this->ctx($request, 'jobs');
        $d = $request->validate([
            'file' => 'required|file|max:2048', 'service_type_id' => 'required|integer|exists:service_types,id', 'city_id' => 'required|integer|exists:cities,id',
            'vehicle_type_id' => 'nullable|integer|exists:vehicle_types,id', 'payment' => 'required|in:'.implode(',', array_keys(\App\Modules\Backoffice\ManualBookingService::PAYMENTS)),
            'customer.name' => 'nullable|string|max:120', 'customer.phone' => 'nullable|string|max:24', 'customer.email' => 'nullable|email:rfc|max:190',
            'pickup.line1' => 'nullable|string|max:255', 'pickup.lat' => 'nullable|required_with:pickup.line1|numeric|between:-90,90',
            'pickup.lng' => 'nullable|required_with:pickup.line1|numeric|between:-180,180', 'pickup.contact_phone' => 'nullable|string|max:24',
        ], ['file.required' => 'Choose the spreadsheet (CSV) to upload.']);
        $defaults = [
            'service_type_id' => (int) $d['service_type_id'], 'city_id' => (int) $d['city_id'], 'vehicle_type_id' => isset($d['vehicle_type_id']) ? (int) $d['vehicle_type_id'] : null,
            'payment' => $d['payment'], 'customer' => array_filter($d['customer'] ?? [], fn ($v) => $v !== null && $v !== ''),
            'pickup' => array_filter(['line1' => $d['pickup']['line1'] ?? null, 'lat' => isset($d['pickup']['lat']) ? (float) $d['pickup']['lat'] : null,
                'lng' => isset($d['pickup']['lng']) ? (float) $d['pickup']['lng'] : null, 'contact_phone' => $d['pickup']['contact_phone'] ?? null], fn ($v) => $v !== null && $v !== ''),
        ];
        try {
            $batch = $svc->check((int) $op->id, $request->user()->id, (string) file_get_contents($request->file('file')->getRealPath()), $defaults, $request->file('file')->getClientOriginalName());
        } catch (RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('provider.bulk.show', $batch);
    }

    public function bulkShow(Request $request, string $batch)
    {
        [$op] = $this->ctx($request, 'jobs');
        $b = DB::table('bulk_batches')->where(['public_id' => $batch, 'operator_id' => $op->id])->first();
        abort_unless($b, 404);
        $rows = DB::table('bulk_batch_rows as r')->leftJoin('shipments as s', 's.id', '=', 'r.shipment_id')->leftJoin('agreements as a', 'a.id', '=', 'r.agreement_id')
            ->where('r.batch_id', $b->id)->orderBy('r.line')->get(['r.*', 's.public_id as shipment_public_id', 's.tracking_code', 'a.number as agreement_number', 'a.price']);

        return $this->view('backoffice.bulk-batch', $request, ['b' => $b, 'rows' => $rows, 'defaults' => json_decode($b->defaults, true), 'pagetitle' => 'Bulk booking'], 'jobs');
    }

    public function bulkBook(Request $request, string $batch, BulkBookingService $svc)
    {
        [$op] = $this->ctx($request, 'jobs');
        try {
            $r = $svc->book((int) $op->id, $batch, $request->user()->id);
        } catch (RuntimeException $e) {
            return redirect()->route('provider.bulk.show', $batch)->with('error', $e->getMessage());
        }
        $msg = "{$r['booked']} ".($r['booked'] === 1 ? 'delivery' : 'deliveries').' booked.'.($r['failed'] ? " {$r['failed']} could not be booked; the reason is shown on each." : '');

        return redirect()->route('provider.bulk.show', $batch)->with($r['failed'] ? 'error' : 'success', $msg);
    }

    public function bulkDiscard(Request $request, string $batch, BulkBookingService $svc)
    {
        [$op] = $this->ctx($request, 'jobs');
        try {
            $svc->discard((int) $op->id, $batch);
        } catch (RuntimeException $e) {
            return redirect()->route('provider.bulk.show', $batch)->with('error', $e->getMessage());
        }

        return redirect()->route('provider.bulk')->with('success', 'Upload discarded. Nothing was booked.');
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
