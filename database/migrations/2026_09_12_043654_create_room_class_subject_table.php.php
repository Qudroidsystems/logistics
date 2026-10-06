<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('room_class_subject', function (Blueprint $table) {
            $table->id();

            $table->foreignId('room_id')
                  ->constrained('rooms')
                  ->cascadeOnDelete();

            $table->foreignId('schoolclass_id')
                  ->constrained('schoolclass')
                  ->cascadeOnDelete();

            // NULL = "any subject for this class" — a generic room mapping.
            $table->foreignId('subject_id')
                  ->nullable()
                  ->constrained('subject')
                  ->cascadeOnDelete();

            $table->foreignId('session_id')
                  ->constrained('schoolsession')
                  ->cascadeOnDelete();

            $table->foreignId('term_id')
                  ->nullable()
                  ->constrained('schoolterm')
                  ->cascadeOnDelete();

            $table->string('note', 190)->nullable();

            // Application-computed uniqueness key (see model boot).
            $table->string('scope_key', 190);

            $table->timestamps();

            $table->unique('scope_key', 'rcs_scope_unique');
            $table->index(['schoolclass_id', 'subject_id'], 'rcs_class_subject_idx');
            $table->index(['room_id', 'schoolclass_id'], 'rcs_room_class_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('room_class_subject');
    }
};