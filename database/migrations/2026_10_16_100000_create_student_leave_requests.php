<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Student leave of absence: a student (or their parent) asks to be away from
 * school for a period. Flow: class teacher recommends → principal approves.
 * If the class has no class teacher, it goes straight to the principal.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('student_leave_requests')) {
            Schema::create('student_leave_requests', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('student_id')->index();
                $table->unsignedBigInteger('requested_by')->nullable();       // users.id (student or parent)
                $table->string('requester_type', 12)->default('student');     // student | parent | staff
                $table->unsignedBigInteger('class_id')->nullable();
                $table->unsignedBigInteger('term_id')->nullable();
                $table->unsignedBigInteger('session_id')->nullable();
                $table->string('reason_type', 20)->default('other');          // sick | family | travel | religious | bereavement | other
                $table->text('reason');
                $table->date('start_date')->index();
                $table->date('end_date')->index();
                $table->unsignedSmallInteger('days')->default(1);             // school days requested
                $table->string('attachment')->nullable();
                $table->string('contact_phone', 30)->nullable();
                $table->string('status', 20)->default('pending_teacher')->index(); // pending_teacher | pending_principal | approved | rejected | cancelled
                $table->unsignedBigInteger('teacher_id')->nullable();
                $table->timestamp('teacher_at')->nullable();
                $table->string('teacher_note', 500)->nullable();
                $table->unsignedBigInteger('approver_id')->nullable();
                $table->timestamp('approved_at')->nullable();
                $table->string('approver_note', 500)->nullable();
                $table->timestamp('resumed_at')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('student_leave_requests');
    }
};
