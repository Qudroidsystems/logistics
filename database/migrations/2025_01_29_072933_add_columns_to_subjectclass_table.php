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
        Schema::table('subjectclass', function (Blueprint $table) {
            $table->string('termid')->nullable();
            $table->string('session')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('subjectclass', function (Blueprint $table) {
            $table->dropColumn('termid'); // Drop the column if the migration is rolled back
            $table->dropColumn('session');
        });
    }
};
