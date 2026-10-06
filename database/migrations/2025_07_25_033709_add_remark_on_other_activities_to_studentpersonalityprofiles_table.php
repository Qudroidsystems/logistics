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
           $table->text('remark_on_other_activities')->nullable()->after('classteachercomment');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('studentpersonalityprofiles', function (Blueprint $table) {
            $table->dropColumn('remark_on_other_activities');
        });
    }
};
