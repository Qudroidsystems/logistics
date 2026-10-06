<?php

namespace Database\Factories;

use App\Models\Subject;
use Illuminate\Database\Eloquent\Factories\Factory;

class SubjectFactory extends Factory
{
    protected $model = Subject::class;

    public function definition()
    {
        return [
            'subject' => $this->faker->randomElement(['Mathematics', 'English', 'Physics', 'Chemistry']),
            'subject_code'=> $this->faker->randomElement(['CHM101','MAT202']),
            'remark'=> $this->faker->randomElement(['math','eng']),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}