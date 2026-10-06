<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('timetable_generation_runs', function (Blueprint $table) {
            $table->id();

            // Public-facing 10-char alphanumeric code. Unique. Indexed so
            // lookups-by-code are fast. Unambiguous charset (no 0/O/1/l/I).
            $table->string('run_code', 10)->unique();

            $table->string('name', 150);
            $table->text('description')->nullable();

            // Scope context — what the wizard was run against.
            $table->foreignId('session_id')
                  ->constrained('schoolsession')
                  ->cascadeOnDelete();

            $table->foreignId('term_id')
                  ->nullable()
                  ->constrained('schoolterm')
                  ->nullOnDelete();

            // The full wizard payload that produced this run.
            $table->json('wizard_input');

            // The advanced rules snapshot the generator actually used.
            $table->json('advanced_rules')->nullable();

            // Aggregate stats at run time.
            $table->unsignedInteger('class_count')->default(0);
            $table->unsignedInteger('total_placed')->default(0);
            $table->unsignedInteger('total_shortfall')->default(0);
            $table->unsignedInteger('total_conflicts')->default(0);

            // success | shortfalls | reverted
            $table->enum('status', ['success', 'shortfalls', 'reverted'])
                  ->default('success');

            // Original random seed the generator used.
            $table->unsignedBigInteger('seed')->nullable();

            // Provenance.
            $table->foreignId('created_by')
                  ->constrained('users')
                  ->cascadeOnDelete();

            $table->timestamps();

            $table->index(['session_id', 'term_id'], 'tgr_scope_idx');
            $table->index(['status', 'created_at'], 'tgr_status_created_idx');
            $table->index('created_by', 'tgr_creator_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('timetable_generation_runs');
    }
};