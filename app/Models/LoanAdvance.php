<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/** Staff loan, salary advance or cooperative loan — repaid through payroll. */
class LoanAdvance extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'loans_advances';

    protected $fillable = [
        'staff_id', 'type', 'reference_no', 'amount', 'interest_rate',
        'repayment_months', 'monthly_repayment', 'total_repayable', 'balance', 'approval_date',
        'first_repayment_date', 'purpose', 'attachment', 'status',
        'approved_by', 'approved_at', 'rejection_reason', 'created_by',
        'guarantor_staff_id', 'guarantor_status', 'paused_until', 'disbursed_at',
        'disbursement_method', 'disbursement_reference', 'notes',
    ];

    protected $casts = [
        'amount' => 'float', 'interest_rate' => 'float', 'monthly_repayment' => 'float',
        'total_repayable' => 'float', 'balance' => 'float',
        'approval_date' => 'date', 'first_repayment_date' => 'date', 'paused_until' => 'date',
        'approved_at' => 'datetime', 'disbursed_at' => 'datetime', 'deleted_at' => 'datetime',
    ];

    public const TYPES = ['loan' => 'Staff loan', 'advance' => 'Salary advance', 'cooperative' => 'Cooperative loan'];

    public const STATUS = [
        'pending' => ['Awaiting approval', 'st-pending'], 'approved' => ['Approved — to disburse', 'st-info'],
        'disbursing' => ['Being paid out', 'st-pending'], 'active' => ['Repaying', 'st-paid'],
        'completed' => ['Fully repaid', 'st-muted'], 'rejected' => ['Declined', 'st-danger'],
        'cancelled' => ['Cancelled', 'st-muted'], 'written_off' => ['Written off', 'st-warning'],
    ];

    public function staff() { return $this->belongsTo(Staff::class, 'staff_id'); }
    public function guarantor() { return $this->belongsTo(Staff::class, 'guarantor_staff_id'); }
    public function approvedBy() { return $this->belongsTo(User::class, 'approved_by'); }
    public function createdBy() { return $this->belongsTo(User::class, 'created_by'); }
    public function repaymentSchedule() { return $this->hasMany(LoanRepaymentSchedule::class, 'loan_id')->orderBy('installment_no'); }
    public function repayments() { return $this->hasMany(LoanRepayment::class, 'loan_id')->orderByDesc('paid_on'); }

    public function scopeActive($q) { return $q->where('status', 'active'); }
    public function scopeApproved($q) { return $q->where('status', 'approved'); }
    public function scopeCompleted($q) { return $q->where('status', 'completed'); }
    public function scopeOpen($q) { return $q->whereIn('status', ['pending', 'approved', 'disbursing', 'active']); }

    public function label(): array { return self::STATUS[$this->status] ?? [ucfirst($this->status), 'st-muted']; }
    public function typeLabel(): string { return self::TYPES[$this->type] ?? ucfirst((string) $this->type); }
    public function repaid(): float { return round(max(0, (float) $this->total_repayable - (float) $this->balance), 2); }
    public function progress(): int { return $this->total_repayable > 0 ? (int) min(100, round($this->repaid() / $this->total_repayable * 100)) : 0; }
    public function isPaused(?string $onDate = null): bool
    {
        return $this->paused_until && $this->paused_until->toDateString() >= ($onDate ?: now()->toDateString());
    }

    public function getStatusBadgeAttribute()
    {
        [$l, $c] = $this->label();
        return "<span class='status-pill {$c}'>{$l}</span>";
    }
}
