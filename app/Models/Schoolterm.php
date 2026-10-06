<?php
// app/Models/Schoolterm.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Schoolterm extends Model
{
    use HasFactory;

    protected $table = "schoolterm";

    protected $fillable = [
        'term',
        'status',
        'is_promotional',
    ];

    protected $casts = [
        'status'         => 'boolean',
        'is_promotional' => 'boolean',
    ];

    // ── Scopes ────────────────────────────────────────────────
    public function scopeActive($query)
    {
        return $query->where('status', true);
    }

    public function scopeInactive($query)
    {
        return $query->where('status', false);
    }

    public function scopePromotional($query)
    {
        return $query->where('is_promotional', true);
    }

    // ── Current-term helpers ──────────────────────────────────

    /**
     * The single term currently marked active. Cached because the
     * device processor and every attendance blade hit this constantly.
     * Call ::forgetCurrent() whenever term status changes.
     */
    public static function current(): ?self
    {
        return cache()->remember('schoolterm.current', 120, function () {
            return static::where('status', true)->first();
        });
    }

    public static function forgetCurrent(): void
    {
        cache()->forget('schoolterm.current');
    }

    /**
     * Make this term the only active one, atomically, and bust the cache.
     * Use instead of a raw ->update(['status' => true]) wherever a term
     * is switched, so two terms can never both be "current".
     */
    public function activateExclusively(): void
    {
        \DB::transaction(function () {
            static::where('id', '!=', $this->id)->update(['status' => false]);
            $this->update(['status' => true]);
        });

        static::forgetCurrent();
        \App\Models\AttendanceTermSetting::forget();
    }
}