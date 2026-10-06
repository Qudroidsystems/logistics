<?php

namespace Database\Seeders;

use App;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Models\Schoolterm;

class TermTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Safe to run again: each term is created only if it doesn't exist yet.
        foreach (['First Term', 'Second Term', 'Third Term'] as $term) {
            if (!Schoolterm::where('term', $term)->exists()) {
                Schoolterm::create(['term' => $term]);
            }
        }
    }
}
