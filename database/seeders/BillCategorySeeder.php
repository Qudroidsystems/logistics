<?php
// database/seeders/BillCategorySeeder.php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class BillCategorySeeder extends Seeder
{
    public function run(): void
    {
        // This seeder adds categories to the school_bill table's 'category' column
        // No separate table needed - these are just reference values

        $categories = [
            ['code' => 'TUITION', 'name' => 'Tuition Fee', 'description' => 'Core tuition fees', 'is_mandatory' => true, 'sort_order' => 1],
            ['code' => 'DEV_LEVY', 'name' => 'Development Levy', 'description' => 'School development and infrastructure', 'is_mandatory' => true, 'sort_order' => 2],
            ['code' => 'ICT', 'name' => 'ICT / Computer Fee', 'description' => 'Computer lab and IT facilities', 'is_mandatory' => true, 'sort_order' => 3],
            ['code' => 'SPORTS', 'name' => 'Sports Fee', 'description' => 'Sports activities and facilities', 'is_mandatory' => false, 'sort_order' => 4],
            ['code' => 'LIBRARY', 'name' => 'Library Fee', 'description' => 'Library resources and services', 'is_mandatory' => true, 'sort_order' => 5],
            ['code' => 'SCIENCE_LAB', 'name' => 'Science Lab Fee', 'description' => 'Laboratory equipment and materials', 'is_mandatory' => false, 'sort_order' => 6],
            ['code' => 'UNIFORM', 'name' => 'Uniform Fee', 'description' => 'School uniform purchase', 'is_mandatory' => false, 'sort_order' => 7],
            ['code' => 'TEXTBOOKS', 'name' => 'Textbooks Fee', 'description' => 'Textbook purchase or rental', 'is_mandatory' => false, 'sort_order' => 8],
            ['code' => 'EXAM', 'name' => 'Examination Fee', 'description' => 'Internal and external examination fees', 'is_mandatory' => true, 'sort_order' => 9],
            ['code' => 'REGISTRATION', 'name' => 'Registration Fee', 'description' => 'Annual registration fee', 'is_mandatory' => true, 'sort_order' => 10],
            ['code' => 'PTA', 'name' => 'PTA Levy', 'description' => 'Parent-Teacher Association levy', 'is_mandatory' => false, 'sort_order' => 11],
            ['code' => 'INSURANCE', 'name' => 'Insurance Fee', 'description' => 'Student insurance cover', 'is_mandatory' => false, 'sort_order' => 12],
            ['code' => 'MEDICAL', 'name' => 'Medical Fee', 'description' => 'School clinic and medical services', 'is_mandatory' => false, 'sort_order' => 13],
            ['code' => 'TRANSPORT', 'name' => 'Transport Fee', 'description' => 'School bus transport service', 'is_mandatory' => false, 'sort_order' => 14],
            ['code' => 'BOARDING', 'name' => 'Boarding Fee', 'description' => 'Hostel accommodation fee', 'is_mandatory' => false, 'sort_order' => 15],
            ['code' => 'EXCURSION', 'name' => 'Excursion Fee', 'description' => 'Educational trips and excursions', 'is_mandatory' => false, 'sort_order' => 16],
            ['code' => 'UNIFORM', 'name' => 'Uniform Fee', 'description' => 'School uniform purchase', 'is_mandatory' => false, 'sort_order' => 17],
            ['code' => 'EXTRA_CURRICULAR', 'name' => 'Extra-Curricular', 'description' => 'Clubs, societies, and activities', 'is_mandatory' => false, 'sort_order' => 18],
            ['code' => 'LATE_REG', 'name' => 'Late Registration', 'description' => 'Late registration penalty', 'is_mandatory' => false, 'sort_order' => 19],
            ['code' => 'OTHER', 'name' => 'Other Fees', 'description' => 'Miscellaneous fees', 'is_mandatory' => false, 'sort_order' => 20],
        ];

        // Update existing bills with categories (optional)
        // This will set categories for bills that don't have one
        if (Schema::hasTable('school_bill')) {
            $updatedCount = 0;

            foreach ($categories as $category) {
                // Check if any bills exist without category and assign default categories
                // This is optional - you may want to handle this manually
            }

            $this->command->info('✅ Bill categories reference loaded successfully! (' . count($categories) . ' categories)');
        } else {
            $this->command->error('❌ school_bill table does not exist. Run migrations first.');
        }

        // Optionally create a separate categories table if needed
        $this->createBillCategoriesTableIfNeeded($categories);
    }

    private function createBillCategoriesTableIfNeeded($categories)
    {
        // Check if bill_categories table exists, if not, create it
        if (!Schema::hasTable('bill_categories')) {
            $this->command->info('📝 bill_categories table does not exist. Creating it now...');

            Schema::create('bill_categories', function ($table) {
                $table->id();
                $table->string('code', 50)->unique();
                $table->string('name');
                $table->text('description')->nullable();
                $table->boolean('is_mandatory')->default(false);
                $table->integer('sort_order')->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });

            foreach ($categories as $category) {
                DB::table('bill_categories')->insert([
                    'code' => $category['code'],
                    'name' => $category['name'],
                    'description' => $category['description'],
                    'is_mandatory' => $category['is_mandatory'],
                    'sort_order' => $category['sort_order'],
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            $this->command->info('✅ bill_categories table created and seeded!');
        }
    }
}
