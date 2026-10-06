<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('schoolterm', function (Blueprint $table) {
            $table->boolean('status')->default(true)->after('term');
            // ────────────────────────────────────────
            // Alternative choices (pick ONE):
            //
            // $table->tinyInteger('status')->unsigned()->default(1);     // 0=inactive, 1=active
            // $table->enum('status', ['active', 'inactive', 'archived'])->default('active');
            // $table->string('status', 20)->default('active');
        });
    }

    public function down(): void
    {
        Schema::table('schoolterm', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
};
