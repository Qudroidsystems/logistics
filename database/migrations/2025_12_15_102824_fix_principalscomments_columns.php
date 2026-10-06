<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('principalscomments', function (Blueprint $table) {
            // Change existing columns to proper unsignedBigInteger (fix previous string issue)
            $table->unsignedBigInteger('staffId')->nullable()->change();
            $table->unsignedBigInteger('schoolclassid')->nullable()->change();

            // Add new columns
            $table->unsignedBigInteger('sessionid')->nullable()->after('schoolclassid');
            $table->unsignedBigInteger('termid')->nullable()->after('sessionid');

            // Foreign keys
            $table->foreign('staffId')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('schoolclassid')->references('id')->on('schoolclass')->onDelete('cascade');
            $table->foreign('sessionid')->references('id')->on('schoolsession')->onDelete('cascade'); // or your table name
            $table->foreign('termid')->references('id')->on('schoolterm')->onDelete('cascade'); // or your table name

            // Unique constraint: one assignment per staff/class/session/term
            $table->unique(['staffId', 'schoolclassid', 'sessionid', 'termid']);
        });
    }

    public function down(): void
    {
        Schema::table('principalscomments', function (Blueprint $table) {
            $table->dropUnique(['staffId', 'schoolclassid', 'sessionid', 'termid']);
            $table->dropForeign(['staffId', 'schoolclassid', 'sessionid', 'termid']);
            $table->dropColumn(['sessionid', 'termid']);

            $table->string('staffId')->nullable()->change();
            $table->string('schoolclassid')->nullable()->change();
        });
    }
};