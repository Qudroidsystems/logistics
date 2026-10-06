<?php
// database/migrations/2026_05_25_000004_add_scheduled_unlock_to_broadsheets.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('broadsheets', function (Blueprint $table) {
            if (!Schema::hasColumn('broadsheets', 'scheduled_unlock_at')) {
                $table->timestamp('scheduled_unlock_at')->nullable()->after('lock_reason');
            }
            if (!Schema::hasColumn('broadsheets', 'unlock_scheduled_by')) {
                $table->unsignedBigInteger('unlock_scheduled_by')->nullable()->after('scheduled_unlock_at');
            }
        });

        // Add foreign key for unlock_scheduled_by
        try {
            Schema::table('broadsheets', function (Blueprint $table) {
                if (Schema::hasColumn('broadsheets', 'unlock_scheduled_by')) {
                    $table->foreign('unlock_scheduled_by', 'broadsheets_unlock_scheduled_by_foreign')
                        ->references('id')
                        ->on('users')
                        ->onDelete('set null');
                }
            });
        } catch (\Exception $e) {
            // Foreign key might already exist
        }

        // Add index for scheduled_unlock_at
        try {
            if (!Schema::hasIndex('broadsheets', 'broadsheets_scheduled_unlock_index')) {
                Schema::table('broadsheets', function (Blueprint $table) {
                    $table->index('scheduled_unlock_at', 'broadsheets_scheduled_unlock_index');
                });
            }
        } catch (\Exception $e) {
            // Index might already exist
        }
    }

    public function down()
    {
        Schema::table('broadsheets', function (Blueprint $table) {
            // Drop foreign key
            try {
                $table->dropForeign('broadsheets_unlock_scheduled_by_foreign');
            } catch (\Exception $e) {}

            // Drop index
            try {
                $table->dropIndex('broadsheets_scheduled_unlock_index');
            } catch (\Exception $e) {}

            // Drop columns
            if (Schema::hasColumn('broadsheets', 'scheduled_unlock_at')) {
                $table->dropColumn('scheduled_unlock_at');
            }
            if (Schema::hasColumn('broadsheets', 'unlock_scheduled_by')) {
                $table->dropColumn('unlock_scheduled_by');
            }
        });
    }
};
