<?php
// database/migrations/2026_05_25_000005_add_scheduled_unlock_to_scoresheet_locks.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('scoresheet_locks', function (Blueprint $table) {
            if (!Schema::hasColumn('scoresheet_locks', 'scheduled_unlock_at')) {
                $table->timestamp('scheduled_unlock_at')->nullable()->after('reason');
            }
            if (!Schema::hasColumn('scoresheet_locks', 'auto_unlock_job_id')) {
                $table->string('auto_unlock_job_id')->nullable()->after('scheduled_unlock_at');
            }
        });

        // Add index for scheduled_unlock_at
        try {
            if (!Schema::hasIndex('scoresheet_locks', 'sl_scheduled_unlock_index')) {
                Schema::table('scoresheet_locks', function (Blueprint $table) {
                    $table->index('scheduled_unlock_at', 'sl_scheduled_unlock_index');
                });
            }
        } catch (\Exception $e) {}
    }

    public function down()
    {
        Schema::table('scoresheet_locks', function (Blueprint $table) {
            try {
                $table->dropIndex('sl_scheduled_unlock_index');
            } catch (\Exception $e) {}

            if (Schema::hasColumn('scoresheet_locks', 'scheduled_unlock_at')) {
                $table->dropColumn('scheduled_unlock_at');
            }
            if (Schema::hasColumn('scoresheet_locks', 'auto_unlock_job_id')) {
                $table->dropColumn('auto_unlock_job_id');
            }
        });
    }
};
