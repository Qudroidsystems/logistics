<?php

namespace Database\Factories;

use App\Models\BroadsheetsMock;
use App\Models\BroadsheetRecordMock;
use App\Models\Subjectclass;
use App\Models\Schoolterm;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class BroadsheetsMockFactory extends Factory
{
    protected $model = BroadsheetsMock::class;

    public function definition()
    {
        $exam = $this->faker->numberBetween(0, 100);
        $total = $exam;

        return [
            'broadsheet_records_mock_id' => BroadsheetRecordMock::factory(),
            'subjectclass_id' => Subjectclass::factory(),
            'term_id' => Schoolterm::factory(),
            'staff_id' => User::factory(),
            'exam' => $exam,
            'total' => $total,
            'grade' => $this->faker->randomElement(['A', 'B', 'C', 'D', 'F']),
            'allsubjectstotalscores' => $this->faker->numberBetween(100, 500),
            'subjectpositionclass' => $this->faker->numberBetween(1, 30),
            'cmin' => $this->faker->numberBetween(0, 50),
            'cmax' => $this->faker->numberBetween(50, 100),
            'avg' => $this->faker->numberBetween(0, 100),
            'remark' => $this->faker->sentence,
            'submiitedby' => User::factory(),
            'vettedby' => User::factory(),
            'vettedstatus' => $this->faker->randomElement(['Pending', 'Approved', 'Rejected']),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}