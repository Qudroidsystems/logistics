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
        Schema::table('studentpersonalityprofiles', function (Blueprint $table) {
            $table->string('guidancescomment')->nullable()->after('classteachercomment');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('studentpersonalityprofiles', function (Blueprint $table) {
            $table->dropColumn('guidancescomment');
        });
    }
};
