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
        Schema::create('broadsheet_sub_assessment_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('broadsheet_id')->constrained('broadsheets')->onDelete('cascade');
            $table->foreignId('sub_assessment_id')->constrained('sub_assessments')->onDelete('cascade');
            $table->decimal('score', 8, 2)->default(0);
            $table->timestamps();
            $table->unique(['broadsheet_id', 'sub_assessment_id'], 'broadsheet_sub_scores_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('broadsheet_sub_assessment_scores');
    }
};