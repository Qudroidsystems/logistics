<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('timetable_period_allocation_sets', function (Blueprint $table) {
            $table->id();

            $table->foreignId('session_id')
                  ->constrained('schoolsession')
                  ->cascadeOnDelete();

            $table->foreignId('term_id')
                  ->nullable()
                  ->constrained('schoolterm')
                  ->cascadeOnDelete();

            $table->string('name');
            $table->text('description')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            $table->unique(['session_id', 'term_id', 'name'], 'tpas_session_term_name_unique');
            $table->index(['session_id', 'term_id'], 'tpas_session_term_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('timetable_period_allocation_sets');
    }
};
