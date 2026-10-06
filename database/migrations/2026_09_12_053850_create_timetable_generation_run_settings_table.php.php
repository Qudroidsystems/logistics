<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('timetable_generation_run_settings', function (Blueprint $table) {
            $table->id();

            $table->foreignId('run_id')
                  ->constrained('timetable_generation_runs')
                  ->cascadeOnDelete();

            // Reference to the live setting this snapshot was copied from.
            $table->unsignedBigInteger('source_setting_id')->nullable();

            $table->foreignId('schoolclass_id')
                  ->constrained('schoolclass')
                  ->cascadeOnDelete();

            $table->string('class_name', 150);

            // Full copy of the setting row at save time.
            $table->json('setting_snapshot');

            // Full copy of periods, constraints, priorities, and slots.
            $table->json('periods_snapshot');
            $table->json('constraints_snapshot');
            $table->json('priorities_snapshot');
            $table->json('slots_snapshot');

            // Per-class stats at save time.
            $table->unsignedInteger('placed')->default(0);
            $table->unsignedInteger('unplaced')->default(0);
            $table->unsignedInteger('room_shortfall')->default(0);

            $table->timestamps();

            $table->index('run_id', 'tgrs_run_idx');
            $table->index(['run_id', 'schoolclass_id'], 'tgrs_run_class_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('timetable_generation_run_settings');
    }
};