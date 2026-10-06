<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('timetable_period_limits', function (Blueprint $table) {
            $table->id();

            $table->foreignId('session_id')
                  ->constrained('schoolsession')
                  ->cascadeOnDelete();

            $table->foreignId('term_id')
                  ->nullable()
                  ->constrained('schoolterm')
                  ->cascadeOnDelete();

            $table->enum('scope', [
                'teacher_total',
                'teacher_class',
                'teacher_day',
                'class_total',
            ]);

            $table->foreignId('teacher_id')
                  ->nullable()
                  ->constrained('users')
                  ->cascadeOnDelete();

            $table->foreignId('schoolclass_id')
                  ->nullable()
                  ->constrained('schoolclass')
                  ->cascadeOnDelete();

            $table->enum('day', [
                'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday',
            ])->nullable();

            $table->unsignedSmallInteger('max_periods');

            // Application-computed uniqueness key (see model boot).
            $table->string('scope_key', 190);

            $table->timestamps();

            $table->unique('scope_key', 'tpl_scope_unique');
            $table->index(['session_id', 'scope'], 'tpl_session_scope_idx');
            $table->index(['teacher_id', 'scope'], 'tpl_teacher_scope_idx');
            $table->index(['schoolclass_id', 'scope'], 'tpl_class_scope_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('timetable_period_limits');
    }
};