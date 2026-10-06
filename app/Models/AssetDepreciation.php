<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AssetDepreciation extends Model
{
    protected $fillable = ['fixed_asset_id', 'period', 'amount', 'journal_entry_id'];
    protected $casts = ['amount' => 'float'];

    public function asset() { return $this->belongsTo(FixedAsset::class, 'fixed_asset_id'); }
}
