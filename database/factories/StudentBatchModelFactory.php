<?php

namespace Database\Factories;

use App\Models\StudentBatchModel;
use App\Models\Schoolterm;
use App\Models\Schoolsession;
use App\Models\Schoolclass;
use Illuminate\Database\Eloquent\Factories\Factory;

class StudentBatchModelFactory extends Factory
{
    protected $model = StudentBatchModel::class;

    public function definition()
    {
        return array(
            'title' => $this->faker->word,
            'studentid' => $this->faker->numberBetween(1, 1000),
            'schoolclassid' => Schoolclass::factory(),
            'termid' => Schoolterm::factory(),
            'session' => Schoolsession::factory(),
            'status' => $this->faker->randomElement(array('active', 'inactive')),
            'created_at' => now(),
            'updated_at' => now(),
        );
    }
}