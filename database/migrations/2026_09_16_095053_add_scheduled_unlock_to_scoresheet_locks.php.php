<?php
// database/migrations/2025_XX_XX_add_scheduled_unlock_to_scoresheet_locks.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('scoresheet_locks', function (Blueprint $table) {
            if (!Schema::hasColumn('scoresheet_locks', 'scheduled_unlock_at')) {
                $table->timestamp('scheduled_unlock_at')->nullable()->after('reason');
            }
        });
    }

    public function down(): void
    {
        Schema::table('scoresheet_locks', function (Blueprint $table) {
            $table->dropColumn('scheduled_unlock_at');
        });
    }
};