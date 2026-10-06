<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('broadsheets', function (Blueprint $table) {
            $table->unsignedSmallInteger('subject_position_class_total')->nullable()->default(null)->after('subject_position_class');
            $table->unsignedSmallInteger('arm_position_cum')->nullable()->default(null)->after('arm_position');
        });
    }

    public function down(): void
    {
        Schema::table('broadsheets', function (Blueprint $table) {
            $table->dropColumn(['subject_position_class_total', 'arm_position_cum']);
        });
    }
};
