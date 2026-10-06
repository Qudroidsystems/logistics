<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('student_batch_upload', function (Blueprint $table) {
            $table->longText('import_errors')->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('student_batch_upload', function (Blueprint $table) {
            $table->dropColumn('import_errors');
        });
    }
};