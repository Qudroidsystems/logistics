<?php

namespace Database\Factories;

use App\Models\Schoolsession;
use Illuminate\Database\Eloquent\Factories\Factory;

class SchoolsessionFactory extends Factory
{
    protected $model = Schoolsession::class;

    public function definition()
    {
        return array(
            'session' => $this->faker->year . '/' . ($this->faker->year + 1),
            'created_at' => now(),
            'updated_at' => now(),
        );
    }
}