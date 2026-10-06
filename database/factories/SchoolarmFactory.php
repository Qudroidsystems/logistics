<?php

namespace Database\Factories;

use App\Models\Schoolarm;
use Illuminate\Database\Eloquent\Factories\Factory;

class SchoolarmFactory extends Factory
{
    protected $model = Schoolarm::class;

    public function definition()
    {
        return array(
            'arm' => $this->faker->randomElement(array('A', 'B', 'C')),
            'description' => $this->faker->sentence,
            'created_at' => now(),
            'updated_at' => now(),
        );
    }
}