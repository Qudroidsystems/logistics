<?php
// database/migrations/2026_05_25_000001_add_audit_and_lock_fields_to_broadsheets.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('broadsheets', function (Blueprint $table) {
            // Audit trail fields
            if (!Schema::hasColumn('broadsheets', 'entered_by')) {
                $table->unsignedBigInteger('entered_by')->nullable();
            }
            if (!Schema::hasColumn('broadsheets', 'entered_at')) {
                $table->timestamp('entered_at')->nullable();
            }
            if (!Schema::hasColumn('broadsheets', 'last_modified_by')) {
                $table->unsignedBigInteger('last_modified_by')->nullable();
            }
            if (!Schema::hasColumn('broadsheets', 'last_modified_at')) {
                $table->timestamp('last_modified_at')->nullable();
            }
            if (!Schema::hasColumn('broadsheets', 'entry_source')) {
                $table->string('entry_source')->nullable()->default('teacher')->comment('teacher or admin');
            }

            // Lock fields
            if (!Schema::hasColumn('broadsheets', 'is_locked')) {
                $table->boolean('is_locked')->default(false);
            }
            if (!Schema::hasColumn('broadsheets', 'locked_by')) {
                $table->unsignedBigInteger('locked_by')->nullable();
            }
            if (!Schema::hasColumn('broadsheets', 'locked_at')) {
                $table->timestamp('locked_at')->nullable();
            }
            if (!Schema::hasColumn('broadsheets', 'lock_reason')) {
                $table->text('lock_reason')->nullable();
            }

            // Scheduled unlock fields
            if (!Schema::hasColumn('broadsheets', 'scheduled_unlock_at')) {
                $table->timestamp('scheduled_unlock_at')->nullable();
            }
            if (!Schema::hasColumn('broadsheets', 'unlock_scheduled_by')) {
                $table->unsignedBigInteger('unlock_scheduled_by')->nullable();
            }
        });

        // Add foreign keys
        try {
            Schema::table('broadsheets', function (Blueprint $table) {
                if (Schema::hasColumn('broadsheets', 'entered_by')) {
                    $table->foreign('entered_by', 'broadsheets_entered_by_foreign')->references('id')->on('users')->onDelete('set null');
                }
                if (Schema::hasColumn('broadsheets', 'last_modified_by')) {
                    $table->foreign('last_modified_by', 'broadsheets_last_modified_by_foreign')->references('id')->on('users')->onDelete('set null');
                }
                if (Schema::hasColumn('broadsheets', 'locked_by')) {
                    $table->foreign('locked_by', 'broadsheets_locked_by_foreign')->references('id')->on('users')->onDelete('set null');
                }
                if (Schema::hasColumn('broadsheets', 'unlock_scheduled_by')) {
                    $table->foreign('unlock_scheduled_by', 'broadsheets_unlock_scheduled_by_foreign')->references('id')->on('users')->onDelete('set null');
                }
            });
        } catch (\Exception $e) {}

        // Add indexes
        try {
            if (!Schema::hasIndex('broadsheets', 'broadsheets_lock_index')) {
                Schema::table('broadsheets', function (Blueprint $table) {
                    $table->index(['is_locked'], 'broadsheets_lock_index');
                });
            }
            if (!Schema::hasIndex('broadsheets', 'broadsheets_entered_at_index')) {
                Schema::table('broadsheets', function (Blueprint $table) {
                    $table->index('entered_at', 'broadsheets_entered_at_index');
                });
            }
            if (!Schema::hasIndex('broadsheets', 'broadsheets_modified_at_index')) {
                Schema::table('broadsheets', function (Blueprint $table) {
                    $table->index('last_modified_at', 'broadsheets_modified_at_index');
                });
            }
            if (!Schema::hasIndex('broadsheets', 'broadsheets_scheduled_unlock_index')) {
                Schema::table('broadsheets', function (Blueprint $table) {
                    $table->index('scheduled_unlock_at', 'broadsheets_scheduled_unlock_index');
                });
            }
        } catch (\Exception $e) {}
    }

    public function down()
    {
        Schema::table('broadsheets', function (Blueprint $table) {
            // Drop foreign keys
            try {
                $table->dropForeign('broadsheets_entered_by_foreign');
                $table->dropForeign('broadsheets_last_modified_by_foreign');
                $table->dropForeign('broadsheets_locked_by_foreign');
                $table->dropForeign('broadsheets_unlock_scheduled_by_foreign');
            } catch (\Exception $e) {}

            // Drop indexes
            try {
                $table->dropIndex('broadsheets_lock_index');
                $table->dropIndex('broadsheets_entered_at_index');
                $table->dropIndex('broadsheets_modified_at_index');
                $table->dropIndex('broadsheets_scheduled_unlock_index');
            } catch (\Exception $e) {}

            // Drop columns
            $columns = ['entered_by', 'entered_at', 'last_modified_by', 'last_modified_at',
                       'entry_source', 'is_locked', 'locked_by', 'locked_at', 'lock_reason',
                       'scheduled_unlock_at', 'unlock_scheduled_by'];
            foreach ($columns as $column) {
                if (Schema::hasColumn('broadsheets', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
