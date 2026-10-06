<?php
// database/seeders/ScholarshipTypeSeeder.php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ScholarshipType;

class ScholarshipTypeSeeder extends Seeder
{
    public function run()
    {
        $types = [
            ['name' => 'Full Scholarship', 'code' => 'FULL', 'type' => 'full', 'application' => 'manual'],
            ['name' => 'Merit Scholarship', 'code' => 'MERIT', 'type' => 'percentage', 'application' => 'manual'],
            ['name' => 'Sports Scholarship', 'code' => 'SPORTS', 'type' => 'percentage', 'application' => 'manual'],
            ['name' => 'Need-Based Scholarship', 'code' => 'NEED', 'type' => 'percentage', 'application' => 'manual'],
            ['name' => 'Staff Children', 'code' => 'STAFF', 'type' => 'percentage', 'application' => 'auto'],
            ['name' => 'Alumni Referral', 'code' => 'ALUMNI', 'type' => 'percentage', 'application' => 'manual'],
            ['name' => 'Academic Excellence', 'code' => 'ACADEMIC', 'type' => 'percentage', 'application' => 'manual'],
        ];

        foreach ($types as $type) {
            ScholarshipType::updateOrCreate(
                ['code' => $type['code']],
                $type
            );
        }
    }
}
