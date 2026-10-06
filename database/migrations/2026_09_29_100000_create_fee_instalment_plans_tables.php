<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Fee instalment plans: split a term's fees into dated instalments
 * (e.g. 50% by 30 Sep, 30% by 31 Oct, 20% by 30 Nov).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('fee_instalment_plans')) {
            Schema::create('fee_instalment_plans', function (Blueprint $table) {
                $table->id();
                $table->string('name', 120);
                $table->string('description', 500)->nullable();
                $table->unsignedBigInteger('session_id')->index();
                $table->unsignedBigInteger('term_id')->index();
                $table->string('applies_to', 10)->default('selected');   // selected | classes | all
                $table->json('class_ids')->nullable();                     // for applies_to = classes
                $table->json('schedule');                                  // [{label, percent, due_date}]
                $table->boolean('results_when_on_track')->default(true);   // on-track students can see results
                $table->boolean('is_active')->default(true);
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('fee_instalment_assignments')) {
            Schema::create('fee_instalment_assignments', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('plan_id')->index();
                $table->unsignedBigInteger('student_id')->index();
                $table->unsignedBigInteger('assigned_by')->nullable();
                $table->string('note', 255)->nullable();
                $table->timestamps();
                $table->unique(['plan_id', 'student_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('fee_instalment_assignments');
        Schema::dropIfExists('fee_instalment_plans');
    }
};
