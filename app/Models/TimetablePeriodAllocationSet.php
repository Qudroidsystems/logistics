<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TimetablePeriodAllocationSet extends Model
{
    use HasFactory;

    protected $table = 'timetable_period_allocation_sets';

    protected $fillable = [
        'session_id',
        'term_id',
        'name',
        'description',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'session_id' => 'integer',
        'term_id'    => 'integer',
        'created_by' => 'integer',
        'updated_by' => 'integer',
    ];

    public function session()
    {
        return $this->belongsTo(Schoolsession::class, 'session_id', 'id');
    }

    public function term()
    {
        return $this->belongsTo(Schoolterm::class, 'term_id', 'id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by', 'id');
    }

    public function allocations()
    {
        return $this->hasMany(TimetablePeriodAllocation::class, 'set_id', 'id');
    }

    /**
     * Sets scoped to a session, either for one specific term or
     * (when no term is given) the whole session. A set saved with no
     * term ("All Terms") is treated as reusable across every term in
     * that session, mirroring TimetablePeriodLimit::scopeForScope().
     */
    public function scopeForScope($query, int $sessionId, ?int $termId)
    {
        return $query->where('session_id', $sessionId)
            ->where(function ($q) use ($termId) {
                $q->whereNull('term_id');
                if ($termId) {
                    $q->orWhere('term_id', $termId);
                }
            });
    }
}
