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
        Schema::create('student_current_term', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('studentId');
            $table->unsignedBigInteger('schoolclassId');
            $table->unsignedBigInteger('termId');
            $table->unsignedBigInteger('sessionId');
            $table->boolean('is_current')->default(true); // Flag to mark current term
            $table->timestamps();

            // Foreign key constraints
            $table->foreign('studentId')
                  ->references('id')
                  ->on('studentRegistration')
                  ->onDelete('cascade');

            $table->foreign('schoolclassId')
                  ->references('id')
                  ->on('schoolclass')
                  ->onDelete('cascade');

            $table->foreign('termId')
                  ->references('id')
                  ->on('schoolterm')
                  ->onDelete('cascade');

            $table->foreign('sessionId')
                  ->references('id')
                  ->on('schoolsession')
                  ->onDelete('cascade');

            // Unique constraint to ensure one current term per student
            $table->unique(['studentId', 'is_current']);

            // Indexes for performance
            $table->index(['studentId', 'is_current']);
            $table->index(['sessionId', 'termId']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_current_term');
    }
};
