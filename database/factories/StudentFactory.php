<?php

namespace Database\Factories;

use App\Models\Student;
use App\Models\StudentBatchModel;
use Illuminate\Database\Eloquent\Factories\Factory;

class StudentFactory extends Factory
{
    protected $model = Student::class;

    public function definition()
    {
        return array(
            'title' => $this->faker->title,
            'admissionNo' => $this->faker->unique()->numerify('########'),
            'firstname' => $this->faker->firstName,
            'lastname' => $this->faker->lastName,
            'othername' => $this->faker->firstName,
            'gender' => $this->faker->randomElement(array('Male', 'Female')),
            'home_address' => $this->faker->address,
            'home_address2' => $this->faker->address,
            'nationality' => $this->faker->country,
            'placeofbirth' => $this->faker->city,
            'dateofbirth' => $this->faker->date('Y-m-d', '2005-01-01'),
            'age' => $this->faker->numberBetween(10, 20),
            'religion' => $this->faker->randomElement(array('Christian', 'Muslim', 'Other')),
            'state' => $this->faker->state,
            'local' => $this->faker->city,
            'last_school' => $this->faker->company,
            'last_class' => $this->faker->randomElement(array('JSS1', 'JSS2', 'JSS3', 'SSS1', 'SSS2', 'SSS3')),
            'registeredBy' => $this->faker->numberBetween(1, 10),
            'statusId' => $this->faker->numberBetween(1, 5),
            'batchid' => StudentBatchModel::factory()->create()->id,
            'created_at' => now(),
            'updated_at' => now(),
        );
    }
}