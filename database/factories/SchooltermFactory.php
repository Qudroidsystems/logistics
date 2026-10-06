<?php

namespace Database\Factories;

use App\Models\Schoolterm;
use Illuminate\Database\Eloquent\Factories\Factory;

class SchooltermFactory extends Factory
{
    protected $model = Schoolterm::class;

    public function definition()
    {
        return array(
            'term' => $this->faker->randomElement(array('First Term', 'Second Term', 'Third Term')),
            'created_at' => now(),
            'updated_at' => now(),
        );
    }
}