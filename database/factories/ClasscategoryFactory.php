<?php

namespace Database\Factories;

use App\Models\Classcategory;
use Illuminate\Database\Eloquent\Factories\Factory;

class ClasscategoryFactory extends Factory
{
    protected $model = Classcategory::class;

    public function definition()
    {
        return array(
            'category' => $this->faker->randomElement(array('Junior Secondary', 'Senior Secondary')),
            'ca1score' => $this->faker->numberBetween(10, 20),
            'ca2score' => $this->faker->numberBetween(10, 20),
            'examscore' => $this->faker->numberBetween(50, 60),
            'is_senior' => $this->faker->boolean,
            'created_at' => now(),
            'updated_at' => now(),
        );
    }
}