<?php
// database/migrations/xxxx_add_term_session_mingrade_to_compulsory_subject_classes.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('compulsory_subject_classes', function (Blueprint $table) {
            // NULL means "applies to all terms"
            $table->unsignedBigInteger('termid')->nullable()->after('subjectId');
            $table->unsignedBigInteger('sessionid')->nullable()->after('termid');
            $table->string('min_grade', 10)->nullable()->after('sessionid');

            $table->foreign('termid')->references('id')->on('schoolterm')->onDelete('set null');
            $table->foreign('sessionid')->references('id')->on('schoolsession')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('compulsory_subject_classes', function (Blueprint $table) {
            $table->dropForeign(['termid']);
            $table->dropForeign(['sessionid']);
            $table->dropColumn(['termid', 'sessionid', 'min_grade']);
        });
    }
};
