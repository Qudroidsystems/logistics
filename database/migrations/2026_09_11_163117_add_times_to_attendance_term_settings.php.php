<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendance_term_settings', function (Blueprint $table) {
            $table->time('resumption_time')->default('08:00:00')->after('vacation_date');
            $table->time('closing_time')->default('14:00:00')->after('resumption_time');
            $table->time('morning_end_time')->default('12:00:00')->after('closing_time');
            $table->unsignedSmallInteger('late_grace_minutes')->default(0)->after('morning_end_time');
        });
    }

    public function down(): void
    {
        Schema::table('attendance_term_settings', function (Blueprint $table) {
            $table->dropColumn([
                'resumption_time',
                'closing_time',
                'morning_end_time',
                'late_grace_minutes',
            ]);
        });
    }
};