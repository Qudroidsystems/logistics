<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class TimetableChangeRequest extends Model
{
    use SoftDeletes;

    protected $table = 'timetable_change_requests';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'requested_by',
        'current_slot_id',
        'proposed_slot_id',
        'change_type',
        'reason',
        'status',
        'admin_notes',
        'approved_by',
        'approved_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'approved_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Change type options
     */
    const CHANGE_TYPES = [
        'swap' => 'Swap Periods',
        'move' => 'Move to Different Time',
        'substitute' => 'Request Substitute',
        'cancel' => 'Cancel Period',
    ];

    /**
     * Status options
     */
    const STATUSES = [
        'pending' => 'Pending',
        'approved' => 'Approved',
        'rejected' => 'Rejected',
        'cancelled' => 'Cancelled',
    ];

    /**
     * Get the user who requested the change.
     */
    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    /**
     * Get the current timetable slot.
     */
    public function currentSlot(): BelongsTo
    {
        return $this->belongsTo(TimetableSlot::class, 'current_slot_id');
    }

    /**
     * Get the proposed timetable slot.
     */
    public function proposedSlot(): BelongsTo
    {
        return $this->belongsTo(TimetableSlot::class, 'proposed_slot_id');
    }

    /**
     * Get the admin who approved/rejected the request.
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Scope a query to only pending requests.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope a query to only approved requests.
     */
    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    /**
     * Scope a query to only rejected requests.
     */
    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    /**
     * Scope a query for a specific teacher.
     */
    public function scopeForTeacher($query, int $teacherId)
    {
        return $query->whereHas('currentSlot', function($q) use ($teacherId) {
            $q->where('teacher_id', $teacherId);
        })->orWhere('requested_by', $teacherId);
    }

    /**
     * Scope a query for a specific change type.
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('change_type', $type);
    }

    /**
     * Get formatted change type with badge.
     */
    public function getFormattedChangeTypeAttribute(): string
    {
        $badges = [
            'swap' => 'badge bg-info',
            'move' => 'badge bg-warning',
            'substitute' => 'badge bg-primary',
            'cancel' => 'badge bg-danger',
        ];

        $icons = [
            'swap' => '🔄',
            'move' => '📅',
            'substitute' => '👨‍🏫',
            'cancel' => '❌',
        ];

        $badgeClass = $badges[$this->change_type] ?? 'badge bg-secondary';
        $icon = $icons[$this->change_type] ?? '📝';
        $label = self::CHANGE_TYPES[$this->change_type] ?? ucfirst($this->change_type);

        return "<span class='{$badgeClass}'><i class='ri-{$icon}'></i> {$label}</span>";
    }

    /**
     * Get formatted status with badge.
     */
    public function getFormattedStatusAttribute(): string
    {
        $badges = [
            'pending' => 'badge bg-warning',
            'approved' => 'badge bg-success',
            'rejected' => 'badge bg-danger',
            'cancelled' => 'badge bg-secondary',
        ];

        $icons = [
            'pending' => '⏳',
            'approved' => '✓',
            'rejected' => '✗',
            'cancelled' => '🗑️',
        ];

        $badgeClass = $badges[$this->status] ?? 'badge bg-secondary';
        $icon = $icons[$this->status] ?? '📝';
        $label = self::STATUSES[$this->status] ?? ucfirst($this->status);

        return "<span class='{$badgeClass}'>{$icon} {$label}</span>";
    }

    /**
     * Check if request is pending.
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Check if request is approved.
     */
    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    /**
     * Check if request is rejected.
     */
    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

    /**
     * Approve the request.
     */
    public function approve(int $adminId, ?string $notes = null): bool
    {
        return $this->update([
            'status' => 'approved',
            'approved_by' => $adminId,
            'approved_at' => now(),
            'admin_notes' => $notes,
        ]);
    }

    /**
     * Reject the request.
     */
    public function reject(int $adminId, ?string $notes = null): bool
    {
        return $this->update([
            'status' => 'rejected',
            'approved_by' => $adminId,
            'approved_at' => now(),
            'admin_notes' => $notes,
        ]);
    }

    /**
     * Cancel the request.
     */
    public function cancel(): bool
    {
        return $this->update(['status' => 'cancelled']);
    }

    /**
     * Get a summary of the change request.
     */
    public function getSummaryAttribute(): string
    {
        $current = $this->currentSlot;
        $proposed = $this->proposedSlot;

        if (!$current) {
            return 'Request details not available';
        }

        $summary = match($this->change_type) {
            'swap' => "Swap {$current->subject?->subject} on {$current->day} with {$proposed?->subject?->subject} on {$proposed?->day}",
            'move' => "Move {$current->subject?->subject} from {$current->day} {$current->period?->name} to {$proposed?->day} {$proposed?->period?->name}",
            'substitute' => "Request substitute for {$current->subject?->subject} on {$current->day} {$current->period?->name}",
            'cancel' => "Cancel {$current->subject?->subject} on {$current->day} {$current->period?->name}",
            default => "Change request for {$current->subject?->subject}"
        };

        return $summary;
    }
}
