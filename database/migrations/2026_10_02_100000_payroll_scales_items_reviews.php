<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Payroll phase 2: salary scales (grade + step, versioned by date), a library
 * of pay items, items given to staff (recurring or one-off), grade history and
 * salary reviews (increments / raises with automatic arrears).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('salary_grades')) {
            Schema::create('salary_grades', function (Blueprint $table) {
                $table->id();
                $table->string('code', 20)->unique();         // e.g. GL07, TS3
                $table->string('name', 100);
                $table->string('description', 255)->nullable();
                $table->unsignedSmallInteger('max_step')->default(10);
                $table->unsignedSmallInteger('sort')->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('salary_grade_steps')) {
            Schema::create('salary_grade_steps', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('grade_id')->index();
                $table->unsignedSmallInteger('step');
                $table->date('effective_from');               // a raise adds a new set of rows
                $table->decimal('basic', 15, 2)->default(0);
                $table->decimal('housing', 15, 2)->default(0);
                $table->decimal('transport', 15, 2)->default(0);
                $table->decimal('meal', 15, 2)->default(0);
                $table->decimal('medical', 15, 2)->default(0);
                $table->decimal('utility', 15, 2)->default(0);
                $table->decimal('other', 15, 2)->default(0);
                $table->unsignedBigInteger('review_id')->nullable();
                $table->timestamps();
                $table->unique(['grade_id', 'step', 'effective_from'], 'uq_grade_step_from');
            });
        }

        if (!Schema::hasTable('pay_items')) {
            Schema::create('pay_items', function (Blueprint $table) {
                $table->id();
                $table->string('code', 30)->unique();
                $table->string('name', 100);
                $table->string('type', 10);                    // earning | deduction
                $table->string('calc', 16)->default('fixed');  // fixed | percent_basic | percent_gross
                $table->decimal('default_amount', 15, 2)->default(0);
                $table->decimal('default_rate', 8, 4)->default(0);   // % for percent_* items
                $table->boolean('taxable')->default(true);
                $table->boolean('pensionable')->default(false);
                $table->boolean('one_off')->default(false);    // taxed as a lump sum (bonus, arrears…)
                $table->boolean('is_system')->default(false);
                $table->boolean('is_active')->default(true);
                $table->unsignedBigInteger('account_id')->nullable(); // for accounting later
                $table->string('description', 255)->nullable();
                $table->timestamps();
            });

            $now = now();
            $items = [
                ['OVERTIME', 'Overtime', 'earning', 1, 0, 1], ['BONUS', 'Bonus', 'earning', 1, 0, 1], ['THIRTEENTH', '13th month pay', 'earning', 1, 0, 1],
                ['ARREARS', 'Salary arrears', 'earning', 1, 1, 1], ['LEAVE_ALLOW', 'Leave allowance', 'earning', 1, 0, 1],
                ['RESPONSIBILITY', 'Responsibility allowance', 'earning', 1, 0, 0], ['EXAM_DUTY', 'Exam duty allowance', 'earning', 1, 0, 1],
                ['EXTRA_LESSON', 'Extra lessons', 'earning', 1, 0, 0], ['REIMBURSEMENT', 'Reimbursement (not taxed)', 'earning', 0, 0, 1],
                ['UNION_DUES', 'Union dues', 'deduction', 0, 0, 0], ['COOPERATIVE', 'Cooperative contribution', 'deduction', 0, 0, 0],
                ['LATENESS', 'Lateness / absence deduction', 'deduction', 0, 0, 1], ['STAFF_CHILD_FEES', "Staff child's school fees", 'deduction', 0, 0, 0],
                ['OTHER_DEDUCTION', 'Other deduction', 'deduction', 0, 0, 1],
            ];
            foreach ($items as [$code, $name, $type, $taxable, $pensionable, $oneOff]) {
                DB::table('pay_items')->insert(['code' => $code, 'name' => $name, 'type' => $type, 'taxable' => $type === 'earning' && $taxable,
                    'pensionable' => (bool) $pensionable, 'one_off' => (bool) $oneOff, 'is_system' => in_array($code, ['ARREARS'], true),
                    'created_at' => $now, 'updated_at' => $now]);
            }
        }

        if (!Schema::hasTable('staff_pay_items')) {
            Schema::create('staff_pay_items', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('staff_id')->index();
                $table->unsignedBigInteger('pay_item_id')->index();
                $table->decimal('amount', 15, 2)->nullable();  // overrides the item's default
                $table->decimal('rate', 8, 4)->nullable();
                $table->date('from_month');                     // first day of first month
                $table->date('to_month')->nullable();           // null = ongoing; same as from = one month only
                $table->string('note', 255)->nullable();
                $table->unsignedBigInteger('review_id')->nullable(); // set for arrears from a salary review
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();
                $table->index(['staff_id', 'from_month', 'to_month']);
            });
        }

        if (!Schema::hasTable('staff_grade_history')) {
            Schema::create('staff_grade_history', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('staff_id')->index();
                $table->unsignedBigInteger('grade_id');
                $table->unsignedSmallInteger('step');
                $table->date('effective_from');
                $table->string('reason', 30)->default('placement'); // placement | increment | promotion | correction
                $table->unsignedBigInteger('review_id')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('salary_reviews')) {
            Schema::create('salary_reviews', function (Blueprint $table) {
                $table->id();
                $table->string('name', 150);
                $table->string('type', 20);                     // step_increment | percent_raise
                $table->date('effective_from');
                $table->decimal('percent', 8, 4)->nullable();
                $table->json('grade_ids')->nullable();          // null = all grades
                $table->json('components')->nullable();         // percent_raise: which columns rise
                $table->string('status', 12)->default('draft'); // draft | approved | applied
                $table->unsignedBigInteger('arrears_period_id')->nullable();
                $table->json('summary')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->unsignedBigInteger('approved_by')->nullable();
                $table->timestamp('approved_at')->nullable();
                $table->timestamp('applied_at')->nullable();
                $table->timestamps();
            });
        }

        if (Schema::hasTable('staff_pay_profiles')) {
            Schema::table('staff_pay_profiles', function (Blueprint $table) {
                if (!Schema::hasColumn('staff_pay_profiles', 'salary_source')) $table->string('salary_source', 10)->default('structure'); // structure | grade
            });
        }
    }

    public function down(): void
    {
        foreach (['salary_reviews', 'staff_grade_history', 'staff_pay_items', 'pay_items', 'salary_grade_steps', 'salary_grades'] as $t) Schema::dropIfExists($t);
    }
};
