<?php
// database/migrations/2026_09_08_000001_add_date_title_to_holidays_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('holidays')) {
            return; // nothing to patch
        }

        Schema::table('holidays', function (Blueprint $table) {
            if (!Schema::hasColumn('holidays', 'date')) {
                $table->date('date')->nullable()->after('id');
            }
            if (!Schema::hasColumn('holidays', 'title')) {
                $table->string('title')->nullable()->after('date');
            }
            if (!Schema::hasColumn('holidays', 'is_full_day')) {
                $table->boolean('is_full_day')->default(true);
            }
            if (!Schema::hasColumn('holidays', 'cutoff_time')) {
                $table->time('cutoff_time')->nullable();
            }
            if (!Schema::hasColumn('holidays', 'session_id')) {
                $table->unsignedBigInteger('session_id')->nullable();
            }
            if (!Schema::hasColumn('holidays', 'term_id')) {
                $table->unsignedBigInteger('term_id')->nullable();
            }
            if (!Schema::hasColumn('holidays', 'created_by')) {
                $table->unsignedBigInteger('created_by')->nullable();
            }
        });

        // Index the date column now that it exists, if not already indexed
        $dateIndexExists = collect(DB::select("SHOW INDEX FROM holidays WHERE Key_name = 'holidays_date_index'"))->isNotEmpty();
        if (!$dateIndexExists) {
            Schema::table('holidays', function (Blueprint $table) {
                $table->index('date');
            });
        }

        // Foreign keys — only add if column exists, referenced table exists, and FK isn't already there
        $this->addForeignKeyIfMissing('session_id', 'schoolsession');
        $this->addForeignKeyIfMissing('term_id', 'schoolterm');
        $this->addForeignKeyIfMissing('created_by', 'users');
    }

    private function addForeignKeyIfMissing(string $column, string $referencesTable): void
    {
        if (!Schema::hasColumn('holidays', $column) || !Schema::hasTable($referencesTable)) {
            return;
        }

        $exists = DB::select("
            SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE
            WHERE TABLE_NAME = 'holidays'
            AND COLUMN_NAME = ?
            AND REFERENCED_TABLE_NAME = ?
            AND TABLE_SCHEMA = DATABASE()
        ", [$column, $referencesTable]);

        if (empty($exists)) {
            try {
                Schema::table('holidays', function (Blueprint $table) use ($column, $referencesTable) {
                    $table->foreign($column)->references('id')->on($referencesTable)->nullOnDelete();
                });
            } catch (\Exception $e) {
                // Column may have orphaned values that violate the FK — log and skip
                // rather than fail the whole migration.
                error_log("Could not add foreign key for holidays.{$column}: " . $e->getMessage());
            }
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('holidays')) return;

        Schema::table('holidays', function (Blueprint $table) {
            if (Schema::hasColumn('holidays', 'date')) {
                try { $table->dropIndex(['date']); } catch (\Exception $e) {}
                $table->dropColumn('date');
            }
            if (Schema::hasColumn('holidays', 'title')) {
                $table->dropColumn('title');
            }
        });
    }
};