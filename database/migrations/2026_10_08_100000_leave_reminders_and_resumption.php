<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Leave reminders log, handover notes, resumption tracking and extensions. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leave_requests', function (Blueprint $table) {
            if (!Schema::hasColumn('leave_requests', 'handover_note')) $table->text('handover_note')->nullable();
            if (!Schema::hasColumn('leave_requests', 'resumed_at')) $table->timestamp('resumed_at')->nullable();
            if (!Schema::hasColumn('leave_requests', 'resume_source')) $table->string('resume_source', 20)->nullable(); // staff | login | admin
            if (!Schema::hasColumn('leave_requests', 'extension_of')) $table->unsignedBigInteger('extension_of')->nullable()->index();
        });

        if (!Schema::hasTable('leave_reminder_logs')) {
            Schema::create('leave_reminder_logs', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('leave_request_id')->index();
                $table->string('kind', 30);          // starting | countdown_N | last_day | resumes_today | overdue | cover
                $table->string('channel', 12);       // sms | whatsapp | email | portal
                $table->string('recipient', 150)->nullable();
                $table->string('status', 12)->default('sent');
                $table->string('error', 255)->nullable();
                $table->timestamp('created_at')->useCurrent();
                $table->unique(['leave_request_id', 'kind', 'channel'], 'uq_leave_reminder');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('leave_reminder_logs');
    }
};
