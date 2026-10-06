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
        // ── 6. Notification log ───────────────────────────────────────────────
        Schema::create('timetable_notifications', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('teacher_id');
            $table->unsignedBigInteger('slot_id')->nullable();
            $table->enum('type', ['daily_summary', 'class_reminder', 'change_alert', 'weekly_preview']);
            $table->string('email', 191)->nullable();
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->enum('status', ['pending', 'sent', 'failed'])->default('pending');
            $table->text('payload')->nullable();
            $table->timestamps();

            $table->index(['teacher_id', 'status']);
            $table->index(['scheduled_at', 'status']);
            $table->foreign('teacher_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('slot_id')->references('id')->on('timetable_slots')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('timetable_notifications');
    }
};
