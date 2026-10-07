<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

/**
 * Platform identity. One User row per human; the business role they play
 * (customer, driver, shopper, vendor staff, store staff, admin staff ...)
 * is expressed through Spatie roles + the profile tables added in the
 * Phase 1 schema. Phone-first: phone_number is a primary login handle.
 */
class User extends Authenticatable
{
    use HasApiTokens, HasFactory, HasRoles, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'username',
        'phone_number',
        'password',
        'avatar',
        'gender',
        'date_of_birth',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at'   => 'datetime',
        'date_of_birth'       => 'date',
        'must_change_password'=> 'boolean',
        'is_disabled'         => 'boolean',
        'last_login_at'       => 'datetime',
    ];

    public function getFirstNameAttribute(): string
    {
        return explode(' ', trim($this->name))[0] ?? '';
    }

    public function getLastNameAttribute(): string
    {
        $parts = explode(' ', trim($this->name));
        return $parts[1] ?? '';
    }

    public function getAvatarUrlAttribute(): string
    {
        if ($this->avatar) {
            return asset('storage/avatars/' . $this->avatar);
        }
        return 'https://ui-avatars.com/api/?name=' . urlencode($this->name) . '&color=7F9CF5&background=EBF4FF';
    }

    public function isActive(): bool
    {
        return !$this->is_disabled;
    }
}
