<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('timetable_subject_priorities', function (Blueprint $table) {
            $table->id();

            $table->foreignId('setting_id')
                  ->constrained('timetable_settings')
                  ->cascadeOnDelete();

            $table->foreignId('subject_id')
                  ->constrained('subject')
                  ->cascadeOnDelete();

            // 1 = Critical, 2 = High, 3 = Normal (default), 4 = Low, 5 = Minimal.
            $table->unsignedTinyInteger('priority_level')->default(3);

            $table->boolean('use_priority')->default(false);
            $table->boolean('affects_ordering')->default(true);
            $table->boolean('affects_slot_quality')->default(false);
            $table->boolean('is_protected')->default(false);

            $table->timestamps();

            $table->unique(['setting_id', 'subject_id'], 'tsp_setting_subject_unique');
            $table->index(['setting_id', 'priority_level'], 'tsp_setting_priority_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('timetable_subject_priorities');
    }
};