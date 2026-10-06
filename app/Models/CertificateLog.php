<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CertificateLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'certificate_id', 'template_id', 'student_id', 'serial', 'action',
        'user_id', 'user_name', 'ip', 'note', 'created_at',
    ];

    protected $casts = ['created_at' => 'datetime'];

    public const ACTIONS = [
        'created'    => ['Created', 'st-muted'],
        'generated'  => ['Generated', 'st-info'],
        'reprinted'  => ['Reprinted', 'st-pending'],
        'approved'   => ['Approved', 'st-info'],
        'issued'     => ['Issued', 'st-paid'],
        'revoked'    => ['Revoked', 'st-danger'],
        'downloaded' => ['Downloaded', 'st-muted'],
        'verified'   => ['Verified (scan)', 'st-info'],
    ];

    public function label(): array { return self::ACTIONS[$this->action] ?? [ucfirst((string) $this->action), 'st-muted']; }

    public static function record(string $action, array $attrs = []): void
    {
        try {
            $u = auth()->user();
            static::create(array_merge([
                'action' => $action,
                'user_id' => $u?->id,
                'user_name' => $u?->name,
                'ip' => request()->ip(),
                'created_at' => now(),
            ], $attrs));
        } catch (\Throwable $e) {}
    }
}
