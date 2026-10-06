<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PayoutBatch extends Model
{
    protected $fillable = [
        'payroll_period_id', 'reference', 'provider', 'mode', 'status', 'total_amount', 'item_count',
        'success_count', 'failed_count', 'prepared_by', 'released_by', 'released_at', 'completed_at', 'note',
    ];

    protected $casts = ['total_amount' => 'float', 'released_at' => 'datetime', 'completed_at' => 'datetime'];

    public const STATUS = [
        'draft' => ['Ready to release', 'st-muted'], 'processing' => ['Sending', 'st-pending'],
        'completed' => ['Paid', 'st-paid'], 'partial' => ['Part paid', 'st-warning'],
        'failed' => ['Failed', 'st-danger'], 'cancelled' => ['Cancelled', 'st-muted'],
    ];

    public function period() { return $this->belongsTo(PayrollPeriod::class, 'payroll_period_id'); }
    public function items() { return $this->hasMany(PayoutItem::class); }
    public function preparer() { return $this->belongsTo(User::class, 'prepared_by'); }
    public function releaser() { return $this->belongsTo(User::class, 'released_by'); }

    public function label(): array { return self::STATUS[$this->status] ?? [ucfirst($this->status), 'st-muted']; }

    /** Recount items and settle the batch status. */
    public function refreshTotals(): void
    {
        $items = $this->items()->where('status', '!=', 'skipped')->get();
        $ok = $items->whereIn('status', ['success', 'manual'])->count();
        $bad = $items->whereIn('status', ['failed', 'reversed'])->count();
        $open = $items->count() - $ok - $bad;
        $status = $this->status;
        if ($this->status !== 'draft' && $this->status !== 'cancelled') {
            $status = $open > 0 ? 'processing' : ($bad === 0 ? 'completed' : ($ok === 0 ? 'failed' : 'partial'));
        }
        $this->update([
            'success_count' => $ok, 'failed_count' => $bad, 'item_count' => $items->count(),
            'total_amount' => round($items->sum('amount'), 2), 'status' => $status,
            'completed_at' => $open === 0 && in_array($status, ['completed', 'partial', 'failed'], true) ? ($this->completed_at ?? now()) : null,
        ]);
    }
}
