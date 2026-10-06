<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BioModel extends Model
{
    use HasFactory;

    protected $table = "staff_bio";
    protected $primaryKey = "id";

    protected $fillable = [
        'user_id',
        'firstname',
        'lastname',
        'othernames',
        'phone',
        'address',
        'gender',
        'maritalstatus',
        'nationality',
        'dob',
    ];

    /**
     * Get the user that owns the BioModel
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get full name
     */
    public function getFullNameAttribute(): string
    {
        $names = [$this->firstname, $this->lastname];
        if ($this->othernames && $this->othernames !== 'no info') {
            $names[] = $this->othernames;
        }
        return implode(' ', array_filter($names));
    }

    /**
     * Get formatted date of birth
     */
    public function getFormattedDobAttribute(): ?string
    {
        if ($this->dob && $this->dob !== 'no info') {
            try {
                return date('F j, Y', strtotime($this->dob));
            } catch (\Exception $e) {
                return $this->dob;
            }
        }
        return null;
    }
}
