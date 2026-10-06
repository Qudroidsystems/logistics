<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ResultAccessException extends Model
{
    protected $table = 'result_access_exceptions';

    protected $fillable = [
        'student_id', 'term_id', 'session_id', 'expires_on', 'reason',
        'granted_by', 'revoked_at', 'revoked_by',
    ];

    protected $casts = [
        'expires_on' => 'date',
        'revoked_at' => 'datetime',
    ];

    /** Not revoked and not past its expiry date. */
    public function scopeActive(Builder $q): Builder
    {
        return $q->whereNull('revoked_at')
            ->where(fn ($w) => $w->whereNull('expires_on')->orWhereDate('expires_on', '>=', now()->toDateString()));
    }

    /** Covers this term: an exact term/session match, or an "all terms" exception. */
    public function scopeCovering(Builder $q, int $termId, int $sessionId): Builder
    {
        return $q->where(function ($w) use ($termId, $sessionId) {
            $w->where(fn ($x) => $x->where('term_id', $termId)->where('session_id', $sessionId))
              ->orWhere(fn ($x) => $x->whereNull('term_id')->whereNull('session_id'));
        });
    }

    public function isAllTerms(): bool
    {
        return is_null($this->term_id) && is_null($this->session_id);
    }
}
