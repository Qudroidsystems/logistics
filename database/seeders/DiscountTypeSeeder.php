<?php
// database/seeders/DiscountTypeSeeder.php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\DiscountType;

class DiscountTypeSeeder extends Seeder
{
    public function run()
    {
        $types = [
            ['name' => 'Early Payment', 'code' => 'EARLY', 'type' => 'percentage'],
            ['name' => 'Sibling Discount', 'code' => 'SIBLING', 'type' => 'percentage'],
            ['name' => 'Loyalty Discount', 'code' => 'LOYALTY', 'type' => 'percentage'],
            ['name' => 'Bulk Payment', 'code' => 'BULK', 'type' => 'percentage'],
            ['name' => 'Referral Discount', 'code' => 'REFERRAL', 'type' => 'fixed_amount'],
        ];

        foreach ($types as $type) {
            DiscountType::updateOrCreate(
                ['code' => $type['code']],
                $type
            );
        }
    }
}
