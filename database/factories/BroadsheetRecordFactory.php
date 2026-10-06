<?php

namespace Database\Factories;

use App\Models\BroadsheetRecord;
use App\Models\Student;
use App\Models\Schoolclass;
use App\Models\Subject;
use App\Models\Schoolsession;
use Illuminate\Database\Eloquent\Factories\Factory;

class BroadsheetRecordFactory extends Factory
{
    protected $model = BroadsheetRecord::class;

    public function definition()
    {
        return [
            'student_id' => Student::factory(),
            'subject_id' => Subject::factory(),
            'schoolclass_id' => Schoolclass::factory(),
            'session_id' => Schoolsession::factory(),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}