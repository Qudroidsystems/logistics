<?php

namespace Database\Seeders;

use App\Models\StudentStatus;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class StudentStatusTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        StudentStatus::create([
            'status' => 'old',
        ]);
        StudentStatus::create([
            'status' => 'new',
        ]);
    }
}
