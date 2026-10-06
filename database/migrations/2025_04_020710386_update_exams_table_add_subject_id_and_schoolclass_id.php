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
        Schema::table('exams', function (Blueprint $table) {
            $table->string('subject_id')->nullable()->after('staffId'); // Add student_id
            $table->string('schoolclass_id')->nullable()->after('subject_id'); // Add student_id
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('exams', function (Blueprint $table) {
            $table->dropColumn('subject_id'); // Remove student_id if rolled back
            $table->dropColumn('schoolclass_id'); // Remove student_id if rolled back
        });
    }
};
