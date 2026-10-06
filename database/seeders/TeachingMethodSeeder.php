<?php

namespace Database\Seeders;

use App\Models\TeachingMethod;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

/** Seeds a default teaching-methods library (idempotent by name). */
class TeachingMethodSeeder extends Seeder
{
    public function run(): void
    {
        if (!Schema::hasTable('teaching_methods')) return;

        $methods = [
            'Lecture / exposition', 'Class discussion', 'Demonstration', 'Question & answer',
            'Guided discovery', 'Group / cooperative work', 'Think-pair-share', 'Role play / drama',
            'Practical / experiment', 'Project-based learning', 'Flipped classroom', 'Brainstorming',
            'Storytelling', 'Field trip / excursion', 'Problem-solving', 'Use of audio-visual aids',
        ];
        foreach ($methods as $m) {
            TeachingMethod::firstOrCreate(['name' => $m], ['is_active' => true]);
        }
    }
}
