<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('timetable_period_allocations', function (Blueprint $table) {
            $table->id();

            $table->foreignId('set_id')
                  ->constrained('timetable_period_allocation_sets')
                  ->cascadeOnDelete();

            $table->foreignId('schoolclass_id')
                  ->constrained('schoolclass')
                  ->cascadeOnDelete();

            $table->foreignId('subject_id')
                  ->constrained('subject')
                  ->cascadeOnDelete();

            $table->unsignedSmallInteger('periods_per_week')->default(2);
            $table->boolean('allow_double_period')->default(false);
            $table->unsignedSmallInteger('max_double_periods_per_week')->default(1);

            $table->timestamps();

            $table->unique(['set_id', 'schoolclass_id', 'subject_id'], 'tpa_set_class_subject_unique');
            $table->index(['schoolclass_id', 'subject_id'], 'tpa_class_subject_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('timetable_period_allocations');
    }
};
