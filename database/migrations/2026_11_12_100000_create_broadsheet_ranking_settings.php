<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Configurable (unofficial) best-student ranking settings, one row per section
 * (junior / senior), since grading formats differ. Drives ONLY the best-student
 * panel and an optional "Rank" column — never the four official position columns.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('broadsheet_ranking_settings')) {
            Schema::create('broadsheet_ranking_settings', function (Blueprint $t) {
                $t->id();
                $t->string('section')->unique();            // junior | senior
                $t->string('primary_measure')->default('cum_ave');
                $t->json('tiebreakers')->nullable();        // up to 3 ordered measure keys
                // eligibility
                $t->unsignedInteger('min_subjects')->nullable();
                $t->boolean('require_all_compulsory')->default(false);
                $t->boolean('exclude_failed')->default(false);   // no failed subject
                $t->decimal('min_average', 5, 2)->nullable();
                // scope / output
                $t->string('scope')->default('overall');    // overall | per_arm | per_subject
                $t->unsignedInteger('top_n')->default(3);    // 1 | 3 | 5
                $t->json('core_subject_ids')->nullable();    // for core-subjects average
                $t->boolean('show_rank_column')->default(false);
                $t->boolean('is_active')->default(true);
                $t->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('broadsheet_ranking_settings');
    }
};
