<?php

namespace App\Http\Controllers\Partner;

use App\Http\Controllers\Controller;
use App\Models\BrandSetting;
use App\Modules\DropOff\DropOffService;
use Illuminate\Http\Request;
use RuntimeException;

/** The counter screen at a partner store: take parcels in from senders and hand them to drivers. */
class CounterController extends Controller
{
    public function __construct(private DropOffService $dropoffs) {}

    /** The store this user is working at (?centre=<public id> when they work at more than one). */
    private function centre(Request $request): object
    {
        $mine = collect($this->dropoffs->centresFor($request->user()->id));
        abort_if($mine->isEmpty(), 403, 'Your account is not linked to a drop-off store.');
        $pick = $request->input('centre') ?? session('counter_centre');
        $c = ($pick ? $mine->firstWhere('public_id', $pick) : null) ?? $mine->first();
        session(['counter_centre' => $c->public_id]);

        return $c;
    }

    public function index(Request $request)
    {
        $c = $this->centre($request);

        return view('partner.counter', [
            'brand' => BrandSetting::forApp('customer'), 'centre' => $c, 'centres' => $this->dropoffs->centresFor($request->user()->id),
            'parcels' => $this->dropoffs->counter($c->id), 'earnings' => $this->dropoffs->earnings($c->id),
            'commission' => DropOffService::describeCommission((int) $c->commission_flat, (int) $c->commission_bp),
        ]);
    }

    public function receive(Request $request)
    {
        $c = $this->centre($request);
        $d = $request->validate(['code' => 'required|string|max:40']);
        try {
            $this->dropoffs->receive($c->id, $d['code'], $request->user()->id);
        } catch (RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Received. Keep the parcel at the counter: a driver is being found to collect it.');
    }

    public function collect(Request $request)
    {
        $c = $this->centre($request);
        $d = $request->validate(['code' => 'required|string|max:40']);
        try {
            $this->dropoffs->collect($c->id, $d['code'], $request->user()->id);
        } catch (RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Handed to the driver. Thank you!');
    }

    public function addStaff(Request $request)
    {
        $c = $this->centre($request);
        $d = $request->validate(['email' => 'required|email']);
        try {
            $this->dropoffs->addStaff($c->id, $d['email']);
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Added. They can now use the counter screen.');
    }
}
