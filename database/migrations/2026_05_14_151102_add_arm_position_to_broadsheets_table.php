<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('broadsheets', function (Blueprint $table) {
            $table->unsignedSmallInteger('arm_position')->nullable()->default(null)->after('subject_position_class');
        });
    }

    public function down(): void
    {
        Schema::table('broadsheets', function (Blueprint $table) {
            $table->dropColumn('arm_position');
        });
    }
};
