<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CertificateTemplate extends Model
{
    protected $fillable = [
        'name', 'kind', 'description', 'orientation', 'width', 'height', 'background_path',
        'design', 'serial_prefix', 'requires_approval', 'generation_limit', 'is_active', 'created_by',
    ];

    protected $casts = [
        'width' => 'integer', 'height' => 'integer',
        'requires_approval' => 'boolean', 'is_active' => 'boolean',
        'generation_limit' => 'integer',
    ];

    public function isTestimonial(): bool { return $this->kind === 'testimonial'; }

    public function certificates()
    {
        return $this->hasMany(Certificate::class, 'template_id');
    }

    public function designArray(): array
    {
        $d = json_decode((string) $this->design, true);
        return is_array($d) ? $d : [];
    }
}
