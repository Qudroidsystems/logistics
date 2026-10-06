<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Certificate module (confidential): designable templates (Fabric canvas JSON),
 * issued certificates with a verifiable QR, per-student generation lock,
 * approval + revocation, and a full audit trail.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('certificate_templates')) {
            Schema::create('certificate_templates', function (Blueprint $table) {
                $table->id();
                $table->string('name', 150);
                $table->string('description', 500)->nullable();
                $table->string('orientation', 12)->default('landscape'); // landscape | portrait
                $table->unsignedInteger('width')->default(1123);          // px @96dpi (A4 landscape)
                $table->unsignedInteger('height')->default(794);
                $table->string('background_path')->nullable();
                $table->longText('design')->nullable();                   // Fabric.js canvas JSON
                $table->string('serial_prefix', 30)->default('CERT');
                $table->boolean('requires_approval')->default(true);
                $table->unsignedInteger('generation_limit')->nullable();  // per student; null = unlimited
                $table->boolean('is_active')->default(true)->index();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('certificates')) {
            Schema::create('certificates', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('template_id')->index();
                $table->unsignedBigInteger('student_id')->index();
                $table->string('serial', 60)->unique();
                $table->string('verify_token', 64)->unique();
                $table->string('title', 180)->nullable();
                $table->string('status', 12)->default('draft')->index();  // draft | approved | issued | revoked
                $table->unsignedBigInteger('class_id')->nullable();
                $table->unsignedBigInteger('term_id')->nullable();
                $table->unsignedBigInteger('session_id')->nullable();
                $table->longText('data_snapshot')->nullable();            // resolved field values (json)
                $table->longText('design_snapshot')->nullable();          // design used (json)
                $table->string('rendered_path')->nullable();              // stored PNG of the issued cert
                $table->unsignedInteger('generation_count')->default(0);  // reprints (same serial)
                $table->timestamp('last_generated_at')->nullable();
                $table->timestamp('approved_at')->nullable();
                $table->unsignedBigInteger('approved_by')->nullable();
                $table->timestamp('issued_at')->nullable();
                $table->unsignedBigInteger('issued_by')->nullable();
                $table->timestamp('revoked_at')->nullable();
                $table->unsignedBigInteger('revoked_by')->nullable();
                $table->string('revoke_reason', 300)->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();
                // One certificate per student per template (reprint keeps the same serial).
                $table->unique(['template_id', 'student_id'], 'cert_tpl_student_unique');
            });
        }

        if (!Schema::hasTable('certificate_logs')) {
            Schema::create('certificate_logs', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('certificate_id')->nullable()->index();
                $table->unsignedBigInteger('template_id')->nullable();
                $table->unsignedBigInteger('student_id')->nullable()->index();
                $table->string('serial', 60)->nullable();
                $table->string('action', 20)->index();  // created|generated|reprinted|approved|issued|revoked|downloaded|verified
                $table->unsignedBigInteger('user_id')->nullable();
                $table->string('user_name', 120)->nullable();
                $table->string('ip', 45)->nullable();
                $table->string('note', 300)->nullable();
                $table->timestamp('created_at')->nullable()->index();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('certificate_logs');
        Schema::dropIfExists('certificates');
        Schema::dropIfExists('certificate_templates');
    }
};
