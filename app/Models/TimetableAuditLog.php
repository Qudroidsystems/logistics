<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TimetableAuditLog extends Model
{
    protected $table = 'timetable_audit_logs';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'action',
        'model_type',
        'model_id',
        'old_values',
        'new_values',
        'ip_address',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the user who performed the action.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the model that was changed (polymorphic relationship).
     */
    public function model()
    {
        return $this->morphTo();
    }

    /**
     * Scope a query to get logs for a specific model.
     */
    public function scopeForModel($query, string $modelType, ?int $modelId = null)
    {
        $query->where('model_type', $modelType);

        if ($modelId) {
            $query->where('model_id', $modelId);
        }

        return $query;
    }

    /**
     * Scope a query to get logs for a specific action.
     */
    public function scopeForAction($query, string $action)
    {
        return $query->where('action', $action);
    }

    /**
     * Scope a query to get logs for a specific user.
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope a query to get recent logs.
     */
    public function scopeRecent($query, int $limit = 50)
    {
        return $query->orderBy('created_at', 'desc')->limit($limit);
    }

    /**
     * Get formatted action with icon.
     */
    public function getFormattedActionAttribute(): string
    {
        $icons = [
            'create' => '➕',
            'update' => '✏️',
            'delete' => '🗑️',
            'view' => '👁️',
            'generate' => '⚙️',
            'approve' => '✅',
            'reject' => '❌',
        ];

        $icon = $icons[$this->action] ?? '📝';
        $actionText = ucfirst($this->action);

        return "{$icon} {$actionText}";
    }

    /**
     * Get the model name in a readable format.
     */
    public function getModelNameAttribute(): string
    {
        $names = [
            'TimetableSetting' => 'Timetable Setting',
            'TimetablePeriod' => 'Period',
            'TimetableConstraint' => 'Constraint',
            'TimetableSlot' => 'Timetable Slot',
            'TimetableNotification' => 'Notification',
            'TeacherAvailability' => 'Teacher Availability',
            'SubstituteAssignment' => 'Substitute Assignment',
            'Room' => 'Room',
            'RoomBooking' => 'Room Booking',
            'ExamTimetable' => 'Exam Timetable',
            'ExamSlot' => 'Exam Slot',
            'Holiday' => 'Holiday',
            'TimetableOverride' => 'Timetable Override',
        ];

        return $names[$this->model_type] ?? $this->model_type;
    }

    /**
     * Get a summary of what changed.
     */
    public function getChangeSummaryAttribute(): string
    {
        if ($this->action === 'create') {
            return "Created new {$this->model_name}";
        }

        if ($this->action === 'delete') {
            return "Deleted {$this->model_name}";
        }

        if ($this->action === 'update' && $this->old_values && $this->new_values) {
            $changedFields = array_keys(array_diff_assoc($this->new_values, $this->old_values));
            $fields = array_slice($changedFields, 0, 3);
            $summary = 'Updated: ' . implode(', ', $fields);

            if (count($changedFields) > 3) {
                $summary .= ' and ' . (count($changedFields) - 3) . ' more';
            }

            return $summary;
        }

        return "{$this->action} operation on {$this->model_name}";
    }

    /**
     * Get the user's name who performed the action.
     */
    public function getUserNameAttribute(): string
    {
        return $this->user?->name ?? 'System';
    }
}
