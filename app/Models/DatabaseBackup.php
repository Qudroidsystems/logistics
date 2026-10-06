<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class DatabaseBackup extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'filename', 'path', 'disk', 'size', 'type', 'status', 'method',
        'emailed_to', 'note', 'created_by', 'created_at',
    ];

    protected $casts = ['size' => 'integer', 'created_at' => 'datetime'];

    public function humanSize(): string
    {
        $b = (int) $this->size;
        if ($b <= 0) return '—';
        $u = ['B', 'KB', 'MB', 'GB']; $i = 0;
        while ($b >= 1024 && $i < 3) { $b /= 1024; $i++; }
        return round($b, $b < 10 && $i > 0 ? 1 : 0) . ' ' . $u[$i];
    }

    public function exists(): bool
    {
        try { return Storage::disk($this->disk)->exists($this->path); } catch (\Throwable $e) { return false; }
    }
}
