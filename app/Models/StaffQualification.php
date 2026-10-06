<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StaffQualification extends Model
{
    use HasFactory;

    protected $table = 'staff_qualifications';

    protected $fillable = [
        'user_id',
        'institution',
        'qualification',
        'field_of_study',
        'year_obtained',
        'certificate_file',
        'remarks'
    ];

    protected $casts = [
        'year_obtained' => 'integer',
    ];

    /**
     * Get the user that owns the qualification
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get certificate URL
     */
    public function getCertificateUrlAttribute(): ?string
    {
        if ($this->certificate_file) {
            return asset('storage/' . $this->certificate_file);
        }
        return null;
    }

    /**
     * Get certificate file name
     */
    public function getCertificateFileNameAttribute(): ?string
    {
        if ($this->certificate_file) {
            return basename($this->certificate_file);
        }
        return null;
    }
}
