<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
       // Add workload tracking
        Schema::create('teacher_workload_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('teacher_id');
            $table->unsignedBigInteger('session_id');
            $table->unsignedBigInteger('term_id');
            $table->integer('total_periods_assigned');
            $table->integer('total_classes_handled');
            $table->json('subjects_taught');
            $table->timestamps();

            $table->index(['teacher_id', 'session_id', 'term_id']);
            $table->foreign('teacher_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('session_id')->references('id')->on('schoolsession')->onDelete('cascade');
            $table->foreign('term_id')->references('id')->on('schoolterm')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('teacher_workload_logs');
    }
};
