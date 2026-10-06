<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TimetablePeriodLimit extends Model
{
    use HasFactory;

    protected $table = 'timetable_period_limits';

    protected $fillable = [
        'session_id',
        'term_id',
        'scope',
        'teacher_id',
        'schoolclass_id',
        'day',
        'max_periods',
    ];

    protected $casts = [
        'session_id'     => 'integer',
        'term_id'        => 'integer',
        'teacher_id'     => 'integer',
        'schoolclass_id' => 'integer',
        'max_periods'    => 'integer',
    ];

    public const SCOPES = [
        'teacher_total' => 'Teacher — total periods per week',
        'teacher_class' => 'Teacher — periods per week for one class',
        'teacher_day'   => 'Teacher — periods on one day',
        'class_total'   => 'Class — total periods per week',
    ];

    protected static function booted(): void
    {
        static::saving(function (TimetablePeriodLimit $row) {
            $target = match ($row->scope) {
                'teacher_total' => "t{$row->teacher_id}",
                'teacher_class' => "t{$row->teacher_id}c{$row->schoolclass_id}",
                'teacher_day'   => "t{$row->teacher_id}d{$row->day}",
                'class_total'   => "c{$row->schoolclass_id}",
                default         => 'invalid',
            };

            $termKey = $row->term_id ? "t{$row->term_id}" : 'all';

            $row->scope_key = "s{$row->session_id}:{$termKey}:{$row->scope}:{$target}";
        });
    }

    public function session()
    {
        return $this->belongsTo(Schoolsession::class, 'session_id', 'id');
    }

    public function term()
    {
        return $this->belongsTo(Schoolterm::class, 'term_id', 'id');
    }

    public function teacher()
    {
        return $this->belongsTo(User::class, 'teacher_id', 'id');
    }

    public function schoolclass()
    {
        return $this->belongsTo(Schoolclass::class, 'schoolclass_id', 'id');
    }

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