<?php

namespace Database\Factories;

use App\Models\Subjectclass;
use App\Models\Schoolclass;
use App\Models\Subject;
use App\Models\User;
use App\Models\Schoolterm;
use App\Models\Schoolsession;
use Illuminate\Database\Eloquent\Factories\Factory;

class SubjectclassFactory extends Factory
{
    protected $model = Subjectclass::class;

    public function definition()
    {
        return array(
            'schoolclassid' => Schoolclass::factory(),
            'subjectid' => Subject::factory(),
            'subjectteacherid' => User::factory(),
            // 'termid' => Schoolterm::factory(),
            // 'session' => Schoolsession::factory(),
            'created_at' => now(),
            'updated_at' => now(),
        );
    }
}