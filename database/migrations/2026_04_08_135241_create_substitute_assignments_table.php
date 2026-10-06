<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('substitute_assignments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('original_teacher_id');
            $table->unsignedBigInteger('substitute_teacher_id');
            $table->unsignedBigInteger('slot_id');
            $table->date('assignment_date');
            $table->enum('status', ['pending', 'approved', 'rejected', 'completed'])->default('pending');
            $table->text('reason')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();

            $table->index(['original_teacher_id', 'assignment_date']);
            $table->foreign('original_teacher_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('substitute_teacher_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('slot_id')->references('id')->on('timetable_slots')->onDelete('cascade');
            $table->foreign('approved_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('substitute_assignments');
    }
};
