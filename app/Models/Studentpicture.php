<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Studentpicture extends Model
{
    use HasFactory;

    protected $table = "studentpicture";
    protected $primaryKey = "studentid";
    public $incrementing = false;
    protected $keyType = 'integer';

    protected $fillable = [
        'studentid',
        'picture',
    ];

    /**
     * Get the student that owns the Studentpicture
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'studentid', 'id');
    }

    /**
     * Get the full URL for the picture
     */
    public function getPictureUrlAttribute(): ?string
    {
        if ($this->picture) {
            return asset('storage/student_avatars/' . $this->picture);
        }
        return null;
    }

    /**
     * Get the file path for the picture
     */
    public function getPicturePathAttribute(): ?string
    {
        if ($this->picture) {
            return storage_path('app/public/student_avatars/' . $this->picture);
        }
        return null;
    }
}
