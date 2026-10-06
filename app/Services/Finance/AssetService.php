<?php

namespace App\Services\Finance;

use App\Models\AssetDepreciation;
use App\Models\FixedAsset;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/** Fixed-asset register: tags, straight-line monthly depreciation, disposal. */
class AssetService
{
    public static function available(): bool
    {
        return Schema::hasTable('fixed_assets') && Schema::hasTable('asset_depreciations');
    }

    public function nextTag(string $code): string
    {
        $p = ['1101' => 'BLD', '1102' => 'FUR', '1103' => 'VEH', '1104' => 'ICT'][$code] ?? 'AST';
        $n = FixedAsset::where('asset_tag', 'like', "CSK-{$p}-%")->count() + 1;
        do { $tag = "CSK-{$p}-" . str_pad((string) $n++, 4, '0', STR_PAD_LEFT); } while (FixedAsset::where('asset_tag', $tag)->exists());
        return $tag;
    }

    /**
     * Depreciate every active asset for a month (Y-m). Idempotent: each asset
     * is charged at most once per month and never below salvage value.
     */
    public function depreciate(string $period, ?int $userId = null): array
    {
        $end = Carbon::parse($period . '-01')->endOfMonth();
        $rows = []; $total = 0.0;
        DB::transaction(function () use ($period, $end, &$rows, &$total) {
            foreach (FixedAsset::whereIn('status', ['active', 'under_repair'])->whereDate('acquisition_date', '<=', $end)->get() as $a) {
                if (AssetDepreciation::where('fixed_asset_id', $a->id)->where('period', $period)->exists()) continue;
                $room = round($a->cost - $a->salvage_value - $a->accumulated_depreciation, 2);
                $amt = min($a->monthlyDepreciation(), max(0, $room));
                if ($amt <= 0) continue;
                AssetDepreciation::create(['fixed_asset_id' => $a->id, 'period' => $period, 'amount' => $amt]);
                $a->update(['accumulated_depreciation' => round($a->accumulated_depreciation + $amt, 2), 'last_depreciated_period' => $period]);
                $rows[] = ['asset' => $a, 'amount' => $amt]; $total += $amt;
            }
        });
        if ($total > 0 && class_exists(\App\Services\Accounting\LedgerPoster::class)) {
            try {
                $je = app(\App\Services\Accounting\LedgerPoster::class)->depreciation($period, $rows);
                if ($je) AssetDepreciation::where('period', $period)->whereNull('journal_entry_id')->update(['journal_entry_id' => $je->id]);
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('Depreciation not posted', ['period' => $period, 'error' => $e->getMessage()]);
            }
        }
        return ['count' => count($rows), 'total' => round($total, 2)];
    }

    public function dispose(FixedAsset $a, string $status, string $date, float $proceeds, ?string $note, ?int $userId = null): void
    {
        if (in_array($a->status, ['disposed', 'lost'], true)) throw new \RuntimeException('This asset is already off the register.');
        $a->update(['status' => $status, 'disposal_date' => $date, 'disposal_amount' => $proceeds, 'disposal_note' => $note]);
        if (class_exists(\App\Services\Accounting\LedgerPoster::class)) {
            try { app(\App\Services\Accounting\LedgerPoster::class)->assetDisposed($a->fresh()); } catch (\Throwable $e) {}
        }
    }

    public function summary(): array
    {
        $all = FixedAsset::whereIn('status', ['active', 'under_repair'])->get();
        $by = [];
        foreach (FixedAsset::CLASSES as $code => $label) {
            $x = $all->where('account_code', $code);
            $by[$code] = ['label' => $label, 'count' => $x->count(), 'cost' => $x->sum('cost'), 'nbv' => $x->sum(fn ($a) => $a->bookValue())];
        }
        return ['by' => $by, 'cost' => $all->sum('cost'), 'nbv' => $all->sum(fn ($a) => $a->bookValue()), 'count' => $all->count(),
                'monthly' => $all->sum(fn ($a) => $a->monthlyDepreciation())];
    }
}
