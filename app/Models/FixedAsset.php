<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FixedAsset extends Model
{
    protected $fillable = [
        'asset_tag', 'name', 'account_code', 'description', 'serial_no', 'location', 'custodian_staff_id', 'vendor_id',
        'acquisition_date', 'cost', 'salvage_value', 'useful_life_months', 'accumulated_depreciation', 'last_depreciated_period',
        'status', 'disposal_date', 'disposal_amount', 'disposal_note', 'expense_voucher_id', 'created_by',
    ];

    protected $casts = [
        'acquisition_date' => 'date', 'disposal_date' => 'date', 'cost' => 'float', 'salvage_value' => 'float',
        'accumulated_depreciation' => 'float', 'disposal_amount' => 'float',
    ];

    public const CLASSES = ['1101' => 'Land & buildings', '1102' => 'Furniture & equipment', '1103' => 'Vehicles', '1104' => 'Computers & IT'];
    public const LIVES = ['1101' => 480, '1102' => 60, '1103' => 48, '1104' => 36];
    public const STATUS = ['active' => ['In use', 'st-paid'], 'under_repair' => ['Under repair', 'st-warning'], 'disposed' => ['Disposed', 'st-muted'], 'lost' => ['Lost / stolen', 'st-danger']];

    public function depreciations() { return $this->hasMany(AssetDepreciation::class)->orderByDesc('period'); }
    public function vendor() { return $this->belongsTo(Vendor::class); }
    public function custodian() { return $this->belongsTo(Staff::class, 'custodian_staff_id'); }

    public function bookValue(): float { return round($this->cost - $this->accumulated_depreciation, 2); }
    public function monthlyDepreciation(): float
    {
        if ($this->account_code === '1101' && str_contains(strtolower($this->name), 'land')) return 0.0; // land is not depreciated
        return $this->useful_life_months > 0 ? round(max(0, $this->cost - $this->salvage_value) / $this->useful_life_months, 2) : 0.0;
    }
    public function label(): array { return self::STATUS[$this->status] ?? [ucfirst($this->status), 'st-muted']; }
}
