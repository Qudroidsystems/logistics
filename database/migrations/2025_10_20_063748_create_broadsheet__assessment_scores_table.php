<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('broadsheet_assessment_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('broadsheet_id')->constrained('broadsheets')->onDelete('cascade');
            $table->foreignId('assessment_id')->constrained('assessments')->onDelete('cascade');
            $table->decimal('score', 8, 2)->default(0);
            $table->timestamps();

            // Unique constraint to prevent duplicate scores per broadsheet/assessment
            $table->unique(['broadsheet_id', 'assessment_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('broadsheet_assessment_scores');
    }
};