<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // NOTE: confirm the actual table name backing the StudentAttendance model
    // before running (it was referenced as 'studentattendance' — verify against
    // your existing migrations; adjust the string below if different).
    private string $table = 'student_attendance';

    public function up(): void
    {
        Schema::table($this->table, function (Blueprint $table) {
            $table->time('time_in')->nullable()->after('status');
            $table->time('time_out')->nullable()->after('time_in');
            $table->enum('source', ['device', 'manual'])->default('manual')->after('time_out');
        });
    }

    public function down(): void
    {
        Schema::table($this->table, function (Blueprint $table) {
            $table->dropColumn(['time_in', 'time_out', 'source']);
        });
    }
};
