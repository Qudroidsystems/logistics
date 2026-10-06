<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class SchoolInformation extends Model
{
    use HasFactory;

    protected $table = 'school_information';

    protected $fillable = [
        'school_name',
        'school_address',
        'school_phones',
        'school_email',
        'school_logo',
        'app_logo',
        'school_stamp',
        'school_motto',
        'school_website',
        'no_of_times_school_opened',
        'date_school_opened',
        'date_school_closed',
        'date_next_term_begins',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'date_school_opened' => 'date',
        'date_school_closed' => 'date',
        'date_next_term_begins' => 'date',
        'school_phones' => 'array',
    ];

    /**
     * Get the active school information
     */
    public static function getActiveSchool()
    {
        return self::where('is_active', true)->first();
    }

    /**
     * Get the school logo URL
     */
    public function getLogoUrlAttribute()
    {
        if (!$this->school_logo) {
            return null;
        }

        if (filter_var($this->school_logo, FILTER_VALIDATE_URL)) {
            return $this->school_logo;
        }

        if (Storage::disk('public')->exists($this->school_logo)) {
            return asset('storage/' . $this->school_logo);
        }

        return null;
    }

    /**
     * Get the app logo URL
     */
    public function getAppLogoUrlAttribute()
    {
        if (!$this->app_logo) {
            return null;
        }

        if (filter_var($this->app_logo, FILTER_VALIDATE_URL)) {
            return $this->app_logo;
        }

        if (Storage::disk('public')->exists($this->app_logo)) {
            return asset('storage/' . $this->app_logo);
        }

        return null;
    }

    /**
     * Get the school stamp URL - ADD THIS METHOD
     */
    public function getStampUrlAttribute()
    {
        if (!$this->school_stamp) {
            return null;
        }

        if (filter_var($this->school_stamp, FILTER_VALIDATE_URL)) {
            return $this->school_stamp;
        }

        if (Storage::disk('public')->exists($this->school_stamp)) {
            return asset('storage/' . $this->school_stamp);
        }

        return null;
    }

    /**
     * Get formatted phones as string
     */
    public function getFormattedPhonesAttribute()
    {
        if (empty($this->school_phones)) {
            return '-';
        }
        return implode(', ', $this->school_phones);
    }

    /**
     * Get primary phone (first one)
     */
    public function getPrimaryPhoneAttribute()
    {
        if (empty($this->school_phones)) {
            return null;
        }
        return $this->school_phones[0];
    }

    /**
     * Get logo with fallback
     */
    public function getLogoWithFallbackAttribute()
    {
        return $this->getLogoUrlAttribute() ?? asset('theme/layouts/assets/images/logo-dark.png');
    }

    /**
     * Get app logo with fallback
     */
    public function getAppLogoWithFallbackAttribute()
    {
        return $this->getAppLogoUrlAttribute() ?? $this->getLogoWithFallbackAttribute();
    }

    /**
     * Delete old files when updating
     */
    public function deleteOldFiles($newSchoolLogo = null, $newAppLogo = null, $newStamp = null)
    {
        if ($this->getOriginal('school_logo') && $this->getOriginal('school_logo') !== $newSchoolLogo) {
            Storage::disk('public')->delete($this->getOriginal('school_logo'));
        }
        if ($this->getOriginal('app_logo') && $this->getOriginal('app_logo') !== $newAppLogo) {
            Storage::disk('public')->delete($this->getOriginal('app_logo'));
        }
        if ($this->getOriginal('school_stamp') && $this->getOriginal('school_stamp') !== $newStamp) {
            Storage::disk('public')->delete($this->getOriginal('school_stamp'));
        }
    }

    /**
 * Add these methods to app/Models/SchoolInformation.php, alongside the
 * existing getLogoUrlAttribute() / getStampUrlAttribute() methods.
 *
 * Why: DomPDF cannot reliably fetch images over HTTP (asset() URLs) unless
 * 'isRemoteEnabled' is explicitly turned on in config/dompdf.php, and even
 * then remote fetches during PDF rendering are slow and can fail silently,
 * leaving the logo blank. Embedding the image as a base64 data URI avoids
 * the network round-trip entirely and always renders.
 */
 
/**
 * Get the school logo as a base64 data URI, safe for embedding in PDFs.
 */
public function getLogoBase64Attribute()
{
    if (!$this->school_logo || !\Illuminate\Support\Facades\Storage::disk('public')->exists($this->school_logo)) {
        return null;
    }
 
    $path = \Illuminate\Support\Facades\Storage::disk('public')->path($this->school_logo);
    $mime = mime_content_type($path) ?: 'image/png';
    $data = base64_encode(file_get_contents($path));
 
    return "data:{$mime};base64,{$data}";
}
 
/**
 * Get the school stamp as a base64 data URI, safe for embedding in PDFs.
 */
public function getStampBase64Attribute()
{
    if (!$this->school_stamp || !\Illuminate\Support\Facades\Storage::disk('public')->exists($this->school_stamp)) {
        return null;
    }
 
    $path = \Illuminate\Support\Facades\Storage::disk('public')->path($this->school_stamp);
    $mime = mime_content_type($path) ?: 'image/png';
    $data = base64_encode(file_get_contents($path));
 
    return "data:{$mime};base64,{$data}";
}

}
