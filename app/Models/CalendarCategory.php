<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class CalendarCategory extends Model
{
    protected $fillable = ['name', 'slug', 'color', 'icon', 'is_active', 'sort'];
    protected $casts = ['is_active' => 'boolean', 'sort' => 'integer'];

    public function events()
    {
        return $this->hasMany(CalendarEvent::class, 'category_id');
    }

    public static function slugFor(string $name): string
    {
        $base = Str::slug($name) ?: 'category';
        $slug = $base; $i = 2;
        while (static::where('slug', $slug)->exists()) { $slug = $base . '-' . $i++; }
        return $slug;
    }
}
