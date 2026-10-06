<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('timetable_constraints', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('setting_id');
            $table->unsignedBigInteger('subject_id');
            $table->unsignedSmallInteger('periods_per_week')->default(2);
            $table->boolean('allow_double_period')->default(false);
            $table->unsignedSmallInteger('max_double_periods_per_week')->default(1);
            $table->json('preferred_days')->nullable(); // Remove default
            $table->json('avoid_days')->nullable(); // Remove default
            $table->json('preferred_periods')->nullable(); // Remove default
            $table->boolean('is_compulsory')->default(true);
            $table->timestamps();

            $table->unique(['setting_id', 'subject_id'], 'constraint_setting_subject');
            $table->foreign('setting_id')->references('id')->on('timetable_settings')->onDelete('cascade');
            $table->foreign('subject_id')->references('id')->on('subject')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('timetable_constraints');
    }
};
