<?php

namespace Database\Factories;

use App\Models\Broadsheets;
use App\Models\BroadsheetRecord;
use App\Models\Subjectclass;
use App\Models\Schoolterm;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class BroadsheetsFactory extends Factory
{
    protected $model = Broadsheets::class;

    public function definition()
    {
        $ca1 = $this->faker->numberBetween(0, 20);
        $ca2 = $this->faker->numberBetween(0, 20);
        $ca3 = $this->faker->numberBetween(0, 20);
        $exam = $this->faker->numberBetween(0, 60);
        $total = $ca1 + $ca2 + $ca3 + $exam;

        return [
            'broadsheet_record_id' => BroadsheetRecord::factory(),
            'subjectclass_id' => Subjectclass::factory(),
            'term_id' => Schoolterm::factory(),
            'staff_id' => User::factory(),
            'ca1' => $ca1,
            'ca2' => $ca2,
            'ca3' => $ca3,
            'exam' => $exam,
            'total' => $total,
            'bf' => $this->faker->numberBetween(0, 100),
            'cum' => $this->faker->numberBetween(0, 100),
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