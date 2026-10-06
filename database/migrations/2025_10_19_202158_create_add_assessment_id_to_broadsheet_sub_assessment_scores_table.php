<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('broadsheet_sub_assessment_scores', function (Blueprint $table) {
            if (!Schema::hasColumn('broadsheet_sub_assessment_scores', 'assessment_id')) {
                $table->unsignedBigInteger('assessment_id')->after('id')->nullable(false);
            }
        });
    }


    /**
     * Reverse the migrations.
     */
    
    public function down(): void
    {
        Schema::table('broadsheet_sub_assessment_scores', function (Blueprint $table) {
            if (Schema::hasColumn('broadsheet_sub_assessment_scores', 'assessment_id')) {
                $table->dropColumn('assessment_id');
            }
        });
    }

};