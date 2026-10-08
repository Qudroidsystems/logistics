<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * Removes tables inherited from the school portal this project started from.
 * Fresh installs never create them; existing databases lose them here.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (['job_progress', 'notification_preferences', 'password_reset_logs'] as $table) {
            Schema::dropIfExists($table);
        }
    }

    public function down(): void
    {
        // Intentionally empty: these tables are not part of the logistics platform.
    }
};
