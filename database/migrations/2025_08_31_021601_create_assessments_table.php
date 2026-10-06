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
        Schema::create('assessments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('classcategory_id');
            $table->string('name', 100); // e.g., CA1, CA2, Exam
            $table->decimal('max_score', 5, 2); // Maximum score for this assessment
            $table->timestamps();

            $table->foreign('classcategory_id')
                  ->references('id')
                  ->on('classcategories')
                  ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('assessments');
    }
};