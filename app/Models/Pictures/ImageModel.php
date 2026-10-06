<?php

namespace App\Models\Pictures;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;


class ImageModel extends Model
{
    use HasFactory;

    protected $table = "staff_picture";
    protected $primaryKey = "user_id";

    protected $fillable = [
        'user_id',
        'avatar',

    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class,'user_id');
    }
}
