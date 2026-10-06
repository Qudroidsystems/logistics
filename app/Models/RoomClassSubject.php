<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RoomClassSubject extends Model
{
    use HasFactory;

    protected $table = 'room_class_subject';

    protected $fillable = [
        'room_id',
        'schoolclass_id',
        'subject_id',
        'session_id',
        'term_id',
        'note',
    ];

    protected $casts = [
        'room_id'        => 'integer',
        'schoolclass_id' => 'integer',
        'subject_id'     => 'integer',
        'session_id'     => 'integer',
        'term_id'        => 'integer',
    ];

    protected static function booted(): void
    {
        static::saving(function (RoomClassSubject $row) {
            $row->scope_key = implode('|', [
                $row->room_id        ?? 'x',
                $row->schoolclass_id ?? 'x',
                $row->subject_id     ?? 'any',
                $row->session_id     ?? 'x',
                $row->term_id        ?? 'all',
            ]);
        });
    }

    public function room()
    {
        return $this->belongsTo(Room::class);
    }

    public function schoolclass()
    {
        return $this->belongsTo(Schoolclass::class, 'schoolclass_id', 'id');
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    public function session()
    {
        return $this->belongsTo(Schoolsession::class, 'session_id', 'id');
    }

    public function term()
    {
        return $this->belongsTo(Schoolterm::class, 'term_id', 'id');
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