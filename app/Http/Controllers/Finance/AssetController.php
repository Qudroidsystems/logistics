<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\FixedAsset;
use App\Models\Vendor;
use App\Services\Finance\AssetService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/** Fixed-asset register. */
class AssetController extends Controller
{
    public function __construct(protected AssetService $svc)
    {
        $this->middleware('permission:Manage assets|View financial reports')->only(['index', 'show', 'export']);
        $this->middleware('permission:Manage assets')->except(['index', 'show', 'export']);
    }

    protected function staff()
    {
        return DB::table('staffbioinfo as s')->join('users as u', 'u.id', '=', 's.userid')->orderBy('u.name')->get(['s.id', 'u.name']);
    }

    public function index(Request $request)
    {
        $assets = FixedAsset::query()
            ->when($request->filled('class'), fn ($q) => $q->where('account_code', $request->class))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status), fn ($q) => $q->whereIn('status', ['active', 'under_repair']))
            ->when($request->filled('q'), fn ($q) => $q->where(fn ($w) => $w->where('name', 'like', "%{$request->q}%")->orWhere('asset_tag', 'like', "%{$request->q}%")->orWhere('location', 'like', "%{$request->q}%")->orWhere('serial_no', 'like', "%{$request->q}%")))
            ->orderBy('asset_tag')->paginate(30)->withQueryString();
        return view('finance.assets.index', [
            'pagetitle' => 'Fixed Assets', 'assets' => $assets, 'summary' => $this->svc->summary(), 'staff' => $this->staff(),
            'names' => $this->staff()->pluck('name', 'id'), 'vendors' => Vendor::active()->orderBy('name')->get(),
            'lastRun' => DB::table('asset_depreciations')->max('period'),
        ]);
    }

    public function store(Request $request)
    {
        $d = $request->validate([
            'name' => 'required|string|max:150', 'account_code' => 'required|in:' . implode(',', array_keys(FixedAsset::CLASSES)),
            'description' => 'nullable|string|max:500', 'serial_no' => 'nullable|string|max:80', 'location' => 'nullable|string|max:120',
            'custodian_staff_id' => 'nullable|integer|exists:staffbioinfo,id', 'vendor_id' => 'nullable|integer|exists:vendors,id',
            'acquisition_date' => 'required|date|before_or_equal:today', 'cost' => 'required|numeric|min:1', 'salvage_value' => 'nullable|numeric|min:0',
            'useful_life_months' => 'nullable|integer|min:1|max:1200', 'accumulated_depreciation' => 'nullable|numeric|min:0',
        ]);
        $d['useful_life_months'] = $d['useful_life_months'] ?? (FixedAsset::LIVES[$d['account_code']] ?? 60);
        $d['salvage_value'] = $d['salvage_value'] ?? 0;
        $d['accumulated_depreciation'] = min((float) ($d['accumulated_depreciation'] ?? 0), (float) $d['cost']);
        $a = FixedAsset::create($d + ['asset_tag' => $this->svc->nextTag($d['account_code']), 'created_by' => auth()->id()]);
        if ($request->boolean('post_opening', true) && class_exists(\App\Services\Accounting\LedgerPoster::class)) {
            try { app(\App\Services\Accounting\LedgerPoster::class)->assetRegistered($a); } catch (\Throwable $e) {}
        }
        return redirect()->route('finance.assets.show', $a)->with('success', "Asset {$a->asset_tag} added. Write the tag on the item.");
    }

    public function show(FixedAsset $asset)
    {
        $asset->load(['depreciations', 'vendor']);
        return view('finance.assets.show', ['pagetitle' => $asset->asset_tag, 'a' => $asset, 'staff' => $this->staff(),
            'custodian' => $asset->custodian_staff_id ? $this->staff()->firstWhere('id', $asset->custodian_staff_id)?->name : null]);
    }

    public function update(Request $request, FixedAsset $asset)
    {
        $d = $request->validate(['name' => 'required|string|max:150', 'location' => 'nullable|string|max:120', 'custodian_staff_id' => 'nullable|integer',
                                 'serial_no' => 'nullable|string|max:80', 'status' => 'required|in:active,under_repair', 'description' => 'nullable|string|max:500']);
        if (in_array($asset->status, ['disposed', 'lost'])) return back()->with('error', 'This asset is off the register.');
        $asset->update($d);
        return back()->with('success', 'Asset updated.');
    }

    public function depreciate(Request $request)
    {
        $d = $request->validate(['period' => 'required|date_format:Y-m|before_or_equal:' . now()->format('Y-m')]);
        $r = $this->svc->depreciate($d['period'], (int) auth()->id());
        return back()->with('success', $r['count'] ? "Depreciation for {$d['period']}: ₦" . number_format($r['total'], 2) . " on {$r['count']} asset(s)." : 'Nothing to depreciate for that month (already done or fully depreciated).');
    }

    public function dispose(Request $request, FixedAsset $asset)
    {
        $d = $request->validate(['status' => 'required|in:disposed,lost', 'disposal_date' => 'required|date|before_or_equal:today', 'disposal_amount' => 'nullable|numeric|min:0', 'disposal_note' => 'required|string|max:255']);
        try { $this->svc->dispose($asset, $d['status'], $d['disposal_date'], (float) ($d['disposal_amount'] ?? 0), $d['disposal_note'], (int) auth()->id()); }
        catch (\Throwable $e) { return back()->with('error', $e->getMessage()); }
        return back()->with('success', 'Asset taken off the register.');
    }

    public function export()
    {
        $names = $this->staff()->pluck('name', 'id');
        $rows = FixedAsset::orderBy('asset_tag')->get();
        return response()->streamDownload(function () use ($rows, $names) {
            $o = fopen('php://output', 'w');
            fputcsv($o, ['Tag', 'Name', 'Class', 'Serial', 'Location', 'Custodian', 'Acquired', 'Cost', 'Accumulated depreciation', 'Book value', 'Monthly depreciation', 'Status']);
            foreach ($rows as $a) fputcsv($o, [$a->asset_tag, $a->name, FixedAsset::CLASSES[$a->account_code] ?? $a->account_code, $a->serial_no, $a->location,
                $names[$a->custodian_staff_id] ?? '', $a->acquisition_date->toDateString(), $a->cost, $a->accumulated_depreciation, $a->bookValue(), $a->monthlyDepreciation(), $a->status]);
            fclose($o);
        }, 'asset-register-' . now()->format('Ymd') . '.csv', ['Content-Type' => 'text/csv']);
    }
}
