<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\HandlesBackoffice;
use App\Http\Controllers\Controller;
use App\Modules\Backoffice\JobEditService;
use App\Modules\Backoffice\ManualBookingService;
use App\Modules\Backoffice\ReportService;
use App\Modules\Providers\ProviderOnboardingService;
use App\Support\Platform;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Staff console back office: book a delivery for a customer, correct a job, and the business reports.
 *
 * In company mode every booking goes to the house operator; otherwise staff choose the provider. Reports cover the
 * whole platform, or one provider when one is picked. Permissions are checked on the routes in routes/ops.php, and per
 * report here.
 */
class BackofficeController extends Controller
{
    use HandlesBackoffice;

    /** @return \Illuminate\Support\Collection<int, object> active providers staff may book with */
    private function providers()
    {
        return DB::table('operators')->whereIn('type', ProviderOnboardingService::TYPES)->where('status', 'active')
            ->when(Platform::companyOnly(), fn ($q) => $q->where('id', Platform::houseOperatorId()))
            ->orderBy('display_name')->get(['id', 'public_id', 'display_name', 'type']);
    }

    private function operatorFor(Request $request): int
    {
        if (Platform::companyOnly()) {
            return Platform::houseOperatorId();
        }
        $id = DB::table('operators')->where('public_id', (string) $request->input('provider'))->whereIn('type', ProviderOnboardingService::TYPES)->where('status', 'active')->value('id');
        if (! $id) {
            throw new RuntimeException('Choose the provider that will do this delivery.');
        }

        return (int) $id;
    }

    // ---------------------------------------------------------------- manual booking

    public function newBooking()
    {
        return view('backoffice.booking', $this->bookingFormData() + [
            'action' => route('ops.booking.store'), 'priceUrl' => route('ops.booking.price'), 'back' => route('ops.orders'),
            'drivers' => [], 'providers' => Platform::companyOnly() ? null : $this->providers(), 'pagetitle' => 'New booking',
        ]);
    }

    public function price(Request $request)
    {
        try {
            $op = $this->operatorFor($request);
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return $this->priceJson($request, $op);
    }

    public function storeBooking(Request $request, ManualBookingService $svc)
    {
        $in = $this->bookingInput($request);
        $in['driver_user_id'] = null; // staff do not pick a provider's drivers; the provider's dispatch does
        try {
            $r = $svc->book($this->operatorFor($request), $in, $request->user()->id);
        } catch (RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
        $order = $r['order_id'] ? DB::table('orders')->where('id', $r['order_id'])->value('public_id') : null;

        return $order
            ? redirect()->route('ops.order', $order)->with('success', $r['message'])
            : redirect()->route('ops.booking.new')->with('success', $r['message'])->with('payment_url', $r['payment_url'])->with('booked', $r['agreement']);
    }

    // ---------------------------------------------------------------- editing a job

    private function shipmentOf(string $order): object
    {
        $s = DB::table('shipments as s')->join('orders as o', 'o.id', '=', 's.order_id')->where('o.public_id', $order)->where('s.kind', 'forward')
            ->first(['s.*', 'o.order_number', 'o.public_id as order_public_id']);
        abort_unless($s, 404);

        return $s;
    }

    public function editJob(string $order, JobEditService $svc)
    {
        $s = $this->shipmentOf($order);

        return view('backoffice.edit-job', $svc->load((int) $s->id) + [
            's' => $s, 'can' => JobEditService::editable($s->status), 'order' => $s->order_number,
            'action' => route('ops.order.update', $order), 'back' => route('ops.order', $order), 'pagetitle' => 'Edit job',
        ]);
    }

    public function updateJob(Request $request, string $order)
    {
        return $this->saveEdit($request, (int) $this->shipmentOf($order)->id, 'staff', route('ops.order', $order));
    }

    // ---------------------------------------------------------------- reports

    private function allowedReports(Request $request): array
    {
        $u = $request->user();
        if ($u->can('View reports')) {
            return array_keys(ReportService::REPORTS);
        }

        return array_keys(array_filter(ReportService::PERMISSION, fn ($p) => $u->can($p)));
    }

    /** The provider to narrow to (marketplace installs only); company mode reports on the whole install. */
    private function providerFilter(Request $request): ?object
    {
        if (Platform::companyOnly() || ! $request->filled('provider')) {
            return null;
        }

        return DB::table('operators')->where('public_id', (string) $request->query('provider'))->first(['id', 'public_id', 'display_name']);
    }

    public function reports(Request $request, ReportService $reports, ?string $report = null)
    {
        $allowed = $this->allowedReports($request);
        abort_unless($allowed, 403);
        $report ??= $allowed[0];
        abort_unless(isset(ReportService::REPORTS[$report]), 404);
        abort_unless(in_array($report, $allowed, true), 403);
        $range = $this->reportRange($request);
        $p = $this->providerFilter($request);

        return view('backoffice.reports', [
            'report' => $report, 'allowed' => $allowed, 'table' => $reports->table($report, $p?->id, ...$range), 'from' => $range[0], 'to' => $range[1],
            'routeName' => 'ops.reports', 'exportRoute' => 'ops.reports.export', 'canExport' => $request->user()->can('Export reports'),
            'providers' => Platform::companyOnly() ? null : $this->providers(), 'provider' => $p, 'scope' => $p->display_name ?? config('app.name'),
            'pagetitle' => 'Reports',
        ]);
    }

    public function export(Request $request, ReportService $reports, string $report, string $format)
    {
        abort_unless(in_array($report, $this->allowedReports($request), true), 403);
        $p = $this->providerFilter($request);

        return $this->exportReport($report, $format, $reports, $p?->id, $this->reportRange($request), $p->display_name ?? config('app.name'));
    }
}
