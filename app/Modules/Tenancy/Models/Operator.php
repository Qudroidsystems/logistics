<?php

namespace App\Modules\Tenancy\Models;

use App\Models\User;
use App\Support\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Operator extends Model
{
    use HasFactory, HasPublicId, SoftDeletes;

    public const TYPE_PLATFORM = 'platform';
    public const TYPE_COMPANY = 'company';
    public const TYPE_FRANCHISE = 'franchise';
    public const TYPE_INDEPENDENT_DRIVER = 'independent_driver';
    public const TYPE_MARKET_SHOPPER = 'market_shopper';
    public const TYPE_MERCHANT = 'merchant';
    public const TYPE_CORPORATE_FLEET = 'corporate_fleet';

    /** Provider types that can appear in the marketplace directory. */
    public const PROVIDER_TYPES = [
        self::TYPE_COMPANY,
        self::TYPE_FRANCHISE,
        self::TYPE_INDEPENDENT_DRIVER,
        self::TYPE_MARKET_SHOPPER,
    ];

    protected $guarded = ['id', 'public_id'];

    protected function casts(): array
    {
        return [
            'branding' => 'array',
            'support_contact' => 'array',
            'approved_at' => 'datetime',
            'commission_bp' => 'integer',
        ];
    }

    public function isPlatform(): bool
    {
        return $this->type === self::TYPE_PLATFORM;
    }

    public function isProvider(): bool
    {
        return in_array($this->type, self::PROVIDER_TYPES, true);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_operator_id');
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'operator_members')
            ->withPivot(['role', 'status', 'joined_at'])
            ->withTimestamps();
    }

    public function capabilities(): HasMany
    {
        return $this->hasMany(OperatorCapability::class);
    }

    public function can(string $capability): bool
    {
        return $this->capabilities()
            ->where('capability', $capability)
            ->where('enabled', true)
            ->exists();
    }
}
