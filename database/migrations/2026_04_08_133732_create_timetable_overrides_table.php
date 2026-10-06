<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('timetable_overrides', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('setting_id');
            $table->date('override_date');
            $table->enum('override_type', ['holiday', 'special_event', 'exam', 'emergency', 'custom'])->default('custom');
            $table->string('title', 200);
            $table->text('description')->nullable();

            // Modified schedule for that day (JSON stored) - NO DEFAULT VALUES
            $table->json('modified_slots')->nullable();
            $table->json('custom_schedule')->nullable();
            $table->json('affected_periods')->nullable();

            // Cancellation info
            $table->boolean('cancel_all_classes')->default(false);
            $table->text('cancellation_reason')->nullable();

            // Approval workflow
            $table->enum('status', ['draft', 'pending_approval', 'approved', 'rejected', 'active'])->default('draft');
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->text('approval_notes')->nullable();

            // Notification settings
            $table->boolean('notify_teachers')->default(true);
            $table->boolean('notify_students')->default(false);
            $table->boolean('notify_parents')->default(false);
            $table->timestamp('notification_sent_at')->nullable();

            // Audit trail
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('updated_by')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['setting_id', 'override_date']);
            $table->index(['override_date', 'status']);
            $table->index(['override_type', 'status']);
            $table->index('status');

            // Foreign keys
            $table->foreign('setting_id')
                  ->references('id')
                  ->on('timetable_settings')
                  ->onDelete('cascade');

            $table->foreign('approved_by')
                  ->references('id')
                  ->on('users')
                  ->onDelete('set null');

            $table->foreign('created_by')
                  ->references('id')
                  ->on('users')
                  ->onDelete('cascade');

            $table->foreign('updated_by')
                  ->references('id')
                  ->on('users')
                  ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('timetable_overrides');
    }
};
