<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subjectclass', function (Blueprint $table) {
            // Drop existing columns
            $table->dropColumn(['schoolclassid', 'subjectid', 'subjectteacherid']);
            
            // Add new columns with correct types
            $table->unsignedBigInteger('schoolclassid')->after('id');
            $table->unsignedBigInteger('subjectid')->after('schoolclassid');
            $table->unsignedBigInteger('subjectteacherid')->after('subjectid');
            
            // Add foreign key constraints
            $table->foreign('schoolclassid')->references('id')->on('schoolclass')->onDelete('cascade');
            $table->foreign('subjectid')->references('id')->on('subject')->onDelete('cascade');
            $table->foreign('subjectteacherid')->references('id')->on('subjectteacher')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('subjectclass', function (Blueprint $table) {
            // Drop foreign keys
            $table->dropForeign(['schoolclassid']);
            $table->dropForeign(['subjectid']);
            $table->dropForeign(['subjectteacherid']);
            
            // Drop new columns
            $table->dropColumn(['schoolclassid', 'subjectid', 'subjectteacherid']);
            
            // Restore original columns
            $table->string('schoolclassid')->after('id');
            $table->string('subjectid')->after('schoolclassid');
            $table->string('subjectteacherid')->after('subjectid');
        });
    }
};