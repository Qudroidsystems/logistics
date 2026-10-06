<?php

namespace Database\Factories;

use App\Models\Schoolclass;
use App\Models\Schoolarm;
use App\Models\Classcategory;
use Illuminate\Database\Eloquent\Factories\Factory;

class SchoolclassFactory extends Factory
{
    protected $model = Schoolclass::class;

    public function definition()
    {
        return array(
            'schoolclass' => $this->faker->randomElement(array('JSS1', 'JSS2', 'JSS3', 'SSS1', 'SSS2', 'SSS3')),
            'arm' => Schoolarm::factory(),
            'classcategoryid' => Classcategory::factory(),
            'description' => $this->faker->sentence,
            'created_at' => now(),
            'updated_at' => now(),
        );
    }
}