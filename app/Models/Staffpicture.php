<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Staffpicture extends Model
{
    use HasFactory;

    protected $table = "staffpicture";
    protected $primaryKey = "staffid";
    public $incrementing = false;
    protected $keyType = 'integer';

    protected $fillable = [
        'staffId',
        'picture',
    ];

    /**
     * Get the user that owns the Staffpicture
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'staffId', 'id');
    }

    /**
     * Get the full URL for the picture
     */
    public function getPictureUrlAttribute(): ?string
    {
        if ($this->picture) {
            return asset('storage/staff_avatars/' . $this->picture);
        }
        return null;
    }

    /**
     * Get the file path for the picture
     */
    public function getPicturePathAttribute(): ?string
    {
        if ($this->picture) {
            return storage_path('app/public/staff_avatars/' . $this->picture);
        }
        return null;
    }
}
