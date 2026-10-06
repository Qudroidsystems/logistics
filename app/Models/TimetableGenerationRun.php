<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class TimetableGenerationRun extends Model
{
    use HasFactory;

    protected $table = 'timetable_generation_runs';

    protected $fillable = [
        'run_code',
        'name',
        'description',
        'session_id',
        'term_id',
        'wizard_input',
        'advanced_rules',
        'class_count',
        'total_placed',
        'total_shortfall',
        'total_conflicts',
        'status',
        'seed',
        'created_by',
        'last_restored_at',
        'last_restored_by',
        'restore_count',
        'reverted_at',
        'reverted_by',
    ];

    protected $casts = [
        'session_id'       => 'integer',
        'term_id'          => 'integer',
        'wizard_input'     => 'array',
        'advanced_rules'   => 'array',
        'class_count'      => 'integer',
        'total_placed'     => 'integer',
        'total_shortfall'  => 'integer',
        'total_conflicts'  => 'integer',
        'seed'             => 'integer',
        'created_by'       => 'integer',
        'last_restored_at' => 'datetime',
        'last_restored_by' => 'integer',
        'restore_count'    => 'integer',
        'reverted_at'      => 'datetime',
        'reverted_by'      => 'integer',
    ];

    // Unambiguous charset: no 0/O, no 1/l/I. 10 chars.
    const CODE_CHARS  = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghjkmnpqrstuvwxyz23456789';
    const CODE_LENGTH = 10;

    protected static function booted(): void
    {
        static::creating(function (TimetableGenerationRun $run) {
            if (empty($run->run_code)) {
                $run->run_code = self::generateUniqueCode();
            }
        });
    }

    public static function generateUniqueCode(): string
    {
        $chars = self::CODE_CHARS;
        $len   = strlen($chars);

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $code = '';
            for ($i = 0; $i < self::CODE_LENGTH; $i++) {
                $code .= $chars[random_int(0, $len - 1)];
            }
            if (!self::where('run_code', $code)->exists()) {
                return $code;
            }
        }

        return strtoupper(Str::random(self::CODE_LENGTH));
    }

    // ── Relationships ────────────────────────────────────────────────

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

    public function lastRestorer()
    {
        return $this->belongsTo(User::class, 'last_restored_by', 'id');
    }

    public function reverter()
    {
        return $this->belongsTo(User::class, 'reverted_by', 'id');
    }

    public function snapshots()
    {
        return $this->hasMany(TimetableGenerationRunSetting::class, 'run_id', 'id');
    }

    // ── Scopes ───────────────────────────────────────────────────────

    public function scopeSearch($query, ?string $term)
    {
        if (!$term) return $query;
        $term = trim($term);
        return $query->where(function ($q) use ($term) {
            $q->where('name', 'like', "%{$term}%")
              ->orWhere('description', 'like', "%{$term}%")
              ->orWhere('run_code', 'like', "%{$term}%");
        });
    }

    public function scopeStatus($query, ?string $status)
    {
        if (!$status) return $query;
        return $query->where('status', $status);
    }

    public function scopeBetweenDates($query, ?string $from, ?string $to)
    {
        if ($from) $query->whereDate('created_at', '>=', $from);
        if ($to)   $query->whereDate('created_at', '<=', $to);
        return $query;
    }
}