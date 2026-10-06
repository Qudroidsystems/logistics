<?php
// app/Models/DiscountType.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DiscountType extends Model
{
    use HasFactory;

    protected $table = 'discount_types';

    protected $fillable = [
        'name', 'code', 'type', 'description', 'criteria', 'is_active'
    ];

    protected $casts = [
        'criteria' => 'array',
        'is_active' => 'boolean',
    ];

    // Relationships
    public function discounts()
    {
        return $this->hasMany(Discount::class, 'discount_type_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
